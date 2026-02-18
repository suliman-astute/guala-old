<form id="async" method="POST">
    {!! csrf_field() !!}
    <div class="row">
        <div class="col-12 mb-2">
            <label class="label label-primary">Name*</label>
            <h3>{{ $user->name }}</h3>
            <input type="hidden" name="id" value="{{ $user->id }}">
        </div>


        @foreach ($sites as $site)
        <div class="col-12 mb-2">
            <h5>{{ $site->name }}</h5>

            <!-- Pulsanti per selezione/disselezione -->
            <button type="button" class="btn btn-sm btn-success select-all" data-site-id="{{ $site->id }}">
                Seleziona tutto
            </button>
            <button type="button" class="btn btn-sm btn-danger deselect-all" data-site-id="{{ $site->id }}">
                Deseleziona tutto
            </button>
        </div>

        @foreach ($site->active_apps as $active_app)
            <div class="col-4 mb-2">
                <input id="active_app{{ $active_app->id }}" type="checkbox" name="active_apps[]"
                    value="{{ $active_app->id }}"
                    class="site-app-checkbox site-{{ $site->id }}"
                    {{ $user->active_apps->contains($active_app->id) ? 'checked' : '' }}>
                <label for="active_app{{ $active_app->id }}" class="label label-primary">
                    {{ $active_app->name }}
                </label>
            </div>
        @endforeach
    @endforeach
    </div>
    <button class="d-none" id="click-me"></button>
</form>
<script>
    $(document).ready(function () {
        $('.select-all').on('click', function () {
            let siteId = $(this).data('site-id');
            $('.site-' + siteId).prop('checked', true);
        });

        $('.deselect-all').on('click', function () {
            let siteId = $(this).data('site-id');
            $('.site-' + siteId).prop('checked', false);
        });
    });
</script>
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
                url: '{{ route('users.store_active_apps') }}',
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
