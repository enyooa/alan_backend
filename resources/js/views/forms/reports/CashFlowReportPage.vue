<template>
    <div class="dashboard-container">
      <div class="main-content">
        <main class="content">
          <h2 class="page-title">Отчёт по кассе</h2>

          <!-- ===== ФИЛЬТРЫ ===== -->
          <div class="filters">
            <label>Дата с
              <input type="date" v-model="filters.dateFrom">
            </label>

            <label>по
              <input type="date" v-model="filters.dateTo">
            </label>

            <label>Касса
              <input type="text"
                     v-model="filters.cashbox"
                     placeholder="Название кассы">
            </label>

            <!-- новый фильтр -->
            <label>Статья
              <input type="text"
                     v-model="filters.element"
                     placeholder="Название статьи">
            </label>

            <button class="apply-btn" @click="fetchOrders">Применить</button>
          </div>

          <!-- Loading / Error -->
          <div v-if="loading"  class="loading-indicator">Загрузка…</div>
          <div v-else-if="error" class="error-message">{{ error }}</div>

          <!-- ===== ТАБЛИЦА ===== -->
          <div v-else>
            <table class="report-table">
              <thead>
                <tr>
                  <th>Название кассы</th>
                  <th>Начальный остаток</th>
                  <th>Приход</th>
                  <th>Расход</th>
                  <th>Конечный остаток</th>
                  <th>Дата отчёта</th>
                </tr>
              </thead>

              <!-- одна группа tbody на каждую кассу -->
              <tbody v-for="g in groups" :key="g.cashbox">
                <!-- строка кассы -->
                <tr class="group-row">
                  <td><strong>{{ g.cashbox }}</strong></td>
                  <td><strong>{{ g.start   | fmt }}</strong></td>
                  <td><strong>{{ g.income  | fmt }}</strong></td>
                  <td><strong>{{ g.expense | fmt }}</strong></td>
                  <td><strong>{{ g.end     | fmt }}</strong></td>
                  <td></td>
                </tr>

                <!-- строки‑детали -->
                <tr v-for="(r,i) in g.rows"
                    :key="g.cashbox + i"
                    class="detail-row">
                  <td class="pl20">{{ r.element }}</td>
                  <td></td>
                  <td>{{ r.income  | fmt }}</td>
                  <td>{{ r.expense | fmt }}</td>
                  <td></td>
                  <td>{{ r.date }}</td>
                </tr>
              </tbody>

              <tfoot v-if="groups.length">
                <tr>
                  <td><strong>Итого</strong></td>
                  <td><strong>{{ total.start   | fmt }}</strong></td>
                  <td><strong>{{ total.income  | fmt }}</strong></td>
                  <td><strong>{{ total.expense | fmt }}</strong></td>
                  <td><strong>{{ total.end     | fmt }}</strong></td>
                  <td></td>
                </tr>
              </tfoot>

              <tbody v-if="!groups.length">
                <tr><td colspan="6">Нет данных</td></tr>
              </tbody>
            </table>

            <!-- export buttons -->
            <div class="export-buttons">
              <button class="export-btn pdf-btn"   @click="exportToPdf">Экспорт PDF</button>
              <button class="export-btn excel-btn" @click="exportToExcel">Экспорт Excel</button>
            </div>
          </div>
        </main>
      </div>
    </div>
  </template>

  <script>
  import axios from "axios";

  export default {
    name: "CashFlowReportPage",

    data() {
      return {
        loading: false,
        error:   "",
        orders:  [],   // сырой ответ API
        groups:  [],   // сгруппированные данные

        /* фильтры */
        filters: {
          dateFrom: "",
          dateTo:   "",
          cashbox:  "",
          element:  ""   // <-- новый
        }
      };
    },

    created() { this.fetchOrders(); },

    /* простой формат‑фильтр для чисел */
    filters: {
      fmt(v) { return Number(v || 0).toLocaleString(); }
    },

    computed: {
      /* суммарные итоги */
      total() {
        return this.groups.reduce((a,g)=>({
          start:   a.start   + g.start,
          income:  a.income  + g.income,
          expense: a.expense + g.expense,
          end:     a.end     + g.end
        }), {start:0,income:0,expense:0,end:0});
      }
    },

    methods: {
      async fetchOrders() {
        this.loading = true;
        try {
          const token = localStorage.getItem("token");
          if (!token) { this.error = "Токен не найден."; return; }

          const params = {};
          if (this.filters.dateFrom) params.date_from = this.filters.dateFrom;
          if (this.filters.dateTo)   params.date_to   = this.filters.dateTo;
          if (this.filters.cashbox)  params.cashbox   = this.filters.cashbox;
          if (this.filters.element)  params.element   = this.filters.element; // новый

          const { data } = await axios.get("/api/financial-order", {
            headers:{ Authorization:`Bearer ${token}` },
            params
          });

          this.orders = data || [];
          this.buildGroups();
        } catch (e) {
          this.error = "Ошибка: " + e.message;
        } finally { this.loading = false; }
      },

      /* группируем "касса → строки" */
      buildGroups() {
        const map = {};
        this.orders.forEach(o => {
          const key = o.admin_cash ? o.admin_cash.name : "—";
          if (!map[key]) map[key] = {
            cashbox: key, rows: [],
            start:0, income:0, expense:0, end:0
          };

          const inc = o.type === "income"  ? (+o.summary_cash || 0) : 0;
          const out = o.type === "expense" ? (+o.summary_cash || 0) : 0;

          map[key].income  += inc;
          map[key].expense += out;
          map[key].end      = map[key].start + map[key].income - map[key].expense;

          map[key].rows.push({
            element: o.financial_element ? o.financial_element.name : "Нет данных",
            income: inc, expense: out, date: o.date_of_check
          });
        });

        this.groups = Object.values(map);
      },

      /* экспорт‑заглушки */
      exportToExcel() { alert("Экспорт в Excel пока не реализован."); },
      exportToPdf()   { alert("Экспорт в PDF пока не реализован."); }
    }
  };
  </script>

  <style scoped>
  /* базовая раскладка и предыдущие стили */
  .dashboard-container { display:flex; flex-direction:column; min-height:100vh; background:#f5f5f5; }
  .main-content { flex:1; }
  .content      { padding:20px; }
  .page-title   { text-align:center; color:#0288d1; margin-bottom:20px; }

  .report-table { width:100%; border-collapse:collapse; margin-bottom:20px; background:#fff; }
  .report-table th, .report-table td { padding:8px 6px; border:1px solid #ddd; text-align:right; }
  .report-table thead { background:#0288d1; color:#fff; text-align:center; }
  .report-table td:first-child,
  .report-table th:first-child { text-align:left; }

  .export-buttons { display:flex; justify-content:flex-end; gap:10px; }
  .export-btn { padding:8px 16px; border:0; border-radius:4px; color:#fff; cursor:pointer; }
  .pdf-btn   { background:#d32f2f; }
  .excel-btn { background:#388e3c; }

  .filters { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:16px; align-items:flex-end; }
  .apply-btn { padding:6px 14px; background:#0288d1; color:#fff; border:0; border-radius:4px; cursor:pointer; }
  .apply-btn:hover { background:#0277bd; }

  .group-row  td { background:#f9f9f9; font-weight:600; }
  .detail-row td { background:#fff; }
  .pl20 { padding-left:20px; }
  </style>
