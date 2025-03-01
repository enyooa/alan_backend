<template>
  <div class="dashboard-container">
    <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
    <div class="main-content">
      <Header />

      <main class="content">
        <h2 class="page-title">Операции</h2>

        <!-- 1) CREATE DROPDOWN & MODAL -->
        <div class="dropdown-section">
          <label class="dropdown-label">Создать:</label>
          <select
            v-model="selectedOption"
            @change="openPopup"
            class="dropdown-select"
          >
            <!-- default option plus other entries -->
            <option
              v-for="option in productOptions"
              :key="option.value"
              :value="option.value"
            >
              {{ option.label }}
            </option>
          </select>
        </div>

        <!-- "Create" Modal -->
        <div v-if="showPopup" class="modal-overlay">
          <div class="modal-container">
            <button class="close-modal-btn" @click="closePopup">✖</button>
            <component
              :is="currentComponent"
              @close="closePopup"
              @saved="onNewRecordSaved"
            />
          </div>
        </div>

        <!-- 2) Search Field -->
        <div class="search-section">
          <input
            v-model="searchQuery"
            type="text"
            class="search-input"
            placeholder="Поиск..."
          />
        </div>

        <!-- 3) Unified Table: История операции -->
        <div class="history-section" style="margin-top: 30px;">
          <h3>История операции</h3>
          <div class="table-container">
            <table class="history-table">
              <thead>
                <tr>
                  <th>№</th>
                  <th>Тип</th>
                  <th>Дата</th>
                  <th>Кол-во</th>
                  <th>Цена</th>
                  <th>Сумма</th>
                  <th>Действия</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(item, idx) in filteredHistories"
                  :key="item.type + '_' + item.id"
                >
                  <td>{{ item.id }}</td>

                  <!-- Show friendly label for type -->
                  <td>
                    <span v-if="item.type === 'adminWarehouse'">
                      Приходование
                    </span>
                    <span v-else-if="item.type === 'sale'">Продажа</span>
                    <span v-else-if="item.type === 'priceOffer'">
                      Ценовое предложение
                    </span>
                    <span v-else>{{ item.type }}</span>
                  </td>

                  <td>{{ formatDate(item.date) }}</td>

                  <!-- If adminWarehouse => quantity; if sale => amount -->
                  <td>
                    <span v-if="item.type === 'adminWarehouse'">
                      {{ item.quantity }}
                    </span>
                    <span v-else-if="item.type === 'sale'">
                      {{ item.amount }}
                    </span>
                    <span v-else-if="item.type === 'priceOffer'">
                      {{ item.quantity || '-' }}
                    </span>
                  </td>

                  <td>{{ item.price }}</td>

                  <!-- If adminWarehouse => total_sum; if sale => totalsum -->
                  <td>
                    <span v-if="item.type === 'adminWarehouse'">
                      {{ item.total_sum }}
                    </span>
                    <span v-else-if="item.type === 'sale'">
                      {{ item.totalsum }}
                    </span>
                    <span v-else-if="item.type === 'priceOffer'">
                      {{ item.totalsum }}
                    </span>
                  </td>

                  <td>
                    <button class="edit-btn" @click="openEditModal(item)">
                      Ред.
                    </button>
                    <button
                      class="delete-btn"
                      @click="deleteRecord(item, idx)"
                    >
                      Удал.
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- 4) Dynamic Edit Modal -->
        <div v-if="showEditModal" class="modal-overlay">
          <div class="modal-container">
            <button class="close-modal-btn" @click="closeEditModal">✖</button>
            <component
              :is="currentEditComponent"
              :record="itemToEdit"
              @close="closeEditModal"
              @saved="onItemEdited"
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

// "Create" pages
import SalePage from "./SalePage.vue";
import PriceOfferPage from "./PriceOfferPage.vue";
import InventoryPage from "./InventoryPage.vue";
import ProductReceivingPage from "./ProductReceivingPage.vue";
import WriteOffPage from "./WriteOffPage.vue";
import InventoryCheckPage from "./InventoryCheckPage.vue";

// Edit components
import AdminWarehouseEdit from "./forms/products/EditReceivingModal.vue";
import SaleEditModal from "./forms/products/SaleEdit.vue";
import PriceOfferEdit from "./forms/products/PriceOfferEdit.vue";

import axios from "axios";

export default {
  name: "OperationsPage",
  components: {
    Sidebar,
    Header,
    // "Create" pages
    SalePage,
    PriceOfferPage,
    InventoryPage,
    ProductReceivingPage,
    WriteOffPage,
    InventoryCheckPage,
    // "Edit" modals
    AdminWarehouseEdit,
    SaleEditModal,
    PriceOfferEdit,
  },
  data() {
    return {
      isSidebarOpen: true,

      // CREATE dropdown
      selectedOption: "", // start empty
      productOptions: [
        { label: "Выберите...", value: "" }, // default empty
        { label: "Продажа", value: "sale" },
        { label: "Ценовое предложение", value: "priceOffer" },
        { label: "Склад", value: "inventory" },
        { label: "Поступление товара", value: "productReceiving" },
        { label: "Списание", value: "writeOff" },
        { label: "Инвентаризация", value: "inventoryCheck" },
      ],

      // Map from value -> actual component
      pageMap: {
        sale: "SalePage",
        priceOffer: "PriceOfferPage",
        inventory: "InventoryPage",
        productReceiving: "ProductReceivingPage",
        writeOff: "WriteOffPage",
        inventoryCheck: "InventoryCheckPage",
      },

      showPopup: false,

      // Merged Histories
      allHistories: [],
      searchQuery: "",

      // Edit modal
      showEditModal: false,
      itemToEdit: null,
    };
  },
  computed: {
    // If user picks a real value, load that component.
    // If it's "", we do nothing
    currentComponent() {
      if (!this.selectedOption) return null;
      return this.pageMap[this.selectedOption] || null;
    },
    filteredHistories() {
      const q = this.searchQuery.toLowerCase();
      if (!q) return this.allHistories;

      return this.allHistories.filter((item) => {
        const idStr = String(item.id).toLowerCase();
        let typeLabel = "";
        if (item.type === "adminWarehouse") {
          typeLabel = "приходование";
        } else if (item.type === "sale") {
          typeLabel = "продажа";
        } else if (item.type === "priceOffer") {
          typeLabel = "ценовое предложение";
        } else {
          typeLabel = item.type;
        }
        const dateStr = item.date
          ? new Date(item.date).toLocaleDateString()
          : "";
        const priceStr = item.price ? String(item.price) : "";

        return (
          idStr.includes(q) ||
          typeLabel.includes(q) ||
          dateStr.includes(q) ||
          priceStr.includes(q)
        );
      });
    },
    currentEditComponent() {
      if (!this.itemToEdit) return null;
      switch (this.itemToEdit.type) {
        case "adminWarehouse":
          return "AdminWarehouseEdit";
        case "sale":
          return "SaleEditModal";
        case "priceOffer":
          return "PriceOfferEdit";
        default:
          return "AdminWarehouseEdit"; // fallback
      }
    },
  },
  created() {
    this.fetchAllHistories();
  },
  methods: {
    toggleSidebar() {
      this.isSidebarOpen = !this.isSidebarOpen;
    },

    // CREATE logic
    openPopup() {
      // If user selected the default or no selection, do nothing
      if (!this.selectedOption) return;
      this.showPopup = true;
    },
    closePopup() {
      this.showPopup = false;
      // optional: reset selection so user sees "Выберите..." again
      // this.selectedOption = "";
    },
    onNewRecordSaved() {
      this.closePopup();
      this.fetchAllHistories();
    },

    // Fetch merged data
    async fetchAllHistories() {
      try {
        const { data } = await axios.get("/api/products/allHistories");
        this.allHistories = data;
      } catch (err) {
        console.error("Error fetching allHistories:", err);
      }
    },
    formatDate(d) {
      return d ? new Date(d).toLocaleDateString() : "";
    },

    // EDIT logic
    openEditModal(item) {
      this.itemToEdit = { ...item };
      this.showEditModal = true;
    },
    closeEditModal() {
      this.showEditModal = false;
      this.itemToEdit = null;
    },
    onItemEdited(updated) {
      this.closeEditModal();
      this.fetchAllHistories();
    },

    // DELETE logic
    async deleteRecord(item, index) {
      if (!confirm(`Удалить запись ID ${item.id}?`)) return;
      try {
        await axios.delete(`/api/products/${item.type}/${item.id}`);
        this.allHistories.splice(index, 1);
      } catch (err) {
        console.error("Delete error:", err);
        alert("Не удалось удалить запись.");
      }
    },
  },
};
</script>

<style scoped>
/* Basic layout */
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

/* "Create" dropdown styling */
.dropdown-section {
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 8px;
}
.dropdown-label {
  font-weight: bold;
}
.dropdown-select {
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 5px;
}

/* Search section */
.search-section {
  margin: 10px 0;
}
.search-input {
  width: 100%;
  max-width: 300px;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 5px;
  margin-bottom: 15px;
}

/* Table styling */
.history-section {
  margin-top: 30px;
}
.table-container {
  background-color: #fff;
  padding: 20px;
  border-radius: 8px;
  overflow-x: auto;
  box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
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
  color: #fff;
}

/* Buttons */
.edit-btn {
  background-color: #0288d1;
  color: #fff;
  border: none;
  border-radius: 5px;
  padding: 6px 10px;
  cursor: pointer;
  margin-right: 5px;
}
.edit-btn:hover {
  background-color: #026ca0;
}
.delete-btn {
  background-color: #f44336;
  color: #fff;
  border: none;
  border-radius: 5px;
  padding: 6px 10px;
  cursor: pointer;
}
.delete-btn:hover {
  background-color: #d32f2f;
}

/* Modal */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 999;
}
.modal-container {
  background: #fff;
  padding: 20px;
  border-radius: 10px;
  max-width: 90%;
  max-height: 90%;
  overflow-y: auto;
  position: relative;
}
.close-modal-btn {
  position: absolute;
  top: 10px;
  right: 10px;
  background: transparent;
  border: none;
  font-size: 20px;
  cursor: pointer;
}
</style>
