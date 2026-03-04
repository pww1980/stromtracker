/* ═══════════════════════════════════════════════════════════════
   StromTracker – Dashboard JS
   ═══════════════════════════════════════════════════════════════ */

let monthlyChart = null;
let donutChart   = null;
let chartIsLine  = true;

function initDashboard(chartData, donutData, opts) {
  // Year tab navigation
  document.querySelectorAll('.year-tab').forEach(btn => {
    btn.addEventListener('click', () => {
      const url = new URL(window.location.href);
      url.searchParams.set('year', btn.dataset.year);
      window.location.href = url.toString();
    });
  });

  // Monthly chart
  if (chartData && document.getElementById('monthlyChart')) {
    buildMonthlyChart(chartData);
  }

  // Donut chart
  if (donutData && document.getElementById('donutChart')) {
    buildDonutChart(donutData);
  }

  // Toggle chart type
  document.getElementById('toggleChartType')?.addEventListener('click', () => {
    chartIsLine = !chartIsLine;
    const btn = document.getElementById('toggleChartType');
    if (chartIsLine) {
      btn.innerHTML = '<i class="bi bi-bar-chart-fill me-1"></i>Balken';
    } else {
      btn.innerHTML = '<i class="bi bi-graph-up me-1"></i>Linie';
    }
    if (monthlyChart) {
      monthlyChart.config.type = chartIsLine ? 'line' : 'bar';
      monthlyChart.update();
    }
  });
}

function buildMonthlyChart(data) {
  const ctx = document.getElementById('monthlyChart').getContext('2d');

  monthlyChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: data.labels,
      datasets: [
        {
          label: 'Verbrauch (Netz)',
          data: data.consumed,
          borderColor: '#60a5fa',
          backgroundColor: 'rgba(96,165,250,.12)',
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#60a5fa',
          pointRadius: 4,
          pointHoverRadius: 7,
          borderWidth: 2,
        },
        {
          label: 'Solar-Produktion',
          data: data.produced,
          borderColor: '#ffd700',
          backgroundColor: 'rgba(255,215,0,.10)',
          fill: true,
          tension: 0.4,
          pointBackgroundColor: '#ffd700',
          pointRadius: 4,
          pointHoverRadius: 7,
          borderWidth: 2,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top' },
        tooltip: {
          callbacks: {
            label: ctx => ` ${ctx.dataset.label}: ${fmtKwh(ctx.raw)}`,
          },
        },
      },
      scales: {
        x: {
          grid: { color: 'rgba(255,255,255,.05)' },
          ticks: { color: 'rgba(255,255,255,.5)' },
        },
        y: {
          grid: { color: 'rgba(255,255,255,.05)' },
          ticks: {
            color: 'rgba(255,255,255,.5)',
            callback: v => v + ' kWh',
          },
          beginAtZero: true,
        },
      },
    },
  });
}

function buildDonutChart(data) {
  const ctx = document.getElementById('donutChart').getContext('2d');
  const total = data.consumed + data.produced;

  donutChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Verbrauch (Netz)', 'Solar-Produktion'],
      datasets: [{
        data: [data.consumed, data.produced],
        backgroundColor: ['rgba(96,165,250,.8)', 'rgba(255,215,0,.8)'],
        borderColor: ['#60a5fa', '#ffd700'],
        borderWidth: 2,
        hoverOffset: 8,
      }],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: '68%',
      plugins: {
        legend: { display: false },
        tooltip: {
          callbacks: {
            label: ctx => {
              const pct = total > 0 ? ((ctx.raw / total) * 100).toFixed(1) : 0;
              return ` ${ctx.label}: ${fmtKwh(ctx.raw)} (${pct}%)`;
            },
          },
        },
      },
    },
  });
}
