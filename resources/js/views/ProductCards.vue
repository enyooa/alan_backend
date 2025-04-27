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
                  :key="`${op.opType}-${op.id}`"
                >
                  <td>{{ getFriendlyType(op.opType) }}</td>
                  <td>{{ formatDate(op.created_at) }}</td>
                  <td>
                    <span v-if="op.opType === 'productCard'">
                      {{ op.description || '—' }}
                    </span>
                    <span
                      v-else-if="['subproductCard','provider','address'].includes(op.opType)"
                    >
                      {{ op.name || '—' }}
                    </span>
                    <span v-else-if="op.opType === 'unit'">
                      {{ op.value ? op.value + ' г/кг/л' : '—' }}
                    </span>
                    <span v-else-if="op.opType === 'expense'">
                      {{ op.name }}<span v-if="op.value"> ({{ op.value }})</span>
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
      ProductCardEdit,
      ProductSubCardEdit,
      ProviderEdit,
      UnitEdit,
      AddressEdit,
      ExpenseEdit,
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
          expense: false,
        },
        createModal: {
          productCard: false,
          subproductCard: false,
          provider: false,
          unit: false,
          address: false,
          expense: false,
        },
        selectedCreateType: "",
        titleToType: {
          'Карточка товара': 'productCard',
          'Подкарточка товара': 'subproductCard',
          'Поставщик': 'provider',
          'Единица измерения': 'unit',
          'Адрес': 'address',
          'Расход': 'expense',
          'Приход': 'income',
        },
        typeToSlug: {
          productCard: 'product-card',
          subproductCard: 'product-subcard',
          provider: 'provider',
          unit: 'unit',
          address: 'address',
          expense: 'expense',
          income: 'income',
        },
        friendly: {
          productCard: 'Карточка товара',
          subproductCard: 'Подкарточка',
          provider: 'Поставщик',
          unit: 'Единица измерения',
          address: 'Адрес',
          expense: 'Расход',
        },
      };
    },
    created() {
      this.fetchOperationHistory();
    },
    methods: {
      toggleSidebar() {
        this.isSidebarOpen = !this.isSidebarOpen;
      },

      flattenRefs(arr, type) {
    return (arr || [])
      .flatMap(r => r.RefferenceItem || [])
      .map(item => ({
        ...item,
        opType: type,
        created_at: item.created_at
      }));   // <-- закрыли объект и .map(), а затем функцию flattenRefs
  },

      async fetchOperationHistory() {
        try {
          const token = localStorage.getItem('token');
          if (!token) {
            this.$router.push('/login');
            return;
          }
          const { data } = await axios.get('/api/reference', { headers: { Authorization: `Bearer ${token}` } });
          const ops = data.refferences.flatMap((ref) => {
            const type = this.titleToType[ref.title] || 'unknown';
            return (ref.RefferenceItem || []).map((item) => ({
              ...item,
              opType: type,
              created_at: ref.created_at,
            }));
          });
          this.allOperations = ops;
          this.filterOperations();
        } catch (e) {
          console.error('Ошибка загрузки операций:', e);
        }
      },

      filterOperations() {
        const q = this.searchQuery.toLowerCase();
        let res = this.allOperations.filter((op) => {
          let field = '';
          if (op.opType === 'unit') field = op.value ? String(op.value) : '';
          else field = op.name || op.description || '';
          return field.toLowerCase().includes(q);
        });
        if (this.filterType) {
          res = res.filter((op) => op.opType === this.filterType);
        }
        this.filteredOperationsList = res;
      },

      formatDate(d) {
        return d ? new Date(d).toLocaleDateString() : '';
      },

      getFriendlyType(type) {
        return this.friendly[type] || 'Неизвестно';
      },

      editOperation(op) {
        this.operationToEdit = { ...op };
        Object.keys(this.showModal).forEach((key) => (this.showModal[key] = false));
        this.showModal[op.opType] = true;
      },

      async deleteOperation(op, idx) {
        if (!confirm(`Удалить операцию ${op.id}?`)) return;
        try {
          const token = localStorage.getItem('token');
          const slug = this.typeToSlug[op.opType];
          await axios.delete(`/api/reference/${slug}/${op.id}`, { headers: { Authorization: `Bearer ${token}` } });
          this.filteredOperationsList.splice(idx, 1);
          this.allOperations = this.allOperations.filter((o) => o.id !== op.id);
        } catch {
          alert('Не удалось удалить');
        }
      },

      closeAllModals() {
        Object.keys(this.showModal).forEach((key) => (this.showModal[key] = false));
        this.operationToEdit = null;
      },

      onOperationSaved() {
        this.closeAllModals();
        this.fetchOperationHistory();
      },

      onNewRecordSaved() {
        this.closeCreateModal();
        this.fetchOperationHistory();
      },

      onCreateTypeChange() {
        Object.keys(this.createModal).forEach((key) => (this.createModal[key] = false));
        if (this.selectedCreateType) this.createModal[this.selectedCreateType] = true;
        this.selectedCreateType = '';
      },
      closeCreateModal() {
        Object.keys(this.createModal).forEach((key) => (this.createModal[key] = false));
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
  .dashboard-container { display: flex; min-height: 100vh; }
  .main-content { flex: 1; background-color: #f5f5f5; }
  .content { padding: 20px; }
  .page-title { text-align: center; color: #0288d1; margin-bottom: 20px; }
  .form-controls-row { display: flex; gap: 10px; margin-bottom: 20px; }
  .form-control { height: 40px; padding: 10px; font-size: 14px; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
  .create-select, .filter-select { width: 200px; }
  .search-box { flex: 1; }
  .table-container { overflow-x: auto; background-color: #fff; border-radius: 8px; box-shadow: 0 3px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
  .history-table { width: 100%; border-collapse: collapse; }
  .history-table th, .history-table td { padding: 10px; border: 1px solid #ddd; text-align: center; }
  .history-table thead { background-color: #0288d1; color: white; }
  .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; z-index: 999; }
  .modal-container { background: #fff; padding: 20px; border-radius: 12px; width: 90%; max-width: 700px; }
  .edit-btn, .delete-btn { border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
  .delete-btn { background-color: #f44336; color: #fff; }
  </style>
