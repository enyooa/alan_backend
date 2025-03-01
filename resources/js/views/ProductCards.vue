<template>
  <div class="dashboard-container">
    <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
    <div class="main-content">
      <Header />
      <main class="content">
        <h2 class="page-title">История операций</h2>

        <!-- CONTROLS ROW (Create dropdown, search, filter) -->
        <div class="form-controls-row">
          <!-- CREATE DROPDOWN -->
          <select
            v-model="selectedCreateType"
            class="create-select form-control"
            @change="onCreateTypeChange"
          >
            <option disabled value="">➕ Создать новую запись</option>
            <option value="productCard">Карточка товара</option>
            <option value="subproductCard">Подкарточка</option>
            <option value="provider">Поставщик</option>
            <option value="unit">Единица измерения</option>
            <option value="address">Адрес</option>
            <option value="expense">Расход</option>
          </select>

          <!-- SEARCH BOX -->
          <input
            v-model="searchQuery"
            type="text"
            class="search-box form-control"
            placeholder="🔍 Поиск..."
            @input="filterOperations"
          />

          <!-- FILTER DROPDOWN -->
          <select
            v-model="filterType"
            class="filter-select form-control"
            @change="filterOperations"
          >
            <option value="">Все</option>
            <option value="productCard">Карточка товара</option>
            <option value="subproductCard">Подкарточка</option>
            <option value="provider">Поставщик</option>
            <option value="unit">Единица измерения</option>
            <option value="address">Адрес</option>
            <option value="expense">Расход</option>
          </select>
        </div>

        <!-- TABLE OF HISTORY -->
        <div class="table-container">
          <table class="history-table">
            <thead>
              <tr>
                <th>Операция</th>
                <th>Дата</th>
                <th>Описание</th>
                <th>Действия</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(op, index) in filteredOperationsList"
                :key="`${op.type}-${op.id}`"
              >
                <td>{{ getFriendlyType(op.type) }}</td>
                <td>{{ formatDate(op.created_at) }}</td>
                <td>
                  <!-- Different fields by type -->
                  <span v-if="op.type === 'productCard'">
                    {{ op.description || "—" }}
                  </span>
                  <span v-else-if="op.type === 'subproductCard'">
                    {{ op.name || "—" }}
                  </span>
                  <span v-else-if="op.type === 'provider'">
                    {{ op.name || "—" }}
                  </span>
                  <span v-else-if="op.type === 'unit'">
                    {{ op.tare ? op.tare + " г/кг/л" : "—" }}
                  </span>
                  <span v-else-if="op.type === 'address'">
                    {{ op.name || "—" }}
                  </span>
                  <!-- If "expense", maybe show op.name or op.amount, up to you -->
                  <span v-else-if="op.type === 'expense'">
                    {{ op.name }} ({{ op.amount }})
                  </span>
                </td>
                <td>
                  <button class="edit-btn" @click="editOperation(op)">✏️</button>
                  <button
                    class="delete-btn"
                    @click="deleteOperation(op, index)"
                  >🗑</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- EDIT MODALS -->
        <div v-if="showModal.productCard" class="modal-overlay">
          <div class="modal-container">
            <ProductCardEdit
              :operation="operationToEdit"
              @close="closeAllModals"
              @saved="onOperationSaved"
            />
          </div>
        </div>

        <div v-if="showModal.subproductCard" class="modal-overlay">
          <div class="modal-container">
            <ProductSubCardEdit
              :operation="operationToEdit"
              @close="closeAllModals"
              @saved="onOperationSaved"
            />
          </div>
        </div>

        <div v-if="showModal.provider" class="modal-overlay">
          <div class="modal-container">
            <ProviderEdit
              :operation="operationToEdit"
              @close="closeAllModals"
              @saved="onOperationSaved"
            />
          </div>
        </div>

        <div v-if="showModal.unit" class="modal-overlay">
          <div class="modal-container">
            <UnitEdit
              :operation="operationToEdit"
              @close="closeAllModals"
              @saved="onOperationSaved"
            />
          </div>
        </div>

        <div v-if="showModal.address" class="modal-overlay">
          <div class="modal-container">
            <AddressEdit
              :operation="operationToEdit"
              @close="closeAllModals"
              @saved="onOperationSaved"
            />
          </div>
        </div>

        <!-- NEW: ExpenseEdit Modal -->
        <div v-if="showModal.expense" class="modal-overlay">
          <div class="modal-container">
            <ExpenseEdit
              :operation="operationToEdit"
              @close="closeAllModals"
              @saved="onOperationSaved"
            />
          </div>
        </div>

        <!-- CREATE MODALS -->
        <div v-if="createModal.productCard" class="modal-overlay">
          <div class="modal-container">
            <ProductCardPage
              @close="closeCreateModal"
              @saved="onNewRecordSaved"
            />
          </div>
        </div>

        <div v-if="createModal.subproductCard" class="modal-overlay">
          <div class="modal-container">
            <ProductSubCardPage
              @close="closeCreateModal"
              @saved="onNewRecordSaved"
            />
          </div>
        </div>

        <div v-if="createModal.provider" class="modal-overlay">
          <div class="modal-container">
            <ProviderPage
              @close="closeCreateModal"
              @saved="onNewRecordSaved"
            />
          </div>
        </div>

        <div v-if="createModal.unit" class="modal-overlay">
          <div class="modal-container">
            <UnitFormPage
              @close="closeCreateModal"
              @saved="onNewRecordSaved"
            />
          </div>
        </div>

        <div v-if="createModal.address" class="modal-overlay">
          <div class="modal-container">
            <AddressPage
              @close="closeCreateModal"
              @saved="onNewRecordSaved"
            />
          </div>
        </div>

        <!-- NEW: ExpenseFormPage Modal -->
        <div v-if="createModal.expense" class="modal-overlay">
          <div class="modal-container">
            <ExpenseFormPage
              @close="closeCreateModal"
              @saved="onNewRecordSaved"
            />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue";

// Edit components
import ProductCardEdit from "./forms/references/ProductCardEdit.vue";
import ProductSubCardEdit from "./forms/references/ProductSubCardEdit.vue";
import ProviderEdit from "./forms/references/ProviderEdit.vue";
import UnitEdit from "./forms/references/UnitEdit.vue";
import AddressEdit from "./forms/references/AddressEdit.vue";
import ExpenseEdit from "./forms/references/ExpenseEdit.vue";

// Create components
import ProductCardPage from "./forms/references/ProductCardPage.vue";
import ProductSubCardPage from "./forms/references/ProductSubCardPage.vue";
import ProviderPage from "./forms/references/ProviderPage.vue";
import UnitFormPage from "./forms/references/UnitFormPage.vue";
import AddressPage from "./forms/references/AddressPage.vue";
import ExpenseFormPage from "./forms/references/ExpensePage.vue";

import axios from "axios";

export default {
  name: "HistoryOperations",
  components: {
    Sidebar,
    Header,
    // Edit
    ProductCardEdit,
    ProductSubCardEdit,
    ProviderEdit,
    UnitEdit,
    AddressEdit,
    ExpenseEdit,
    // Create
    ProductCardPage,
    ProductSubCardPage,
    ProviderPage,
    UnitFormPage,
    AddressPage,
    ExpenseFormPage,
  },
  data() {
    return {
      isSidebarOpen: true,
      allOperations: [],
      filteredOperationsList: [],
      searchQuery: "",
      filterType: "",
      operationToEdit: null,
      showModal: {
        productCard: false,
        subproductCard: false,
        provider: false,
        unit: false,
        address: false,
        expense: false, // NEW
      },
      createModal: {
        productCard: false,
        subproductCard: false,
        provider: false,
        unit: false,
        address: false,
        expense: false, // NEW
      },
      selectedCreateType: "",
    };
  },
  created() {
    this.fetchOperationHistory();
  },
  methods: {
    toggleSidebar() {
      this.isSidebarOpen = !this.isSidebarOpen;
    },

    // 1) Fetch references: productCard, subproductCard, provider, unit, address, expense
    async fetchOperationHistory() {
      try {
        const token = localStorage.getItem("token");
        if (!token) {
          alert("Отсутствует токен. Пожалуйста, войдите в систему.");
          this.$router.push("/login");
          return;
        }

        const types = [
          "productCard",
          "subproductCard",
          "provider",
          "unit",
          "address",
          "expense" // fetch from /api/references/expense
        ];
        const requests = types.map((type) =>
          axios
            .get(`/api/references/${type}`, {
              headers: { Authorization: `Bearer ${token}` },
            })
            .then((response) =>
              response.data.map((item) => ({ ...item, type }))
            )
        );
        const results = await Promise.all(requests);
        this.allOperations = results.flat();
        this.filterOperations();
      } catch (error) {
        console.error("Ошибка загрузки данных:", error);
      }
    },

    filterOperations() {
      let res = [...this.allOperations];
      const q = this.searchQuery.toLowerCase();

      // search by type
      if (q) {
        res = res.filter((op) => {
          let searchField = "";
          if (op.type === "productCard") {
            searchField = op.name_of_products || "";
          } else if (
            op.type === "subproductCard" ||
            op.type === "provider" ||
            op.type === "address"
          ) {
            searchField = op.name || "";
          } else if (op.type === "unit") {
            searchField = op.tare ? op.tare.toString() : "";
          } else if (op.type === "expense") {
            searchField = op.name || "";
          }
          return searchField.toLowerCase().includes(q);
        });
      }

      // filter by type
      if (this.filterType) {
        res = res.filter((op) => op.type === this.filterType);
      }

      this.filteredOperationsList = res;
    },

    formatDate(d) {
      return d ? new Date(d).toLocaleDateString() : "";
    },

    getFriendlyType(type) {
      switch (type) {
        case "productCard":
          return "Карточка товара";
        case "subproductCard":
          return "Подкарточка";
        case "provider":
          return "Поставщик";
        case "unit":
          return "Единица измерения";
        case "address":
          return "Адрес";
        case "expense":
          return "Расход";
        default:
          return "Неизвестно";
      }
    },

    // 2) Edit existing record
    editOperation(op) {
      this.operationToEdit = { ...op };
      this.closeAllModalsWithoutReset();

      if (op.type === "productCard") {
        this.showModal.productCard = true;
      } else if (op.type === "subproductCard") {
        this.showModal.subproductCard = true;
      } else if (op.type === "provider") {
        this.showModal.provider = true;
      } else if (op.type === "unit") {
        this.showModal.unit = true;
      } else if (op.type === "address") {
        this.showModal.address = true;
      } else if (op.type === "expense") {
        this.showModal.expense = true; // open the expense edit modal
      }
    },

    // 3) Delete record
    async deleteOperation(op, idx) {
      if (!confirm(`Удалить операцию ${op.id}?`)) return;

      try {
        const token = localStorage.getItem("token");
        const endpoint = `/api/references/${op.type}/${op.id}`;
        await axios.delete(endpoint, {
          headers: { Authorization: `Bearer ${token}` },
        });

        // Remove from local arrays
        this.filteredOperationsList.splice(idx, 1);
        const realIndex = this.allOperations.findIndex((o) => o.id === op.id);
        if (realIndex >= 0) this.allOperations.splice(realIndex, 1);
      } catch (err) {
        console.error("Ошибка при удалении:", err);
        alert("Не удалось удалить операцию.");
      }
    },

    closeAllModalsWithoutReset() {
      Object.keys(this.showModal).forEach((key) => {
        this.showModal[key] = false;
      });
    },

    closeAllModals() {
      this.closeAllModalsWithoutReset();
      this.operationToEdit = null;
    },

    // When an operation is saved (from the edit modals)
    onOperationSaved(updatedRecord) {
      if (this.operationToEdit) {
        // Merge old + new data
        const mergedRecord = {
          ...this.operationToEdit,
          ...updatedRecord,
        };

        // Replace in local arrays
        this.allOperations = this.allOperations.map((op) =>
          op.id === this.operationToEdit.id && op.type === this.operationToEdit.type
            ? mergedRecord
            : op
        );
      }
      this.filterOperations();
      this.closeAllModals();
      this.fetchOperationHistory();
    },

    // For newly created records
    onNewRecordSaved(newRecord) {
      this.fetchOperationHistory();
      this.closeCreateModal();
    },

    // ========== CREATE LOGIC ==========

    onCreateTypeChange() {
      // open create modals
      this.resetCreateModals();
      if (this.selectedCreateType === "productCard") {
        this.createModal.productCard = true;
      } else if (this.selectedCreateType === "subproductCard") {
        this.createModal.subproductCard = true;
      } else if (this.selectedCreateType === "provider") {
        this.createModal.provider = true;
      } else if (this.selectedCreateType === "unit") {
        this.createModal.unit = true;
      } else if (this.selectedCreateType === "address") {
        this.createModal.address = true;
      } else if (this.selectedCreateType === "expense") {
        this.createModal.expense = true;
      }

      this.selectedCreateType = "";
    },

    resetCreateModals() {
      Object.keys(this.createModal).forEach((key) => {
        this.createModal[key] = false;
      });
    },

    closeCreateModal() {
      this.resetCreateModals();
    },
  },
  watch: {
    searchQuery() {
      this.filterOperations();
    },
    filterType() {
      this.filterOperations();
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
}
.content {
  padding: 20px;
}
.page-title {
  text-align: center;
  color: #0288d1;
  margin-bottom: 20px;
}

/* FORM CONTROLS ROW */
.form-controls-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 20px;
}

/* Common form control style */
.form-control {
  height: 40px;
  padding: 10px;
  font-size: 14px;
  border: 1px solid #ddd;
  border-radius: 5px;
  box-sizing: border-box;
}

/* Specific widths */
.create-select,
.filter-select {
  width: 200px; /* Fixed width for dropdowns */
}
.search-box {
  flex: 1; /* Search box fills remaining space */
}

/* TABLE STYLES */
.table-container {
  overflow-x: auto;
  background-color: #fff;
  border-radius: 8px;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 20px;
}
.history-table {
  width: 100%;
  border-collapse: collapse;
}
.history-table th,
.history-table td {
  padding: 10px;
  border: 1px solid #ddd;
  text-align: center;
}
.history-table thead {
  background-color: #0288d1;
  color: white;
}

/* MODAL STYLES */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}
.modal-container {
  background: #fff;
  padding: 20px;
  border-radius: 12px;
  width: 90%;
  max-width: 700px;
}

/* BUTTONS */
.edit-btn,
.delete-btn,
.submit-btn,
.close-btn {
  height: 40px;
  padding: 10px 15px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  font-size: 14px;
  box-sizing: border-box;
}
.edit-btn {
  background-color: inherit;
}
.delete-btn {
  background-color: #f44336;
  color: #fff;
}
.submit-btn {
  background-color: #0288d1;
  color: #fff;
}
.submit-btn:hover {
  background-color: #026ca0;
}
.close-btn {
  background-color: #f44336;
  color: #fff;
  flex: 1;
}
</style>
