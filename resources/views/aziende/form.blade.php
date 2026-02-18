<form id="async" method="POST" action="{{ route('aziende.store') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $aziende->id }}">

    <div class="form-group">
        <label for="nome">Nome Azienda</label>
        <input type="text" class="form-control" name="nome" value="{{ old('nome', $aziende->nome) }}" required>
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
                url: "{{ route('aziende.store') }}",
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
