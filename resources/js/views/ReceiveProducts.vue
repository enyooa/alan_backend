<template>
    <section class="operations-page">
      <h2 class="page-title">Операции (Docs)</h2>

      <!-- ▸ “Create” dropdown -->
      <div class="dropdown-section">
        <label class="dropdown-label">Создать:</label>
        <select
          v-model="selectedOption"
          @change="openCreateModal"
          class="dropdown-select"
        >
          <option v-for="o in productOptions" :key="o.value" :value="o.value">
            {{ o.label }}
          </option>
        </select>
      </div>

      <!-- ▸ CREATE modal -->
      <div v-if="showCreateModal" class="modal-overlay">
        <div class="modal-container">
          <button class="close-modal-btn" @click="closeCreateModal">✖</button>
          <component
            :is="currentCreateComponent"
            @close="closeCreateModal"
            @saved="onNewRecordSaved"
          />
        </div>
      </div>

      <!-- ▸ Search --------------------------------------------------------- -->
      <div class="search-section">
        <input
          v-model="searchQuery"
          class="search-input"
          placeholder="Поиск…"
        />
      </div>

      <!-- ▸ Documents table ------------------------------------------------ -->
      <div class="history-section">
        <h3>Список документов</h3>
        <div class="table-container">
          <table class="history-table">
            <thead>
              <tr>
                <th>№</th><th>Тип</th><th>Дата</th>
                <th>Поставщик</th><th>Итог</th><th>Действия</th>
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
                  <button class="edit-btn"   @click="openEditModal(doc)">✏️</button>
                  <button class="delete-btn" @click="deleteRecord(doc, idx)">🗑</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ▸ EDIT modal ----------------------------------------------------- -->
      <div v-if="showEditModal" class="modal-overlay">
        <div class="modal-container">
          <button class="close-modal-btn" @click="closeEditModal">✖</button>

          <!-- one modal per doc.type -->
          <EditIncomeModal     v-if="isType('income')"      :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
          <EditWriteOffModal   v-else-if="isType('write_off')"  :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
          <EditSaleModal       v-else-if="isType('sale')"        :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
          <EditTransferModal   v-else-if="isType('transfer')"    :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
          <EditPriceOfferModal v-else-if="isType('priceOffer')"  :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
          <EditInventoryModal  v-else-if="isType('inventory')"   :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>

          <p v-else>Нет формы для редактирования: {{ docToEdit?.type }}</p>
        </div>
      </div>
    </section>
  </template>

  <script>
  /* “Create” pages ------------------------------------------------------ */
  import SalePage            from "./SalePage.vue";
  import PriceOfferPage      from "./PriceOfferPage.vue";
  import InventoryPage       from "./InventoryPage.vue";
  import ProductReceivingPage from "./ProductReceivingPage.vue";
  import WriteOffPage        from "./WriteOffPage.vue";
  import InventoryCheckPage  from "./InventoryCheckPage.vue";

  /* “Edit” modals ------------------------------------------------------- */
  import EditIncomeModal     from "./forms/products/EditReceivingModal.vue";
  import EditWriteOffModal   from "./forms/products/EditWriteOffModal.vue";
  import EditSaleModal       from "./forms/products/EditSaleModal.vue";
  import EditTransferModal   from "./forms/products/EditTransferModal.vue";
  import EditPriceOfferModal from "./forms/products/PriceOfferEdit.vue";
  import EditInventoryModal  from "./forms/products/EditInventoryModal.vue";

  import axios from "axios";

  export default {
    name: "OperationsPage",
    components: {
      /* create */
      SalePage, PriceOfferPage, InventoryPage,
      ProductReceivingPage, WriteOffPage, InventoryCheckPage,
      /* edit */
      EditIncomeModal, EditWriteOffModal, EditSaleModal,
      EditTransferModal, EditPriceOfferModal, EditInventoryModal
    },
    data() {
      return {
        /* create modal */
        selectedOption: "",
        productOptions: [
          { label: "Выберите…", value: "" },
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

        /* documents list */
        allDocuments: [],
        searchQuery: "",

        /* edit modal */
        showEditModal: false,
        docToEdit: null,
      };
    },

    computed: {
      currentCreateComponent() {
        return this.pageMap[this.selectedOption] || null;
      },
      filteredDocs() {
        const q = this.searchQuery.toLowerCase();
        if (!q) return this.allDocuments;
        return this.allDocuments.filter((d) =>
          [
            d.doc_id,
            d.document_number,
            d.provider_name,
            this.formatDate(d.document_date),
          ]
            .join(" ")
            .toLowerCase()
            .includes(q)
        );
      },
    },

    created() { this.fetchAllDocuments(); },

    methods: {
      /* ---------- create modal ----------------------------------------- */
      openCreateModal() { if (this.selectedOption) this.showCreateModal = true; },
      closeCreateModal(){ this.showCreateModal = false; },
      onNewRecordSaved(){ this.closeCreateModal(); this.fetchAllDocuments(); },

      /* ---------- docs list -------------------------------------------- */
      async fetchAllDocuments() {
        try {
          const { data } = await axios.get("/api/documents/allHistories");
          this.allDocuments = data;
        } catch (err) { console.error("fetch error:", err); }
      },
      formatDate(d){ return d ? new Date(d).toLocaleDateString() : ""; },
      mapTypeToLabel(t){
        return {income:"Приход",sale:"Продажа",write_off:"Списание",
                transfer:"Перемещение",priceOffer:"Ценовое предложение",
                inventory:"Инвентаризация"}[t] || t;
      },

      /* ---------- edit modal ------------------------------------------- */
      isType(t){ return this.docToEdit && this.docToEdit.type === t; },
      openEditModal(doc){ this.docToEdit = {...doc}; this.showEditModal=true; },
      closeEditModal(){ this.showEditModal=false; this.docToEdit=null; },
      onDocEdited(){ this.closeEditModal(); this.fetchAllDocuments(); },

      /* ---------- delete ----------------------------------------------- */
      async deleteRecord(doc, idx){
        if(!confirm(`Удалить документ #${doc.doc_id}?`)) return;
        try{
          await axios.delete(`/api/documents/${doc.doc_id}`);
          this.allDocuments.splice(idx,1);
        }catch(e){ alert("Не удалось удалить документ.");}
      },
    },
  };
  </script>

  <style scoped>
  .operations-page{padding:20px;background:#f5f5f5;min-height:calc(100vh - 56px)}
  .page-title{text-align:center;color:#0288d1;margin-bottom:20px}

  /* dropdown */
  .dropdown-section{display:flex;align-items:center;gap:8px;margin-bottom:20px}
  .dropdown-label{font-weight:600}
  .dropdown-select{padding:8px;border:1px solid #ddd;border-radius:5px}

  /* search */
  .search-section{margin:10px 0}
  .search-input{max-width:300px;width:100%;padding:8px;border:1px solid #ddd;border-radius:5px}

  /* table */
  .table-container{background:#fff;padding:20px;border-radius:8px;box-shadow:0 3px 8px rgba(0,0,0,.1);overflow-x:auto}
  .history-table{width:100%;border-collapse:collapse}
  .history-table thead{background:#0288d1;color:#fff}
  .history-table th,.history-table td{padding:10px;border:1px solid #ddd;text-align:center}

  /* buttons */
  .edit-btn{background:#0288d1;color:#fff;border:none;border-radius:5px;padding:6px 10px;cursor:pointer;margin-right:5px}
  .delete-btn{background:#f44336;color:#fff;border:none;border-radius:5px;padding:6px 10px;cursor:pointer}

  /* modal */
  .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);display:flex;justify-content:center;align-items:center;z-index:999}
  .modal-container{background:#fff;padding:20px;border-radius:10px;max-width:90%;max-height:90%;overflow:auto;position:relative}
  .close-modal-btn{position:absolute;top:10px;right:10px;background:none;border:none;font-size:20px;cursor:pointer}
  </style>
