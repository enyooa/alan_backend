<template>
  <div class="inventory-check-container">
    <h2 class="page-title">Инвентаризация</h2>

    <!-- Cards Container: Left for the Check Form, Right for the Summary -->
    <div class="cards-container">
      <!-- Inventory Check Form Card -->
      <div class="card check-form-card">
        <div class="card-header flex-between">
          <h3>Инвентаризация товаров</h3>
          <button class="action-btn" @click="addRow">➕ Добавить строку</button>
        </div>
        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr>
                <th>Наименование товара</th>
                <th>Партия</th>
                <th>Фактическое количество</th>
                <th>Остаток на складе</th>
                <th>Дата</th>
                <th>Удалить</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(row, index) in inventoryRows" :key="row._key || index">
                <!-- Product Dropdown -->
                <td>
                  <select v-model="row.product_subcard_id" class="table-select">
                    <option value="">— Выбрать товар —</option>
                    <option v-for="product in products" :key="product.id" :value="product.id">
                      {{ product.name }}
                    </option>
                  </select>
                </td>
                <!-- Batch Dropdown -->
                <td>
                  <select
                    v-if="row.product_subcard_id && getBatchesForProduct(row.product_subcard_id).length"
                    v-model="row.selectedBatchId"
                    class="table-select"
                  >
                    <option value="">— Выберите партию —</option>
                    <option v-for="batch in getBatchesForProduct(row.product_subcard_id)"
                            :key="batch.id" :value="batch.id">
                      {{ batch.quantity }} {{ batch.unit_measurement }} ({{ batch.date }})
                    </option>
                  </select>
                  <span v-else>-</span>
                </td>
                <!-- Actual Amount -->
                <td>
                  <input type="number" class="table-input" v-model.number="row.actual_amount" />
                </td>
                <!-- Remaining Inventory -->
                <td>
                  {{ subcardRemainderAndUnit(row.product_subcard_id) }}
                </td>
                <!-- Date -->
                <td>
                  <input type="date" class="table-input" v-model="row.date" />
                </td>
                <!-- Remove Button -->
                <td>
                  <button class="remove-btn" @click="removeRow(index)">❌</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Inventory Summary Card (Right Side) -->
      <div class="card inventory-summary-card">
        <div class="card-header">
          <h3>Остаток товаров</h3>
        </div>
        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr>
                <th>Наименование товара</th>
                <th>Остаток</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="prod in products" :key="prod.id">
                <td>{{ prod.name }}</td>
                <td>
                  {{ prod.total_quantity !== null ? prod.total_quantity + ' ' + (prod.unit_measurement || '') : '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Submit Button -->
    <div class="mt-3">
      <button class="action-btn save-btn" @click="submitInventoryCheck">
        Сохранить
      </button>
    </div>

    <!-- Feedback Message -->
    <div v-if="message" :class="['feedback-message', messageType]">
      {{ message }}
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from "vue";
import axios from "axios";

export default {
  name: "InventoryCheckPage",
  setup() {
    const inventoryRows = ref([]);
    const products = ref([]);
    const units = ref([]);
    const message = ref("");
    const messageType = ref("");

    onMounted(async () => {
      await fetchProducts();
      await fetchUnits();
      addRow();
    });

    const fetchProducts = async () => {
      try {
        const { data } = await axios.get("/api/product_subcards");
        // Expected format includes total_quantity, unit_measurement, and an array of batches.
        products.value = data;
      } catch (err) {
        console.error("Ошибка при загрузке товаров:", err);
      }
    };

    const fetchUnits = async () => {
      try {
        const { data } = await axios.get("/api/unit-measurements");
        units.value = data;
      } catch (err) {
        console.error("Ошибка при загрузке единиц измерения:", err);
      }
    };

    const addRow = () => {
      inventoryRows.value.push({
        _key: Date.now() + Math.random(),
        product_subcard_id: "",
        selectedBatchId: "", // New: store chosen batch ID
        unit_measurement: "",
        actual_amount: 0,
        date: new Date().toISOString().slice(0, 10),
      });
    };

    const removeRow = (idx) => {
      inventoryRows.value.splice(idx, 1);
    };

    const findProduct = (id) => {
      return products.value.find((prod) => prod.id == id) || null;
    };

    // Returns the batches array for the selected product
    const getBatchesForProduct = (productId) => {
      const product = findProduct(productId);
      return product && product.batches ? product.batches : [];
    };

    // Returns overall inventory string (e.g. "639 кг")
    const subcardRemainderAndUnit = (productId) => {
      if (!productId) return "-";
      const found = findProduct(productId);
      if (!found) return "-";
      const remainder = found.total_quantity || 0;
      const measure = found.unit_measurement || "";
      return measure ? `${remainder} ${measure}` : remainder;
    };

    const submitInventoryCheck = async () => {
      if (!inventoryRows.value.length) {
        alert("Добавьте хотя бы одну строку.");
        return;
      }
      const payload = inventoryRows.value.map(row => ({
        product_subcard_id: row.product_subcard_id,
        unit_measurement: row.unit_measurement,
        actual_amount: row.actual_amount,
        date: row.date,
        // Include batch_id if the user has selected a batch
        batch_id: row.selectedBatchId || null,
      }));
      try {
        await axios.post("/api/bulkStoreInventory", { inventory_checks: payload });
        message.value = "Инвентаризация сохранена!";
        messageType.value = "success";
        inventoryRows.value = [];
      } catch (err) {
        console.error("Ошибка при сохранении инвентаризации:", err);
        message.value = "Ошибка при сохранении инвентаризации.";
        messageType.value = "error";
      }
    };

    return {
      inventoryRows,
      products,
      units,
      message,
      messageType,
      addRow,
      removeRow,
      getBatchesForProduct,
      subcardRemainderAndUnit,
      submitInventoryCheck,
      findProduct,
    };
  },
};
</script>

<style scoped>
.inventory-check-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 20px;
}
.page-title {
  text-align: center;
  color: #0288d1;
  margin-bottom: 20px;
}

/* Cards Container: Two Cards Side-by-Side */
.cards-container {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}
.check-form-card {
  flex: 2;
}
.inventory-summary-card {
  flex: 1;
}

/* Card Styling */
.card {
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
  font-size: 16px;
}
.card-body {
  padding: 16px;
}

/* Table Styling */
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
  font-size: 14px;
}

/* Buttons */
.action-btn {
  background-color: #0288d1;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 10px 14px;
  cursor: pointer;
  font-size: 14px;
}
.action-btn:hover {
  background-color: #026ca0;
}
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
.save-btn {
  width: 100%;
  margin-top: 10px;
}

/* Inputs */
.table-select,
.table-input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

/* Feedback Message */
.feedback-message {
  margin-top: 20px;
  text-align: center;
  font-weight: bold;
  padding: 10px;
  border-radius: 8px;
  font-size: 14px;
}
.feedback-message.success {
  background-color: #d4edda;
  color: #155724;
}
.feedback-message.error {
  background-color: #f8d7da;
  color: #721c24;
}
.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}
</style>
