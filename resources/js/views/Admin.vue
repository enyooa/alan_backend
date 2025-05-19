<template>
  <section class="dashboard">
    <h2 class="page-title">Панель управления</h2>

    <!-- KPI ----------------------------------------------------------- -->
    <div class="cards">
      <div
        class="card kpi-card"
        v-for="card in kpiData"
        :key="card.label"
      >
        <span class="kpi">{{ card.value }}</span>
        <span class="label">{{ card.label }}</span>
      </div>
    </div>

    <!-- CHART --------------------------------------------------------- -->
    <div class="charts">
      <div class="chart-card">
        <canvas ref="revenueChart"></canvas>
      </div>
    </div>
  </section>
</template>

<script>
import axios from '@/plugins/axios'
import { Chart, LineController, LineElement, CategoryScale,
         LinearScale, PointElement, Tooltip, Legend } from 'chart.js'

/* регистрируем только нужное --------------------------------------- */
Chart.register(
  LineController, LineElement,
  CategoryScale, LinearScale, PointElement,
  Tooltip, Legend
)

export default {
  name: 'Admin',

  data () {
    return {
      /* KPI-карточки заполняются после запроса */
      kpiData: [
        { label: 'Выручка',         value: '—' },
        { label: 'Себестоимость',   value: '—' },
        { label: 'Прибыль',         value: '—' },
        { label: 'Средний чек',     value: '—' }
      ],
      /* данные для графика */
      chart:      null,
      chartScope: 'week',               // «day|week|month|year» — при желании добавьте селектор
      pivot:      new Date().toISOString().substr(0, 10) // YYYY-MM-DD
    }
  },

  created () { this.loadSummary() },

  beforeDestroy () { if (this.chart) this.chart.destroy() },

  methods: {
    /* ─────────── API ─────────── */
    async loadSummary () {
      try {
        const { data } = await axios.get('/api/financial-summary', {
          params: { by: this.chartScope, date: this.pivot }
        })

        /* KPI -------------------------------------------------------- */
        const money = v => Number(v || 0).toLocaleString('ru-RU') + ' ₸'
        const avg   = () => {
          const cnt = data.details.filter(d => d.revenue > 0).length || 1
          return data.total / cnt
        }

        this.kpiData = [
          { label: 'Выручка',       value: money(data.total) },
          { label: 'Себестоимость', value: money(data.costprice) },
          { label: 'Прибыль',       value: money(data.profit) },
          { label: 'Средний чек',   value: money(avg()) }
        ]

        /* график ----------------------------------------------------- */
        this.$nextTick(() => this.renderChart(data.details))
      } catch (e) {
        console.error('Ошибка загрузки дашборда:', e)
      }
    },

    /* ─────────── Chart.js ─────── */
    renderChart (details) {
      const labels = details.map(d => d.value)
      const values = details.map(d => d.revenue)

      if (this.chart) this.chart.destroy()

      this.chart = new Chart(this.$refs.revenueChart, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Выручка, ₸',
            data: values,
            fill: true,
            tension: 0.35,
            pointRadius: 4,
            borderWidth: 2
          }]
        },
        options: {
          maintainAspectRatio: false,
          plugins: { legend: { display: false } },
          scales: {
            y: { beginAtZero: true },
            x: { grid: { display: false } }
          }
        }
      })
    }
  }
}
</script>

<style scoped>
/* ===== theme helpers ================================================== */
:root{
  --c1:#7ebf52;      /* lime-green */
  --c2:#00c3ff;      /* cyan       */
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

/* CHART --------------------------------------------------------------- */
.charts{
  display:grid;
  grid-template-columns:minmax(380px,1fr); /* один график */
  gap:24px;
}
.chart-card{
  background:#fff;
  border-radius:20px;
  box-shadow:0 10px 18px rgba(0,0,0,0.05);
  padding:20px;
  height:360px;
}
</style>
