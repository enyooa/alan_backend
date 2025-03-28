<template>
    <div class="sales-report-page">
      <h2>Отчет по продажам</h2>

      <!-- Filters -->
      <div class="filters">
        <label>Период c:</label>
        <input type="date" v-model="startDate" />
        <label>по:</label>
        <input type="date" v-model="endDate" />

        <button @click="fetchSalesReport">Сформировать</button>
        <button @click="exportToPdf">Выгрузить PDF</button>
        <button @click="exportToExcel">Выгрузить Excel</button>
      </div>

      <div v-if="loading" class="loading">
        Загрузка данных...
      </div>
      <div v-else-if="error" class="error">
        Ошибка: {{ error }}
      </div>
      <div v-else>
        <table class="report-table">
          <thead>
            <tr>
              <th>Товар</th>
              <th>Количество</th>
              <th>Сумма продаж</th>
              <th>Себестоимость</th>
              <th>Прибыль</th>
              <th>Дата документа</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, index) in salesData" :key="index">
              <td>{{ row.product_name }}</td>
              <td>{{ row.quantity }}</td>
              <td>{{ row.sale_amount }}</td>
              <td>{{ row.cost_amount }}</td>
              <td :class="{'negative-profit': row.profit < 0}">
                {{ row.profit }}
              </td>
              <td>{{ row.doc_date }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </template>

  <script>
  import axios from "axios";

  export default {
    name: "SalesReportPage",
    data() {
      return {
        salesData: [],
        startDate: null,
        endDate: null,
        loading: false,
        error: null,
      };
    },
    methods: {
      async fetchSalesReport() {
        this.loading = true;
        this.error = null;
        this.salesData = [];

        try {
          // Build query params if user selected dates
          const params = {};
          if (this.startDate && this.endDate) {
            params.start_date = this.startDate;
            params.end_date = this.endDate;
          }

          // Suppose your endpoint is /api/sales-report
          // If you need auth, pass token in headers
          const response = await axios.get("/api/sales-report", { params });
          this.salesData = response.data;
        } catch (err) {
          console.error("Error fetching sales data:", err);
          this.error = err.response?.data?.error || err.message;
        } finally {
          this.loading = false;
        }
      },
      exportToPdf() {
        // Open PDF export in new tab. Make a route /api/sales-report/pdf if needed
        const params = [];
        if (this.startDate && this.endDate) {
          params.push(`start_date=${this.startDate}`);
          params.push(`end_date=${this.endDate}`);
        }
        const queryString = params.length ? "?" + params.join("&") : "";
        window.open("/api/sales-report/pdf" + queryString, "_blank");
      },
      exportToExcel() {
        // Same idea, if you have an Excel export route
        const params = [];
        if (this.startDate && this.endDate) {
          params.push(`start_date=${this.startDate}`);
          params.push(`end_date=${this.endDate}`);
        }
        const queryString = params.length ? "?" + params.join("&") : "";
        window.open("/api/sales-report/excel" + queryString, "_blank");
      },
    },
  };
  </script>

  <style scoped>
  .sales-report-page {
    max-width: 1000px;
    margin: 0 auto;
  }

  .filters {
    margin-bottom: 16px;
  }

  .filters label {
    margin-right: 4px;
  }

  .filters input {
    margin-right: 8px;
  }

  .filters button {
    margin-right: 8px;
    padding: 6px 12px;
    background-color: #0288d1;
    color: white;
    border: none;
    cursor: pointer;
  }

  .filters button:hover {
    background-color: #0277bd;
  }

  .report-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
  }

  .report-table thead th {
    background-color: #0288d1;
    color: #fff;
  }

  .report-table th,
  .report-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
  }

  .negative-profit {
    color: red;
    font-weight: bold;
  }
  .error {
    color: red;
    margin-top: 10px;
  }
  </style>
