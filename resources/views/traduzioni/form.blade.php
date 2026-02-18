<form id="async" method="POST">
    {!! csrf_field() !!}
    <div class="row">
        <div class="col-4 mb-2">
            <label class="label label-primary">Colonna</label>
            <input class="form-control form-control-sm" type="text" name="ENG" value="{{ $traduzione->column_name ?? '' }}" required>
        </div>
        <div class="col-4 mb-2">
            <label class="label label-primary">IT (Italiano)*</label>
            <input class="form-control form-control-sm" type="text" name="IT" value="{{ $traduzione->IT ?? '' }}" required>
            <input type="hidden" name="id" value="{{ $traduzione->id ?? '' }}">
        </div>
        <div class="col-4 mb-2">
            <label class="label label-primary">ENG (Inglese)*</label>
            <input class="form-control form-control-sm" type="text" name="EN" value="{{ $traduzione->EN ?? '' }}" required>
        </div>
    </div>
    <button class="d-none" id="click-me"></button>
</form>
<script>
    $(function() {
        $('#async').submit(function(e) {
            e.preventDefault();
            var url = '{{ isset($traduzione->id) && $traduzione->id ? route("traduzioni.update", $traduzione->id) : route("traduzioni.store") }}';
            var formData = $(this).serialize();
            $.post(url, formData, function(data) {
                if (data.error) {
                    Array.from(data.error).forEach(item => {
                        toastr.error(item);
                    });
                } else {
                    toastr.success('Dati salvati');
                    $("#ModalManage").modal('hide');
                    loadGridData(gridApi);
                }
            });
        });
    });
</script>
