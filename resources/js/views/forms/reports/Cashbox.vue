<!-- CashboxReportPage.vue -->
<template>
    <div class="dashboard-container">
      <!-- … таблицы, кнопки, итог  полностью без изменений … -->

      <!-- ===== MODAL ===== -->
      <transition name="fade-zoom">
        <div v-if="isModalVisible" class="modal-overlay" @click.self="closeModal">
          <div class="modal-content">
            <h3 class="modal-title">Название кассыы</h3>
            <p>{{ currentElementName }}</p>
            <button class="modal-close-btn" @click="closeModal">Закрыть</button>
          </div>
        </div>
      </transition>
    </div>
  </template>

  <script>
  import axios from "axios";
  import dayjs from "dayjs";

  export default {
    name: "CashboxReportPage",
    data() {
      return {
        loading: false,
        error: "",
        financialOrders: [],
        totalIncome: 0,
        totalExpense: 0,
        saldo: 0,
        isModalVisible: false,
        currentElementName: ""
      };
    },
    created() {
      this.fetchFinancialOrders();
    },
    methods: {
      /* ---------- DATA ---------- */
      async fetchFinancialOrders() {
        this.loading = true;
        try {
          const token = localStorage.getItem("token");
          if (!token) {
            this.error = "Ошибка: токен не найден.";
            return;
          }
          const { data } = await axios.get("/api/financial-order", {
            headers: { Authorization: `Bearer ${token}` }
          });
          this.financialOrders = data || [];
          const income  = this.financialOrders.filter(o => o.type === "income");
          const expense = this.financialOrders.filter(o => o.type === "expense");
          this.totalIncome  = income.reduce((s, o) => s + (+o.summary_cash || 0), 0);
          this.totalExpense = expense.reduce((s, o) => s + (+o.summary_cash || 0), 0);
          this.saldo = this.totalIncome - this.totalExpense;
        } catch (e) {
          this.error = "Ошибка: " + e.message;
        } finally {
          this.loading = false;
        }
      },

      /* ---------- MODAL ---------- */
      openModal(elementName) {
        console.log("openModal ->", elementName);          // проверка
        this.currentElementName = elementName || "—";
        this.isModalVisible = true;
      },
      closeModal() {
        this.isModalVisible = false;
      },

      /* ---------- STUB EXPORT ---------- */
      exportToExcel() { alert("Экспорт в Excel пока не реализован."); },
      exportToPdf()   { alert("Экспорт в PDF пока не реализован.");   },

      /* ---------- HELPERS ---------- */
      formatDate(str) {
        return str ? dayjs(str).format("DD.MM.YYYY") : "—";
      }
    }
  };
  </script>

  <style scoped>
  /* базовая раскладка — как было */
  .dashboard-container { display:flex; flex-direction:column; min-height:100vh; background:#f5f5f5; }
  .main-content        { flex:1; }
  .content             { padding:20px; }
  .page-title          { text-align:center; color:#0288d1; margin-bottom:20px; }

  /* таблицы — как было */
  .report-table { width:100%; border-collapse:collapse; margin-bottom:20px; background:#fff; }
  .report-table th, .report-table td { padding:10px; border:1px solid #ddd; text-align:center; }
  .report-table thead { background:#0288d1; color:#fff; }
  .clickable { cursor:pointer; }
  .clickable:hover { text-decoration:underline; }

  /* кнопки экспорта */
  .export-buttons { display:flex; justify-content:flex-end; gap:10px; }
  .export-btn { padding:10px 20px; border:0; border-radius:5px; color:#fff; cursor:pointer; }
  .pdf-btn   { background:#d32f2f; }
  .excel-btn { background:#388e3c; }

  /* ----- fixed modal styles (no `inset`) ----- */
  .modal-overlay {
    position:fixed; top:0; right:0; bottom:0; left:0;
    background:rgba(0,0,0,.5);
    display:flex; align-items:center; justify-content:center;
    z-index:1000;
  }
  .modal-content  { background:#fff; padding:24px; border-radius:8px; width:90%; max-width:380px;
                    box-shadow:0 10px 24px rgba(0,0,0,.15); }
  .modal-title    { font-size:20px; font-weight:600; margin-bottom:12px; }
  .modal-close-btn{ margin-top:20px; background:#0288d1; color:#fff; padding:8px 16px;
                    border:0; border-radius:4px; cursor:pointer; }
  .modal-close-btn:hover { background:#0277bd; }

  /* fade + zoom */
  .fade-zoom-enter-active, .fade-zoom-leave-active { transition:opacity .3s ease, transform .3s ease; }
  .fade-zoom-enter, .fade-zoom-leave-to { opacity:0; transform:scale(.95); }
  </style>
