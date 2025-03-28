<template>
    <div class="dashboard-container">
      <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />

      <div class="main-content">
        <Header />
        <main class="content">
          <h2 class="page-title">Операции (Docs)</h2>

          <!-- "Create" dropdown -->
          <div class="dropdown-section">
            <label class="dropdown-label">Создать:</label>
            <select
              v-model="selectedOption"
              @change="openCreateModal"
              class="dropdown-select"
            >
              <option
                v-for="option in productOptions"
                :key="option.value"
                :value="option.value"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- CREATE Modal -->
          <div v-if="showCreateModal" class="modal-overlay">
            <div class="modal-container">
              <button class="close-modal-btn" @click="closeCreateModal">✖</button>
              <!-- Dynamically render the creation component -->
              <component
                :is="currentCreateComponent"
                @close="closeCreateModal"
                @saved="onNewRecordSaved"
              />
            </div>
          </div>

          <!-- Search Field -->
          <div class="search-section">
            <input
              v-model="searchQuery"
              type="text"
              class="search-input"
              placeholder="Поиск..."
            />
          </div>

          <!-- TABLE: one row per doc -->
          <div class="history-section" style="margin-top: 30px;">
            <h3>Список документов</h3>
            <div class="table-container">
              <table class="history-table">
                <thead>
                  <tr>
                    <th>№ Документа</th>
                    <th>Тип</th>
                    <th>Дата</th>
                    <th>Поставщик</th>
                    <th>Итог</th>
                    <th>Действия</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(doc, idx) in filteredDocs" :key="doc.doc_id">
                    <td>{{ doc.document_number || doc.doc_id }}</td>
                    <td>{{ mapTypeToLabel(doc.type) }}</td>
                    <td>{{ formatDate(doc.document_date) }}</td>
                    <td>{{ doc.provider_name || '-' }}</td>
                    <td>{{ doc.doc_total_sum }}</td>
                    <td>
                      <!-- Single edit button that always calls openEditModal -->
                      <button class="edit-btn" @click="openEditModal(doc)">
                        ✏️
                      </button>
                      <!-- Delete -->
                      <button class="delete-btn" @click="deleteRecord(doc, idx)">
                        🗑
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- EDIT Modal -->
          <div v-if="showEditModal" class="modal-overlay">
            <div class="modal-container">
              <button class="close-modal-btn" @click="closeEditModal">✖</button>

              <!-- Render a different edit component for each doc.type -->

              <!-- Приход (income) -->
              <EditIncomeModal
                v-if="docToEdit && docToEdit.type === 'income'"
                :document-id="docToEdit.doc_id"
                @close="closeEditModal"
                @saved="onDocEdited"
              />

              <!-- Списание (write_off) -->
              <EditWriteOffModal
                v-else-if="docToEdit && docToEdit.type === 'write_off'"
                :document-id="docToEdit.doc_id"
                @close="closeEditModal"
                @saved="onDocEdited"
              />

              <!-- Продажа (sale) -->
              <EditSaleModal
                v-else-if="docToEdit && docToEdit.type === 'sale'"
                :document-id="docToEdit.doc_id"
                @close="closeEditModal"
                @saved="onDocEdited"
              />

              <!-- Перемещение (transfer) -->
              <EditTransferModal
                v-else-if="docToEdit && docToEdit.type === 'transfer'"
                :document-id="docToEdit.doc_id"
                @close="closeEditModal"
                @saved="onDocEdited"
              />

              <!-- Ценовое предложение (priceOffer) -->
              <EditPriceOfferModal
                v-else-if="docToEdit && docToEdit.type === 'priceOffer'"
                :document-id="docToEdit.doc_id"
                @close="closeEditModal"
                @saved="onDocEdited"
              />

              <!-- Инвентаризация (inventory) -->
              <EditInventoryModal
                v-else-if="docToEdit && docToEdit.type === 'inventory'"
                :document-id="docToEdit.doc_id"
                @close="closeEditModal"
                @saved="onDocEdited"
              />

              <!-- Otherwise, no recognized doc type -->
              <div v-else>
                <p>Нет формы для редактирования: {{ docToEdit?.type }}</p>
              </div>
            </div>
          </div>
        </main>
      </div>
    </div>
  </template>

  <script>
  import Sidebar from "../components/Sidebar.vue";
  import Header from "../components/Header.vue";

  // “Create” modals/pages
  import SalePage from "./SalePage.vue";
  import PriceOfferPage from "./PriceOfferPage.vue";
  import InventoryPage from "./InventoryPage.vue";
  import ProductReceivingPage from "./ProductReceivingPage.vue";
  import WriteOffPage from "./WriteOffPage.vue";
  import InventoryCheckPage from "./InventoryCheckPage.vue";

  // “Edit” modals
  import EditIncomeModal from "./forms/products/EditReceivingModal.vue";
  import EditWriteOffModal from "./forms/products/EditWriteOffModal.vue";
  import EditSaleModal from "./forms/products/EditSaleModal.vue";
  import EditTransferModal from "./forms/products/EditTransferModal.vue";
  import EditPriceOfferModal from "./forms/products/PriceOfferEdit.vue";
  import EditInventoryModal from "./forms/products/EditInventoryModal.vue";

  import axios from "axios";

  export default {
    name: "OperationsPage",
    components: {
      Sidebar,
      Header,
      // “Create” pages
      SalePage,
      PriceOfferPage,
      InventoryPage,
      ProductReceivingPage,
      WriteOffPage,
      InventoryCheckPage,
      // “Edit” modals
      EditIncomeModal,
      EditWriteOffModal,
      EditSaleModal,
      EditTransferModal,
      EditPriceOfferModal,
      EditInventoryModal,
    },
    data() {
      return {
        isSidebarOpen: true,

        // “Create” selection
        selectedOption: "",
        productOptions: [
          { label: "Выберите...", value: "" },
          { label: "Продажа", value: "sale" },
          { label: "Ценовое предложение", value: "priceOffer" },
          { label: "Перемещение", value: "inventory" },
          { label: "Поступление товара", value: "productReceiving" },
          { label: "Списание", value: "writeOff" },
          { label: "Инвентаризация", value: "inventoryCheck" },
        ],
        pageMap: {
          sale: "SalePage",
          priceOffer: "PriceOfferPage",
          inventory: "InventoryPage",
          productReceiving: "ProductReceivingPage",
          writeOff: "WriteOffPage",
          inventoryCheck: "InventoryCheckPage",
        },
        showCreateModal: false,

        // Documents
        allDocuments: [],
        searchQuery: "",

        // Edit doc
        showEditModal: false,
        docToEdit: null,
      };
    },
    computed: {
      // Which create component to display
      currentCreateComponent() {
        if (!this.selectedOption) return null;
        return this.pageMap[this.selectedOption] || null;
      },
      filteredDocs() {
        const q = this.searchQuery.toLowerCase();
        if (!q) return this.allDocuments;
        return this.allDocuments.filter((doc) => {
          const docIdStr = String(doc.doc_id).toLowerCase();
          const docNumStr = String(doc.document_number || "").toLowerCase();
          const dateStr = doc.document_date
            ? new Date(doc.document_date).toLocaleDateString()
            : "";
          const providerStr = (doc.provider_name || "").toLowerCase();

          return (
            docIdStr.includes(q) ||
            docNumStr.includes(q) ||
            dateStr.includes(q) ||
            providerStr.includes(q)
          );
        });
      },
    },
    created() {
      this.fetchAllDocuments();
    },
    methods: {
      // Show/hide create
      openCreateModal() {
        if (!this.selectedOption) return;
        this.showCreateModal = true;
      },
      closeCreateModal() {
        this.showCreateModal = false;
      },
      onNewRecordSaved() {
        // after doc created, refresh
        this.closeCreateModal();
        this.fetchAllDocuments();
      },

      // Load docs
      async fetchAllDocuments() {
        try {
          // GET /api/documents/allHistories
          const { data } = await axios.get("/api/documents/allHistories");
          this.allDocuments = data;
        } catch (err) {
          console.error("Error fetching documents:", err);
        }
      },

      // Date formatting
      formatDate(d) {
        return d ? new Date(d).toLocaleDateString() : "";
      },
      mapTypeToLabel(code) {
        switch (code) {
          case "income":      return "Приход";
          case "sale":        return "Продажа";
          case "write_off":   return "Списание";
          case "transfer":    return "Перемещение";
          case "priceOffer":  return "Ценовое предложение";
          case "inventory":   return "Инвентаризация";
          default:            return code;
        }
      },

      // Edit doc
      openEditModal(doc) {
        this.docToEdit = { ...doc }; // copy
        this.showEditModal = true;
      },
      closeEditModal() {
        this.showEditModal = false;
        this.docToEdit = null;
      },
      onDocEdited() {
        // re-fetch docs after editing
        this.closeEditModal();
        this.fetchAllDocuments();
      },

      // Delete doc
      async deleteRecord(doc, idx) {
        if (!confirm(`Удалить документ #${doc.doc_id}?`)) return;
        try {
          // DELETE /api/documents/{doc.doc_id}
          await axios.delete(`/api/documents/${doc.doc_id}`);
          this.allDocuments.splice(idx, 1);
        } catch (err) {
          console.error("Delete error:", err);
          alert("Не удалось удалить документ.");
        }
      },

      // Sidebar
      toggleSidebar() {
        this.isSidebarOpen = !this.isSidebarOpen;
      },
    },
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
  }
  .page-title {
    text-align: center;
    color: #0288d1;
    margin-bottom: 20px;
  }

  /* Create dropdown */
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

  /* Search */
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
  .history-table thead {
    background-color: #0288d1;
    color: #fff;
  }
  .history-table th,
  .history-table td {
    padding: 10px;
    border: 1px solid #ddd;
    text-align: center;
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
  .delete-btn {
    background-color: #f44336;
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 6px 10px;
    cursor: pointer;
  }

  /* Modal overlay */
  .modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    display: flex;
    align-items: center;
    justify-content: center;
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
