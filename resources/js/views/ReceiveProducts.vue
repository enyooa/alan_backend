<template>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />

    <!-- Main Content -->
    

      <div class="main-content">
        <Header />
      
        <main class="content">
          <h2 class="page-title">Поступление товара</h2>
      
          <!-- Provider & Date Row -->
          <div class="dropdown-container">
            <div class="dropdown">
              <label for="provider">Выберите поставщика:</label>
              <select v-model="selectedProviderId" id="provider" class="select">
                <option disabled value="">Выберите поставщика</option>
                <option v-for="provider in providers" :key="provider.id" :value="provider.id">
                  {{ provider.name }}
                </option>
              </select>
            </div>
      
            <div class="dropdown">
              <label for="date">Выберите дату:</label>
              <input type="date" v-model="selectedDate" id="date" class="select" />
            </div>
          </div>
      
          <!-- Product Table -->
          <div class="table-container">
            <table class="product-table">
              <thead>
                <tr>
                  <th>Товар</th>
                  <th>Кол-во тары</th>
                  <th>Ед. изм / Тара</th>
                  <th>Брутто</th>
                  <th>Нетто</th>
                  <th>Цена</th>
                  <th>Сумма</th>
                  <th>Допрасход</th>
                  <th>Себестоимость</th>
                  <th>Удалить</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, index) in productRows" :key="index">
                  <td>
                    <select v-model="row.product_subcard_id" class="select">
                      <option v-for="product in products" :key="product.id" :value="product.id">
                        {{ product.name }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <input v-model.number="row.quantity" type="number" class="input-field" />
                  </td>
                  <td>
                    <select v-model="row.unit_measurement" class="select">
                      <option v-for="unit in units" :key="unit.name" :value="unit.name">
                        {{ unit.name }} ({{ unit.tare }} г)
                      </option>
                    </select>
                  </td>
                  <td>
                    <input v-model.number="row.brutto" type="number" class="input-field" />
                  </td>
                  <td>{{ calculateNetto(row).toFixed(2) }}</td>
                  <td>
                    <input v-model.number="row.price" type="number" class="input-field" />
                  </td>
                  <td>{{ calculateTotal(row).toFixed(2) }}</td>
                  <td>{{ calculateAdditionalExpense(row).toFixed(2) }}</td>
                  <td>{{ calculateCostPrice(row).toFixed(2) }}</td>
                  <td>
                    <button @click="removeProductRow(index)" class="remove-btn">❌</button>
                  </td>
                </tr>
      
                <!-- ИТОГО Summary Row -->
                <tr class="summary-row">
                  <td colspan="3"><strong>ИТОГО</strong></td>
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
      
          <button @click="addProductRow" class="add-btn">Добавить строку</button>
      
          <!-- Expenses Table -->
          <div class="table-container">
            <h3>Дополнительные расходы</h3>
            <table class="product-table">
              <thead>
                <tr>
                  <th>Наименование</th>
                  <th>Сумма</th>
                  <th>Удалить</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(expense, index) in expenses" :key="index">
                  <td><input v-model="expense.name" class="input-field" /></td>
                  <td><input v-model.number="expense.amount" type="number" class="input-field" /></td>
                  <td><button @click="removeExpense(index)" class="remove-btn">❌</button></td>
                </tr>
              </tbody>
            </table>
          </div>
          <button @click="addExpenseRow" class="add-btn">Добавить расход</button>

          <button @click="submitProductReceivingData" class="submit-btn">Сохранить</button>
        </main>
      </div>
      
    </div>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import axios from "axios";
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue";

export default {
  components: { Sidebar, Header },
  setup() {
    const isSidebarOpen = ref(true);
    const selectedProviderId = ref(null);
    const selectedDate = ref(null);
    const providers = ref([]);
    const products = ref([]);
    const units = ref([]);
    const expenses = ref([]);
    const productRows = ref([
      { product_subcard_id: null, unit_measurement: null, quantity: 0, brutto: 0, price: 0 },
    ]);
    const message = ref("");
    const messageType = ref("");
    const isSubmitting = ref(false);

    // Fetch data on mounted
    onMounted(async () => {
      await fetchProviders();
      await fetchProducts();
      await fetchUnits();
    });

    const fetchProviders = async () => {
      try {
        const response = await axios.get("/api/providers");
        providers.value = response.data;
      } catch (error) {
        console.error("Error fetching providers:", error);
      }
    };

    const fetchProducts = async () => {
      try {
        const response = await axios.get("/api/product_subcards");
        products.value = response.data;
      } catch (error) {
        console.error("Error fetching products:", error);
      }
    };

    const fetchUnits = async () => {
      try {
        const response = await axios.get("/api/unit-measurements");
        units.value = response.data;
      } catch (error) {
        console.error("Error fetching units:", error);
      }
    };

    const addProductRow = () => {
      productRows.value.push({
        product_subcard_id: null,
        unit_measurement: null,
        quantity: 0,
        brutto: 0,
        price: 0,
      });
    };

    const removeProductRow = (index) => {
      productRows.value.splice(index, 1);
    };

    const addExpenseRow = () => {
      expenses.value.push({ name: "", amount: 0 });
    };

    const removeExpense = (index) => {
      expenses.value.splice(index, 1);
    };

    // Compute Netto Weight
    const calculateNetto = (row) => {
      const unit = units.value.find((u) => u.name === row.unit_measurement) || { tare: 0 };
      const tareWeight = (unit.tare || 0) / 1000; // Convert to KG
      return (row.brutto || 0) - (row.quantity || 0) * tareWeight;
    };

    // Compute Row Total
    const calculateTotal = (row) => {
      return calculateNetto(row) * (row.price || 0);
    };

    // Compute Additional Expense for Each Row
    const calculateAdditionalExpense = (row) => {
      const totalQuantity = productRows.value.reduce((sum, r) => sum + (r.quantity || 0), 0);
      const totalExpenses = expenses.value.reduce((sum, e) => sum + (e.amount || 0), 0);
      const expensePerQuantity = totalQuantity > 0 ? totalExpenses / totalQuantity : 0;
      return expensePerQuantity * (row.quantity || 0);
    };

    // Compute Cost Price per Unit
    const calculateCostPrice = (row) => {
      const quantity = row.quantity || 1;
      return quantity > 0 ? (calculateTotal(row) + calculateAdditionalExpense(row)) / quantity : 0;
    };

    // Compute Summary Totals
    const totalNetto = computed(() => {
      return productRows.value.reduce((sum, row) => sum + calculateNetto(row), 0);
    });

    const totalSum = computed(() => {
      return productRows.value.reduce((sum, row) => sum + calculateTotal(row), 0);
    });

    const totalExpenses = computed(() => {
      return expenses.value.reduce((sum, expense) => sum + (expense.amount || 0), 0);
    });

    // Submit Data
    const submitProductReceivingData = async () => {
      isSubmitting.value = true;
      const receivingData = productRows.value.map((row) => ({
        provider_id: selectedProviderId.value,
        product_subcard_id: row.product_subcard_id,
        unit_measurement: row.unit_measurement,
        quantity: row.quantity,
        brutto: row.brutto,
        netto: calculateNetto(row),
        price: row.price,
        total_sum: calculateTotal(row),
        additional_expense: calculateAdditionalExpense(row),
        cost_price: calculateCostPrice(row),
        date: selectedDate.value || new Date().toISOString(),
      }));

      try {
        await axios.post("/api/product-receiving", { products: receivingData, expenses: expenses.value });
        message.value = "Данные успешно сохранены!";
        messageType.value = "success";
        productRows.value = [{ product_subcard_id: null, unit_measurement: null, quantity: 0, brutto: 0, price: 0 }];
        expenses.value = [];
        selectedProviderId.value = null;
        selectedDate.value = null;
      } catch (error) {
        message.value = "Ошибка при сохранении данных.";
        messageType.value = "error";
      } finally {
        isSubmitting.value = false;
      }
    };

    return {
      isSidebarOpen,
      selectedProviderId,
      selectedDate,
      providers,
      products,
      units,
      productRows,
      expenses,
      addProductRow,
      removeProductRow,
      addExpenseRow,
      removeExpense,
      submitProductReceivingData,
      calculateNetto,
      calculateTotal,
      calculateAdditionalExpense,
      calculateCostPrice,
      totalNetto,
      totalSum,
      totalExpenses,
      message,
      messageType,
      isSubmitting,
    };
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
  display: flex;
  flex-direction: column;
  background-color: #f5f5f5;
}

.content {
  width: 100%;
  max-width: 900px;
  margin: 0 auto;
}

.page-title {
  color: #0288d1;
  text-align: center;
  font-size: 1.5rem;
  margin-bottom: 20px;
}

.select {
  width: 100%;
  padding: 12px;
  margin-top: 8px;
  border: 1px solid #ddd;
  border-radius: 5px;
}

.input-field {
  padding: 12px;
  border: 1px solid #ddd;
  border-radius: 5px;
  width: 100%;
}

.product-table {
  width: 100%;
  margin-top: 20px;
  border-collapse: collapse;
  table-layout: fixed; /* Fix column widths */
}

.product-table th,
.product-table td {
  padding: 10px;
  text-align: center;
  border: 1px solid #ddd;
}

.product-table th {
  background-color: #0288d1;
  color: white;
}

.product-table td select,
.product-table td input {
  width: 100%; /* Make inputs and selects take full column width */
}

.product-table td {
  text-align: left; /* Align text to the left for better readability */
}

.product-table td select {
  font-size: 14px; /* Smaller font size for dropdowns */
}

.product-table td input {
  font-size: 14px; /* Smaller font size for inputs */
}

button {
  background-color: #0288d1;
  color: white;
  padding: 12px;
  border: none;
  border-radius: 5px;
  width: 100%;
  cursor: pointer;
}

button:disabled {
  background-color: #aaa;
}

button:hover:not(:disabled) {
  background-color: #026ca0;
}

.feedback-message {
  margin-top: 20px;
  font-weight: bold;
  text-align: center;
}

.success {
  color: green;
}

.error {
  color: red;
}

.add-btn,
.submit-btn {
  background-color: #0288d1;
  padding: 12px;
  margin-top: 20px;
  width: 100%;
}

.add-btn:hover,
.submit-btn:hover {
  background-color: #026ca0;
}

.remove-btn {
  background-color: red;
  color: white;
  padding: 8px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.remove-btn:hover {
  background-color: darkred;
}
</style>