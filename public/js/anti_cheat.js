(function () {
  const el = document.getElementById('proctor');
  if (!el) return;

  const state = {
    tabHiddenCount: 0,
    copyCount: 0,
    pasteCount: 0,
    incidents: [] // {t, type}
  };

  function log(type) {
    state.incidents.push({ t: Date.now(), type });
    if (state.incidents.length > 30) state.incidents.shift();
    el.value = JSON.stringify(state);
  }

  el.value = JSON.stringify(state);

  document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
      state.tabHiddenCount++;
      log('tab_hidden');
      alert("⚠️ Ne quittez pas l’onglet pendant l’examen.");
    }
  });

  document.addEventListener('copy', () => {
    state.copyCount++;
    log('copy');
  });

  document.addEventListener('paste', () => {
    state.pasteCount++;
    log('paste');
  });
})();
