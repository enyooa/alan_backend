<template>
    <div class="report-container">
      <h1>Отчёт по движению товаров</h1>

      <!-- Фильтры дат -->
      <div class="filters">
        <label>Дата от:</label>
        <input type="date" v-model="dateFrom" />

        <label>Дата до:</label>
        <input type="date" v-model="dateTo" />

        <button @click="getReport">Сформировать</button>
      </div>

      <!-- Таблица -->
      <table class="report-table">
        <thead>
          <tr>
            <th>Наименование</th>
            <th>Нач. остаток</th>
            <th>Приход</th>
            <th>Расход</th>
            <th>Конеч. остаток</th>
            <th>Себестоимость</th>
            <th>Сумма остатка</th>
          </tr>
        </thead>

        <!-- tbody на каждый склад -->
        <tbody v-for="(wh, wIdx) in groupedByWarehouse" :key="wIdx">
          <!-- строка-итог по складу -->
          <tr class="warehouse-row">
            <td><strong>{{ wh.warehouse_name }}</strong></td>
            <td><strong>{{ wh.sumOpening }}</strong></td>
            <td><strong>{{ wh.sumInbound }}</strong></td>
            <td><strong>{{ wh.sumOutbound }}</strong></td>
            <td><strong>{{ wh.sumClosing }}</strong></td>
            <td></td>
            <td><strong>{{ wh.sumValue }}</strong></td>
          </tr>

          <!-- товары склада -->
          <tr v-for="(p, pIdx) in wh.products" :key="pIdx">
            <td style="padding-left:2em;">{{ p.product_name }}</td>
            <td>{{ p.opening || 0 }}</td>
            <td>{{ p.total_inbound || 0 }}</td>
            <td>{{ p.total_outbound || 0 }}</td>
            <td>{{ p.remainder || 0 }}</td>
            <td>{{ p.cost_price || 0 }}</td>
            <td>{{ p.remainder_value || 0 }}</td>
          </tr>
        </tbody>

        <!-- общий итог -->
        <tfoot>
          <tr>
            <td><strong>Итого по выборке:</strong></td>
            <td><strong>{{ totalOpening }}</strong></td>
            <td><strong>{{ totalInbound }}</strong></td>
            <td><strong>{{ totalOutbound }}</strong></td>
            <td><strong>{{ totalClosing }}</strong></td>
            <td></td>
            <td><strong>{{ totalValue }}</strong></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'MovementReport',
    data() {
      return {
        dateFrom: null,
        dateTo: null,
        /** raw rows из API */
        reportRows: []
      };
    },
    computed: {
      /** группируем товары по складу */
      groupedByWarehouse() {
        const map = {};
        this.reportRows.forEach(r => {
          const id = r.warehouse_id;
          if (!map[id]) {
            map[id] = {
              warehouse_id: id,
              warehouse_name: r.warehouse_name,
              sumOpening: 0,
              sumInbound: 0,
              sumOutbound: 0,
              sumClosing: 0,
              sumValue: 0,
              products: []
            };
          }
          map[id].sumOpening  += Number(r.opening || 0);
          map[id].sumInbound  += Number(r.total_inbound || 0);
          map[id].sumOutbound += Number(r.total_outbound || 0);
          map[id].sumClosing  += Number(r.remainder || 0);
          map[id].sumValue    += Number(r.remainder_value || 0);

          map[id].products.push(r);
        });
        return Object.values(map);
      },

      /* общие итоги */
      totalOpening() {
        return this.reportRows.reduce((a, r) => a + Number(r.opening || 0), 0);
      },
      totalInbound() {
        return this.reportRows.reduce((a, r) => a + Number(r.total_inbound || 0), 0);
      },
      totalOutbound() {
        return this.reportRows.reduce((a, r) => a + Number(r.total_outbound || 0), 0);
      },
      totalClosing() {
        return this.reportRows.reduce((a, r) => a + Number(r.remainder || 0), 0);
      },
      totalValue() {
        return this.reportRows.reduce((a, r) => a + Number(r.remainder_value || 0), 0);
      }
    },
    methods: {
      async getReport() {
        try {
          const { data } = await axios.get('/api/storage-report', {
            params: { date_from: this.dateFrom, date_to: this.dateTo }
          });
          /* API отдаёт rows ⟶ переводим в плоский массив товаров */
          this.reportRows = data.rows.flatMap(wh => wh.products.map(p => ({
            ...p,
            warehouse_id:   wh.warehouse_id,
            warehouse_name: wh.warehouse_name
          })));
        } catch (e) {
          console.error(e);
          alert('Не удалось получить отчёт');
        }
      }
    }
  };
  </script>

  <style scoped>
  .report-container { max-width:900px; margin:30px auto; font-family:sans-serif; }
  .filters { margin-bottom:20px; display:flex; gap:1rem; align-items:center; }
  button { padding:6px 14px; cursor:pointer; border:1px solid #ccc; background:#f4f4f4; transition:.2s; }
  button:hover { background:#e2e2e2; }

  .report-table { width:100%; border-collapse:collapse; margin-top:10px; background:#fff; }
  .report-table thead { background:#0288d1; color:#fff; }
  .report-table th, .report-table td { border:1px solid #ddd; padding:8px 6px; text-align:right; }
  .report-table th:first-child, .report-table td:first-child { text-align:left; }

  .warehouse-row td { background:#f5f5f5; font-weight:600; }

  .report-table tbody tr:not(.warehouse-row) td:first-child { padding-left:20px; }
  .report-table tfoot td { background:#fafafa; font-weight:600; }
  </style>
