<form id="async" method="POST" action="{{ route('gestione_piovan.store') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $gestione_piovan->id }}">

    <div class="form-group">
        <label for="endpoint">Endpoint</label>
        <input type="text" class="form-control" name="endpoint" value="{{ old('endpoint', $gestione_piovan->endpoint) }}" required>
    </div>

    <div class="form-group">
        <label for="chiamata_soap">Soap Action</label>
        <input type="text" class="form-control" name="chiamata_soap" value="{{ old('chiamata_soap', $gestione_piovan->chiamata_soap) }}" required>
    </div>
    <div class="form-group mb-4">
        <label class="label label-primary">Azienda</label>
        <select class="form-control form-control-sm" name="azienda">
            <option value="0" {{ $gestione_piovan->azienda == '' ? 'selected' : '' }}></option>
            @foreach($aziende as $id => $nome)
                <option value="{{ $id }}" {{ $gestione_piovan->azienda == $id ? 'selected' : '' }}>
                    {{ $nome }}
                </option>
            @endforeach
        </select>
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
                url: "{{ route('gestione_piovan.store') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function (res) {
                    if (res.success) {
                        $('#ModalManage').modal('hide');
                        Swal.fire("Salvato!", "Il turno è stato salvato correttamente.", "success");
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
