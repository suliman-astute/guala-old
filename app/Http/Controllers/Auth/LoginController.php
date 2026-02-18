<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use LdapRecord\Container;
use App\Models\User;
use LdapRecord\Models\ActiveDirectory\User as LdapUser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use App\Models\ad;
use LdapRecord\Connection;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/home';

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function validateLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|string', // può contenere DOMINIO\username o matricola
            'password' => 'required|string',
        ]);
    }
    protected function attemptLogin(Request $request)
    {
        // Normalizza input
        $loginRaw = (string) $request->input('email');
        $password = (string) $request->input('password');
        $remember = $request->filled('remember');

        $login = trim($loginRaw);

        // 1) Login locale con email valida
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            if (Auth::attempt(['email' => $login, 'password' => $password], $remember)) {
                Log::channel('login')->info("Login locale riuscito con email: {$login}");
                return true;
            }
            // non blocco: lascio proseguire a matricola/LDAP
        }

        // 2) Login locale con matricola numerica
        if (preg_match('/^\d+$/', $login)) {
            if (Auth::attempt(['matricola' => $login, 'password' => $password], $remember)) {
                Log::channel('login')->info("Login locale riuscito con matricola: {$login}");
                return true;
            }
            Log::channel('login')->warning("Login locale con matricola fallito: {$login}");
            throw ValidationException::withMessages([
                $this->username() => ['Credenziali non valide.'],
            ]);
        }

        // 3) LDAP (supporta: "DOMINIO\username" oppure solo "username")
        try {
            if (strpos($login, '@') === false) {
                // Estrai DOMINIO\sam se presente
                $netbios = null;    // es. GUALADIS
                $sam     = $login;  // es. mrossi
                if (strpos($login, '\\') !== false) {
                    [$netbios, $sam] = array_pad(explode('\\', $login, 2), 2, null);
                    $sam = (string) $sam;
                }

                // L’utente deve esistere localmente ed essere marcato AD
                $user = User::where('name', $sam)->where('is_ad_user', 1)->first();
                if (!$user) {
                    throw ValidationException::withMessages([
                        $this->username() => ['Utente non autorizzato ad accedere tramite LDAP.'],
                    ]);
                }

                // Dominio da usare (priorità: specificato nell’utente → netbios nell’input)
                $dominioNome = $user->tipo_dominio ?: $netbios; // es. "GUALADIS"
                $dominio = ad::where('dominio', $dominioNome)->first();
                if (!$dominio) {
                    throw ValidationException::withMessages([
                        $this->username() => ['Dominio non trovato nella configurazione.'],
                    ]);
                }

                // Deriva il realm DNS: usa colonna se presente, altrimenti da base_dn
                $realm = null; // es. contoso.local
                if (!empty($dominio->dominio_dns)) {
                    $realm = strtolower(trim($dominio->dominio_dns));
                } elseif (!empty($dominio->base_dn) && preg_match_all('/DC=([^,]+)/i', (string) $dominio->base_dn, $m)) {
                    $realm = strtolower(implode('.', $m[1]));
                }

                // Connessione LdapRecord runtime
                $connName = 'runtime_' . Str::slug($dominio->dominio, '_');
                $cfg = [
                    'hosts'   => [$dominio->host],                // puoi aggiungere altri DC in array
                    'base_dn' => $dominio->base_dn,
                    'port'    => $dominio->porta ?? 389,
                    'use_ssl' => false,                           // abilita a true se usi 636
                    'use_tls' => false,                           // abilita a true se richiedi STARTTLS su 389
                    'options' => [
                        LDAP_OPT_PROTOCOL_VERSION => 3,
                        LDAP_OPT_REFERRALS        => 0,
                        LDAP_OPT_NETWORK_TIMEOUT  => 5,           // timeout socket (s)
                        LDAP_OPT_TIMELIMIT        => 5,           // timeout operazione (s)
                    ],
                ];

                // Crea/recupera connessione e forza connect()
                try {
                    try {
                        $connection = Container::getConnection($connName);
                    } catch (\Throwable $e) {
                        Container::addConnection(new Connection($cfg), $connName);
                        $connection = Container::getConnection($connName);
                    }

                    $connection->connect();
                    if ($connection->isConnected()) {
                        Log::channel('login')->info("Connessione LDAP '{$connName}' stabilita a {$dominio->host}:{$dominio->porta}");
                    } else {
                        Log::channel('login')->warning("Connessione LDAP '{$connName}' NON stabilita dopo connect()");
                    }
                } catch (\Throwable $e) {
                    Log::channel('login')->error("Connessione LDAP '{$connName}' fallita: " . $e->getMessage());
                    throw ValidationException::withMessages([
                        $this->username() => ['Impossibile connettersi al server LDAP. Controllare host/porta/firewall.'],
                    ]);
                }

                // Tentativi di bind: 1) DOMINIO\sam  2) sam@realm (UPN) se disponibile
                $identities = [];
                if (!empty($dominio->dominio) && !empty($sam)) {
                    $identities[] = $dominio->dominio . '\\' . $sam;
                }
                if ($realm && $sam) {
                    $identities[] = $sam . '@' . $realm;
                }

                $ok = false;
                $diag = '';
                $usedIdentity = null;

                foreach ($identities as $id) {
                    if ($connection->auth()->attempt($id, $password, true)) {
                        $ok = true;
                        $usedIdentity = $id;
                        break;
                    }

                    // raccogli diagnosi dell’ultimo tentativo
                    try {
                        $ldap = $connection->getLdapConnection();
                        if (method_exists($ldap, 'getDiagnosticMessage')) {
                            $diag = (string) $ldap->getDiagnosticMessage();
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }

                    // Se primo tentativo ha dato 52e e stai per provare UPN, continua;
                    // altrimenti se non ci sono altri tentativi, uscirai con errore sotto.
                }

                if (!$ok) {
                    $msg = $this->ldapHumanMessage($diag);
                    Log::channel('login')->error("Login LDAP fallito per tentativi [" . implode(', ', $identities) . "] su {$connName} - diag: {$diag}");
                    throw ValidationException::withMessages([
                        $this->username() => [$msg],
                    ]);
                }

                Log::channel('login')->info("Login LDAP riuscito con identità '{$usedIdentity}' su {$connName}");

                // Recupero utente AD: prima per samAccountName, poi fallback per UPN se necessario
                $ldapUserQuery = LdapUser::on($connName)->in($dominio->base_dn);
                $ldapUser = $ldapUserQuery->whereEquals('samaccountname', $sam)->first();

                if (!$ldapUser && $realm) {
                    $upn = $sam . '@' . $realm;
                    $ldapUser = LdapUser::on($connName)
                        ->in($dominio->base_dn)
                        ->whereEquals('userprincipalname', $upn)
                        ->first();
                }

                if (!$ldapUser) {
                    throw ValidationException::withMessages([
                        $this->username() => ['Utente non trovato in Active Directory.'],
                    ]);
                }

                // Autentica utente locale associato
                Auth::login($user, $remember);
                return true;
            }
        } catch (ValidationException $ve) {
            throw $ve; // rilancio per mostrarlo nel form
        } catch (\Throwable $e) {
            Log::channel('login')->error("Eccezione LDAP: " . $e->getMessage());
            throw ValidationException::withMessages([
                $this->username() => ['Errore di connessione LDAP.'],
            ]);
        }

        // Se nessuna via ha funzionato
        throw ValidationException::withMessages([
            $this->username() => [__('auth.failed')],
        ]);
    }

    /* protected function attemptLogin(Request $request)
    {
        $login    = $request->input('email');
        $password = $request->input('password');

        // 1) Login locale con email valida
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            if (Auth::attempt(['email' => $login, 'password' => $password], $request->filled('remember'))) {
                Log::info("Login locale riuscito con email: {$login}");
                return true;
            }
            // non lancio eccezione qui: lascio proseguire alle altre modalità (se previste)
        }

        // 2) Login locale con matricola numerica
        if (preg_match('/^\d+$/', $login)) {
            if (Auth::attempt(['matricola' => $login, 'password' => $password], $request->filled('remember'))) {
                Log::info("Login locale riuscito con matricola: {$login}");
                return true;
            }
            Log::warning("Login locale con matricola fallito: {$login}");
            // fermiamo qui per matricola, niente LDAP per matricola
            throw ValidationException::withMessages([
                $this->username() => ['Credenziali non valide.'],
                // Se vuoi sotto la password:
                // 'password' => ['Credenziali non valide.'],
            ]);
        }

        // 3) LDAP (DOMINIO\username o solo username)
        try {
            if (strpos($login, '@') === false) {
                // L’utente deve esistere ed essere marcato come AD
                $user = User::where('name', $login)->where('is_ad_user', 1)->first();
                if (!$user) {
                    throw ValidationException::withMessages([
                        $this->username() => ['Utente non autorizzato ad accedere tramite LDAP.'],
                    ]);
                }

                // Recupero configurazione dominio
                $dominioNome = $user->tipo_dominio; // es. "GUALADIS"
                $dominio = ad::where('dominio', $dominioNome)->first();
                if (!$dominio) {
                    throw ValidationException::withMessages([
                        $this->username() => ['Dominio non trovato nella configurazione.'],
                    ]);
                }

                // Connessione LdapRecord runtime
                $connName = 'runtime_' . Str::slug($dominio->dominio, '_');
                $cfg = [
                    'hosts'   => [$dominio->host],
                    'base_dn' => $dominio->base_dn,
                    'port'    => $dominio->porta ?? 389,
                    'use_ssl' => false,
                    'use_tls' => false,
                    'options' => [
                        LDAP_OPT_PROTOCOL_VERSION => 3,
                        LDAP_OPT_REFERRALS        => 0,
                    ],
                ];

                try {
                    $connection = Container::getConnection($connName);
                } catch (\Throwable $e) {
                    Container::addConnection(new Connection($cfg), $connName);
                    $connection = Container::getConnection($connName);
                }
                
                try {
                    $connection->connect();
                    if ($connection->isConnected()) {
                        Log::info("Connessione LDAP '{$connName}' stabilita a {$dominio->host}:{$dominio->porta}");
                    } else {
                        Log::warning("Connessione LDAP '{$connName}' NON stabilita dopo connect()");
                    }
                } catch (\Throwable $e) {
                    Log::error("Connessione LDAP '{$connName}' fallita: " . $e->getMessage());
                    throw ValidationException::withMessages([
                        $this->username() => ['Impossibile connettersi al server LDAP. Controllare host/porta/firewall.'],
                    ]);
                }
                // Formato: DOMINIO\username
                $ldapLogin = $dominio->dominio . '\\' . $login;

                // Bind come utente (terzo parametro = true)
                if (!$connection->auth()->attempt($ldapLogin, $password, true)) {

                    // Prendi diagnostic AD e lancia subito ValidationException
                    $diag = '';
                    try {
                         
                        $ldap = $connection->getLdapConnection();
                        if (method_exists($ldap, 'getDiagnosticMessage')) {
                            $diag = (string) $ldap->getDiagnosticMessage();
                        }
                    } catch (\Throwable $e) {
                        // ignore
                    }

                    $msg = $this->ldapHumanMessage($diag);
                    Log::error("Login LDAP fallito per {$ldapLogin} su {$connName} - {$diag}");

                    throw ValidationException::withMessages([
                        $this->username() => [$msg],
                        // Se vuoi sotto la password:
                        // 'password' => [$msg],
                    ]);
                }

                Log::info("Login LDAP riuscito: {$ldapLogin} su {$connName}");

                // Recupero utente AD
                $ldapUser = LdapUser::on($connName)
                    ->in($dominio->base_dn)
                    ->whereEquals('samaccountname', $login)
                    ->first();

                if (!$ldapUser) {
                    throw ValidationException::withMessages([
                        $this->username() => ['Utente non trovato in Active Directory.'],
                    ]);
                }

                // Autentica utente locale associato
                Auth::login($user, $request->filled('remember'));
                return true;
            }
        } catch (ValidationException $ve) {
            // eccezioni di validazione: rilanciale così arrivano al form
            throw $ve;
        } catch (\Throwable $e) {
            Log::error("Eccezione LDAP: " . $e->getMessage());
            throw ValidationException::withMessages([
                $this->username() => ['Errore di connessione LDAP.'],
            ]);
        }

        // Se nessuna via ha funzionato:
        throw ValidationException::withMessages([
            $this->username() => [__('auth.failed')], // messaggio generico
        ]);
    } */

    /**
     * Traduce il diagnostic LDAP di Active Directory in un messaggio leggibile.
     */
    /**
     * Traduce il diagnostic LDAP/AD in un messaggio leggibile.
     * Supporta codici AD nel diagnostic "data XYZ" e comuni stringhe LDAP.
     */
    private function ldapHumanMessage(?string $diag): string
    {
        $raw = trim((string) $diag);
        $d = strtolower($raw);

        // 1) Mappa dei codici AD più comuni (da "data XYZ")
        // Riferimento: errori di logon AD
        $adMap = [
            '525' => 'Utente non trovato nel dominio (codice 525).',
            '52e' => 'Credenziali errate (codice 52e).',
            '530' => 'Accesso non consentito in questo orario (codice 530).',
            '531' => 'Accesso non consentito da questa workstation (codice 531).',
            '532' => 'Password scaduta (codice 532).',
            '533' => 'Account disabilitato (codice 533).',
            '534' => 'Restrizione di logon sull’account (codice 534).',
            '568' => 'Tipo di accesso non consentito per l’utente (codice 568).',
            '701' => 'Account scaduto (codice 701).',
            '773' => 'È richiesto il cambio password (codice 773).',
            '775' => 'Account bloccato (codice 775).',
        ];

        // Estrai eventuale "data XYZ" (XYZ può essere esadecimale o decimale)
        if (preg_match('/\bdata\s+([0-9a-f]{3})\b/i', $raw, $m)) {
            $code = strtolower($m[1]);
            if (isset($adMap[$code])) {
                return $adMap[$code];
            }
        }

        // 2) Riconoscimenti testuali comuni (LDAP / stack AD)
        if (str_contains($d, 'invalid credentials') || str_contains($d, 'invalidcredentials')) {
            return 'Credenziali non valide.';
        }
        if (str_contains($d, "can't contact ldap server") || str_contains($d, 'server down')) {
            return 'Impossibile contattare il server LDAP (rete/DNS/porta/TLS).';
        }
        if (str_contains($d, 'confidentiality required')) {
            return 'Il server richiede una connessione sicura (TLS/LDAPS).';
        }
        if (str_contains($d, 'stronger auth required') || str_contains($d, 'strongerauthrequired')) {
            return 'Il server richiede un metodo di autenticazione più forte (signing/sealing o TLS).';
        }
        if (str_contains($d, 'unwilling to perform')) {
            return 'Il server ha rifiutato l’operazione (policy AD o requisiti di sicurezza).';
        }
        if (str_contains($d, 'constraint violation')) {
            return 'Violazione di vincolo LDAP.';
        }
        if (str_contains($d, 'invalid dn syntax')) {
            return 'Sintassi del DN non valida.';
        }
        if (str_contains($d, 'no such object')) {
            return 'Oggetto/utente non trovato nel directory.';
        }
        if (str_contains($d, 'inappropriate authentication')) {
            return 'Metodo di autenticazione non appropriato per il server.';
        }

        // 3) Fallback: mostra diagnostic raw se disponibile
        return $raw !== '' ? "Errore LDAP: {$raw}" : 'Errore di autenticazione LDAP.';
    }
}
