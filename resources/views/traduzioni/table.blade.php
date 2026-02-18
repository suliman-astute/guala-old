@extends('adminlte::page')

@section('title', "Dizionario")

@section('content_header')
    <h1>Dizionario</h1>
@stop

@section('content')
<div class="card shadow mb-4">
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
                <h5 class="modal-title">Gestisci Dizionario</h5>
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
    function formatTableName(raw) {
        let noView = raw.replace(/_view$/i, "");
        let withSpaces = noView.replace(/_/g, " ");
        return withSpaces.replace(/\w\S*/g, (txt) =>
            txt.charAt(0).toUpperCase() + txt.substring(1).toLowerCase()
        );
    }

    $(document).ready(function() {
        const eGridDiv = document.querySelector('#my-grid');
        if (!eGridDiv) {
            console.error("Elemento #my-grid non trovato");
            return;
        }

        const gridOptions = {
            columnDefs: [
                {
                    headerName: "Tabella",
                    field: "table_name",
                    width: 220,
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) {
                            return `<strong style="font-size:1.1em">${params.data.table_label}</strong>`;
                        }
                        return "";
                    }
                },
                { field: "column_name", headerName: "Nome Colonna", flex: 1 },
                { field: "IT", headerName: "IT (Italiano)", flex: 1 },
                { field: "EN", headerName: "ENG (Inglese)", flex: 1 },
                {
                    headerName: 'Azioni',
                    cellClass: "actions-button-cell",
                    width: 130,
                    sortable: false,
                    filter: false,
                    cellRenderer: function(params) {
                        if (params.data.is_group) return "";
                        const id = params.data.id;
                        return `
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
                    }
                }
            ],
            rowData: [],
            defaultColDef: { sortable: true, filter: true },
            onGridReady: function(parameter) {
                loadGridData(parameter.api);
            }
        };

        new agGrid.createGrid(eGridDiv, gridOptions);
    });

    function loadGridData(api) {
        fetch("{{ route('traduzioni.json') }}", {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(res => res.json())
            .then(data => {
                const grouped = {};
                data.data.forEach(r => {
                    if (!grouped[r.table_name]) grouped[r.table_name] = [];
                    grouped[r.table_name].push(r);
                });
                const flatData = [];
                Object.keys(grouped).forEach(table => {
                    flatData.push({
                        table_name: table,
                        table_label: formatTableName(table),
                        is_group: true
                    });
                    grouped[table].forEach(row => {
                        flatData.push(Object.assign({}, row, { is_group: false }));
                    });
                });
                gridApi = api;
                api.setGridOption("rowData", flatData);
            })
            .catch(err => console.error("Errore caricamento:", err));
    }

    function updateItem(id) {
        $('#ModalManage .modal-body').html("");
        $('#ModalManage .modal-body').load("/traduzioni/edit/" + id);
        $('#ModalManage').modal('show');
    }
</script>

@endsection
