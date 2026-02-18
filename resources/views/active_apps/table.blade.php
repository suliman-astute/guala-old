@extends('adminlte::page')

@section('title', $page)

@section('content_header')
    <h1>{{ $page }}</h1>
@stop

@section('content')

    <div class="card shadow mb-4">

        <div class="card-header py-3">
            <h6 class="font-weight-bold">
                <button onclick="createItem()" class="btn-primary btn btn-sm float-right"><i
                        class="fas fa-plus fa-fw"></i></button>
            </h6>
        </div>


        <div class="card-body">
            <div class="row">
                <div class="col-12">
                    <div id="my-grid" class="my-ag-grid ag-theme-alpine"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="ModalManage" tabindex="-1" aria-labelledby="ModalManageLabel" aria-hidden="true"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Manage {{ $page }}</h5>
                </div>
                <div class="modal-body"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" onclick="confirmCloseModal();">Chiudi</button>
                    <button type="button" class="btn btn-sm btn-primary"
                        onclick="$(this).hide().delay(1000).show(0); $('#click-me').click()">Salva e chiudi</button>
                </div>
            </div>
        </div>
    </div>



@endsection

@section('js')

    <script>
        $(document).ready(function() {
            var csrfToken = $('meta[name="csrf-token"]').attr('content');
            var apiUrl = "{{ route('active_apps.json') }}";

            const eGridDiv = document.querySelector('#my-grid');
            if (!eGridDiv) {
                console.error("Elemento #my-grid non trovato");
                return;
            }

            const gridOptions = {
                columnDefs: [{
                        headerName: "Immagine Icon",

                        field: "name",
                        width: 150,
                        autoHeight: true,
                        cellRenderer: function(params) {
                            const id = params.data.id;
                            const dropdownId = `dropdownMenuButton${id}`;

                            // Creo un container temporaneo
                            const container = document.createElement('div');
                            container.innerHTML = `
                                <img src="${params.data.icon_link}" alt="Icon" class="w-100 img-fluid img-thumbnail">
                            `;

                            // Ritorno il primo elemento, cioè il div.dropdown
                            return container.firstElementChild;
                        }
                    },
                    {
                        field: "name",
                        flex: 1,
                        headerName: "Nome"
                    },

                    {
                        field: "site.name",
                        flex: 1,
                        headerName: "Site"
                    },
                    {

                        headerName: 'Azioni',
                        cellClass: "actions-button-cell",
                        sortable: false,
                        filter: false,
                        cellRenderer: function(params) {
                            const id = params.data.id;
                            const dropdownId = `dropdownMenuButton${id}`;

                            // Creo un container temporaneo
                            const container = document.createElement('div');
                            container.innerHTML = `
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                                        Azioni
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#" onclick="updateItem(${id}); return false;">
                                        <i class="fas fa-cog"></i> Update
                                        </a>
                                        <a class="dropdown-item text-danger" href="#" onclick="deleteItem(${id}); return false;">
                                        <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </div>
                                </div>`;

                            // Ritorno il primo elemento, cioè il div.dropdown
                            return container.firstElementChild;
                        }
                    }
                ],
                rowData: [],
                /* pagination: true,

                paginationPageSize: 50, */
                defaultColDef: {
                    sortable: true,
                    filter: true
                },
                onGridReady: function(parameter) {
                    loadGridData(parameter.api);
                }
            };

            new agGrid.createGrid(eGridDiv, gridOptions);
        });


        function loadGridData(api) {
            fetch("{{ route('active_apps.json') }}", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                            .content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    gridApi = api;
                    api.setGridOption("rowData", data.data);
                })
                .catch(err => console.error("Errore caricamento:", err));
        }
    </script>

    <script>
        function createItem() {
            $('#ModalManage .modal-body').html("");
            $('#ModalManage .modal-body').load("{{ route('active_apps.create') }}");
            $('#ModalManage').modal('show');
        }

        function updateItem(id) {
            $('#ModalManage .modal-body').html("");
            $('#ModalManage .modal-body').load("{{ route('active_apps.create') }}/" + id);
            $('#ModalManage').modal('show');
        }

        function deleteItem(id) {
            Swal.fire({
                title: "Are you sure?",
                text: "This action is irreversible!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#d33",
                cancelButtonColor: "#3085d6",
                confirmButtonText: "Yes, delete!",
                cancelButtonText: "Cancel"
            }).then((result) => {

                if (result.value === true) {

                    $.post("{{ route('active_apps.destroy') }}", {
                            id_to_del: id,
                            _token: "{{ csrf_token() }}"
                        })
                        .done(function() {
                            loadGridData(gridApi);
                        });
                    Swal.fire("Deleted!", "The record has been deleted.", "success");
                }
            });
        }
    </script>

    <script>
        function confirmCloseModal() {
            if (initialState === $('#async').serialize()) {
                $('#ModalManage').modal('hide');
            } else {
                Swal.fire({
                    title: "Unsaved changes!",
                    text: "If you close the form, your changes will be lost. Confirm the closure?",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Yes, close",
                    cancelButtonText: "Cancel",
                    confirmButtonColor: "#d33",
                    cancelButtonColor: "#3085d6",
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#ModalManage').modal('hide');
                    }
                });
            }
        }
    </script>

@endsection
