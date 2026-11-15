(function () {
  const STORAGE_DATA_KEY = 'sx_dataset_v3';
  const STORAGE_STOP_KEY = 'sx_last_fermata';

  let dataset = null;
  let showAllDay = false;

  const elClock = document.getElementById('liveClock');
  const elGiorno = document.getElementById('selectGiorno');
  const elPartenza = document.getElementById('selectFermata');
  const elDest = document.getElementById('selectDestinazione');
  const elOrario = document.getElementById('inputOrario');
  const elContainer = document.getElementById('tratteContainer');
  const elBanner = document.getElementById('bannerArea');
  const elFooterUpdate = document.getElementById('footerUpdate');
  const elFab = document.getElementById('fabFilter');

  const btnAdesso = document.getElementById('btnAdesso');
  const btnDaCxc = document.getElementById('btnDaCxc');
  const btnVersoCxc = document.getElementById('btnVersoCxc');
  const btnTutte = document.getElementById('btnTutte');

  function getTodayBit() {
    const jsDay = new Date().getDay();
    return jsDay === 0 ? 64 : (1 << (jsDay - 1));
  }

  function formatNowHHMM() {
    const now = new Date();
    return String(now.getHours()).padStart(2, '0') + ':' + String(now.getMinutes()).padStart(2, '0');
  }

  function timeToMinutes(hhmm) {
    const [h, m] = hhmm.split(':').map(Number);
    return h * 60 + m;
  }

  function updateClockAndStyle() {
    elClock.textContent = formatNowHHMM();
    const bit = Number(elGiorno.value);
    elGiorno.className = 'day-toggle ' + (bit >= 32 ? 'festivo' : 'feriale');
  }

  function updateActiveChips() {
    [btnAdesso, btnDaCxc, btnVersoCxc, btnTutte].forEach((b) => b.classList.remove('is-active'));
    if (showAllDay) {
      btnTutte.classList.add('is-active');
    } else if (elPartenza.value === 'cxc' && elDest.value === '') {
      btnDaCxc.classList.add('is-active');
    } else if (elDest.value === 'cxc') {
      btnVersoCxc.classList.add('is-active');
    } else {
      btnAdesso.classList.add('is-active');
    }
  }

  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  function populateStops(luoghi) {
    const prevStart = elPartenza.value;
    const prevDest = elDest.value;

    elPartenza.innerHTML = '<option value="">Tutte le fermate</option>';
    elDest.innerHTML = '<option value="">Qualsiasi arrivo</option>';

    luoghi.forEach((l) => {
      elPartenza.add(new Option(l.denominazione, l.id));
      elDest.add(new Option(l.denominazione, l.id));
    });

    if (prevStart) elPartenza.value = prevStart;
    if (prevDest) elDest.value = prevDest;
  }

  function renderBanner(manutenzione, isOffline) {
    let html = '';
    if (isOffline) {
      html += `
        <div class="alert alert-info" role="status">
          <strong><i class="fa-solid fa-wifi"></i> Modalità Offline:</strong> Stai visualizzando gli ultimi orari salvati.
        </div>`;
    }
    if (manutenzione && manutenzione.attiva) {
      html += `
        <div class="alert alert-warning" role="alert">
          <strong><i class="fa-solid fa-triangle-exclamation"></i> Avviso:</strong> ${escapeHtml(manutenzione.messaggio)}
        </div>`;
    }
    elBanner.innerHTML = html;
  }

  function render() {
    if (!dataset) return;
    updateActiveChips();

    if (dataset.manutenzione && dataset.manutenzione.bloccata) {
      elContainer.innerHTML = `
        <section class="card empty-state" role="alert">
          <i class="fa-solid fa-wrench"></i>
          <h2 style="margin:0 0 8px;">Servizio momentaneamente sospeso</h2>
          <p style="margin:0; color:var(--text-muted);">${escapeHtml(dataset.manutenzione.messaggio)}</p>
        </section>`;
      return;
    }

    const dayBit = Number(elGiorno.value);
    const startStop = elPartenza.value;
    const destStop = elDest.value;
    const minMinutes = showAllDay ? 0 : timeToMinutes(elOrario.value || formatNowHHMM());
    const nowMinutes = timeToMinutes(formatNowHHMM());
    const isToday = (dayBit === getTodayBit());

    const risultati = [];

    dataset.tratte.forEach((tratta) => {
      if ((tratta.giorni_attivi & dayBit) === 0) return;

      const fermate = tratta.fermate;
      if (!fermate || fermate.length < 2) return;

      let startIndex = 0;
      if (startStop !== '') {
        startIndex = fermate.findIndex((f, idx) =>
          idx < fermate.length - 1 &&
          f.idLuogo === startStop &&
          timeToMinutes(f.orario) >= minMinutes
        );
        if (startIndex === -1) return;
      } else if (!showAllDay) {
        startIndex = fermate.findIndex((f, idx) =>
          idx < fermate.length - 1 && timeToMinutes(f.orario) >= minMinutes
        );
        if (startIndex === -1) return;
      }

      const subFermate = fermate.slice(startIndex);
      if (subFermate.length < 2) return;

      let destIndex = -1;
      if (destStop !== '') {
        destIndex = subFermate.findIndex((f, idx) => idx > 0 && f.idLuogo === destStop);
        if (destIndex === -1) return;
      }

      risultati.push({
        numero: tratta.numero,
        note: tratta.note,
        fermate: subFermate,
        destStopObj: destIndex !== -1 ? subFermate[destIndex] : null
      });
    });

    risultati.sort((a, b) => timeToMinutes(a.fermate[0].orario) - timeToMinutes(b.fermate[0].orario));

    if (risultati.length === 0) {
      elContainer.innerHTML = `
        <section class="card empty-state" role="status">
          <i class="fa-solid fa-route" aria-hidden="true"></i>
          <h2 style="margin:0 0 6px; font-size:1.1rem;">Nessuna corsa trovata</h2>
          <p style="margin:0 0 14px; color:var(--text-muted); font-size:0.9rem;">
            Nessuna corsa disponibile con i filtri selezionati${showAllDay ? '' : ' dalle ore <strong>' + escapeHtml(elOrario.value) + '</strong>'}.
          </p>
          <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('btnTutte').click()">
            Mostra tutte le corse del giorno
          </button>
        </section>`;
      return;
    }

    elContainer.innerHTML = risultati.map((corsa) => {
      const depMin = timeToMinutes(corsa.fermate[0].orario);
      const diff = depMin - nowMinutes;
      let badgeHtml = '';

      if (isToday && diff >= -1 && diff <= 90) {
        if (diff <= 0) {
          badgeHtml = `<span class="badge badge-now"><i class="fa-solid fa-circle-dot"></i> In partenza</span>`;
        } else if (diff <= 15) {
          badgeHtml = `<span class="badge badge-soon"><i class="fa-regular fa-clock"></i> Tra ${diff} min</span>`;
        } else {
          badgeHtml = `<span class="badge badge-next"><i class="fa-regular fa-clock"></i> Tra ${diff} min</span>`;
        }
      }

      let tripSummaryHtml = '';
      if (corsa.destStopObj) {
        const arrMin = timeToMinutes(corsa.destStopObj.orario);
        const durata = arrMin >= depMin ? (arrMin - depMin) : (1440 - depMin + arrMin);
        tripSummaryHtml = `
          <div class="trip-summary">
            <span><i class="fa-solid fa-flag-checkered"></i> Arrivo previsto: <strong>${escapeHtml(corsa.destStopObj.orario)}</strong></span>
            <span>${durata} min</span>
          </div>`;
      }

      const noteHtml = corsa.note ? `<span class="tratta-note">${escapeHtml(corsa.note)}</span>` : '';

      const stopsHtml = corsa.fermate.map((f, idx) => {
        const isFirst = idx === 0;
        const isLast = idx === corsa.fermate.length - 1;
        const isDest = destStop !== '' && f.idLuogo === destStop && !isFirst;

        let cls = '';
        if (isFirst) cls = 'is-start';
        else if (isDest) cls = 'is-dest';
        else if (isLast) cls = 'is-end';

        return `
          <li class="timeline-item ${cls}">
            <span class="stop-name">
              ${escapeHtml(f.denominazione)}
              ${isDest ? '<span class="badge badge-arrival">Arrivo</span>' : ''}
            </span>
            <time class="stop-time" datetime="${escapeHtml(f.orario)}">${escapeHtml(f.orario)}</time>
          </li>`;
      }).join('');

      return `
        <article class="card" aria-label="Tratta ${corsa.numero}">
          <header class="tratta-header">
            <h2 class="tratta-title">
              <span>Tratta ${corsa.numero}</span>
              ${noteHtml}
            </h2>
            ${badgeHtml}
          </header>
          ${tripSummaryHtml}
          <ol class="timeline">${stopsHtml}</ol>
        </article>`;
    }).join('');
  }

  async function init() {
    const params = new URLSearchParams(window.location.search);
    elGiorno.value = String(getTodayBit());
    elOrario.value = params.get('orario') ? params.get('orario').slice(0, 5) : formatNowHHMM();
    if (params.get('show') === 'all') showAllDay = true;
    updateClockAndStyle();

    const cachedRaw = localStorage.getItem(STORAGE_DATA_KEY);
    if (cachedRaw) {
      try {
        dataset = JSON.parse(cachedRaw);
        populateStops(dataset.luoghi);
        if (params.get('fermata')) elPartenza.value = params.get('fermata');
        if (params.get('destinazione')) elDest.value = params.get('destinazione');
        elFooterUpdate.textContent = dataset.ultima_modifica || '--/--/----';
        renderBanner(dataset.manutenzione, false);
        render();
      } catch (e) {}
    }

    try {
      const res = await fetch('api/orari.php');
      if (res.status === 401) {
        window.location.href = 'index.php?redirect=home';
        return;
      }
      const fresh = await res.json();
      if (fresh && fresh.ok) {
        dataset = fresh;
        localStorage.setItem(STORAGE_DATA_KEY, JSON.stringify(fresh));
        populateStops(fresh.luoghi);
        if (params.get('fermata')) elPartenza.value = params.get('fermata');
        if (params.get('destinazione')) elDest.value = params.get('destinazione');
        elFooterUpdate.textContent = fresh.ultima_modifica || '--/--/----';
        renderBanner(fresh.manutenzione, false);
        render();
      }
    } catch (err) {
      if (dataset) {
        renderBanner(dataset.manutenzione, true);
      } else {
        elContainer.innerHTML = `
          <div class="alert alert-danger" role="alert">
            Impossibile caricare gli orari. Verifica la connessione internet.
          </div>`;
      }
    }

    elGiorno.addEventListener('change', () => {
      updateClockAndStyle();
      render();
    });

    elPartenza.addEventListener('change', () => {
      if (elPartenza.value && elPartenza.value !== 'cxc') {
        localStorage.setItem(STORAGE_STOP_KEY, elPartenza.value);
      }
      showAllDay = false;
      render();
    });

    elDest.addEventListener('change', () => {
      showAllDay = false;
      render();
    });

    elOrario.addEventListener('change', () => {
      showAllDay = false;
      render();
    });

    document.getElementById('btnSwap').addEventListener('click', () => {
      const tmp = elPartenza.value;
      elPartenza.value = elDest.value;
      elDest.value = tmp;
      showAllDay = false;
      render();
    });

    btnAdesso.addEventListener('click', () => {
      showAllDay = false;
      elPartenza.value = '';
      elDest.value = '';
      elOrario.value = formatNowHHMM();
      render();
    });

    btnDaCxc.addEventListener('click', () => {
      elPartenza.value = 'cxc';
      elDest.value = '';
      showAllDay = false;
      render();
    });

    btnVersoCxc.addEventListener('click', () => {
      const saved = localStorage.getItem(STORAGE_STOP_KEY);
      if (!elPartenza.value || elPartenza.value === 'cxc') {
        elPartenza.value = (saved && saved !== 'cxc') ? saved : '';
      }
      elDest.value = 'cxc';
      showAllDay = false;
      render();
    });

    btnTutte.addEventListener('click', () => {
      showAllDay = true;
      elPartenza.value = '';
      elDest.value = '';
      render();
    });

    document.getElementById('btnReset').addEventListener('click', () => {
      showAllDay = false;
      elGiorno.value = String(getTodayBit());
      elPartenza.value = '';
      elDest.value = '';
      elOrario.value = formatNowHHMM();
      updateClockAndStyle();
      render();
    });

    // Mostra pulsante flottante "Filtra" solo quando il pannello filtri è uscito dallo schermo
    window.addEventListener('scroll', () => {
      if (window.scrollY > 220) {
        elFab.classList.add('visible');
      } else {
        elFab.classList.remove('visible');
      }
    }, { passive: true });

    elFab.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });

    setInterval(() => {
      updateClockAndStyle();
      render();
    }, 30000);

    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('sw.js').catch(() => {});
    }
  }

  init();
})();