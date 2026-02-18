@extends('adminlte::page')

@section('title', $page)

@section('content_header')
    <h1>{{ $page }}</h1>
@stop

@section('content')

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="font-weight-bold">
                <button onclick="createItem()" class="btn btn-sm btn-primary float-right">
                    <i class="fas fa-plus fa-fw"></i> Aggiungi
                </button>
            </h6>
        </div>

        <div class="card-body">
            <div id="my-grid" class="ag-theme-alpine" style="height: 500px;"></div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="ModalManage" tabindex="-1" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Gestione {{ $page }}</h5>
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
    let gridApi;

    const columnDefs = [
        { headerName: "Endpoint", field: "endpoint", flex: 1 },
        { headerName: "Soap Action", field: "chiamata_soap", flex: 1 },
        { headerName: "Azienda", field: "azienda", flex: 1 },
        {
            headerName: 'Azioni',
            cellClass: "actions-button-cell",
            sortable: false,
            filter: false,
            cellRenderer: function(params) {
                const id = params.data.id;
                const container = document.createElement('div');
                container.innerHTML = `
                    <div class="dropdown">
                        <button class="btn btn-sm btn-primary dropdown-toggle" type="button" data-toggle="dropdown">
                            Azioni
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#" onclick="updateItem(${id}); return false;">
                                <i class="fas fa-cog"></i> Modifica
                            </a>
                            <a class="dropdown-item text-danger" href="#" onclick="deleteItem(${id}); return false;">
                                <i class="fas fa-trash-alt"></i> Elimina
                            </a>
                        </div>
                    </div>`;
                return container.firstElementChild;
            }
        }
    ];

    const gridOptions = {
        columnDefs: columnDefs,
        rowData: [],
        defaultColDef: {
            sortable: true,
            filter: true,
            resizable: true
        },
        onGridReady: function(params) {
            gridApi = params.api;
            loadGridData();
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        const gridDiv = document.querySelector('#my-grid');
        agGrid.createGrid(gridDiv, gridOptions);
    });

    function loadGridData() {
        fetch("{{ route('gestione_piovan.json') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
            gridApi.setGridOption('rowData', data.data);
        })
        .catch(error => console.error('Errore nel caricamento dati:', error));
    }

    function createItem() {
        $('#ModalManage .modal-body').html("");
        $('#ModalManage .modal-body').load("{{ route('gestione_piovan.create') }}");
        $('#ModalManage').modal('show');
    }

    function updateItem(id) {
        $('#ModalManage .modal-body').html("");
        $('#ModalManage .modal-body').load("{{ route('gestione_piovan.create') }}/" + id);
        $('#ModalManage').modal('show');
    }

    function deleteItem(id) {
        Swal.fire({
            title: "Sei sicuro?",
            text: "Questa azione è irreversibile!",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#d33",
            cancelButtonColor: "#3085d6",
            confirmButtonText: "Sì, elimina!",
            cancelButtonText: "Annulla"
        }).then((result) => {
            if (result.isConfirmed) {
                $.post("{{ route('gestione_piovan.destroy') }}", {
                    id_to_del: id,
                    _token: "{{ csrf_token() }}"
                }).done(function () {
                    loadGridData();
                    Swal.fire("Eliminato!", "Il record è stato eliminato.", "success");
                });
            }
        });
    }

    function confirmCloseModal() {
        if (typeof initialState !== 'undefined' && initialState === $('#async').serialize()) {
            $('#ModalManage').modal('hide');
        } else {
            Swal.fire({
                title: "Modifiche non salvate!",
                text: "Vuoi chiudere senza salvare?",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Sì, chiudi",
                cancelButtonText: "Annulla",
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
