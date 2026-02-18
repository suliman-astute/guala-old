<form id="async" method="POST" action="{{ route('ad.store') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $ad->id }}">

    <div class="form-group">
        <label for="dominio">Dominio</label>
        <input type="text" class="form-control" name="dominio" value="{{ old('dominio', $ad->dominio) }}">
    </div> 

    <div class="form-group">
        <label for="host">Host</label>
        <input type="text" class="form-control" name="host" value="{{ old('host', $ad->host) }}" >
    </div>

    <div class="form-group">
        <label for="base_dn">Base DN </label>&nbsp;&nbsp;<small>Devono essere separati da virgola ' , '</small>
        <input type="text" class="form-control" name="base_dn" value="{{ old('base_dn', $ad->base_dn) }}" >
    </div>

    <div class="form-group">
        <label for="porta">Porta</label>
        <input type="number" class="form-control" name="porta" value="{{ old('porta', $ad->porta) }}" >
    </div>

    <button id="click-me" type="submit" class="d-none"></button>
</form>

<script>
    let initialState = null;

    $(document).ready(function () {
        initialState = $('#async').serialize();

        $('#async').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('ad.store') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function (res) {
                    if (res.success) {
                        $('#ModalManage').modal('hide');
                        Swal.fire("Salvato!", "Il record è stato salvato correttamente.", "success");
                        loadGridData();
                    } else if (res.error) {
                        Swal.fire("Errore", res.error.join('<br>'), "error");
                    }
                },
                error: function (xhr) {
                    Swal.fire("Errore", "Errore durante il salvataggio", "error");
                }
            });
        });
    });
</script>
