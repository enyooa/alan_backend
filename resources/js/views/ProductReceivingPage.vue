<template>
  <div class="full-page">
    <main class="content">
      <h2 class="page-title">Поступление товара</h2>

      <!-- Card for Provider & Date & Warehouse -->
      <div class="card">
        <div class="card-header">
          <h3>Выберите поставщика, дату и склад</h3>
        </div>
        <div class="card-body">
          <div class="flex-row">
            <!-- 1) Поставщик -->
            <div class="dropdown">
              <label for="provider" class="field-label">Поставщик</label>
              <select
                v-model="selectedProviderId"
                id="provider"
                class="dropdown-select"
              >
                <option disabled value="">— Выберите поставщика —</option>
                <option
                  v-for="provider in providers"
                  :key="provider.id"
                  :value="provider.id"
                >
                  {{ provider.name }}
                </option>
              </select>
            </div>

            <!-- 2) Дата -->
            <div class="dropdown">
              <label for="date" class="field-label">Дата</label>
              <input
                type="date"
                v-model="selectedDate"
                id="date"
                class="dropdown-select"
              />
            </div>

            <!-- 3) Склад поступления -->
            <div class="dropdown">
              <label for="warehouse" class="field-label">Склад поступления</label>
              <select
                v-model="selectedWarehouseId"
                id="warehouse"
                class="dropdown-select"
              >
                <option disabled value="">— Выберите склад —</option>
                <option
                  v-for="w in warehouses"
                  :key="w.id"
                  :value="w.id"
                >
                  {{ w.name }}
                </option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- Card for Product Table -->
      <div class="card mt-3">
        <div class="card-header flex-between">
          <h3>Товары</h3>
          <button @click="addProductRow" class="action-btn add-row-btn">
            ➕ Добавить строку
          </button>
        </div>
        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr>
                <th>Товар</th>
                <th>Кол-во тары</th>
                <th>Ед. изм / Тара</th>
                <th>Брутто</th>
                <th>Нетто</th>
                <th>Цена</th>
                <th>Сумма</th>
                <th>Доп. расход</th>
                <th>Себестоимость</th>
                <th>Удалить</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, index) in productRows"
                :key="row._key"
                class="table-row"
              >
                <td>
                  <select
                    v-model="row.product_subcard_id"
                    class="table-select"
                  >
                    <option disabled value="">Выберите товар</option>
                    <option
                      v-for="product in products"
                      :key="product.id"
                      :value="product.id"
                    >
                      {{ product.name }}
                    </option>
                  </select>
                </td>
                <td>
                  <input
                    v-model.number="row.quantity"
                    type="number"
                    class="table-input"
                    placeholder="Кол-во тары"
                  />
                </td>
                <td>
                  <select v-model="row.unit_measurement" class="table-select">
                    <option disabled value="">
                      Выберите ед. изм / Тара
                    </option>
                    <option
                      v-for="unit in units"
                      :key="unit.id || (unit.name + unit.tare)"
                      :value="unit.name"
                    >
                      {{ unit.name }} ({{ unit.tare }} г)
                    </option>
                  </select>
                </td>
                <td>
                  <input
                    v-model.number="row.brutto"
                    type="number"
                    class="table-input"
                    placeholder="Брутто"
                  />
                </td>
                <td>{{ calculateNetto(row).toFixed(2) }}</td>
                <td>
                  <input
                    v-model.number="row.price"
                    type="number"
                    class="table-input"
                    placeholder="Цена"
                  />
                </td>
                <td>{{ calculateTotal(row).toFixed(2) }}</td>
                <td>{{ calculateAdditionalExpense(row).toFixed(2) }}</td>
                <td>{{ formatCostPrice(calculateCostPrice(row)) }}</td>
                <td>
                  <button @click="removeProductRow(index)" class="remove-btn">
                    ❌
                  </button>
                </td>
              </tr>

              <!-- Summary Row -->
              <tr class="summary-row">
                <td colspan="3" class="summary-label"><strong>ИТОГО</strong></td>
                <td>-</td>
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

      <!-- Card for Additional Expenses -->
      <div class="card mt-3">
        <div class="card-header flex-between">
          <h3>Дополнительные расходы</h3>
          <button @click="addExpenseRow" class="action-btn add-row-btn">
            ➕ Добавить расход
          </button>
        </div>
        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr>
                <th>Наименование</th>
                <th>Сумма</th>
                <th>Удалить</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(expense, idx) in expenses"
                :key="expense._key"
                class="table-row"
              >
                <td>
                  <select
                    v-model="expense.selectedExpenseId"
                    class="table-select"
                    @change="onExpenseSelect(expense)"
                  >
                    <option disabled value="">--- Выберите расход ---</option>
                    <option
                      v-for="ex in allExpenses"
                      :key="ex.id"
                      :value="ex.id"
                    >
                      {{ ex.name }}
                    </option>
                  </select>
                </td>
                <td>
                  <input
                    v-model.number="expense.amount"
                    type="number"
                    class="table-input"
                    placeholder="Сумма"
                  />
                </td>
                <td>
                  <button @click="removeExpense(idx)" class="remove-btn">
                    ❌
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Submit Button & Message -->
      <div class="mt-3">
        <button
          @click="submitProductReceivingData"
          class="action-btn save-btn"
          :disabled="isSubmitting"
        >
          {{ isSubmitting ? "⏳ Сохранение..." : "Сохранить" }}
        </button>
      </div>
      <div v-if="message" :class="['feedback-message', messageType]">
        {{ message }}
      </div>
    </main>
  </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import axios from "axios";

export default {
  name: "ProductReceivingPage",
  setup() {
    // Basic references
    const selectedProviderId = ref("");
    const selectedDate = ref("");

    // Warehouses (instead of adminOrStoragers)
    const warehouses = ref([]);
    const selectedWarehouseId = ref("");

    const providers = ref([]);
    const products = ref([]);
    const units = ref([]);

    // All known expenses from /api/references/expense
    const allExpenses = ref([]);

    // The user-chosen expense rows
    const expenses = ref([]);

    // Product rows
    const productRows = ref([
      {
        _key: Date.now(),
        product_subcard_id: null,
        unit_measurement: null,
        quantity: 0,
        brutto: 0,
        price: 0,
      },
    ]);

    // Feedback message
    const message = ref("");
    const messageType = ref("");
    const isSubmitting = ref(false);

    // ======= Lifecycle =======
    onMounted(async () => {
      await fetchProviders();
      await fetchProducts();
      await fetchUnits();
      await fetchAllExpenses();
      await fetchWarehouses(); // <--- load the warehouse data
    });

    // ======= Fetching Data =======
    async function fetchProviders() {
      try {
        const { data } = await axios.get("/api/providers");
        providers.value = data;
      } catch (error) {
        console.error("Error fetching providers:", error);
      }
    }
    async function fetchProducts() {
      try {
        const { data } = await axios.get("/api/product_subcards");
        products.value = data;
      } catch (error) {
        console.error("Error fetching products:", error);
      }
    }
    async function fetchUnits() {
      try {
        const { data } = await axios.get("/api/unit-measurements");
        units.value = data;
      } catch (error) {
        console.error("Error fetching units:", error);
      }
    }
    async function fetchAllExpenses() {
      try {
        const { data } = await axios.get("/api/references/expense");
        allExpenses.value = data;
      } catch (error) {
        console.error("Error fetching allExpenses:", error);
      }
    }

    // New: fetch the warehouses
    async function fetchWarehouses() {
      try {
        const { data } = await axios.get("/api/getWarehouses");
        warehouses.value = data;
      } catch (error) {
        console.error("Error fetching warehouses:", error);
      }
    }

    // ======= Product Rows Logic =======
    function addProductRow() {
      productRows.value.push({
        _key: Date.now() + Math.random(),
        product_subcard_id: null,
        unit_measurement: null,
        quantity: 0,
        brutto: 0,
        price: 0,
      });
    }
    function removeProductRow(index) {
      productRows.value.splice(index, 1);
    }

    // ======= Expense Rows Logic =======
    function addExpenseRow() {
      expenses.value.push({
        _key: Date.now() + Math.random(),
        selectedExpenseId: "",
        amount: 0,
        name: "",
      });
    }
    function removeExpense(idx) {
      expenses.value.splice(idx, 1);
    }
    function onExpenseSelect(row) {
      if (!row.selectedExpenseId) return;
      const found = allExpenses.value.find(
        (ex) => ex.id === row.selectedExpenseId
      );
      if (found) {
        row.amount = found.amount || 0;
        row.name = found.name;
      } else {
        row.amount = 0;
        row.name = "";
      }
    }

    // ======= Calculations =======
    function calculateNetto(row) {
      const unit = units.value.find((u) => u.name === row.unit_measurement) || {
        tare: 0,
      };
      // Convert grams to kg
      const tareWeight = (unit.tare || 0) / 1000;
      return (row.brutto || 0) - (row.quantity || 0) * tareWeight;
    }
    function calculateTotal(row) {
      return calculateNetto(row) * (row.price || 0);
    }
    function calculateAdditionalExpense(row) {
      const totalQuantity = productRows.value.reduce(
        (sum, r) => sum + (r.quantity || 0),
        0
      );
      const totalExp = expenses.value.reduce(
        (sum, e) => sum + (e.amount || 0),
        0
      );
      const expensePerQty = totalQuantity > 0 ? totalExp / totalQuantity : 0;
      return expensePerQty * (row.quantity || 0);
    }
    function calculateCostPrice(row) {
      const qty = row.quantity || 1;
      const totalCost = calculateTotal(row) + calculateAdditionalExpense(row);
      return totalCost / qty;
    }
    function formatCostPrice(val) {
      if (!val) return "0.00";
      return val.toFixed(2);
    }

    // ======= Summaries =======
    const totalNetto = computed(() =>
      productRows.value.reduce((sum, r) => sum + calculateNetto(r), 0)
    );
    const totalSum = computed(() =>
      productRows.value.reduce((sum, r) => sum + calculateTotal(r), 0)
    );
    const totalExpenses = computed(() =>
      expenses.value.reduce((sum, e) => sum + (e.amount || 0), 0)
    );

    // ======= Submit Logic =======
    async function submitProductReceivingData() {
      isSubmitting.value = true;
      try {
        // Build products array
        const receivingData = productRows.value.map((row) => ({
          product_subcard_id: row.product_subcard_id,
          unit_measurement: row.unit_measurement,
          quantity: row.quantity,
          brutto: row.brutto,
          netto: calculateNetto(row),
          price: row.price,
          total_sum: calculateTotal(row),
          additional_expenses: calculateAdditionalExpense(row),
          cost_price: calculateCostPrice(row),
        }));

        // Build expenses array
        const expenseData = expenses.value.map((exp) => ({
          expense_id: exp.selectedExpenseId,
          name: exp.name,
          amount: exp.amount,
        }));

        // Send to your endpoint (example: /api/receivingBulkStore)
        const payload = {
          provider_id: selectedProviderId.value,
          document_date: selectedDate.value,
          assigned_warehouse_id: selectedWarehouseId.value, // <--- use the warehouse ID
          products: receivingData,
          expenses: expenseData,
        };

        await axios.post("/api/receivingBulkStore", payload);

        message.value = "Данные успешно сохранены!";
        messageType.value = "success";

        // Reset
        productRows.value = [
          {
            _key: Date.now(),
            product_subcard_id: null,
            unit_measurement: null,
            quantity: 0,
            brutto: 0,
            price: 0,
          },
        ];
        expenses.value = [];
        selectedProviderId.value = "";
        selectedDate.value = "";
        selectedWarehouseId.value = "";
      } catch (error) {
        console.error("Error saving data:", error);
        message.value = "Ошибка при сохранении данных.";
        messageType.value = "error";
      } finally {
        isSubmitting.value = false;
      }
    }

    return {
      // Warehouse references
      warehouses,
      selectedWarehouseId,

      // Providers & date
      selectedProviderId,
      selectedDate,
      providers,

      // Data
      products,
      units,
      allExpenses,
      expenses,
      productRows,
      message,
      messageType,
      isSubmitting,

      // Methods
      fetchProviders,
      fetchProducts,
      fetchUnits,
      fetchAllExpenses,
      fetchWarehouses,
      addProductRow,
      removeProductRow,
      addExpenseRow,
      removeExpense,
      onExpenseSelect,
      calculateNetto,
      calculateTotal,
      calculateAdditionalExpense,
      calculateCostPrice,
      formatCostPrice,
      totalNetto,
      totalSum,
      totalExpenses,
      submitProductReceivingData,
    };
  },
};
</script>

<style scoped>
/* Full Page Container */
.full-page {
  width: 100vw;
  min-height: 100vh;
  background-color: #f5f5f5;
}

/* Content */
.content {
  width: 100%;
  max-width: 1100px;
  margin: 0 auto;
  padding: 20px;
}

.page-title {
  color: #0288d1;
  text-align: center;
  margin-bottom: 20px;
  font-size: 1.5rem;
}

/* Cards */
.card {
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  margin-bottom: 20px;
  overflow: hidden;
}

.card-header {
  background-color: #f1f1f1;
  padding: 12px 16px;
  border-bottom: 1px solid #ddd;
}

.card-header h3 {
  margin: 0;
  color: #333;
}

.card-body {
  padding: 16px;
}

.mt-3 {
  margin-top: 20px;
}

.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.flex-row {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}

/* Field Labels */
.field-label {
  font-weight: bold;
  color: #555;
  margin-bottom: 6px;
  display: inline-block;
}

/* Form Elements */
.dropdown-select {
  width: 100%;
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.table-select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

.table-input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

/* Styled Table */
.styled-table {
  width: 100%;
  border-collapse: collapse;
}

.styled-table thead tr {
  background-color: #0288d1;
  color: #fff;
}

.styled-table th,
.styled-table td {
  padding: 10px;
  border: 1px solid #ddd;
  text-align: center;
}

.summary-row td {
  background-color: #f8f8f8;
  font-weight: bold;
}

.summary-label {
  text-align: right;
}

/* Buttons */
.action-btn {
  background-color: #0288d1;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 10px 18px;
  cursor: pointer;
  transition: background-color 0.3s;
  font-size: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.action-btn:hover {
  background-color: #026ca0;
}

.add-row-btn {
  font-size: 15px;
}

.save-btn {
  margin-top: 8px;
  width: 100%;
}

/* Remove Button */
.remove-btn {
  background-color: #f44336;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 8px 10px;
  cursor: pointer;
  font-size: 14px;
}

.remove-btn:hover {
  background-color: #d32f2f;
}

/* Messages */
.feedback-message {
  margin-top: 20px;
  text-align: center;
  font-weight: bold;
  padding: 10px;
  border-radius: 8px;
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
