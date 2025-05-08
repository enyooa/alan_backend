<template>
    <div class="unified-page">
      <div class="filters">
        <label>Дата с:<input type="date" v-model="fromDate" /></label>
        <label>по:<input type="date" v-model="toDate" /></label>
        <button @click="fetchReport" class="filter-button">Сформировать</button>
      </div>

      <h2>Поступление + фин ордер + долги клиента</h2>
      <table class="modern-table">
        <thead>
          <tr>
            <th>Наименование</th>
            <th>Приход</th>
            <th>Расход</th>
            <th>Баланс</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, idx) in finalRows" :key="idx">
            <!-- GROUP ROWS => single label -->
            <template v-if="row.row_type === 'group'">
              <td><strong>{{ row.label }}</strong></td>
              <td>–</td>
              <td>–</td>
              <td>–</td>
            </template>

            <!-- PROVIDER ROW -->
            <template v-else-if="row.row_type === 'provider'">
              <td style="font-weight:bold;">
                {{ row.name }}
              </td>
              <td :style="{textAlign:'right'}">{{ formatMoney(row.incoming) }}</td>
              <td :style="{textAlign:'right'}">{{ formatMoney(row.outgoing) }}</td>
              <td
                :style="{
                  textAlign:'right',
                  color: row.balance < 0 ? 'red' : ''
                }"
              >
                {{ formatMoney(row.balance) }}
              </td>
            </template>

            <!-- DOC ROW -->
            <template v-else-if="row.row_type === 'doc'">
              <td style="padding-left:2em;">
                {{ row.name }}
              </td>
              <td :style="{textAlign:'right'}">{{ formatMoney(row.incoming) }}</td>
              <td :style="{textAlign:'right'}">{{ formatMoney(row.outgoing) }}</td>
              <td
                :style="{
                  textAlign:'right',
                  color: row.balance < 0 ? 'red' : ''
                }"
              >
                {{ formatMoney(row.balance) }}
              </td>
            </template>

            <!-- CLIENT ROW -->
            <template v-else-if="row.row_type === 'client'">
              <td style="font-weight:bold; color:#333;">
                {{ row.name }}
              </td>
              <td :style="{textAlign:'right'}">{{ formatMoney(row.incoming) }}</td>
              <td :style="{textAlign:'right'}">{{ formatMoney(row.outgoing) }}</td>
              <td
                :style="{
                  textAlign:'right',
                  color: row.balance < 0 ? 'red' : ''
                }"
              >
                {{ formatMoney(row.balance) }}
              </td>
            </template>

            <!-- ANY OTHER ROW_TYPE (optional) -->
            <template v-else>
              <td>{{ row.name }}</td>
              <td>{{ formatMoney(row.incoming) }}</td>
              <td>{{ formatMoney(row.outgoing) }}</td>
              <td>{{ formatMoney(row.balance) }}</td>
            </template>
          </tr>
        </tbody>
      </table>
    </div>
  </template>

  <script>
  import axios from "axios";

  export default {
    name: "UnifiedDebtsPage",
    data() {
      return {
        fromDate: "",
        toDate: "",
        finalRows: []
      };
    },
    methods: {
      async fetchReport() {
        try {
          const resp = await axios.get("/api/admin-report-debts", {
            params: {
              date_from: this.fromDate,
              date_to: this.toDate
            }
          });
          this.finalRows = resp.data;
        } catch (err) {
          console.error("Error fetching unified report:", err);
          alert("Не удалось загрузить отчет");
        }
      },
      formatMoney(val) {
        if (!val) return "0.00";
        return Number(val).toLocaleString("ru-RU", {
          minimumFractionDigits: 2,
          maximumFractionDigits: 2
        });
      }
    },
    created() {
      // optional: fetch immediately
      this.fetchReport();
    }
  };
  </script>

  <style scoped>
  .unified-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
  }
  .filters {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    align-items: center;
  }
  .filter-button {
    background-color: #0288d1;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
  }
  .filter-button:hover {
    background-color: #0277bd;
  }
  .modern-table {
    width: 100%;
    border-collapse: collapse;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  }
  .modern-table th, .modern-table td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
  }
  .modern-table thead {
    background-color: #0288d1;
    color: #fff;
  }
  </style>

  <style scoped>
  .operation-page {
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
  }

  /* Filters row + button */
  .filters {
    display: flex;
    gap: 16px;
    margin-bottom: 16px;
    align-items: center;
  }
  .filter-button {
    background-color: #0288d1;
    color: #fff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    cursor: pointer;
  }
  .filter-button:hover {
    background-color: #0277bd;
  }

  /* Modern table style */
  .modern-table {
    width: 100%;
    border-collapse: collapse;
    box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    margin-bottom: 24px;
  }
  .modern-table thead {
    background-color: #0288d1;
    color: #fff;
  }
  .modern-table th,
  .modern-table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: left;
  }
  .modern-table tbody tr:hover {
    background-color: #f6f6f6;
  }

  /* For row coloring if you like */
  .provider {
    background-color: #f2f2f2;
    font-weight: bold;
  }
  .doc {
    font-weight: bold;
  }
  .expense {
    color: #666;
  }
  </style>
