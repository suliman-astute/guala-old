@extends('adminlte::page')

@section('title', "Monitor GUALA FP")

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
        <div class="card-body" style="height:820px;">
            <div class="row">
                <div class="col-1" style="text-align:right; font-size:16px; font-weight:bold; margin-bottom:10px;"><button onclick="openPage('PH')" class="btn btn-sm btn-primary">PDF Pharma</button> </div>
                <div class="col-1" style="text-align:right; font-size:16px; font-weight:bold; margin-bottom:10px;"><button onclick="openPage('F')" class="btn btn-sm btn-primary">PDF Food</button> </div>
                <div class="col-2" style="text-align:right; font-size:16px; font-weight:bold; margin-bottom:10px; margin-left: 210px;">
                  <div class="input-group mb-3">
                    <span class="input-group-text">DA</span>
                    <input type="date" class="form-control" id="data_da">
                  </div> 
                </div>
                <div class="col-2" style="text-align:right; font-size:16px; font-weight:bold; margin-bottom:10px;">
                  <div class="input-group mb-3">
                    <span class="input-group-text">A</span>
                    <input type="date" class="form-control" id="data_a">
                    <button onclick="filterData()" style="margin-left: 15px;" class="btn btn-sm btn-primary"><i class="fa fa-search" aria-hidden="true"></i></button>
                  </div>
                  
                </div>
                <div class="col-2" style="text-align:right; font-size:16px; font-weight:bold; margin-bottom:10px;">Ultimo aggiornamento Dati: </div>
                <div id="timer" class="col-2" style="font-size:16px; font-weight:bold; margin-bottom:10px;"></div>
            </div>
            <ul class="nav nav-tabs" id="gridTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="pharma-tab" data-toggle="tab" href="#pharma" role="tab" aria-controls="pharma" aria-selected="true">Pharma</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="food-tab" data-toggle="tab" href="#food" role="tab" aria-controls="food" aria-selected="false">Food</a>
                </li>
            </ul>
            <div class="tab-content mt-3" id="gridTabsContent">
                <div class="tab-pane fade show active" id="pharma" role="tabpanel" aria-labelledby="pharma-tab">
                    <div id="grid-monitor_fp-pharma" class="ag-theme-alpine" style="height: 650px; width: 100%;"></div> 
                </div>
                <div class="tab-pane fade" id="food" role="tabpanel" aria-labelledby="food-tab">
                    <div id="grid-monitor_fp-food" class="ag-theme-alpine" style="height: 650px; width: 100%;"></div> 
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

  const $da = document.getElementById('data_da');
  const $a  = document.getElementById('data_a');

  // ricordo l’ultimo filtro usato (serve anche per l’auto-refresh)
  let lastFilters = { from: '', to: '' };

  // chiamata dal bottone "cerca"
  function filterData() {
    const from = $da && $da.value ? $da.value : '';
    const to   = $a  && $a.value  ? $a.value  : '';

    if (from && to && from > to) { 
      alert('DA non può essere dopo A');
      return;
    }

    lastFilters = { from, to };
    loadGridData(lastFilters);

  }


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

    function openPage(schedule){
      const from = document.getElementById('data_da')?.value || '';
      const to   = document.getElementById('data_a')?.value  || '';

      if (from && to && from > to) { alert('DA non può essere dopo A'); return; }

      const url = new URL("{{ route('monitor_fp.pdf_stampaggio_fp.stampa') }}", window.location.origin);
      if (from) url.searchParams.set('from', from);
      if (to)   url.searchParams.set('to', to);
      if (schedule) url.searchParams.set('schedule', schedule); // <-- QUI

      window.open(url.toString(), "_blank", "noopener,noreferrer");
    }



  // ===== helper: grouping per machinePressFull =====
  function groupDataByMachinePressFull(data) {
    const result = [];
    const grouped = {};

    // se il backend non ha già quantita_rimanente/pdf_exists, li calcolo/inizializzo qui
    data.forEach(row => {
      if (typeof row.quantita_rimanente === 'undefined') {
        row.quantita_rimanente = (row.quantity || 0) - (row.quantita_prodotta || 0);
      }
      if (typeof row.pdf_exists === 'undefined') {
        row.pdf_exists = !!row.pdf_exists; // di default false se non presente
      }

      const key = row.machinePressFull || ''; // es: "Pr 01   -   Pressa 120T"
      if (!grouped[key]) grouped[key] = [];
      grouped[key].push(row);
    });

    Object.keys(grouped).forEach(key => {
      // riga di gruppo (header visivo)
      result.push({ is_group: true, machinePressFull: key });

      // righe figlie
      grouped[key].forEach(r => {
        r.is_group = false;
        result.push(r);
      });
    });

    return result;
  }

  // ===== grid =====
  // ===== grid =====
let gridApiPharma, gridApiFood;

const canEditRow = (data) => {
  if (!data) return false;
  if (data.is_group) return false;          // niente edit sulle righe “gruppo”
  return true;
};

// colonne condivise
const sharedColumnDefs = [
  { headerName: "", width: 250,
    cellRenderer: (p) => (p.data && p.data.is_group)
      ? `<strong style="font-size:1.10em;">${p.data.GUAPosition || ''} ${p.data.machinePressFull || ''}</strong>` : "" },
  { headerName: (window.LABELS_STAMPAGGIO && LABELS_STAMPAGGIO['Pressa']) || "Pressa",
    field: "machineSatmp",
    cellRenderer: p => (p.data && p.data.is_group) ? "" : (p.value ?? "") },
  { field: "startingdatetime", headerName: "Data inizio prod", width: 150,
    cellRenderer: p => (p.data && p.data.is_group) ? "" : (p.value ?? "") },
  { field: "itemNo", headerName: "Codice", width: 100,
    cellRenderer: function (p) {
      if (p.data && p.data.is_group) return "";
      if (!p.value) return "";
      return `<a href="javascript:void(0);" onclick="openPopup('${p.data.prodNo}', '${p.data.itemNo}')">${p.value}</a>`;
    } },
  { field: "itemDescription", headerName: "Descrizione", width: 150,
    cellRenderer: p => (p.data && p.data.is_group) ? "" : (p.value ?? "") },
  { field: "prodNo", headerName: "Ordine MES", width: 120, },
  { field: "quantity", headerName: "Qtà Ordine", width: 90,
    cellRenderer: p => (p.data && p.data.is_group) ? "" : (new Intl.NumberFormat('it-IT').format(p.value || 0)) },
  { field: "quantita_prodotta", headerName: "Qtà Prodotta", width: 100,
    cellRenderer: p => (p.data && p.data.is_group) ? "" : (new Intl.NumberFormat('it-IT').format(p.value || 0)) },
  { field: "quantita_rimanente", headerName: "Qtà Residua", width: 100,
    cellRenderer: p => (p.data && p.data.is_group) ? "" : (new Intl.NumberFormat('it-IT').format(p.value || 0)) },
  { field: "prod", headerName: "Prod", width: 80, cellClass: 'ag-grid-cell-centered',
    cellRenderer: (p) => (p.data && p.data.is_group) ? "" : (p.data.mesStatus === '10' ? "<div class='dot yellow'></div>" : "<div class='dot'></div>") },
  { field: "stop", headerName: "Stop", width: 80, cellClass: 'ag-grid-cell-centered',
    cellRenderer: (p) => (p.data && p.data.is_group) ? "" : ((p.data.mesStatus === '15') ? "<div class='dot red'></div>" : "<div class='dot'></div>") },
  { field: "ok", headerName: "Ok", width: 80, cellClass: 'ag-grid-cell-centered',
    cellRenderer: (p) => (p.data && p.data.is_group) ? "" : (p.data.mesStatus === '30' ? "<div class='dot green'></div>" : "<div class='dot'></div>") },
  { field: "commento", headerName: "Commento", width: 425,
    //editable: true,
    editable: (params) => canEditRow(params.data),
    cellEditor: 'agTextCellEditor',
    onCellValueChanged: (p) => {
      if (p.newValue !== p.oldValue) saveComment(p.data.machineSatmp, p.data.prodNo, p.newValue);
   }
   /*cellRenderer: function (p) {
    console.log(p);

    if (p.data && p.data.is_group) return "";
    const div = document.createElement('div');
    const input = document.createElement('input');
    input.type = 'text';
    input.className = 'comment-input';
    input.value = p.value || '';
    input.addEventListener('blur',   () => saveComment(p.data.id, input.value));
    input.addEventListener('keyup',  e => { if (e.key === 'Enter') input.blur(); });
    div.appendChild(input);

    return div;
   }*/
  }
];

function saveComment(machineSatmp, prodNo, commento) {
  $.ajax({
    url: '{{ route("monitor_fp.save_comment") }}', // crea la route sotto
    method: 'POST',
    data: { machineSatmp, prodNo, commento, _token: '{{ csrf_token() }}' },
    success: () => toastr.success('Commento salvato!'),
    error:   () => toastr.error('Errore nel salvataggio del commento!')
  });
}


function makeGridOptions() {
  return {
    columnDefs: sharedColumnDefs,
    rowData: [],
    defaultColDef: { sortable: true, filter: true },
    getRowClass: p => (p.data && p.data.is_group) ? 'group-row' : '',
    // entra in edit con un singolo click
    singleClickEdit: true,
    // esce e salva quando la cella perde il focus
    stopEditingWhenCellsLoseFocus: true,
  };
}

/* // funzione che decide se una riga è pharma/food
function bucketOf(row) {
  const fam = (row.family || '').toString().toLowerCase();
  const desc = (row.machinePressDesc || row.machinePressFull || '').toString().toLowerCase();

  if (fam.includes('PH')) return 'pharma';
  if (fam.includes('F'))   return 'food';

  if (desc.includes('pharma')) return 'pharma';
  if (desc.includes('food'))   return 'food';

  // fallback: se non riconosciuta, mettila in pharma (o escludila)
  return 'pharma';
}
 */
document.addEventListener('DOMContentLoaded', function () {
  const pharmaDiv = document.querySelector('#grid-monitor_fp-pharma');
  const foodDiv   = document.querySelector('#grid-monitor_fp-food');

  gridApiPharma = agGrid.createGrid(pharmaDiv, makeGridOptions());
  gridApiFood   = agGrid.createGrid(foodDiv,   makeGridOptions());

  // primo caricamento
  loadGridData({ from: '', to: '' });
});

// ===== data loader che popola entrambe le griglie =====
function loadGridData(filters = lastFilters) {
  const base = new URL("{{ route('monitor_fp.data') }}", window.location.origin);
  if (filters && filters.from) base.searchParams.set('from', filters.from);
  if (filters && filters.to)   base.searchParams.set('to',   filters.to);

  fetch(base.toString(), {
    method: "GET",
    headers: {
      "Content-Type": "application/json",
      "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
    }
  })
  .then(res => res.json())
  .then(payload => {
    // payload atteso: { pharma: [...], food: [...] }
    // fallback: se arrivasse un array piatto lo gestiamo comunque
    let pharma = [];
    let food   = [];
console.log("test", payload);
    if (Array.isArray(payload)) {
      // Fallback legacy: array piatto → split in base a GUA_schedule
      payload.forEach(r => {
        const s = (r.GUA_schedule || '').toString().trim().toLowerCase();
        if (s === 'ph') pharma.push(r);
        else if (s === 'f') food.push(r);
       else { pharma.push(r); food.push(r); } // non definito → entrambe
      });
    } else {
      pharma = Array.isArray(payload.pharma) ? payload.pharma : [];
      food   = Array.isArray(payload.food)   ? payload.food   : [];
    }

    const groupedPharma = groupDataByMachinePressFull(pharma);
    const groupedFood   = groupDataByMachinePressFull(food);

    gridApiPharma.setGridOption("rowData", groupedPharma);
    gridApiFood.setGridOption("rowData", groupedFood);
  })
  .catch(err => console.error("Errore caricamento:", err));
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

  // ===== refresh ogni 5 minuti =====
  setInterval(() => {
    aggiornaTimer();
      loadGridData(lastFilters);
  }, 300000);
</script>



@endsection
