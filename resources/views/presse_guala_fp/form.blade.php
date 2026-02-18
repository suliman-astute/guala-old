<form id="async" method="POST" action="{{ route('presse_guala_fp.store') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $presse_guala_fp->id }}">

    <div class="form-group">
        <label for="nome">Posizione</label>
        <input type="text" class="form-control" name="GUAPosition" value="{{ old('GUAPosition', $presse_guala_fp->GUAPosition) }}" required>
    </div>
    <div class="form-group mb-3">
        <label for="nid_meid_messo">ID Mes</label>
        <input type="text" class="form-control" name="id_mes"
               value="{{ old('id_mes', $presse_guala_fp->id_mes) }}">
    </div>

    <div class="form-group mb-4">
        <label for="id_piovan">Id Piovan</label>
        <input type="string" class="form-control" name="id_piovan"
               value="{{ old('id_piovan', $presse_guala_fp->id_piovan) }}">
    </div>
    <div class="form-group mb-4">
        <label for="id_piovan">Ingressi Usati</label>
        <input type="string" class="form-control" name="ingressi_usati"
               value="{{ old('ingressi_usati', $presse_guala_fp->ingressi_usati) }}">
    </div>
    <div class="form-group mb-4">
        <label class="label label-primary">Azienda</label>
        <select class="form-control form-control-sm" name="azienda">
            <option value="0" {{ $presse_guala_fp->azienda == '' ? 'selected' : '' }}></option>
            @foreach($aziende as $id => $nome)
                <option value="{{ $id }}" {{ $presse_guala_fp->azienda == $id ? 'selected' : '' }}>
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
                url: "{{ route('presse_guala_fp.store') }}",
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
