<template>
    <section class="dashboard">
      <h2 class="page-title">Панель управления</h2>

      <!-- KPI CARDS ---------------------------------------------------- -->
      <div class="cards">
        <div class="card kpi-card" v-for="card in kpiData" :key="card.label">
          <span class="kpi">{{ card.value }}</span>
          <span class="label">{{ card.label }}</span>
        </div>
      </div>

      <!-- CHARTS -------------------------------------------------------- -->
      <div class="charts">
        <div class="chart-card">
          <canvas ref="revenueChart" />
        </div>
        <div class="chart-card">
          <canvas ref="requestChart" />
        </div>
      </div>
    </section>
  </template>

  <script>
  import { Chart, BarController, BarElement, LineController, LineElement,
           CategoryScale, LinearScale, PointElement, Tooltip, Legend } from 'chart.js';

  Chart.register(
    BarController, BarElement,
    LineController, LineElement,
    CategoryScale, LinearScale, PointElement,
    Tooltip, Legend
  );

  export default {
    name: 'DashboardView',
    data() {
      return {
        kpiData: [
          { label: 'Новых заявок',   value: 124 },
          { label: 'Выручка сегодня', value: '38 000 ₸' },
          { label: 'Низкий остаток', value: 7 },
        ],
        charts: [], // keep refs to destroy on leave
      };
    },
    mounted() {
      this.renderRevenueChart();
      this.renderRequestChart();
    },
    beforeDestroy() {
      // clean up to avoid detached canvases
      this.charts.forEach(c => c.destroy());
    },
    methods: {
      renderRevenueChart() {
        const ctx = this.$refs.revenueChart;
        const chart = new Chart(ctx, {
          type: 'bar',
          data: {
            labels: ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
            datasets: [{
              label: 'Выручка (₸)',
              data: [18000, 22000, 19500, 25000, 38000, 42000, 31000],
              borderWidth: 1,
            }],
          },
          options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, ticks: { callback: v => `${v / 1000}k` } },
              x: { grid: { display: false } },
            },
          },
        });
        this.charts.push(chart);
      },
      renderRequestChart() {
        const ctx = this.$refs.requestChart;
        const chart = new Chart(ctx, {
          type: 'line',
          data: {
            labels: ['23 Апр', '24 Апр', '25 Апр', '26 Апр', '27 Апр', '28 Апр', '29 Апр'],
            datasets: [{
              label: 'Заявки',
              data: [17, 12, 9, 24, 31, 28, 18],
              fill: true,
              tension: 0.35,
              pointRadius: 4,
              borderWidth: 2,
            }],
          },
          options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
              y: { beginAtZero: true, grid: { dash: [4, 4] } },
              x: { grid: { display: false } },
            },
          },
        });
        this.charts.push(chart);
      },
    },
  };
  </script>

  <style scoped>
  /* ===== theme helpers ================================================== */
  :root{
    --c1:#7ebf52; /* lime-green  */
    --c2:#00c3ff; /* cyan        */
    --c3:#f4aa1c; /* amber       */
    --glass-bg:rgba(255,255,255,0.45);
    --glass-blur:blur(8px);
  }

  /* layout --------------------------------------------------------------- */
  .dashboard{
    padding:24px;
    background:linear-gradient(135deg,#f7faff 0%,#e6f4ff 100%);
    min-height:calc(100vh - 56px);
  }

  /* title ---------------------------------------------------------------- */
  .page-title{
    font-size:22px;
    font-weight:700;
    margin-bottom:24px;
  }

  /* KPI cards ------------------------------------------------------------ */
  .cards{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
    gap:20px;
    margin-bottom:32px;
  }

  .kpi-card{
    background:var(--glass-bg);
    backdrop-filter:var(--glass-blur);
    border-radius:20px;
    padding:24px 20px;
    box-shadow:0 12px 20px rgba(0,0,0,0.06);
    display:flex;
    flex-direction:column;
    align-items:flex-start;
    transition:transform .25s;
  }
  .kpi-card:hover{ transform:translateY(-4px); }

  .kpi   { font-size:32px; font-weight:800; }
  .label { font-size:15px; color:#475569; margin-top:4px; }

  /* CHARTS --------------------------------------------------------------- */
  .charts{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(380px,1fr));
    gap:24px;
  }
  .chart-card{
    background:#fff;
    border-radius:20px;
    box-shadow:0 10px 18px rgba(0,0,0,0.05);
    padding:20px;
    height:360px;      /* responsive height */
  }

  /* give each dataset a lovely gradient */
  .chart-card canvas{
    --from: var(--c1);
    --to:   var(--c2);
  }
  </style>
