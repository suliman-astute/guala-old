@extends('adminlte::page')

@section('title', $page)

@section('content_header')
    <h1>Benvenuto Operatore</h1>
@stop

@section('content')

<div class="modal fade" id="notaModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          Nota per <span id="nota-ordine" class="text-muted"></span> / <span id="nota-lotto" class="text-muted"></span>
        </h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <textarea id="nota-testo" class="form-control" rows="5" maxlength="2000" placeholder="Scrivi qui la nota…"></textarea>
        <small class="form-text text-muted">Max 2000 caratteri.</small>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-dismiss="modal">Annulla</button>
        <button class="btn btn-primary" id="nota-salva">Salva</button>
      </div>
    </div>
  </div>
</div>

<div class="row g-4">
    <div class="col-12 col-lg-6 mt-5">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span class="fw-semibold">Macchinari</span>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" id="collapseAll">Chiudi tutti</button>
                    <button class="btn btn-sm btn-outline-secondary" id="expandAll">Apri tutti</button>
                </div>
            </div>
            <div class="card-body p-3 scroll-panel" id="macchinari-tree">
                Caricamento…
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-6 mt-5">
        <div class="card shadow-sm h-100">
            <div class="card-header">
                <span class="fw-semibold" id="details-title">Dettaglio ordine</span>
            </div>
            <div class="card-body p-0">
                <div id="ordine-details" class="p-3">Seleziona un ordine…</div>
            </div>
            {{-- Card: Materiali/Lotti (Piovan) --}}
            <div class="col-12 col-lg-6 mt-3">
              <div class="card shadow-sm h-100">
                <div class="card-header">
                  <span class="fw-semibold">Materiali / Lotti (Piovan)</span>
                  <small class="text-muted ms-2" id="piovan-idmes"></small>
                </div>
                <div class="card-body p-0">
                  <div id="piovan-details" class="p-3">Seleziona un ordine da una macchina (a sinistra)…</div>
                </div>
              </div>
            </div>
        </div>
        
    </div>
</div>

<style>
    .scroll-panel { max-height: 70vh; overflow: auto; }

    #macchinari-tree details + details { margin-top: .35rem; }
    #macchinari-tree summary{
      list-style:none; cursor:pointer; display:flex; align-items:center; gap:.5rem;
      padding:.35rem .5rem; border-radius:.5rem;
    }
    #macchinari-tree summary:hover{ background:#f8f9fa; }
    #macchinari-tree summary::-webkit-details-marker{ display:none; }
    .chev{ transition:transform .2s ease; }
    details[open] > summary .chev{ transform:rotate(90deg); }

    /* BADGE colori */
    .badge-zero{ background:#e9ecef; color:#6c757d; }          /* grigio per 0 ordini */
    .badge-some{ background:#ffe8cc; color:#d9480f; }          /* arancione per ≥1 */
</style>


@endsection
@section('js')

<script>
@section('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const root       = document.getElementById('macchinari-tree');
  const detailsBox = document.getElementById('ordine-details');
  const piovanBox  = document.getElementById('piovan-details');
  const piovanHdr  = document.getElementById('piovan-idmes');

  let selectedOrdine = null;
  let noteMap = {};
  let currentBtn = null;

  fetch("{{ route('ordini.json') }}")
    .then(r => r.json())
    .then(({ data }) => {
      const byMachine = new Map();
      (data || []).forEach(r => {
        (r.macchinari || []).forEach(m => {
          if (!byMachine.has(m.id)) byMachine.set(m.id, { label: m.label, ordini: new Set() });
          (m.ordini || []).forEach(o => byMachine.get(m.id).ordini.add(o));
        });
      });

      root.innerHTML = '';
      if (byMachine.size === 0) { root.textContent = 'Nessun macchinario.'; return; }

      byMachine.forEach(({ label, ordini }) => {
        const d = document.createElement('details');
        d.open = false;

        const items = Array.from(ordini).sort();
        const count = items.length;
        const colorClass = count > 0 ? 'badge-some' : 'badge-zero';

        // prova a estrarre id_mes dalla label "Cxx - Destination_yy"
        const idMesFromLabel = (label.split(' - ')[1] || '').trim();

        const s = document.createElement('summary');
        s.innerHTML = `
          <span class="chev">▸</span>
          <span class="flex-grow-1">${label}</span>
          <span class="badge ${colorClass}">${count}</span>
        `;
        d.appendChild(s);

        const ul = document.createElement('ul');
        ul.className = 'orders';

        if (count === 0) {
          const li = document.createElement('li');
          li.className = 'text-muted';
          li.textContent = 'Nessun ordine';
          ul.appendChild(li);
        } else {
          items.forEach(o => {
            const li = document.createElement('li');
            const a  = document.createElement('a');
            a.href = '#';
            a.className = 'ordine-link';
            a.dataset.ordine = o;
            a.dataset.idmes  = idMesFromLabel;   // <--- QUI passiamo l'id_mes alla card Piovan
            a.textContent = o;
            li.appendChild(a);
            ul.appendChild(li);
          });
        }

        d.appendChild(ul);
        root.appendChild(d);
      });
    })
    .catch(() => { root.textContent = 'Errore nel caricamento.'; });

  // Expand/Collapse invariati...
  document.getElementById('expandAll')?.addEventListener('click', () => {
    root.querySelectorAll('details').forEach(d => d.open = true);
  });
  document.getElementById('collapseAll')?.addEventListener('click', () => {
    root.querySelectorAll('details').forEach(d => d.open = false);
  });

  // CLICK sugli ordini
  document.addEventListener('click', (e) => {
    const a = e.target.closest('.ordine-link');
    if (!a) return;
    e.preventDefault();

    root.querySelectorAll('.order-link, .ordine-link').forEach(x => x.classList?.remove('active'));
    a.classList.add('active');

    selectedOrdine = a.dataset.ordine;
    const idMes    = a.dataset.idmes || '';

    // 1) card Dettaglio ordine
    detailsBox.innerHTML = 'Caricamento…';
    const urlDett = `{{ route('ordini.dettaglio') }}?ordine=${encodeURIComponent(selectedOrdine)}`;
    const urlNote = `{{ route('ordini.note.list') }}?ordine=${encodeURIComponent(selectedOrdine)}`;

    // 2) card Piovan (se ho id_mes)
    if (idMes) {
      piovanHdr.textContent = `id_mes: ${idMes}`;
      piovanBox.textContent = 'Caricamento…';
    } else {
      piovanHdr.textContent = '';
      piovanBox.textContent = 'Nessun id_mes disponibile per questa macchina.';
    }

    const urlPiovan = idMes ? `{{ route('ordini.piovan') }}?id_mes=${encodeURIComponent(idMes)}` : null;

    const promises = [
      fetch(urlDett).then(r => r.json()),
      fetch(urlNote).then(r => r.json()),
      urlPiovan ? fetch(urlPiovan).then(r => r.json()) : Promise.resolve({ righe: [] }),
    ];

    Promise.allSettled(promises)
      .then(([detRes, noteRes, pioRes]) => {
        const righe   = detRes.status === 'fulfilled' ? (detRes.value.righe || []) : [];
        noteMap       = noteRes.status === 'fulfilled' ? (noteRes.value.note || {}) : {};
        const piovane = pioRes.status === 'fulfilled' ? (pioRes.value.righe || []) : [];

        // --- render card "Dettaglio ordine" (uguale a prima) ---
        if (!righe.length) { detailsBox.textContent = 'Nessun dato trovato per questo ordine.'; }
        else {
          let html = `
            <div class="table-wrap">
              <div class="table-responsive">
                <table class="table table-sm table-striped table-sticky align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Lotto</th>
                      <th>Articolo</th>
                      <th class="text-end">Q.tà</th>
                      <th style="width: 280px;">Note</th>
                    </tr>
                  </thead>
                  <tbody>
          `;
          righe.forEach(r => {
            const lotto = r.lotto ?? '';
            const txt   = (noteMap[lotto] || '').trim();
            const has   = txt.length > 0;
            const btnCl = has ? 'btn-warning' : 'btn-outline-secondary';
            const btnTx = has ? 'Modifica' : 'Aggiungi';

            html += `
              <tr>
                <td>${lotto}</td>
                <td>${r.articolodescrizione ?? ''}</td>
                <td class="text-end">${r.qta ?? ''}</td>
                <td>
                  <button class="btn btn-sm ${btnCl} nota-btn" data-ordine="${selectedOrdine}" data-lotto="${lotto}">
                    📝 ${btnTx} nota
                  </button>
                  ${has ? `<div class="small text-muted mt-1 nota-preview" title="${txt.replace(/"/g,'&quot;')}">${txt}</div>` : ''}
                </td>
              </tr>
            `;
          });
          html += '</tbody></table></div></div>';
          detailsBox.innerHTML = html;
        }

        // --- render card "Materiali / Lotti (Piovan)" ---
        if (!idMes) {
          // già gestito sopra
        } else if (!piovane.length) {
          piovanBox.textContent = 'Nessun materiale/lotto trovato per questo id_mes.';
        } else {
          let phtml = `
            <div class="table-wrap">
              <div class="table-responsive">
                <table class="table table-sm table-striped table-sticky align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="">Material</th>
                      <th style="">Lotto</th>
                    </tr>
                  </thead>
                  <tbody>
          `;
          piovane.forEach(r => {
            const upd = r.updated_at ? new Date(r.updated_at).toLocaleString() : '';
            phtml += `
              <tr>
                <td>${r.material ?? ''}</td>
                <td>
                  ${
                    (!r.lotto || r.lotto.toLowerCase() === 'no')
                      ? `
                        <div class="input-group input-group-sm">
                          <input type="text" class="form-control lotto-input" data-idmes="${idMes}" data-material="${r.material}" placeholder="Inserisci lotto">
                          <button class="btn btn-primary btn-sm salva-lotto" data-idmes="${idMes}" data-material="${r.material}">Salva</button>
                        </div>
                      `
                      : r.lotto
                  }
                </td>
              </tr>
            `;
          });
          phtml += '</tbody></table></div></div>';
          piovanBox.innerHTML = phtml;
        }
      })
      .catch(() => {
        detailsBox.textContent = 'Errore nel caricamento dettagli.';
        piovanBox.textContent  = 'Errore nel caricamento Piovan.';
      });
  });

  // 5) MODALE: salva
  document.getElementById('nota-salva')?.addEventListener('click', () => {
    const ordine = document.getElementById('nota-ordine').textContent;
    const lotto  = document.getElementById('nota-lotto').textContent;
    const nota   = document.getElementById('nota-testo').value.trim();
    const token  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch(`{{ route('ordini.note.save') }}`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin',
      body: JSON.stringify({ ordine, lotto, nota })
    })
    .then(r => r.json())
    .then(_ => {
      noteMap[lotto] = nota;
      $('#notaModal').modal('hide');

      if (currentBtn) {
        const td = currentBtn.closest('td');
        if (nota) {
          currentBtn.classList.remove('btn-outline-secondary');
          currentBtn.classList.add('btn-warning');
          currentBtn.innerHTML = '📝 Modifica nota';

          let prev = td.querySelector('.nota-preview');
          if (!prev) {
            prev = document.createElement('div');
            prev.className = 'small text-muted mt-1 nota-preview';
            td.appendChild(prev);
          }
          prev.textContent = nota;
          prev.title = nota;
        } else {
          currentBtn.classList.add('btn-outline-secondary');
          currentBtn.classList.remove('btn-warning');
          currentBtn.innerHTML = '📝 Aggiungi nota';
          td.querySelector('.nota-preview')?.remove();
        }
      }
    })
    .catch(_ => alert('Errore nel salvataggio'));
  });
  
  // 6) LOTTO: salva se uguala a no, vuoto o null
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.salva-lotto');
    if (!btn) return;

    const idMes   = btn.dataset.idmes;
    const material= btn.dataset.material;
    const input   = btn.parentElement.querySelector('.lotto-input');
    const lotto   = input.value.trim();
    const token   = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    if (!lotto) {
      alert("Inserisci un valore valido per il lotto");
      return;
    }

    fetch("{{ route('ordini.piovan.lotto') }}", {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': token,
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({ id_mes: idMes, material, lotto })
    })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        // aggiorna cella con il valore nuovo
        input.parentElement.outerHTML = lotto;
      } else {
        alert("Errore nel salvataggio");
      }
    })
    .catch(() => alert("Errore di rete"));
  });

});
</script>
@endsection

</script>

@endsection
