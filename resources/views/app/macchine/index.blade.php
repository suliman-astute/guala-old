@extends('adminlte::page')
@section('title', 'Turni Macchine')

@section('content_header')
  <h1>Turni Macchine</h1>
@stop

@section('content')

{{-- Timer (solo operatori) --}}
@if(auth()->check() && (auth()->user()->admin ?? 0) != 1)
  <style>
    #logout-timer{
      position:fixed; top:10px; right:240px; z-index:1050;
      background:#fff; border:1px solid #e5e7eb; border-radius:8px;
      padding:6px 10px; box-shadow:0 2px 8px rgba(0,0,0,.06); font-weight:600;
      transition:all .2s;
    }
    #logout-timer.danger{
      background:#dc3545; color:#fff; border-color:#dc3545; /* rosso a 30s */
    }
  </style>

  <div id="logout-timer">⏱ <span id="logout-timer-display">01:00</span></div>

  <form id="auto-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
    @csrf
  </form>

  <script>
    (function () {
      const START_MIN   = 1;                  // ⬅️ 1 minuto per test
      const DURATION_MS = START_MIN * 60 * 1000;
      const WARNING_MS  = 30 * 1000;          // avviso a 30s
      const KEY         = 'ordiniLogoutDeadline';

      const el   = document.getElementById('logout-timer');
      const disp = document.getElementById('logout-timer-display');
      const form = document.getElementById('auto-logout-form');

      const now = () => Date.now();

      function setDeadline(msFromNow = DURATION_MS) {
        const dl = now() + msFromNow;
        localStorage.setItem(KEY, String(dl));
        return dl;
      }

      function getDeadline() {
        const v = parseInt(localStorage.getItem(KEY) || '0', 10);
        return (v && v > now()) ? v : setDeadline();
      }

      function fmt(ms){
        const t = Math.max(0, Math.ceil(ms/1000));
        const m = Math.floor(t/60), s = t%60;
        return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
      }

      let deadline = getDeadline();

      function render(){
        const rem = deadline - now();
        if (disp) disp.textContent = fmt(rem);

        if (rem <= WARNING_MS && rem > 0) el?.classList.add('danger');
        else                              el?.classList.remove('danger');

        if (rem <= 0) { clearInterval(tick); form?.submit(); }
      }

      // sync tra schede
      window.addEventListener('storage', (e) => {
        if (e.key === KEY) { deadline = getDeadline(); render(); }
      });

      // reset su attività (throttle 2s)
      let lastReset = 0, THROTTLE_MS = 2000;
      function resetOnActivity() {
        const t = now();
        if (t - lastReset < THROTTLE_MS) return;
        lastReset = t;
        deadline = setDeadline();   // riparte da 1 minuto
        el?.classList.remove('danger');
        render();
      }
      ['click','keydown','scroll','touchstart'].forEach(ev =>
        window.addEventListener(ev, resetOnActivity, {passive:true})
      );

      render();
      const tick = setInterval(render, 1000);
    })();
  </script>
@endif

{{-- MODALE NOTE --}}
<div class="modal fade" id="notaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header">
      <h5 class="modal-title">Nota – <span id="m-macchina" class="text-muted"></span></h5>
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="m-id">
      <textarea id="m-nota" class="form-control" rows="5" maxlength="2000" placeholder="Scrivi qui la nota…"></textarea>
      <small class="form-text text-muted">Max 2000 caratteri.</small>
    </div>
    <div class="modal-footer">
      <button class="btn btn-outline-secondary" data-dismiss="modal">Annulla</button>
      <button class="btn btn-primary" id="m-salva">Salva</button>
    </div>
  </div></div>
</div>

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <div class="fw-semibold">Associazioni macchina ↔ operatore</div>
    <div class="d-flex align-items-center gap-2">
      <label class="mb-0 mr-2">Data</label>
      <input type="date" id="f-data" class="form-control form-control-sm" style="min-width: 160px">
      <button class="btn btn-sm btn-outline-secondary ml-2" id="btn-reload">Ricarica</button>
    </div>
  </div>
  <div class="card-body p-0">
    <div id="tbl-wrap" class="table-responsive">
      <table class="table table-sm table-striped table-sticky align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Macchina</th>
            <th>Operatore</th>
            <th>Nota</th>
            <th style="width: 160px;"></th>
          </tr>
        </thead>
        <tbody id="tbody-rows">
          <tr><td colspan="4" class="text-center p-4">Caricamento…</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const $date   = document.getElementById('f-data');
  const $tbody  = document.getElementById('tbody-rows');
  const $reload = document.getElementById('btn-reload');

  // default: oggi
  const today = new Date().toISOString().slice(0,10);
  $date.value = $date.value || today;

  function rowHTML(r){
    const nota = (r.nota || '').trim();
    const has  = nota.length > 0;
    const btnC = has ? 'btn-warning' : 'btn-outline-secondary';
    const btnT = has ? 'Modifica nota' : 'Aggiungi nota';
    const prev = has ? `<div class="small text-muted mt-1 nota-preview" title="${nota.replace(/"/g,'&quot;')}">${nota}</div>` : '';
    return `
      <tr data-id="${r.id}">
        <td>${r.macchina ?? ''}</td>
        <td>${r.operatori ?? ''}</td>
        <td>${prev || '<span class="text-muted">—</span>'}</td>
        <td class="text-right">
          <button class="btn btn-sm ${btnC} btn-nota" data-id="${r.id}" data-macchina="${(r.macchina||'').replace(/"/g,'&quot;')}" data-nota="${nota.replace(/"/g,'&quot;')}">📝 ${btnT}</button>
        </td>
      </tr>`;
  }

  async function load(){
    $tbody.innerHTML = `<tr><td colspan="4" class="text-center p-4">Caricamento…</td></tr>`;
    const url = `{{ route('assmac.json') }}?data=${encodeURIComponent($date.value)}`;
    try{
      const { data } = await fetch(url).then(r=>r.json());
      if(!data || data.length===0){
        $tbody.innerHTML = `<tr><td colspan="4" class="text-center p-4">Nessun dato per la data selezionata.</td></tr>`;
        return;
      }
      $tbody.innerHTML = data.map(rowHTML).join('');
    }catch(e){
      $tbody.innerHTML = `<tr><td colspan="4" class="text-center p-4 text-danger">Errore nel caricamento.</td></tr>`;
    }
  }

  $reload.addEventListener('click', load);
  $date.addEventListener('change', load);
  load(); // first load

  // --- Modale note
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-nota');
    if(!btn) return;

    document.getElementById('m-id').value      = btn.dataset.id;
    document.getElementById('m-nota').value    = btn.dataset.nota || '';
    document.getElementById('m-macchina').textContent = btn.dataset.macchina || '';
    $('#notaModal').modal('show');
  });

  document.getElementById('m-salva').addEventListener('click', async () => {
    const id   = document.getElementById('m-id').value;
    const nota = document.getElementById('m-nota').value.trim();
    const token= document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    try{
      await fetch(`{{ route('assmac.nota.save') }}`, {
        method:'POST',
        headers:{'Content-Type':'application/json','X-CSRF-TOKEN':token,'X-Requested-With':'XMLHttpRequest'},
        credentials:'same-origin',
        body: JSON.stringify({ id, nota })
      }).then(r=>r.json());

      // aggiorna riga senza ricaricare tutta la tabella
      const tr = document.querySelector(`tr[data-id="${id}"]`);
      if(tr){
        const tdNota = tr.children[2];
        const tdBtn  = tr.children[3];
        if(nota){
          tdNota.innerHTML = `<div class="small text-muted mt-1 nota-preview" title="${nota.replace(/"/g,'&quot;')}">${nota}</div>`;
          tdBtn.querySelector('.btn-nota').classList.remove('btn-outline-secondary');
          tdBtn.querySelector('.btn-nota').classList.add('btn-warning');
          tdBtn.querySelector('.btn-nota').innerHTML = '📝 Modifica nota';
          tdBtn.querySelector('.btn-nota').dataset.nota = nota;
        }else{
          tdNota.innerHTML = `<span class="text-muted">—</span>`;
          tdBtn.querySelector('.btn-nota').classList.add('btn-outline-secondary');
          tdBtn.querySelector('.btn-nota').classList.remove('btn-warning');
          tdBtn.querySelector('.btn-nota').innerHTML = '📝 Aggiungi nota';
          tdBtn.querySelector('.btn-nota').dataset.nota = '';
        }
      }

      $('#notaModal').modal('hide');
    }catch(e){
      alert('Errore nel salvataggio');
    }
  });
});
</script>
@endsection
