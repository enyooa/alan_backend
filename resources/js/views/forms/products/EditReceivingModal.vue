<template>
    <div class="edit-income-modal">
      <div class="modal-header">
        <h2>Редактировать «Приход» (ID: {{ documentHeader.id }})</h2>
        <button class="close-btn" @click="$emit('close')">✖</button>
      </div>

      <div class="modal-body">
        <!-- Document Header -->
        <div class="card">
          <div class="card-header">
            <h3>Основная информация</h3>
          </div>
          <div class="card-body">
            <div class="form-row">
              <!-- Provider -->
              <div class="form-group">
                <label>Поставщик</label>
                <select v-model="documentHeader.provider_id" class="form-control">
                  <option disabled value="">— Выберите поставщика —</option>
                  <option
                    v-for="prov in providers"
                    :key="prov.id"
                    :value="prov.id"
                  >
                    {{ prov.name }}
                  </option>
                </select>
              </div>

              <!-- Date -->
              <div class="form-group">
                <label>Дата</label>
                <input
                  type="date"
                  v-model="documentHeader.document_date"
                  class="form-control"
                />
              </div>

              <!-- Warehouse -->
              <div class="form-group">
                <label>Склад</label>
                <select
                  v-model="documentHeader.to_warehouse_id"
                  class="form-control"
                >
                  <option disabled value="">— Выберите склад —</option>
                  <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                    {{ wh.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- Document Items Table -->
        <div class="card mt-2">
          <div class="card-header flex-between">
            <h3>Товары (items)</h3>
            <button class="action-btn" @click="addProductRow">➕ Добавить строку</button>
          </div>
          <div class="card-body">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Товар</th>
                  <th>Кол-во тары</th>
                  <th>Ед.изм</th>
                  <th>Брутто</th>
                  <th>Нетто</th>
                  <th>Цена</th>
                  <th>Сумма</th>
                  <th>Доп. расход</th>
                  <th>Себестоим.</th>
                  <th>Удалить</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, idx) in productRows"
                  :key="row._key"
                >
                  <td>
                    <select v-model="row.product_subcard_id" class="form-control">
                      <option disabled value="">— Товар —</option>
                      <option
                        v-for="p in products"
                        :key="p.id"
                        :value="p.id"
                      >
                        {{ p.name }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <input
                      type="number"
                      v-model.number="row.quantity"
                      class="form-control"
                    />
                  </td>
                  <td>
                    <select v-model="row.unit_measurement" class="form-control">
                      <option disabled value="">— Ед.изм —</option>
                      <option
                        v-for="u in units"
                        :key="u.id"
                        :value="u.name"
                      >
                        {{ u.name }} ({{ u.tare }}г)
                      </option>
                    </select>
                  </td>
                  <td>
                    <input
                      type="number"
                      v-model.number="row.brutto"
                      class="form-control"
                    />
                  </td>
                  <td>{{ calculateNetto(row).toFixed(2) }}</td>
                  <td>
                    <input
                      type="number"
                      v-model.number="row.price"
                      class="form-control"
                    />
                  </td>
                  <td>{{ calculateTotal(row).toFixed(2) }}</td>
                  <td>{{ calculateAdditionalExpense(row).toFixed(2) }}</td>
                  <td>{{ calculateCostPrice(row).toFixed(2) }}</td>
                  <td>
                    <button class="remove-btn" @click="removeProductRow(idx)">
                      ❌
                    </button>
                  </td>
                </tr>
                <!-- Summary row -->
                <tr class="summary-row">
                  <td colspan="4" class="summary-label">ИТОГО</td>
                  <td>{{ totalNetto.toFixed(2) }}</td>
                  <td>-</td>
                  <td>{{ totalSum.toFixed(2) }}</td>
                  <td>{{ totalExpenses.toFixed(2) }}</td>
                  <td>-</td>
                  <td>-</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Additional Expenses -->
        <div class="card mt-2">
          <div class="card-header flex-between">
            <h3>Доп. Расходы</h3>
            <button class="action-btn" @click="addExpenseRow">➕ Добавить</button>
          </div>
          <div class="card-body">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Название</th>
                  <th>Сумма</th>
                  <th>Удалить</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(exp, eidx) in expenses" :key="exp._key">
                  <td>
                    <select
                      v-model="exp.selectedExpenseId"
                      class="form-control"
                      @change="onExpenseSelect(exp)"
                    >
                      <option disabled value="">— Расход —</option>
                      <option
                        v-for="exRef in allExpenses"
                        :key="exRef.id"
                        :value="exRef.id"
                      >
                        {{ exRef.name }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <input
                      type="number"
                      v-model.number="exp.amount"
                      class="form-control"
                    />
                  </td>
                  <td>
                    <button class="remove-btn" @click="removeExpense(eidx)">
                      ❌
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="modal-footer">
        <button class="save-btn" @click="saveIncomeDoc" :disabled="isSubmitting">
          {{ isSubmitting ? "Сохранение..." : "Сохранить" }}
        </button>
        <button class="cancel-btn" @click="$emit('close')">Отмена</button>
        <div v-if="message" :class="['feedback-message', messageType]">
          {{ message }}
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, computed, onMounted } from "vue";
  import axios from "axios";

  export default {
    name: "EditIncomeModal",
    props: {
      documentId: {
        type: Number,
        required: true,
      },
    },
    setup(props, { emit }) {
      // Document header
      const documentHeader = ref({
        id: null,
        provider_id: "",
        document_date: "",
        to_warehouse_id: null,
      });

      // Items & expenses
      const productRows = ref([]);
      const expenses = ref([]);

      // References
      const providers = ref([]);
      const warehouses = ref([]);
      const products = ref([]);
      const units = ref([]);
      const allExpenses = ref([]);

      // UI
      const isSubmitting = ref(false);
      const message = ref("");
      const messageType = ref("");

      onMounted(async () => {
        await fetchReferences();
        await loadDocumentData(props.documentId);
      });

      // 1) References
      async function fetchReferences() {
        try {
          const { data } = await axios.get("/api/getWarehouseDetails");
          providers.value   = data.providers || [];
          warehouses.value  = data.warehouses || [];
          products.value    = data.product_sub_cards || [];
          units.value       = data.unit_measurements || [];
          allExpenses.value = data.expenses || [];
        } catch (err) {
          console.error("Error loading references:", err);
        }
      }

      // 2) Load Document
      async function loadDocumentData(docId) {
        try {
          const { data } = await axios.get(`/api/documents/${docId}`);
          // e.g. data = {id, provider_id, document_date, to_warehouse_id, document_items, expenses, ...}

          documentHeader.value.id            = data.id;
          documentHeader.value.provider_id   = data.provider_id || "";
          // fix date for <input type="date">
          if (data.document_date && data.document_date.length >= 10) {
            documentHeader.value.document_date = data.document_date.substring(0, 10);
          }
          documentHeader.value.to_warehouse_id = data.to_warehouse_id || null;
          
          // Map doc items => productRows
          productRows.value = data.document_items.map(item => ({
            _key: item.id,                  // used as the Vue key
            id: item.id,                    // used by the backend to update or delete
            product_subcard_id: item.product_subcard_id,
            quantity: item.quantity,
            brutto: item.brutto,
            unit_measurement: item.unit_measurement,
            price: item.price,
            additional_expenses: item.additional_expenses,
          }));

          // Map doc expenses => expenses
          expenses.value = data.expenses.map(e => ({
            _key: e.id,
            id: e.id,
            selectedExpenseId: e.expense_id || e.id,
            name: e.name,
            amount: e.amount,
          }));
        } catch (err) {
          console.error("Error loading doc data:", err);
        }
      }

      // 3) Manage item rows
      function addProductRow() {
        productRows.value.push({
          _key: Date.now(),
          id: null, // no DB row yet => create new on save
          product_subcard_id: null,
          quantity: 0,
          brutto: 0,
          unit_measurement: null,
          price: 0,
        });
      }
      function removeProductRow(idx) {
        productRows.value.splice(idx, 1);
      }

      // 4) Manage expense rows
      function addExpenseRow() {
        expenses.value.push({
          _key: Date.now(),
          id: null,
          selectedExpenseId: null,
          name: "",
          amount: 0,
        });
      }
      function removeExpense(i) {
        expenses.value.splice(i, 1);
      }
      function onExpenseSelect(row) {
        const found = allExpenses.value.find(x => x.id === row.selectedExpenseId);
        if (found) row.name = found.name;
      }

      // 5) Calculations
      function parseNumber(val) {
        const num = parseFloat(val);
        return Number.isNaN(num) ? 0 : num;
      }

      function calculateNetto(row) {
        const unit = units.value.find(u => u.name === row.unit_measurement) || { tare: 0 };
        const tareKg = parseNumber(unit.tare) / 1000;
        return parseNumber(row.brutto) - parseNumber(row.quantity) * tareKg;
      }
      function calculateTotal(row) {
        return calculateNetto(row) * parseNumber(row.price);
      }
      function calculateAdditionalExpense(row) {
        const totalQty = productRows.value.reduce((acc, r) => acc + parseNumber(r.quantity), 0);
        const totalExp = expenses.value.reduce((acc, e) => acc + parseNumber(e.amount), 0);
        if (!totalQty) return 0;
        return (totalExp / totalQty) * parseNumber(row.quantity);
      }
      function calculateCostPrice(row) {
        const totalCost = calculateTotal(row) + calculateAdditionalExpense(row);
        const qty = parseNumber(row.quantity);
        return qty > 0 ? (totalCost / qty) : 0;
      }

      // 6) Summaries
      const totalNetto = computed(() =>
        productRows.value.reduce((acc, r) => acc + calculateNetto(r), 0)
      );
      const totalSum = computed(() =>
        productRows.value.reduce((acc, r) => acc + calculateTotal(r), 0)
      );
      const totalExpenses = computed(() =>
        expenses.value.reduce((acc, e) => acc + parseNumber(e.amount), 0)
      );

      // 7) Save
      async function saveIncomeDoc() {
        isSubmitting.value = true;
        message.value = "";
        messageType.value = "";

        try {
          // Build items payload
          const productsPayload = productRows.value.map(r => ({
            id: r.id,  // existing doc item row => update; if null => create
            product_subcard_id: r.product_subcard_id,
            quantity: r.quantity,
            brutto: r.brutto,
            netto: calculateNetto(r),
            unit_measurement: r.unit_measurement,
            price: r.price,
            total_sum: calculateTotal(r),
            additional_expenses: calculateAdditionalExpense(r),
            cost_price: calculateCostPrice(r),
          }));

          // Build expenses payload
          const expensesPayload = expenses.value.map(ex => ({
            id: ex.id,  // if existing expense row => update; if null => create
            expense_id: ex.selectedExpenseId,
            name: ex.name,
            amount: ex.amount,
          }));

          const payload = {
            provider_id: documentHeader.value.provider_id,
            document_date: documentHeader.value.document_date,
            assigned_warehouse_id: documentHeader.value.to_warehouse_id,
            products: productsPayload,
            expenses: expensesPayload,
          };

          // Your endpoint => /api/documents/:id
          await axios.put(`/api/documents/${documentHeader.value.id}`, payload);

          message.value = "Успешно сохранено!";
          messageType.value = "success";
          emit("saved");
        } catch (err) {
          console.error("Error saving doc:", err);
          message.value = "Ошибка при сохранении.";
          messageType.value = "error";
        } finally {
          isSubmitting.value = false;
        }
      }

      return {
        documentHeader,
        productRows,
        expenses,
        providers,
        warehouses,
        products,
        units,
        allExpenses,
        isSubmitting,
        message,
        messageType,

        totalNetto,
        totalSum,
        totalExpenses,

        fetchReferences,
        loadDocumentData,
        addProductRow,
        removeProductRow,
        addExpenseRow,
        removeExpense,
        onExpenseSelect,
        parseNumber,
        calculateNetto,
        calculateTotal,
        calculateAdditionalExpense,
        calculateCostPrice,
        saveIncomeDoc,
      };
    },
  };
  </script>

  <style scoped>
  /* Example styles */
  .edit-income-modal {
    background-color: #fff;
    width: 900px;
    max-width: 90%;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    margin: 20px auto;
    position: relative;
  }
  .modal-header {
    background-color: #0288d1;
    color: #fff;
    padding: 16px;
    position: relative;
  }
  .close-btn {
    position: absolute;
    top: 12px;
    right: 16px;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 20px;
    cursor: pointer;
  }
  .modal-body {
    padding: 16px;
  }
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px;
    border-top: 1px solid #ddd;
  }

  .card {
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-bottom: 12px;
    background-color: #fefefe;
  }
  .card-header {
    background-color: #f1f1f1;
    padding: 8px 12px;
  }
  .mt-2 {
    margin-top: 10px;
  }
  .flex-between {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .styled-table {
    width: 100%;
    border-collapse: collapse;
  }
  .styled-table thead {
    background-color: #0288d1;
    color: #fff;
  }
  .styled-table th,
  .styled-table td {
    border: 1px solid #ddd;
    text-align: center;
    padding: 8px;
  }
  .summary-row td {
    background-color: #fafafa;
    font-weight: bold;
  }
  .summary-label {
    text-align: right;
  }

  .form-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }
  .form-group {
    flex: 1;
    min-width: 180px;
  }
  .form-control {
    width: 100%;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
  }

  .action-btn {
    background-color: #0288d1;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    cursor: pointer;
  }
  .remove-btn {
    background-color: #f44336;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 6px 8px;
    cursor: pointer;
  }
  .save-btn {
    background-color: #0288d1;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    cursor: pointer;
  }
  .cancel-btn {
    background-color: #9e9e9e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    cursor: pointer;
  }

  .feedback-message {
    margin-left: auto;
    font-weight: bold;
    padding: 6px 8px;
    border-radius: 4px;
  }
  .success {
    background-color: #d4edda;
    color: #155724;
  }
  .error {
    background-color: #f8d7da;
    color: #721c24;
  }
  </style>
