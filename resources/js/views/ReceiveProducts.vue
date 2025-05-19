<!-- resources/js/components/operations/OperationsPage.vue -->
<template>
    <section class="operations-page">
      <!-- ▸ top-bar ----------------------------------------------------- -->
      <header class="topbar">
        <h1>Операции</h1>

        <div class="actions">
          <label class="lbl">Создать:</label>
          <select v-model="selectedOption" @change="openCreateModal" class="select">
            <option v-for="o in productOptions" :key="o.value" :value="o.value">
              {{ o.label }}
            </option>
          </select>

          <input v-model.trim="searchQuery"
                 type="search"
                 class="search"
                 placeholder="🔍 Поиск…"/>

          <button class="reload" @click="fetchAllDocuments">⟳</button>
        </div>
      </header>

      <!-- ▸ таблица-карточка ------------------------------------------- -->
      <div class="table-wrapper">
        <table class="docs-table">
          <thead>
            <tr>
              <th>№</th>
              <th>Тип</th>
              <th>Дата</th>
              <th>Поставщик</th>
              <th class="num">Итог, ₸</th>
              <th></th>
            </tr>
          </thead>

          <tbody>
            <tr v-for="(doc, idx) in filteredDocs"
                :key="doc.doc_id"
                class="click-row"
                @click="openEditModal(doc)">
              <td>{{ doc.document_number || doc.doc_id }}</td>
              <td>{{ mapTypeToLabel(doc.type) }}</td>
              <td>{{ formatDate(doc.document_date) }}</td>
              <td>{{ doc.provider_name || '—' }}</td>
              <td class="num">{{ money(doc.doc_total_sum) }}</td>
              <td class="actions" @click.stop>
                <button class="icon-btn" @click="openEditModal(doc)">✏️</button>
                <button class="icon-btn danger" @click="deleteRecord(doc, idx)">🗑</button>
              </td>
            </tr>

            <tr v-if="filteredDocs.length === 0">
              <td colspan="6" class="empty">Данных нет</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- ▸ модалки ----------------------------------------------------- -->
      <ModalShell v-if="showCreateModal" @close="closeCreateModal">
        <component :is="currentCreateComponent"
                   @close="closeCreateModal"
                   @saved="onNewRecordSaved"/>
      </ModalShell>

      <ModalShell v-if="showEditModal" @close="closeEditModal">
        <EditIncomeModal     v-if="isType('income')"         :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
        <EditWriteOffModal   v-else-if="isType('write_off')" :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
        <EditSaleModal       v-else-if="isType('sale')"      :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
        <EditTransferModal   v-else-if="isType('transfer')"  :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
        <EditPriceOfferModal v-else-if="isType('priceOffer')":document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
        <EditInventoryModal  v-else-if="isType('inventory')" :document-id="docToEdit.doc_id" @close="closeEditModal" @saved="onDocEdited"/>
        <p v-else>Нет формы для {{ docToEdit?.type }}</p>
      </ModalShell>
    </section>
  </template>

  <script>
  /* create pages */
  import SalePage              from "./SalePage.vue";
  import ClientSalePage        from "../components/operations/ClientSale.vue";
  import PriceOfferPage        from "./PriceOfferPage.vue";
  import InventoryPage         from "./InventoryPage.vue";
  import ProductReceivingPage  from "./ProductReceivingPage.vue";
  import WriteOffPage          from "./WriteOffPage.vue";
  import InventoryCheckPage    from "./InventoryCheckPage.vue";

  /* edit modals */
  import EditIncomeModal     from "./forms/products/EditReceivingModal.vue";
  import EditWriteOffModal   from "./forms/products/EditWriteOffModal.vue";
  import EditSaleModal       from "./forms/products/EditSaleModal.vue";
  import EditTransferModal   from "./forms/products/EditTransferModal.vue";
  import EditPriceOfferModal from "./forms/products/PriceOfferEdit.vue";
  import EditInventoryModal  from "./forms/products/EditInventoryModal.vue";
  import ModalShell          from "./forms/products/ModalShell.vue";

  import axios from "axios";

  export default {
    name: "OperationsPage",
    components: {
      /* create */
      SalePage, ClientSalePage, PriceOfferPage, InventoryPage,
      ProductReceivingPage, WriteOffPage, InventoryCheckPage,
      /* edit */
      EditIncomeModal, EditWriteOffModal, EditSaleModal,
      EditTransferModal, EditPriceOfferModal, EditInventoryModal,
      ModalShell
    },

    data () {
      return {
        /* create */
        selectedOption: "",
        productOptions: [
          { label:"Выберите…",            value:"" },
          { label:"Продажа",              value:"sale" },
          { label:"Продажа клиенту",      value:"clientSale" },
          { label:"Ценовое предложение",  value:"priceOffer" },
          { label:"Перемещение",          value:"inventory" },
          { label:"Поступление товара",   value:"productReceiving" },
          { label:"Списание",             value:"writeOff" },
          { label:"Инвентаризация",       value:"inventoryCheck" },
        ],
        pageMap:{
          sale:"SalePage",
          clientSale:"ClientSalePage",
          priceOffer:"PriceOfferPage",
          inventory:"InventoryPage",
          productReceiving:"ProductReceivingPage",
          writeOff:"WriteOffPage",
          inventoryCheck:"InventoryCheckPage",
        },
        showCreateModal:false,

        /* list */
        allDocuments:[],
        searchQuery:"",

        /* edit */
        showEditModal:false,
        docToEdit:null,
      };
    },

    computed:{
      currentCreateComponent(){ return this.pageMap[this.selectedOption] || null },
      filteredDocs(){
        const q = this.searchQuery.toLowerCase();
        if (!q) return this.allDocuments;
        return this.allDocuments.filter(d =>
          [
            d.doc_id,
            d.document_number,
            d.provider_name,
            this.formatDate(d.document_date)
          ]
          .join(" ")
          .toLowerCase()
          .includes(q)
        );
      }
    },

    created(){ this.fetchAllDocuments(); },

    methods:{
      /* ─── create ───────────────────────────── */
      openCreateModal(){ if (this.selectedOption) this.showCreateModal = true },
      closeCreateModal(){ this.showCreateModal = false },
      onNewRecordSaved(){ this.closeCreateModal(); this.fetchAllDocuments() },

      /* ─── fetch list ───────────────────────── */
      async fetchAllDocuments(){
        try{
          const { data } = await axios.get("/api/documents/allHistories");
          this.allDocuments = data;
        }catch(e){ console.error(e) }
      },
      formatDate(d){ return d ? new Date(d).toLocaleDateString() : "" },
      money(v){ return Number(v||0).toLocaleString("ru-RU") },
      mapTypeToLabel(t){
        return {
          income:"Приход",
          sale:"Продажа",
          clientSale:"Продажа клиенту",
          write_off:"Списание",
          transfer:"Перемещение",
          priceOffer:"Цен. предложение",
          inventory:"Инвентаризация"
        }[t] || t;
      },

      /* ─── edit ─────────────────────────────── */
      isType(t){ return this.docToEdit && this.docToEdit.type === t },
      openEditModal(doc){ this.docToEdit = { ...doc }; this.showEditModal = true },
      closeEditModal(){ this.showEditModal = false; this.docToEdit = null },
      onDocEdited(){ this.closeEditModal(); this.fetchAllDocuments() },

      /* ─── delete ───────────────────────────── */
      async deleteRecord(doc, idx){
        if (!confirm(`Удалить документ #${doc.doc_id}?`)) return;
        try{
          await axios.delete(`/api/documents/${doc.doc_id}`);
          this.allDocuments.splice(idx,1);
        }catch(e){ alert("Не удалось удалить."); }
      }
    }
  }
  </script>

  <style scoped>
  :root{--from:#03b4d1;--to:#3dc1ff;--r:14px;font-family:Inter,sans-serif}

  /* top-bar */
  .topbar{display:flex;align-items:center;gap:14px;
         background:linear-gradient(90deg,var(--from),var(--to));
         color:#fff;padding:10px 18px;border-radius:var(--r);margin-bottom:20px;
         box-shadow:0 4px 12px rgba(0,0,0,.18)}
  .topbar h1{margin:0;font-size:20px;font-weight:600}
  .actions{margin-left:auto;display:flex;gap:10px;align-items:center}
  .lbl{font-weight:600}
  .select,.search{height:34px;font-size:14px;padding:0 10px;border-radius:8px;border:none}
  .search{min-width:180px}
  .reload{border:none;background:none;color:#c8ff55;font-size:24px;cursor:pointer;line-height:1}

  /* wrapper */
  .table-wrapper{background:rgba(255,255,255,.55);backdrop-filter:blur(12px);
                 padding:24px;border-radius:20px;box-shadow:0 6px 18px rgba(0,0,0,.06);
                 overflow-x:auto}

  /* table */
  .docs-table{width:100%;border-collapse:collapse;font-size:14px}
  .docs-table thead{background:linear-gradient(90deg,var(--from),var(--to));color:#fff}
  .docs-table th,.docs-table td{padding:10px 14px;text-align:center}
  .docs-table tbody tr+tr{border-top:1px solid #e2e8f0}
  .click-row{cursor:pointer;transition:background .15s}
  .click-row:hover{background:#f7fdff}
  .num{text-align:right}
  .empty{text-align:center;color:#7c7c7c;padding:14px 0}

  /* buttons */
  .icon-btn{background:var(--from);color:#fff;border:none;border-radius:6px;
            padding:4px 8px;font-size:16px;cursor:pointer;transition:filter .15s}
  .icon-btn.danger{background:#f44336}
  .icon-btn:hover{filter:brightness(.9)}
  .actions{display:flex;gap:6px;justify-content:center}
  </style>
