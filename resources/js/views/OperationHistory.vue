<template>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />

    <!-- Main Content -->
    <div class="main-content">
      <!-- Header -->
      <Header />

      <main class="content">
        <h2 class="page-title">История операций</h2>

        <!-- Search & Filter Row -->
        <div class="search-filter">
          <input
            v-model="searchQuery"
            type="text"
            class="search-box"
            placeholder="🔍 Поиск..."
            @input="searchOperations"
          />
          <select v-model="selectedFilter" class="filter-select" @change="filterByType">
            <option value="">Все</option>
            <option v-for="(label, key) in filterOptions" :key="key" :value="key">
              {{ label }}
            </option>
          </select>
        </div>

        <!-- Operations Table -->
        <div class="table-container">
          <table class="operations-table">
            <thead>
              <tr>
                <th>Операция</th>
                <th>Тип</th>
                <th>Дата</th>
                <th>Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="operation in filteredOperations" :key="operation.id">
                <td>{{ operation.operation }}</td>
                <td>{{ operation.type }}</td>
                <td>{{ formatDate(operation.created_at) }}</td>
                <td>
                  <button class="edit-btn" @click="editOperation(operation)">✏️</button>
                  <button class="delete-btn" @click="deleteOperation(operation)">🗑</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Edit Modal -->
        <div v-if="showEditModal" class="modal">
          <div class="modal-content">
            <h3>Редактировать {{ selectedOperation.type }}</h3>
            <input v-model="editedOperation.operation" type="text" class="form-input" />
            <button @click="updateOperation" class="save-btn">💾 Сохранить</button>
            <button @click="closeEditModal" class="close-btn">❌ Закрыть</button>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import axios from "axios";
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue";

export default {
  components: { Sidebar, Header },
  data() {
    return {
      isSidebarOpen: true,
      operations: [],
      filteredOperations: [],
      searchQuery: "",
      selectedFilter: "",
      showEditModal: false,
      selectedOperation: null,
      editedOperation: { operation: "" },
      filterOptions: {
        "Карточка товара": "Карточка товара",
        "Подкарточка товара": "Подкарточка товара",
        "Ценовое предложение": "Ценовое предложение",
        "Продажа": "Продажа",
        "Ед измерения": "Ед. изм.",
        "Присвоить роль": "Присвоить роль",
        "Присвоить адрес": "Присвоить адрес",
        "Перемещение в склад": "Перемещение в склад",
        "Поставщик": "Поставщик",
      },
    };
  },
  async created() {
    await this.fetchOperations();
  },
  methods: {
    async fetchOperations() {
      try {
        const token = localStorage.getItem("token");
        if (!token) {
          alert("❌ Ошибка: Отсутствует токен. Пожалуйста, войдите в систему.");
          this.$router.push("/login");
          return;
        }

        const response = await axios.get("/api/operations-history", {
          headers: { Authorization: `Bearer ${token}` },
        });

        this.operations = response.data;
        this.filteredOperations = response.data;
      } catch (error) {
        console.error("Ошибка загрузки операций:", error);
        alert("❌ Ошибка загрузки операций.");
      }
    },
    searchOperations() {
      this.filteredOperations = this.operations.filter((operation) =>
        operation.operation.toLowerCase().includes(this.searchQuery.toLowerCase())
      );
    },
    filterByType() {
      this.filteredOperations = this.selectedFilter
        ? this.operations.filter((operation) => operation.type === this.selectedFilter)
        : this.operations;
    },
    editOperation(operation) {
      this.selectedOperation = operation;
      this.editedOperation = { ...operation };
      this.showEditModal = true;
    },
    async updateOperation() {
      try {
        const token = localStorage.getItem("token");
        await axios.put(`/api/operations/${this.selectedOperation.id}/${this.selectedOperation.type}`, {
          operation: this.editedOperation.operation,
        }, {
          headers: { Authorization: `Bearer ${token}` },
        });

        alert("✅ Операция успешно обновлена!");
        this.showEditModal = false;
        await this.fetchOperations();
      } catch (error) {
        console.error("Ошибка обновления операции:", error);
        alert("❌ Ошибка обновления.");
      }
    },
    async deleteOperation(operation) {
      if (confirm(`❗ Удалить "${operation.operation}"?`)) {
        try {
          const token = localStorage.getItem("token");
          await axios.delete(`/api/operations/${operation.id}/${operation.type}`, {
            headers: { Authorization: `Bearer ${token}` },
          });

          alert("✅ Операция удалена!");
          await this.fetchOperations();
        } catch (error) {
          console.error("Ошибка удаления:", error);
          alert("❌ Ошибка при удалении.");
        }
      }
    },
    closeEditModal() {
      this.showEditModal = false;
      this.selectedOperation = null;
    },
    formatDate(date) {
      return new Date(date).toLocaleDateString();
    },
    toggleSidebar() {
      this.isSidebarOpen = !this.isSidebarOpen;
    },
  },
};
</script>

<style scoped>
/* Layout */
.dashboard-container {
  display: flex;
  min-height: 100vh;
}
.main-content {
  flex: 1;
  background-color: #f5f5f5;
  padding: 20px;
}

/* Title */
.page-title {
  text-align: center;
  color: #0288d1;
  margin-bottom: 20px;
}

/* Search & Filter */
.search-filter {
  display: flex;
  justify-content: space-between;
  margin-bottom: 20px;
}
.search-box {
  flex: 1;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 5px;
}
.filter-select {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 5px;
  margin-left: 10px;
}

/* Table */
.table-container {
  overflow-x: auto;
}
.operations-table {
  width: 100%;
  border-collapse: collapse;
}
.operations-table th,
.operations-table td {
  padding: 10px;
  border: 1px solid #ddd;
  text-align: center;
}
.operations-table th {
  background-color: #0288d1;
  color: white;
}

/* Buttons */
.edit-btn,
.delete-btn {
  padding: 8px;
  margin: 2px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}
.edit-btn {
  background-color: inherit;
}
.delete-btn {
  background-color: #f44336;
  color: white;
}

/* Modal */
.modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
  z-index: 1000;
}
</style>
