<?php
use App\Http\Middleware\ShareTranslations;
use App\Http\Controllers\ActiveAppController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderFromMesController;
use App\Http\Controllers\stampaggiotableViewController;
use App\Http\Controllers\assemblaggiotableViewController;
use App\Http\Controllers\DictionaryController;
use App\Http\Controllers\bomController;
use App\Http\Controllers\adController;
use App\Http\Controllers\turniController;
use App\Http\Controllers\PresseController;
use App\Http\Controllers\GestionePiovanController;
use App\Http\Controllers\AziendeController;
use App\Http\Controllers\GestioneTurniController;
use App\Http\Controllers\GestioneTurnoPresseController;
use App\Http\Controllers\MacchineController;
use App\Http\Controllers\PresseGualaFPController;
use App\Http\Controllers\StampingController;
use App\Http\Controllers\CodiciOggettiController;
use App\Http\Controllers\OrdiniController;
use App\Http\Controllers\ProductionFPController;
use App\Http\Controllers\BOMFPController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\MacchineOperatoriController;


Auth::routes([
    'register' => false, // Registration Routes...
    'reset' => false, // Password Reset Routes...
    'verify' => false, // Email Verification Routes...
]);

//Route::middleware('auth')->group(function () {
Route::middleware(['auth', ShareTranslations::class])->group(function () {
    Route::controller(HomeController::class)->group(function () {
        Route::get('/', 'index')->name('home');
        Route::get('/home', 'index');
        Route::get('/redirect/{code}', 'redirect');

    });

    
    Route::controller(ActiveAppController::class)->prefix('active_apps')->name('active_apps.')->group(function () {
        //Gestione Imagine Active Apps
        Route::get('/image/{id}', 'image')->name('image');
    });


    /**
     * CUSTOM ROUTE HERE
     */
    Route::get('/tableview', [stampaggiotableViewController::class, 'index']);
    Route::get('/tableviewAssemblaggio', [assemblaggiotableViewController::class, 'index']);
    
    Route::post('/save-comment', [stampaggiotableViewController::class, 'updateCommento']);
    Route::post('/save-comment', [assemblaggiotableViewController::class, 'updateCommento']);
    Route::post('/save-comment', [ProductionFPController::class, 'updateCommento'])->name('monitor_fp.save_comment');;
    Route::get('/dettagli-ordine/{id}/{parentitemNo}', [BomController::class, 'showView'])->name('ordine.info.dettagli');
    Route::get('/dettagli-ordine/{id}/{parentitemNo}', [BOMFPController::class, 'showView'])->name('ordine.info.dettagli');

    Route::get('/barcode/{code}', [BarcodeController::class, 'code128'])->name('barcode');

    Route::get('/APP1/PDF/stampa', [assemblaggiotableViewController::class, 'stampa'])->name('app1.pdf.stampa');
    Route::get('/APP1/PDF_Stampaggio/stampa', [stampaggiotableViewController::class, 'stampa'])->name('app1.pdf_stampaggio.stampa');
    Route::get('/Monitor_Fp/PDF_Stampaggio_Fp/stampa', [ProductionFPController::class, 'stampa'])->name('monitor_fp.pdf_stampaggio_fp.stampa');

    Route::middleware(['can:ADMIN'])->group(function () {

        Route::controller(SiteController::class)->prefix('sites')->name('sites.')->group(function () {
            //Gestione Siti
            Route::get('/', 'index')->name('table');
            Route::get('/list', 'json')->name('list');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');
        });

        Route::controller(UserController::class)->prefix('users')->name('users.')->group(function () {
            //Gestione Siti
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');
            Route::get('/form_active_apps/{id?}', 'form_active_apps')->name('form_active_apps');
            Route::post('/form_active_apps', 'store_active_apps')->name('store_active_apps');
        });

        Route::controller(ActiveAppController::class)->prefix('active_apps')->name('active_apps.')->group(function () {
            //Gestione Active Apps
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');

        });

        Route::controller(DictionaryController::class)->prefix('traduzioni')->name('traduzioni.')->group(function () {
            Route::get('/', 'showTraduzioni')->name('index');
            Route::get('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
            Route::get('/edit/{id}', 'edit')->name('edit');
            Route::post('/update/{id}', 'update')->name('update');
            Route::post('/delete', 'delete')->name('destroy');
        });

        Route::controller(TurniController::class)->prefix('turni')->name('turni.')->group(function () {
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');
        });

        Route::controller(CodiciOggettiController::class)->prefix('codice_oggetto')->name('codice_oggetto.')->group(function () {
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');
        });

        Route::controller(GestionePiovanController::class)->prefix('gestione_piovan')->name('gestione_piovan.')->group(function () {
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');
        });

        Route::controller(AziendeController::class)->prefix('aziende')->name('aziende.')->group(function () {
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');
        });

        Route::controller(PresseGualaFPController::class)->prefix('presse_guala_fp')->name('presse_guala_fp.')->group(function () {
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');
        });

        Route::controller(PresseController::class)->prefix('presse')->name('presse.')->group(function () {
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');
        });

        Route::controller(StampingController::class)->prefix('stamping')->name('stamping.')->group(function () {
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');
        });

        Route::controller(MacchineController::class)->prefix('macchine')->name('macchine.')->group(function () {
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');
            Route::get('/form_dati/{id?}', 'form_dati')->name('form_dati');
            Route::post('/form_dati', 'store_dati')->name('store_dati');
        });

        Route::controller(adController::class)->prefix('ad')->name('ad.')->group(function () {
            Route::get('/', 'index')->name('table');
            Route::post('/json', 'json')->name('json');
            Route::get('/form/{id?}', 'create')->name('create');
            Route::post('/form', 'store')->name('store');
            Route::post('/delete', 'destroy')->name('destroy');

        });

        Route::get('/APP1', function () {
            return view('app.APP1.index');
        })->name('app.app1');

        Route::prefix('APP2') // o quello che vuoi come URL
            ->name('gestione_turni.')
            ->controller(GestioneTurniController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');          // gestione_turni.index
                Route::get('/json', 'json')->name('json');        // gestione_turni.json
                Route::get('/form/{id?}', 'create')->name('create'); // gestione_turni.create
                Route::post('/form', 'store')->name('store');     // gestione_turni.store
                Route::post('/delete', 'destroy')->name('destroy'); // gestione_turni.destroy
            });

        Route::prefix('APP3') // o quello che vuoi come URL
            ->name('gestione_turni_presse.')
            ->controller(GestioneTurnoPresseController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');          // gestione_turni.index
                Route::get('/json', 'json')->name('json');        // gestione_turni.json
                Route::get('/form/{id?}', 'create')->name('create'); // gestione_turni.create
                Route::post('/form', 'store')->name('store');     // gestione_turni.store
                Route::post('/delete', 'destroy')->name('destroy'); // gestione_turni.destroy
            });

        Route::prefix('associazione_macchine')
            ->name('assmac.')
            ->controller(MacchineOperatoriController::class)
            ->group(function () {
                Route::get('/',      'index')->name('index');
                Route::get('/json',  'json')->name('json');                 // ?data=YYYY-MM-DD
                Route::post('/nota', 'saveNota')->name('nota.save');        // { id, nota }
            });

    });

    
    Route::get('/APP1', function () {
        return view('app.APP1.index');
    })->name('app.app1');

    Route::get('/monitor_fp', function () {
        return view('app.monitor_fp.index');
    })->name('app.monitor_fp');

    Route::get('/monitor_fp/data', [ProductionFPController::class, 'index'])
        ->name('monitor_fp.data');

    Route::prefix('APP2') // o quello che vuoi come URL
            ->name('gestione_turni.')
            ->controller(GestioneTurniController::class)
            ->group(function () {
                Route::get('/', 'index')->name('index');          // gestione_turni.index
                Route::get('/json', 'json')->name('json');        // gestione_turni.json
                Route::get('/form/{id?}', 'create')->name('create'); // gestione_turni.create
                Route::post('/form', 'store')->name('store');     // gestione_turni.store
                Route::post('/delete', 'destroy')->name('destroy'); // gestione_turni.destroy
            });
        
    Route::prefix('APP3') // o quello che vuoi come URL
        ->name('gestione_turni_presse.')
        ->controller(GestioneTurnoPresseController::class)
        ->group(function () {
            Route::get('/', 'index')->name('index');          // gestione_turni.index
            Route::get('/json', 'json')->name('json');        // gestione_turni.json
            Route::get('/form/{id?}', 'create')->name('create'); // gestione_turni.create
            Route::post('/form', 'store')->name('store');     // gestione_turni.store
            Route::post('/delete', 'destroy')->name('destroy'); // gestione_turni.destroy
        });

    Route::prefix('ordini')
        ->name('ordini.')
        ->controller(OrdiniController::class)
            ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/json', 'json')->name('json');
            Route::get('/dettaglio', 'dettaglio')->name('dettaglio'); // ?ordine=CM2501389/01
            Route::get('/note/list',  'listNote')->name('note.list'); // ?ordine=XYZ
            Route::post('/note',      'saveNota')->name('note.save'); // { ordine, lotto, nota }
            Route::get('/piovan', [OrdiniController::class, 'piovan'])->name('piovan');
            // Salvataggio lotto Piovan (stesso prefisso)
            Route::post('/piovan/lotto', [OrdiniController::class, 'salvaLotto'])->name('piovan.lotto');


        });

    Route::prefix('associazione_macchine')
        ->name('assmac.')
        ->controller(MacchineOperatoriController::class)
        ->group(function () {
            Route::get('/',      'index')->name('index');
            Route::get('/json',  'json')->name('json');                 // ?data=YYYY-MM-DD
            Route::post('/nota', 'saveNota')->name('nota.save');        // { id, nota }
        });
});
