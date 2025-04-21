<template>
  <div class="report-container">
    <h1>Отчёт по движению товаров</h1>

    <!-- Фильтры: даты -->
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
          <!-- Один столбец "Наименование" (склад/товар) -->
          <th>Наименование</th>
          <th>Приход</th>
          <th>Расход</th>
          <th>Остаток</th>
          <th>Себестоимость</th>
          <th>Сумма остатка</th>
        </tr>
      </thead>

      <!-- Для КАЖДОГО склада - свой <tbody> -->
      <tbody v-for="(warehouseGroup, wIndex) in groupedByWarehouse" :key="wIndex">
        <!-- Строка склада с итогами -->
        <tr class="warehouse-row">
          <td><strong>{{ warehouseGroup.warehouse_name }}</strong></td>
          <td><strong>{{ warehouseGroup.sumInbound }}</strong></td>
          <td><strong>{{ warehouseGroup.sumOutbound }}</strong></td>
          <td><strong>{{ warehouseGroup.sumRemainder }}</strong></td>
          <!-- Себестоимость для всего склада обычно не показывают, оставим пустую ячейку -->
          <td></td>
          <td><strong>{{ warehouseGroup.sumRemainderValue }}</strong></td>
        </tr>

        <!-- Товары этого склада -->
        <tr v-for="(product, pIndex) in warehouseGroup.products" :key="pIndex">
          <!-- Отступ в названии товара, чтобы визуально показать иерархию -->
          <td style="padding-left: 2em;">{{ product.product_name }}</td>
          <td>{{ product.total_inbound || 0 }}</td>
          <td>{{ product.total_outbound || 0 }}</td>
          <td>{{ product.remainder || 0 }}</td>
          <td>{{ product.cost_price || 0 }}</td>
          <td>{{ product.remainder_value || 0 }}</td>
        </tr>
      </tbody>

      <!-- Наконец, общий итог по всем складам -->
      <tfoot>
        <tr>
          <!-- colspan="1" + ещё 4 столбца = итого 5 ячеек, шестая под "Сумма остатка" -->
          <td><strong>Итого по выборке:</strong></td>
          <td><strong>{{ sumInbound }}</strong></td>
          <td><strong>{{ sumOutbound }}</strong></td>
          <td><strong>{{ sumRemainder }}</strong></td>
          <td></td>
          <td><strong>{{ sumRemainderValue }}</strong></td>
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
      reportData: []
    };
  },
  computed: {
    /**
     * Группировка reportData по складам:
     * [
     *   {
     *     warehouse_id,
     *     warehouse_name,
     *     sumInbound, sumOutbound, sumRemainder, sumRemainderValue,
     *     products: [ { product_name, total_inbound... }, ... ]
     *   },
     *   ...
     * ]
     */
    groupedByWarehouse() {
      const map = {};

      this.reportData.forEach((row) => {
        const wId = row.warehouse_id;
        if (!map[wId]) {
          map[wId] = {
            warehouse_id: wId,
            warehouse_name: row.warehouse_name,
            sumInbound: 0,
            sumOutbound: 0,
            sumRemainder: 0,
            sumRemainderValue: 0,
            products: []
          };
        }

        // Накапливаем суммы по складу
        map[wId].sumInbound        += Number(row.total_inbound || 0);
        map[wId].sumOutbound       += Number(row.total_outbound || 0);
        map[wId].sumRemainder      += Number(row.remainder || 0);
        map[wId].sumRemainderValue += Number(row.remainder_value || 0);

        // Сам товар добавляем в products
        map[wId].products.push(row);
      });

      // Превращаем объект в массив
      return Object.values(map);
    },

    // Общие итоги (по всем складам)
    sumInbound() {
      return this.reportData.reduce(
        (acc, row) => acc + Number(row.total_inbound || 0), 0
      );
    },
    sumOutbound() {
      return this.reportData.reduce(
        (acc, row) => acc + Number(row.total_outbound || 0), 0
      );
    },
    sumRemainder() {
      return this.reportData.reduce(
        (acc, row) => acc + Number(row.remainder || 0), 0
      );
    },
    sumRemainderValue() {
      return this.reportData.reduce(
        (acc, row) => acc + Number(row.remainder_value || 0), 0
      );
    }
  },
  methods: {
    async getReport() {
      try {
        // Запрос к вашему API (Laravel-контроллер), где фильтруется по датам
        const response = await axios.get('/api/storage-report', {
          params: {
            date_from: this.dateFrom,
            date_to: this.dateTo
          }
        });
        // Присваиваем результат
        this.reportData = response.data;
      } catch (error) {
        console.error('Ошибка при получении отчёта', error);
        alert('Не удалось получить данные отчёта');
      }
    }
  }
};
</script>
<!-- <template> и <script> — без изменений -->

    <style scoped>
    /* контейнер и фильтры — прежние */
    .report-container { max-width:900px; margin:30px auto; font-family:sans-serif; }
    .filters { margin-bottom:20px; display:flex; gap:1rem; align-items:center; }
    button  { padding:6px 14px; cursor:pointer; border:1px solid #ccc; background:#f4f4f4;
              transition:background-color .2s; }
    button:hover { background:#e2e2e2; }

    /* ===== таблица в стиле отчёта по кассе ===== */
    .report-table { width:100%; border-collapse:collapse; margin-top:10px; background:#fff; }

    /* шапка — синяя, текст белый */
    .report-table thead { background:#0288d1; color:#fff; }
    .report-table th, .report-table td { border:1px solid #ddd; padding:8px 6px; text-align:right; }
    .report-table th:first-child,
    .report-table td:first-child     { text-align:left; }

    /* строка‑группа (склад) */
    .warehouse-row td { background:#f5f5f5; font-weight:600; }

    /* строки‑товары */
    .report-table tbody tr:not(.warehouse-row) td { background:#fff; }
    .report-table tbody tr:not(.warehouse-row) td:first-child { padding-left:20px; }

    /* подвал */
    .report-table tfoot td { background:#fafafa; font-weight:600; }
    </style>

