<form id="async" method="POST" action="{{ route('codice_oggetto.store') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $codice_oggetto->id }}">

    <div class="form-group">
        <label for="codici">Codici</label>
        <input type="text" class="form-control" name="codici" value="{{ old('codici', $codice_oggetto->codici) }}" required>
    </div>

    <div class="form-group">
        <label for="oggetto">Oggetto</label>
        <input type="text" class="form-control" name="oggetto" value="{{ old('oggetto', $codice_oggetto->oggetto) }}" required>
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
                url: "{{ route('codice_oggetto.store') }}",
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
