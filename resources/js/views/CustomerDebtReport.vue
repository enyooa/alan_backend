<template>
  <div class="dashboard-container">
    <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
    
    <div class="main-content">
      <Header />
      
      <main class="content">
        <div class="filters">
          <input type="date" v-model="dateFrom" class="filter-input" />
          <input type="date" v-model="dateTo" class="filter-input" />
          <button @click="fetchFinancialOrders" class="filter-btn">Фильтровать</button>
        </div>
        
        <table class="financial-table">
          <thead>
            <tr>
              <th>Дата</th>
              <th>Наименование</th>
              <th>Тип</th>
              <th>Сумма</th>
              <th>Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="order in financialOrders" :key="order.id">
              <td>{{ order.date_of_check }}</td>
              <td>{{ order.financial_element ? order.financial_element.name : 'Не указано' }}</td>
              <td :class="{'income': order.type === 'income', 'expense': order.type === 'expense'}">
                {{ order.type === 'income' ? 'Приход' : 'Расход' }}
              </td>
              <td>{{ order.summary_cash }}</td>
              <td>
                <button @click="deleteOrder(order.id)" class="delete-btn">Удалить</button>
              </td>
            </tr>
          </tbody>
        </table>

        <div class="actions">
          <button @click="exportToExcel" class="action-btn">Экспорт в Excel</button>
          <button @click="exportToPdf" class="action-btn">Экспорт в PDF</button>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue";
import axios from "axios";

export default {
  components: { Sidebar, Header },
  data() {
    return {
      financialOrders: [],
      dateFrom: "",
      dateTo: "",
    };
  },
  methods: {
    async fetchFinancialOrders() {
      try {
        const response = await axios.get("/api/financial-order", {
          headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
        });
        this.financialOrders = response.data;
      } catch (error) {
        console.error("Ошибка загрузки финансовых заказов", error);
      }
    },
    async deleteOrder(id) {
      if (confirm("Вы уверены, что хотите удалить запись?")) {
        try {
          await axios.delete(`/api/financial-order/${id}`, {
            headers: { Authorization: `Bearer ${localStorage.getItem("token")}` },
          });
          this.fetchFinancialOrders();
        } catch (error) {
          console.error("Ошибка при удалении заказа", error);
        }
      }
    },
    exportToExcel() {
      // Implement export to Excel logic
    },
    exportToPdf() {
      // Implement export to PDF logic
    }
  },
  mounted() {
    this.fetchFinancialOrders();
  }
};
</script>

<style scoped>
.dashboard-container {
  display: flex;
  min-height: 100vh;
}
.main-content {
  flex: 1;
  background-color: #f5f5f5;
}
.content {
  padding: 20px;
  text-align: center;
}
.filters {
  display: flex;
  justify-content: space-around;
  margin-bottom: 20px;
}
.filter-input {
  padding: 10px;
  border: 1px solid #ccc;
  border-radius: 5px;
}
.filter-btn {
  background-color: #0288d1;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
.financial-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}
.financial-table th, .financial-table td {
  padding: 10px;
  border: 1px solid #ddd;
  text-align: left;
}
.financial-table th {
  background-color: #0288d1;
  color: white;
}
.income {
  color: green;
  font-weight: bold;
}
.expense {
  color: red;
  font-weight: bold;
}
.delete-btn {
  background-color: red;
  color: white;
  padding: 5px 10px;
  border: none;
  cursor: pointer;
}
.actions {
  margin-top: 20px;
}
.action-btn {
  background-color: #0288d1;
  color: white;
  padding: 10px 20px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
</style>
