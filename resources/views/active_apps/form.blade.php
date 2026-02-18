<form id="async" method="POST">
    {!! csrf_field() !!}
    <div class="row">
        <div class="col-6 mb-2">
            <label class="label label-primary">Name IT*</label>
            <input class="form-control form-control-sm" type="text" name="name_it" value="{{ $active_app->name_it }}">
            <input type="hidden" name="id" value="{{ $active_app->id }}">
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Name EN*</label>
            <input class="form-control form-control-sm" type="text" name="name_en" value="{{ $active_app->name_en }}">
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Code*</label>
            <input class="form-control form-control-sm" type="text" name="code" value="{{ $active_app->code }}">
        </div>
        <div class="col-6 mb-2 no_admin">
            <label class="label label-primary">Sito*</label>
            <select class="form-control form-control-sm" name="site_id">
                @foreach ($sites as $site)
                    <option value={{ $site->id }} {{ $active_app->site_id == $site->id ? ' selected ' : '' }}>
                        {{ $site->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 mb-2">
            <label class="label label-primary">Icon*</label>
            <input class="form-control form-control-sm" type="file" name="icon">
        </div>
        <div class="form-group col-6 mb-2">
            <label class="label label-primary">Azienda</label>
            <select class="form-control form-control-sm" name="azienda">
                <option value="0" {{ $active_app->azienda == '' ? 'selected' : '' }}></option>
                @foreach($aziende as $id => $nome)
                    <option value="{{ $id }}" {{ $active_app->azienda == $id ? 'selected' : '' }}>
                        {{ $nome }}
                    </option>
                @endforeach
            </select>
        </div>

    </div>
    <button class="d-none" id="click-me"></button>
</form>

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
                url: '{{ route('active_apps.store') }}',
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
