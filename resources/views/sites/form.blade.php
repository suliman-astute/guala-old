<form id="async" method="POST">
    {!! csrf_field() !!}
    <div class="row">
        <div class="col-6 mb-2">
            <label class="label label-primary">Name*</label>
            <input class="form-control form-control-sm" type="text" name="name" value="{{ $site->name }}">
            <input type="hidden" name="id" value="{{ $site->id }}">
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
                url: '{{ route('sites.store') }}',
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
