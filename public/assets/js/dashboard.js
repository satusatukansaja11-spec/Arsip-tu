(function () {
  'use strict';
  if (typeof Chart === 'undefined') { return; }

  var palette = ['#0d47a1', '#1e88e5', '#64b5f6', '#26a69a', '#7e57c2', '#ef6c00', '#8d6e63', '#78909c', '#c0ca33', '#ec407a', '#00838f', '#5d4037'];

  function read(id) {
    var el = document.getElementById(id);
    if (!el) { return null; }
    try { return { el: el, data: JSON.parse(el.getAttribute('data-chart')) }; } catch (e) { return null; }
  }

  var s = read('chartStatus');
  if (s) {
    new Chart(s.el, {
      type: 'doughnut',
      data: { labels: s.data.labels, datasets: [{ data: s.data.values, backgroundColor: palette.slice(0, 3) }] },
      options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
  }

  var k = read('chartKategori');
  if (k) {
    new Chart(k.el, {
      type: 'bar',
      data: { labels: k.data.labels, datasets: [{ label: 'Jumlah dokumen', data: k.data.values, backgroundColor: '#1e88e5' }] },
      options: {
        indexAxis: 'y', maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } }
      }
    });
  }
})();
