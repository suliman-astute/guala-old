@extends('adminlte::page')

@section('title', "Ordini Lotti")
@section('content_header')
@stop

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
      const START_MIN   = 10;                  // ⬅️ 1 minuto per test
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
@section('content')
    @include('ordini.table', ['page' => 'Ordini Lotti'])
@endsection
