<!-- resources/js/views/reports/MovementReport.vue -->
<template>
  <div class="report-container">
    <h1>Отчёт по движению товаров</h1>

    <!-- ▸ Фильтры --------------------------------------------------------- -->
    <div class="filters">
      <label>Дата от:</label>
      <input type="date" v-model="dateFrom">

      <label>Дата до:</label>
      <input type="date" v-model="dateTo">

      <button @click="getReport">Сформировать</button>
    </div>

    <!-- ▸ Таблица --------------------------------------------------------- -->
    <table class="report-table" v-if="reportRows.length">
      <thead>
        <tr>
          <th>Наименование</th>
          <th>Ед.</th>
          <th>Нач. остаток</th>
          <th>Приход</th>
          <th>Расход</th>
          <th>Конеч. остаток</th>
          <th>Себестоимость</th>
          <th>Сумма остатка</th>
        </tr>
      </thead>

      <!-- один <tbody> на склад ------------------------------------------ -->
      <tbody v-for="wh in groupedByWarehouse" :key="wh.warehouse_id">
        <!-- строка-итог по складу -->
        <tr class="warehouse-row">
          <td colspan="2"><strong>{{ wh.warehouse_name }}</strong></td>
          <td><strong>{{ fmt(wh.sumOpening) }}</strong></td>
          <td><strong>{{ fmt(wh.sumInbound) }}</strong></td>
          <td><strong>{{ fmt(wh.sumOutbound) }}</strong></td>
          <td><strong>{{ fmt(wh.sumClosing) }}</strong></td>
          <td></td>
          <td><strong>{{ fmt(wh.sumValue) }}</strong></td>
        </tr>

        <!-- товары склада -->
        <tr v-for="p in wh.products" :key="p.product_id + p.unit">
          <td style="padding-left:2em;">{{ p.product_name }}</td>
          <td>{{ p.unit }}</td>
          <td>{{ fmt(p.opening) }}</td>
          <td>{{ fmt(p.total_inbound) }}</td>
          <td>{{ fmt(p.total_outbound) }}</td>
          <td>{{ fmt(p.remainder) }}</td>
          <td>{{ fmt(p.cost_price) }}</td>
          <td>{{ fmt(p.remainder_value) }}</td>
        </tr>
      </tbody>

      <!-- общий итог ------------------------------------------------------ -->
      <tfoot>
        <tr>
          <td colspan="2"><strong>Итого по выборке:</strong></td>
          <td><strong>{{ fmt(totalOpening) }}</strong></td>
          <td><strong>{{ fmt(totalInbound) }}</strong></td>
          <td><strong>{{ fmt(totalOutbound) }}</strong></td>
          <td><strong>{{ fmt(totalClosing) }}</strong></td>
          <td></td>
          <td><strong>{{ fmt(totalValue) }}</strong></td>
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
      dateFrom: '',
      dateTo:   '',
      /** плоский массив строк API */
      reportRows: [],
    };
  },

  computed: {
    /* ▸ группировка по складам ---------------------------------------- */
    groupedByWarehouse() {
      const map = {};

      this.reportRows.forEach(r => {
        const id = r.warehouse_id;
        if (!map[id]) {
          map[id] = {
            warehouse_id:   id,
            warehouse_name: r.warehouse_name,
            sumOpening: 0,
            sumInbound: 0,
            sumOutbound: 0,
            sumClosing: 0,
            sumValue: 0,
            products: [],
          };
        }

        map[id].sumOpening  += +r.opening;
        map[id].sumInbound  += +r.total_inbound;
        map[id].sumOutbound += +r.total_outbound;
        map[id].sumClosing  += +r.remainder;
        map[id].sumValue    += +r.remainder_value;

        map[id].products.push(r);
      });

      return Object.values(map);
    },

    /* ▸ общие итоги ---------------------------------------------------- */
    totalOpening()  { return this.sumField('opening'); },
    totalInbound()  { return this.sumField('total_inbound'); },
    totalOutbound() { return this.sumField('total_outbound'); },
    totalClosing()  { return this.sumField('remainder'); },
    totalValue()    { return this.sumField('remainder_value'); },
  },

  methods: {
    /* API ---------------------------------------------------------------- */
    async getReport() {
      try {
        const { data } = await axios.get('/api/storage-report', {
          params: {
            date_from: this.dateFrom || null,
            date_to:   this.dateTo   || null,
          },
        });

        /* превращаем rows[warehouse].products[] → плоский массив */
        this.reportRows = data.rows.flatMap(wh =>
          wh.products.map(p => ({
            ...p,
            warehouse_id:   wh.warehouse_id,
            warehouse_name: wh.warehouse_name,
          })),
        );
      } catch (e) {
        console.error(e);
        alert('Не удалось получить отчёт');
      }
    },

    /* суммируем поле по всем строкам */
    sumField(field) {
      return this.reportRows.reduce((sum, r) => sum + +r[field], 0);
    },

    /* формат: 1 знак после запятой, пробелы как разделитель тысяч */
    fmt(value) {
      const num = Number(value || 0);
      return num.toLocaleString('ru-RU', {
        minimumFractionDigits: 1,
        maximumFractionDigits: 1,
      });
    },
  },
};
</script>

<style scoped>
.report-container { max-width: 900px; margin: 30px auto; font-family: sans-serif; }

.filters { margin-bottom: 20px; display: flex; gap: 1rem; align-items: center; }
button { padding: 6px 14px; cursor: pointer; border: 1px solid #ccc; background: #f4f4f4; transition: .2s; }
button:hover { background: #e2e2e2; }

.report-table { width: 100%; border-collapse: collapse; margin-top: 10px; background: #fff; }
.report-table thead { background: #0288d1; color: #fff; }
.report-table th,
.report-table td { border: 1px solid #ddd; padding: 8px 6px; text-align: right; }
.report-table th:first-child,
.report-table th:nth-child(2),
.report-table td:first-child,
.report-table td:nth-child(2) { text-align: left; }

.warehouse-row td { background: #f5f5f5; font-weight: 600; }
.report-table tbody tr:not(.warehouse-row) td:first-child { padding-left: 20px; }
.report-table tfoot td { background: #fafafa; font-weight: 600; }
</style>
