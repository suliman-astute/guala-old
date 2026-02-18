<form id="async" method="POST" action="{{ route('turni.store') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $turno->id }}">

    <div class="form-group">
        <label for="nome">Nome turno</label>
        <input type="text" class="form-control" name="nome_turno" value="{{ old('nome_turno', $turno->nome_turno) }}" required>
    </div>

    <div class="form-group">
        <label for="orario_inizio">Orario di inizio</label>
        <input type="number" class="form-control" name="inizio" value="{{ old('inizio', $turno->inizio) }}" required>
    </div>

    <div class="form-group">
        <label for="orario_fine">Orario di fine</label>
        <input type="number" class="form-control" name="fine" value="{{ old('fine', $turno->fine) }}" required>
    </div>
    <div class="form-group mb-4">
        <label class="label label-primary">Azienda</label>
        <select class="form-control form-control-sm" name="azienda">
            <option value="0" {{ $turno->azienda == '' ? 'selected' : '' }}></option>
            @foreach($aziende as $id => $nome)
                <option value="{{ $id }}" {{ $turno->azienda == $id ? 'selected' : '' }}>
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
                url: "{{ route('turni.store') }}",
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
