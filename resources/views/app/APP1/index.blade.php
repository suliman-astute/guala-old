@extends('adminlte::page')

@section('title', "Monitor Gauala DIS")

@section('content_header')
@stop

@section('css')
<style>
    .dot { height: 14px; width: 14px; border-radius: 50%; background-color: #ccc; margin: auto; }
    .dot.green { background-color: #28a745; }
    .dot.red { background-color: #dc3545; }
    .dot.yellow { background-color: #ffc107; }
    .ag-grid-cell-centered {
        display: flex;
        justify-content: center; /* Centra orizzontalmente */
        align-items: center;     /* Centra verticalmente */
    }
    .ag-theme-alpine {
        --ag-borders: none;
        --ag-row-border-width: 0px;
        --ag-row-border-style: none;
        --ag-cell-horizontal-padding: 0px;
        --ag-cell-vertical-padding: 0px;
        --ag-header-cell-horizontal-padding: 0px;
        --ag-header-cell-vertical-padding: 0px;
        --ag-font-size: 12px;
        --ag-grid-size: 0px;
        --ag-odd-row-background-color: white;
        --ag-even-row-background-color: white;
    }
    .ag-row-group-header {
        border-bottom: none !important;
        background-color: transparent !important;
    }
    .ag-row.group-row {
        font-weight: bold;
        background: #f8f8f8;
    }
    .comment-input {
        box-sizing: border-box; width: 100%; border: 1px solid #ccc; padding: 2px 5px;
        font-size: 12px; line-height: normal;
    }
    .ag-row-odd, .ag-row-even {
        border:none!important;
    }
</style>
@endsection

@section('content')
<div class="table-responsive">
    <div class="card shadow mb-4">
        <div class="card-body" style="height:950px;">
            <div class="row">
                <div class="col-1" style="text-align:right; font-size:16px; font-weight:bold; margin-bottom:10px;"><button onclick="openPage(1)" class="btn btn-sm btn-primary">PDF Assemblaggio</button> </div>
                <div class="col-1" style="text-align:right; font-size:16px; font-weight:bold; margin-bottom:10px;"><button onclick="openPage(2)" class="btn btn-sm btn-primary">PDF Stampaggio</button> </div>
                <div class="col-8" style="text-align:right; font-size:16px; font-weight:bold; margin-bottom:10px;">Ultimo aggiornamento Dati: </div>
                <div id="timer" class="col-2" style="font-size:16px; font-weight:bold; margin-bottom:10px;"></div>
            </div>
            <ul class="nav nav-tabs" id="gridTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="stampaggio-tab" data-toggle="tab" href="#stampaggio" role="tab" aria-controls="stampaggio" aria-selected="true">Stampaggio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="assemblaggio-tab" data-toggle="tab" href="#assemblaggio" role="tab" aria-controls="assemblaggio" aria-selected="false">Assemblaggio</a>
                </li>
               
            </ul>
            <div class="tab-content mt-3" id="gridTabsContent">
                <div class="tab-pane fade show active" id="stampaggio" role="tabpanel" aria-labelledby="stampaggio-tab">
                    <div id="grid-stampaggio" class="ag-theme-alpine" style="height: 790px; width: 100%;"></div>
                </div>
                <div class="tab-pane fade" id="assemblaggio" role="tabpanel" aria-labelledby="assemblaggio-tab">
                    <div id="grid-assemblaggio" class="ag-theme-alpine" style="height: 790px; width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    window.LABELS_STAMPAGGIO = @json($trad_stampaggio);
    window.LABELS_ASSEMBLAGGIO = @json($trad_assemblaggio);
</script>

<script>
    function aggiornaTimer() {
        const now = new Date();
        const opzioni = {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false
        };

        const dataFormattata = now.toLocaleString('it-IT', opzioni).replace(',', '');
        $("#timer").html(dataFormattata);
    }
    $(document).ready(function () {
        aggiornaTimer(); // avvia subito
    });

    function openPage(tipoPagina){
        
        if(tipoPagina==1){
         // alert(tipoPagina);
          url = new URL("{{ route('app1.pdf.stampa') }}", window.location.origin);   
        } 
        else {  url = new URL("{{ route('app1.pdf_stampaggio.stampa') }}", window.location.origin);}
        if (tipoPagina) url.searchParams.set('tipoPagina', tipoPagina);
        window.open(url.toString(), "_blank", "noopener,noreferrer");
    }



    // --- Utility per raggruppare visivamente i dati ---
    function groupDataByMachinePressFull(data) {
        const result = [];
        const grouped = {};
        data.forEach(item => {
            if (!grouped[item.machinePressFull]) grouped[item.machinePressFull] = [];
            grouped[item.machinePressFull].push(item);
        });
        Object.keys(grouped).forEach(key => {
            result.push({ is_group: true, machinePressFull: key });
            grouped[key].forEach(row => {
                row.is_group = false;
                result.push(row);
            });
        });
        return result;
    }

    function groupDataByFamilyAndMachine(data) {
        const result = [];
        const grouped = {};

        data.forEach(item => {
            const family = item.family?.trim();
            const machine = item.machineSatmp?.trim();

            if (!family || !machine) return; // salta se manca uno dei due

            if (!grouped[family]) grouped[family] = {};
            if (!grouped[family][machine]) grouped[family][machine] = [];

            grouped[family][machine].push(item);
        });

        // Costruzione struttura piatta: famiglia > macchina > ordini
        Object.entries(grouped).forEach(([familyName, machines]) => {
            const macchineValide = Object.entries(machines).filter(([_, items]) => items.length > 0);
            if (macchineValide.length === 0) return; // nessuna macchina valida → salta famiglia

            result.push({
                is_group: true,
                group_type: 'family',
                value: familyName
            });

            macchineValide.forEach(([machineName, items]) => {
                const label = items[0]?.nome_completo_macchina || machineName;
                result.push({
                    is_group: true,
                    group_type: 'machine',
                    value: label,
                    family: familyName
                });

                items.forEach(item => {
                    item.is_group = false;
                    result.push(item);
                });
            });
        });

        return result;
    }



    // Funzioni globali per popup
    window.openPopup = function(orderNo, parentitemNo) {
        console.log(`Apri popup Dettagli Ordine: ${orderNo} - Parent Item: ${parentitemNo}`);

        // Usa il nome della route per generare l'URL
        const urlPattern = `{{ route('ordine.info.dettagli', ['id' => ':id', 'parentitemNo' => ':parentitemNo']) }}`;
        const finalUrl = urlPattern.replace(':id', orderNo).replace(':parentitemNo', parentitemNo);

        window.open(finalUrl, '_blank');
    };

    window.openPdfPopup = function(pdfUrl, titolo = "Visualizza PDF") {
        // pdfUrl deve essere solo il nome file, es: ST202519003590.pdf
        window.open("/bolle_lavorazione_pdf/" + pdfUrl+".pdf", '_blank');
    };


    // Funzione AJAX per commenti
    $(document).on('change', '.comment-input', function () {
        const id = $(this).data('id');
        const comment = $(this).val();
        $.ajax({
            url: '{{ url("/save-comment") }}',
            method: 'POST',
            data: { id: id, commento: comment, _token: '{{ csrf_token() }}' },
            success: function (response) { toastr.success('Commento salvato!'); },
            error: function () { toastr.error('Errore nel salvataggio del commento!'); }
        });
    });

    var gridApi;

    var gridApiStampaggio;
    var gridApiAssemblaggio;

    document.addEventListener('DOMContentLoaded', function() {
        const eGridDiv = document.querySelector('#grid-stampaggio');
        if (!eGridDiv) return;

        const gridOptions = {
            columnDefs: [
                {
                    headerName: "",
                    width: 250,
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) {
                            return `<strong style="font-size:1.10em;">${params.data.machinePressFull}</strong>`;
                        }
                        return "";
                    }
                },
                {
                    headerName: LABELS_STAMPAGGIO['Pressa'] || "Pressa",
                    field: "machineSatmp",
                    cellRenderer: params => (params.data && params.data.is_group) ? "" : params.value
                },
                { field: "relSequence", headerName: "Seq", width: 90,
                  cellRenderer: params => (params.data && params.data.is_group) ? "" : params.value
                },
                {
                    field: "itemNo", headerName: "Codice", width: 100,
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) return "";
                        return `<a href="javascript:void(0);" onclick="openPopup('${params.data.mesOrderNo}', '${params.data.itemNo}')">${params.value}</a>`;
                    }
                },
                { field: "itemDescription", headerName: "Descrizione", width: 150,
                  cellRenderer: params => (params.data && params.data.is_group) ? "" : params.value },
                {
                    field: "mesOrderNo", headerName: "Ordine MES", width: 120,
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) return "";
                        if (params.data.pdf_exists) {
                            // Link verde e cliccabile
                            return `<a href="javascript:void(0);" onclick="openPdfPopup('${params.value}', '${params.data.itemNo}')"
                                        style="color: green; font-weight: bold; text-decoration: underline;">
                                        ${params.value}
                                    </a>`;
                        } else {
                            // Link rosso, non cliccabile
                            return `<span style="color: red; font-weight: bold; cursor: not-allowed;">
                                        ${params.value}
                                    </span>`;
                        }
                    }
                },
                { 
                    field: "quantity", headerName: "Qtà Ordine", width: 90,
                    valueFormatter: params => (params.value ? new Intl.NumberFormat('it-IT').format(params.value) : '0'),
                    cellRenderer: params => (params.data && params.data.is_group) ? "" : params.valueFormatted },
                { 
                    field: "quantita_prodotta", headerName: "Qtà Prodotta", width: 100,
                    valueFormatter: params => (params.value ? new Intl.NumberFormat('it-IT').format(params.value) : '0'),
                    cellRenderer: params => (params.data && params.data.is_group) ? "" : params.valueFormatted },
                { 
                    field: "quantita_rimanente", headerName: "Qtà Residua", width: 100,
                    valueFormatter: params => (params.value ? new Intl.NumberFormat('it-IT').format(params.value) : '0'),
                    cellRenderer: params => (params.data && params.data.is_group) ? "" : params.valueFormatted },
                {
                    field: "prod", headerName: "Prod", width: 80, cellClass: 'ag-grid-cell-centered',
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) return "";
                        return params.data.mesStatus === 'Active' ? "<div class='dot yellow'></div>" : "<div class='dot'></div>";
                    }
                },
                {
                    field: "stop", headerName: "Stop", width: 80, cellClass: 'ag-grid-cell-centered',
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) return "";
                        if( params.data.mesStatus === 'Stop' ||  params.data.mesStatus === 'Pause') return "<div class='dot red'></div>";
                        else return "<div class='dot'></div>";                    }
                },
                {
                    field: "ok", headerName: "Ok", width: 80, cellClass: 'ag-grid-cell-centered',
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) return "";
                        return params.data.mesStatus === 'Complete' ? "<div class='dot green'></div>" : "<div class='dot'></div>";
                    }
                },
                {
                    field: "commento", headerName: "Commento", width: 425,
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) return "";
                        const div = document.createElement('div');
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.name = 'commento';
                        input.className = 'comment-input';
                        input.dataset.id = params.data.id;
                        input.value = params.value || '';
                        input.style.width = '100%';
                        div.appendChild(input);
                        return div;
                    },
                    editable: true,
                    onCellValueChanged: function(params) {
                        if (params.data && params.data.is_group) return;
                        const id = params.data.id;
                        const newComment = params.newValue;
                        $.ajax({
                            url: '{{ url("/save-comment") }}',
                            method: 'POST',
                            data: { id: id, commento: newComment, _token: '{{ csrf_token() }}' },
                            success: function (response) { toastr.success('Commento salvato!'); },
                            error: function () { toastr.error('Errore nel salvataggio del commento!'); }
                        });
                    }
                }
            ],
            rowData: [],
            /* pagination: true,
            paginationPageSize: 50, */
            defaultColDef: { sortable: true, filter: true },
            getRowClass: params => (params.data && params.data.is_group) ? 'group-row' : '',
            onGridReady: function(parameter) {
                gridApiAssemblaggio = parameter.api;
                loadGridData(parameter.api);
            }
        };

        agGrid.createGrid(eGridDiv, gridOptions);
    });
    document.addEventListener('DOMContentLoaded', function() {
        const eGridDiv = document.querySelector('#grid-assemblaggio');
      
        const gridOptions = {
            columnDefs: [
                {
                    headerName: "",
                    field: "groupLabel",
                    colSpan: params => params.data?.is_group ? 15 : 1,
                    cellRenderer: function(params) {
                        
                        if (params.data.group_type === "family") {
                            return `<div style="text-align: center; font-size: 2em; font-weight: bold; color: #2f62ff;">${params.data.value}</div>`;
                        }
                        if (params.data.group_type === "machine") {
                            return `<strong style="font-size: 1.2em; padding-left: 20px;">${params.data.value}</strong>`;
                        }
                        return ;
                    }
                },
                { 
                    field: "relSequence", headerName: "Seq", width: 90,
                    cellRenderer: params => (params.data?.is_group ? "" : params.value)
                },
                {
                    field: "itemNo", headerName: "Codice", width: 100,
                    cellRenderer: function(params) {
                        if (params.data?.is_group) return "";
                        return `<a href="javascript:void(0);" onclick="openPopup('${params.data.mesOrderNo}', '${params.data.itemNo ?? ''}')">${params.value}</a>`;
                    }
                },
                { 
                    field: "itemDescription", headerName: "Descrizione", width: 150,
                    cellRenderer: params => (params.data?.is_group ? "" : params.value)
                },
                {
                    field: "mesOrderNo", headerName: "Ordine MES", width: 120,
                    cellRenderer: function(params) {
                        if (params.data?.is_group) return "";
                        if (params.data.pdf_exists) {
                            return `<a href="javascript:void(0);" onclick="openPdfPopup('${params.value}', '${params.data.itemNo ?? ''}')"
                                        style="color: green; font-weight: bold; text-decoration: underline;">
                                        ${params.value}
                                    </a>`;
                        } else {
                            return `<span style="color: red; font-weight: bold; cursor: not-allowed;">
                                        ${params.value}
                                    </span>`;
                        }
                    }
                },
                { 
                    field: "guaCustomName", headerName: "Cliente", width: 90,
                    cellRenderer: params => (params.data?.is_group ? "" : params.value)
                },
                { 
                    field: "quantity", headerName: "Qtà Ordine", width: 90,
                    valueFormatter: params => params.value ? new Intl.NumberFormat('it-IT').format(params.value) : '0',
                    cellRenderer: params => (params.data?.is_group ? "" : params.valueFormatted)
                },
                { 
                    field: "quantita_prodotta", headerName: "Qtà Prodotta", width: 100,
                    valueFormatter: params => params.value ? new Intl.NumberFormat('it-IT').format(params.value) : '0',
                    cellRenderer: params => (params.data?.is_group ? "" : params.valueFormatted)
                },
                { 
                    field: "quantita_rimanente", headerName: "Qtà Residua", width: 100,
                    valueFormatter: params => params.value ? new Intl.NumberFormat('it-IT').format(params.value) : '0',
                    cellRenderer: params => (params.data?.is_group ? "" : params.valueFormatted)
                },
                {
                    field: "prod", headerName: "Prod", width: 80, cellClass: 'ag-grid-cell-centered',
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) return "";
                        return params.data.mesStatus === 'Active' ? "<div class='dot yellow'></div>" : "<div class='dot'></div>";
                    }
                },
                {
                    field: "stop", headerName: "Stop", width: 80, cellClass: 'ag-grid-cell-centered',
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) return "";
                        if( params.data.mesStatus === 'Stop' ||  params.data.mesStatus === 'Pause') return "<div class='dot red'></div>";
                        else return "<div class='dot'></div>";                    }
                },
                {
                    field: "ok", headerName: "Ok", width: 80, cellClass: 'ag-grid-cell-centered',
                    cellRenderer: function(params) {
                        if (params.data && params.data.is_group) return "";
                        return params.data.mesStatus === 'Complete' ? "<div class='dot green'></div>" : "<div class='dot'></div>";
                    }
                },
                {
                    field: "commento", headerName: "Commento", width: 425,
                    cellRenderer: function(params) {
                        if (params.data?.is_group) return "";
                        const div = document.createElement('div');
                        const input = document.createElement('input');
                        input.type = 'text';
                        input.name = 'commento';
                        input.className = 'comment-input';
                        input.dataset.id = params.data.id;
                        input.value = params.value ?? '';
                        input.style.width = '100%';
                        div.appendChild(input);
                        return div;
                    },
                    editable: true,
                    onCellValueChanged: function(params) {
                        if (params.data?.is_group) return;
                        const id = params.data.id;
                        const newComment = params.newValue;
                        $.ajax({
                            url: '{{ url("/save-comment") }}',
                            method: 'POST',
                            data: { id: id, commento: newComment, _token: '{{ csrf_token() }}' },
                            success: function () { toastr.success('Commento salvato!'); },
                            error: function () { toastr.error('Errore nel salvataggio del commento!'); }
                        });
                    }
                }
            ],
            rowData: [],
            defaultColDef: { sortable: true, filter: true },
            getRowClass: params => (params.data?.is_group ? 'group-row' : ''),
            onGridReady: function(parameter) {
                gridApiAssemblaggio = parameter.api;
                loadGridDataAssemblaggio(parameter.api);
            }
        };  


        agGrid.createGrid(eGridDiv, gridOptions);
    });

    function loadGridDataAssemblaggio(api) {
        fetch("{{ url('/tableviewAssemblaggio') }}", {
            method: "GET",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(res => res.json())
        .then(data => {
            if (!Array.isArray(data)) {
                console.error("Dati non validi per ag-Grid, mi aspetto un array:", data);
                return;
            }
            const groupedData = groupDataByFamilyAndMachine(data);
            //const groupedData = groupDataByMachineSatmp(groupedData);
            const onlyRows = groupedData.filter(row => !row.is_group);
            gridApi = api;
            if (groupedData.length > 0) {
                api.setGridOption("rowData", groupedData);
            }
        })
        .catch(err => console.error("Errore caricamento:", err));
    }

    function loadGridData(api) {
        fetch("{{ url('/tableview') }}", {
            method: "GET",
            headers: { "Content-Type": "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content }
        })
        .then(res => res.json())
        .then(data => {
            if (!Array.isArray(data)) {
                console.error("Dati non validi per ag-Grid, mi aspetto un array:", data);
                return;
            }
            const groupedData = groupDataByMachinePressFull(data);
            gridApi = api;
            if (groupedData.length > 0) {
                api.setGridOption("rowData", groupedData);
            }
        })
        .catch(err => console.error("Errore caricamento:", err));
    }

setInterval(() => {
    aggiornaTimer();

    if (gridApiStampaggio) loadGridData(gridApiStampaggio);
    if (gridApiAssemblaggio) loadGridDataAssemblaggio(gridApiAssemblaggio);
}, 300000);
</script>


@endsection
