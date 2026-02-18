<form id="async" method="POST">
    {!! csrf_field() !!}
    <div class="row">
        <div class="col-6 mb-2">
            <label class="label label-primary">Name (Name Active Directory)*</label>
            <input class="form-control form-control-sm" type="text" name="name" value="{{ $user->name }}">
            <input type="hidden" name="id" value="{{ $user->id }}">
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">E-mail*</label>
            <input class="form-control form-control-sm" type="mail" name="email" value="{{ $user->email }}">
        </div>

        <div class="col-6 mb-2">
            <label class="label label-primary">User ID</label>
            <input class="form-control form-control-sm" type="text" name="user_id" value="{{ $user->user_id ?? '' }}">
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Cognome</label>
            <input class="form-control form-control-sm" type="text" name="cognome" value="{{ $user->cognome ?? '' }}">
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Matricola</label>
            <input class="form-control form-control-sm" type="text" name="matricola" value="{{ $user->matricola ?? '' }}">
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Utente AD</label>
            <select class="form-control form-control-sm" name="is_ad_user">
                <option value=0 {{ !($user->is_ad_user ?? false) ? ' selected ' : '' }}>No</option>
                <option value=1 {{ ($user->is_ad_user ?? false) ? ' selected ' : '' }}>Sì</option>
            </select>
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Nome Dominio</label>
            <input class="form-control form-control-sm" type="text" name="tipo_dominio" value="{{ $user->tipo_dominio ?? '' }}">            
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Password (only needed for modification)</label>
            <input class="form-control form-control-sm" type="password" name="password" autocomplete="one-time-code">
        </div>

        <div class="col-6 mb-2">
            <label class="label label-primary">Retype password</label>
            <input class="form-control form-control-sm" type="password" name="password_confirmation"
                autocomplete="one-time-code">
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Administrator*</label>
            <select class="form-control form-control-sm" name="admin" id="admin">
                <option value=0 {{ !$user->admin ? ' selected ' : '' }}>No</option>
                <option value=1 {{ $user->admin ? ' selected ' : '' }}>Si</option>
            </select>
        </div>

        <div class="col-6 mb-2 no_admin">
            <label class="label label-primary">Sito*</label>
            <select class="form-control form-control-sm" name="site_id">
                @foreach ($sites as $site)
                    <option value={{ $site->id }} {{ $user->site_id == $site->id ? ' selected ' : '' }}>
                        {{ $site->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-6 mb-2 no_admin">
            <label class="label label-primary">Language*</label>
            <select class="form-control form-control-sm" name="lang">
                @foreach ($langs as $key => $value)
                    <option value={{ $key }} {{ $user->lang == $key ? ' selected ' : '' }}>{{ $value }}
                    </option>
                @endforeach
            </select>
        </div>
       <div class="col-6 mb-2">
            <label class="label label-primary">Destinazione Utenti</label>
            <select class="form-control form-control-sm" name="destinazione_utenti">
                <option value="0" {{ $user->destinazione_utenti == '' ? 'selected' : '' }}></option>
                @foreach($aziende as $id => $nome)
                    <option value="{{ $id }}" {{ $user->destinazione_utenti == $id ? 'selected' : '' }}>
                        {{ $nome }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Ruolo Personale</label>
            <select class="form-control form-control-sm" name="ruolo_personale">
                <option value="" {{ $user->ruolo_personale == '' ? 'selected' : '' }}></option>
                <option value="Operatore Assemblaggio" {{ $user->ruolo_personale == 'Operatore Assemblaggio' ? 'selected' : '' }}>Operatore Assemblaggio</option>
                <option value="Capo turno Assemblaggio" {{ $user->ruolo_personale == 'Capo turno Assemblaggio' ? 'selected' : '' }}>Capo turno Assemblaggio</option>
                <option value="Operatore Stampaggio" {{ $user->ruolo_personale == 'Operatore Stampaggio' ? 'selected' : '' }}>Operatore Stampaggio</option>
                <option value="Capo turno Stampaggio" {{ $user->ruolo_personale == 'Capo turno Stampaggio' ? 'selected' : '' }}>Capo turno Stampaggio</option>
            </select>
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Stato Utente</label>
            <select class="form-control form-control-sm" name="stato">
                <option value="attivo" {{ $user->stato == 'attivo' ? 'selected' : '' }}>Attivo</option>
                <option value="inattivo" {{ $user->stato == 'inattivo' ? 'selected' : '' }}>Inattivo</option>
                <option value="sospeso" {{ $user->stato == 'sospeso' ? 'selected' : '' }}>Sospeso</option>
            </select>
        </div>

    </div>
    <button class="d-none" id="click-me"></button>
</form>
<script>
    $(document).ready(function() {
        function toggleNoAdmin() {
            if ($('#admin').val() == '1') {
                $('.no_admin').hide();
            } else {
                $('.no_admin').show();
            }
        }

        // Esegui al caricamento della pagina
        toggleNoAdmin();

        // Esegui ogni volta che cambia il valore della select
        $('#admin').on('change', toggleNoAdmin);
    });
</script>

<script>
    var initialState;

    $(function() {

        setTimeout(function() {
            initialState = $('#async').serialize();
        }, 100);
        $('#async').submit(function(e) {
            e.preventDefault();
            var formData = new FormData($(this)[0]);
            $.ajax({
                url: '{{ route('users.store') }}',
                type: 'POST',
                data: formData,
                contentType: false,
                cache: false,
                processData: false,
                success: function(data) {
                    if (data.error) {
                        Array.from(data.error).forEach(item => {
                            toastr.error(item);
                        });
                    } else {
                        toastr.success('Data saved successfully');
                        $("#ModalManage").modal('hide');
                        $("#ModalManage .close").click();
                        loadGridData(gridApi);
                    }
                }
            });
        });

    });
</script>