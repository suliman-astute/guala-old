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
                    <h5 class="modal-title">{{ $page }}</h5>
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

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>.choices__list--dropdown{ z-index:2055 }</style> {{-- per stare sopra la modale --}}
@endsection

@section('css')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
<style>.choices__list--dropdown{ z-index:2055 }</style> {{-- per stare sopra la modale --}}
@endsection

@section('js')
<script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script>
<script>
    function initChoicesInModal() {
        const modal = document.getElementById('ModalManage');

        // MULTI: Operatori
        const op = modal?.querySelector('#id_operatori');
        if (op) {
        if (op._choices) op._choices.destroy();     // re-init sicuro
        op._choices = new Choices(op, {
            removeItemButton: true,
            placeholder: true,
            placeholderValue: 'Seleziona operatori…',
            searchEnabled: true,
            allowHTML: false,
        });
        }

        // SINGOLO: Macchinario (se vuoi ricerca anche qui)
        const mac = modal?.querySelector('#id_macchinari_associati');
        if (mac) {
        if (mac._choices) mac._choices.destroy();
        mac._choices = new Choices(mac, {
            searchEnabled: true,
            allowHTML: false,
        });
        }
    }

    // Inizializza subito dopo che il form è stato iniettato nella modale
    function afterFormLoaded() { initChoicesInModal(); }

    // distruggi quando chiudi (opzionale, per pulizia)
    $('#ModalManage').on('hidden.bs.modal', function(){
        ['id_operatori','id_macchinari_associati'].forEach(id=>{
        const el = document.getElementById(id);
        if (el?. _choices) { el._choices.destroy(); el._choices = null; }
        });
    });

    let gridApi;

    const columnDefs = [
        { 
            headerName: "Data", 
            field: "data_turno", 
            flex: 1,  
            cellRenderer: (p) => {
                const el = document.createElement('div');
                (String(p.value || '').split(',')).forEach(n => {
                    const row = document.createElement('div');
                    row.textContent = n.trim();
                    el.appendChild(row);
                });
                return el;
            } 
        },
        { headerName: "Capo Turno", field: "capo_turno", flex: 1 },
        { 
            headerName: "Turno", 
            field: "turno", 
            flex: 1,  
            cellRenderer: (p) => {
                const el = document.createElement('div');
                (String(p.value || '').split(',')).forEach(n => {
                    const row = document.createElement('div');
                    row.textContent = n.trim();
                    el.appendChild(row);
                });
                return el;
            } 
        },
        { headerName: "Operatori", field: "operatori", flex: 1 },
        { headerName: "Macchinari", field: "macchinari_associati", flex: 1 },
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
        fetch("{{ route('gestione_turni.json') }}") // <-- GET
            .then(r => {
            if (!r.ok) throw new Error(r.status + ' ' + r.statusText);
            return r.json();
            })
            .then(({ data }) => {
            // usa uno dei due, come preferisci:
            //gridApi.setRowData(data);                 // metodo classico
            gridApi.setGridOption('rowData', data); // se usi v29+
            })
            .catch(err => {
            console.error('Errore nel caricamento dati:', err);
            Swal.fire('Errore', 'Impossibile caricare i turni', 'error');
            });
    }


    function createItem() {
        $('#ModalManage .modal-body').html("");
        $('#ModalManage .modal-body').load("{{ route('gestione_turni.create') }}/", function(){
            afterFormLoaded();                  // <--- e qui!
        });
        $('#ModalManage').modal('show');
    }

    function updateItem(id) {
        $('#ModalManage .modal-body').html("");
        $('#ModalManage .modal-body').load("{{ route('gestione_turni.create') }}/" + id, function(){
            afterFormLoaded();                  // <--- e qui!
        });
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
                $.post("{{ route('gestione_turni.destroy') }}", {
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
