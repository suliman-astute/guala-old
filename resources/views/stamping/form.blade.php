<form id="async" method="POST" action="{{ route('stamping.store') }}">
    @csrf
    <input type="hidden" name="id" value="{{ optional($macchina)->id }}">

    <div class="form-group mb-3">
        <label for="name">Nome</label>
        <input type="text" class="form-control" name="name"
               value="{{ old('name', optional($macchina)->name) }}" required>
    </div>

    <!-- <div class="form-group mb-3">
        <label for="GUAPosition">GUA Position</label>
        <input type="number" class="form-control" name="GUAPosition"
               value="{{ old('GUAPosition', optional($macchina)->GUAPosition) }}">
    </div> -->

    <div class="form-group mb-3">
        <label for="no">NO</label>
        <input type="text" class="form-control" name="no"
               value="{{ old('no', optional($macchina)->no) }}">
    </div>
    <div class="form-group mb-4">
        <label class="label label-primary">Azienda</label>
        <select class="form-control form-control-sm" name="azienda">
            <option value="0" {{ $macchina->azienda == '' ? 'selected' : '' }}></option>
            @foreach($aziende as $id => $nome)
                <option value="{{ $id }}" {{ $macchina->azienda == $id ? 'selected' : '' }}>
                    {{ $nome }}
                </option>
            @endforeach
        </select>
    </div>
    <button id="click-me" type="submit" class="d-none"></button>
</form>

<script>
    $(function () {
        $('#async').on('submit', function (e) {
            e.preventDefault();

            $.ajax({
                url: "{{ route('stamping.store') }}",
                method: "POST",
                data: $(this).serialize(),
                success: function (res) {
                    if (res.success) {
                        $('#ModalManage').modal('hide');
                        Swal.fire("Salvato!", "La macchina è stata salvata correttamente.", "success");
                        if (typeof loadGridData === 'function') loadGridData();
                    } else if (res.error) {
                        Swal.fire("Errore", Array.isArray(res.error) ? res.error.join('<br>') : res.error, "error");
                    }
                },
                error: function () {
                    Swal.fire("Errore", "Errore durante il salvataggio", "error");
                }
            });
        });
    });
</script>
