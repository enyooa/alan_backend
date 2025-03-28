<template>
  <div class="write-off-page-container">
    <h2 class="page-title">Списание (со склада)</h2>

    <!-- Card: От какого склада / Дата -->
    <div class="card">
      <div class="card-header">
        <h3>Склад и дата списания</h3>
      </div>
      <div class="card-body">
        <div class="top-row">
          <!-- Откуда (Warehouse) -->
          <div class="dropdown-column">
            <label class="dropdown-label">Откуда (Склад):</label>
            <select
              v-model="selectedSourceWarehouse"
              class="dropdown-select"
              @change="onSourceWarehouseChange"
            >
              <option value="">— выберите склад —</option>
              <option
                v-for="wh in warehouses"
                :key="wh.id"
                :value="wh.id"
              >
                {{ wh.name }}
              </option>
            </select>
          </div>

          <!-- Дата -->
          <div class="dropdown-column">
            <label class="dropdown-label">Дата:</label>
            <input
              type="date"
              v-model="selectedDate"
              class="dropdown-select"
            />
          </div>
        </div>
      </div>
    </div>

    <!-- Two cards: Left for writing off items, right for leftover info -->
    <div class="cards-container mt-3">
      <!-- Left card: items to be written off -->
      <div class="card card-writeoff">
        <div class="card-header flex-between">
          <h3>Товары для Списания</h3>
          <button class="action-btn" @click="addProductRow">
            ➕ Добавить строку
          </button>
        </div>
        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr>
                <th>Товар (ост.)</th>
                <th>Кол-во</th>
                <th>Ед. изм</th>
                <th>Удалить</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(row, idx) in productRows"
                :key="row._key"
              >
                <!-- Dropdown of leftover items from the selected warehouse -->
                <td>
                  <select
                    v-model="row.product_subcard_id"
                    class="table-select"
                    @change="onProductChange(row)"
                  >
                    <option disabled value="">
                      — Товар —
                    </option>
                    <option
                      v-for="left in leftovers"
                      :key="left.product_subcard_id"
                      :value="left.product_subcard_id"
                    >
                      {{ left.name }} ({{ formatNumber(left.balance) }})
                    </option>
                  </select>
                </td>

                <!-- Кол-во списания -->
                <td>
                  <input
                    type="number"
                    class="table-input"
                    v-model.number="row.quantity"
                    @change="onQuantityChange(row)"
                  />
                </td>

                <!-- Ед. изм -->
                <td>
                  <select
                    v-model="row.unit_measurement"
                    class="table-select"
                  >
                    <option disabled value="">—</option>
                    <option
                      v-for="u in units"
                      :key="u.id"
                      :value="u.name"
                    >
                      {{ u.name }}
                    </option>
                  </select>
                </td>

                <!-- Remove button -->
                <td>
                  <button class="remove-btn" @click="removeProductRow(idx)">
                    ❌
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Save button & message -->
        <div class="mt-2">
          <button class="action-btn save-btn" @click="saveWriteOff">
            Сохранить списание
          </button>
        </div>
        <div v-if="message" :class="['feedback-message', messageType]">
          {{ message }}
        </div>
      </div>

      <!-- Right card: leftover info for the selected warehouse -->
      <div class="card card-leftovers">
        <div class="card-header">
          <h3>Остатки на складе "{{ sourceWarehouseName }}"</h3>
        </div>
        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr>
                <th>Товар</th>
                <th>Остаток</th>
                <th>Ед. изм</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="left in leftovers" :key="left.product_subcard_id">
                <td>{{ left.name }}</td>
                <td>{{ formatNumber(left.balance) }}</td>
                <td>{{ left.unit_measurement || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- cards-container -->
  </div>
</template>

<script>
import axios from "axios";
import { ref, onMounted, computed } from "vue";

export default {
  name: "WarehouseWriteOffPage",
  setup() {
    // 1) Warehouses for "from which warehouse" selection
    const warehouses = ref([]);

    // 2) Leftovers from the selected warehouse
    const leftovers = ref([]);

    // 3) Units
    const units = ref([]);

    // 4) Selected warehouse & date
    const selectedSourceWarehouse = ref("");
    const selectedDate = ref("");

    // 5) productRows
    const productRows = ref([
      {
        _key: Date.now(),
        product_subcard_id: "",
        quantity: 0,
        unit_measurement: ""
      }
    ]);

    // Feedback
    const message = ref("");
    const messageType = ref("");

    // Formatting function to remove trailing zeros like 10.000 -> 10
    function formatNumber(value) {
      if (value === null || value === undefined) return "";
      const num = Number(value);
      if (Number.isNaN(num)) return value; // not a valid number

      // If effectively an integer, show no decimals
      if (Number.isInteger(num)) {
        return num.toString();
      }
      // Otherwise, show up to 3 decimals. Then remove trailing zeros by converting to float again.
      return parseFloat(num.toFixed(3)).toString();
    }

    // Computed: name of the selected warehouse
    const sourceWarehouseName = computed(() => {
      if (!selectedSourceWarehouse.value) return "—";
      const found = warehouses.value.find(w => w.id == selectedSourceWarehouse.value);
      if (!found) return "???";
      return found.name;
    });

    // On mount
    onMounted(() => {
      fetchWarehouses();
      fetchUnits();
    });

    // Fetch warehouses
    async function fetchWarehouses() {
      try {
        const resp = await axios.get("/api/getWarehouses");
        warehouses.value = resp.data;
      } catch (err) {
        console.error("Ошибка при загрузке складов:", err);
      }
    }

    // Fetch units
    async function fetchUnits() {
      try {
        const resp = await axios.get("/api/unit-measurements");
        units.value = resp.data;
      } catch (err) {
        console.error("Ошибка при загрузке единиц измерения:", err);
      }
    }

    // When user selects a warehouse, load leftovers from `warehouse_items`
    async function onSourceWarehouseChange() {
      if (!selectedSourceWarehouse.value) {
        leftovers.value = [];
        return;
      }
      try {
        // e.g. GET /api/warehouse-items?warehouse_id=XXX
        const resp = await axios.get("/api/warehouse-items", {
          params: { warehouse_id: selectedSourceWarehouse.value }
        });
        leftovers.value = resp.data; 
      } catch (err) {
        console.error("Ошибка при загрузке остатков:", err);
      }
    }

    // Add/remove row
    function addProductRow() {
      productRows.value.push({
        _key: Date.now() + Math.random(),
        product_subcard_id: "",
        quantity: 0,
        unit_measurement: ""
      });
    }
    function removeProductRow(idx) {
      productRows.value.splice(idx,1);
    }

    // Reset quantity / unit when product changes
    function onProductChange(row) {
      row.quantity = 0;
      row.unit_measurement = "";
    }

    // Ensure we don't exceed leftover stock
    function onQuantityChange(row) {
      const maxQty = getBalance(row.product_subcard_id);
      if (row.quantity > maxQty) {
        alert(`Нельзя списать больше, чем ${maxQty}.`);
        row.quantity = maxQty;
      }
    }
    function getBalance(product_subcard_id) {
      const item = leftovers.value.find(l => l.product_subcard_id === product_subcard_id);
      return item ? item.balance : 0;
    }

    // Save write-off
    async function saveWriteOff() {
      // Basic validations
      if (!selectedSourceWarehouse.value) {
        alert("Укажите склад, с которого списываем");
        return;
      }
      if (!selectedDate.value) {
        alert("Укажите дату списания");
        return;
      }
      if (!productRows.value.length) {
        alert("Нет ни одной строки для списания");
        return;
      }

      // Build items payload
      const items = productRows.value.map(r => ({
        product_subcard_id: r.product_subcard_id,
        quantity: r.quantity,
        unit_measurement: r.unit_measurement
      }));

      try {
        // e.g. POST /api/writeoff/store
        await axios.post("/api/writeoff/store", {
          warehouse_id: selectedSourceWarehouse.value,
          document_date: selectedDate.value,
          items
        });

        message.value = "Списание успешно сохранено!";
        messageType.value = "success";

        // Reset
        selectedSourceWarehouse.value = "";
        selectedDate.value = "";
        productRows.value = [
          {
            _key: Date.now(),
            product_subcard_id: "",
            quantity: 0,
            unit_measurement: ""
          }
        ];
        leftovers.value = [];
      } catch (err) {
        console.error("Ошибка при сохранении списания:", err);
        message.value = "Ошибка при сохранении списания.";
        messageType.value = "error";
      }
    }

    return {
      // State
      warehouses,
      leftovers,
      units,
      selectedSourceWarehouse,
      selectedDate,
      productRows,
      message,
      messageType,

      // Computed
      sourceWarehouseName,

      // Methods
      fetchWarehouses,
      fetchUnits,
      onSourceWarehouseChange,
      addProductRow,
      removeProductRow,
      onProductChange,
      onQuantityChange,
      getBalance,
      saveWriteOff,

      // The format function
      formatNumber,
    };
  },
};
</script>

<style scoped>
.write-off-page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}
.page-title {
  text-align: center;
  margin-bottom: 20px;
  font-size: 1.4rem;
  color: #0288d1;
}

/* Cards */
.card {
  background-color: #fff;
  border-radius: 8px;
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
.mt-2 { margin-top: 12px; }
.mt-3 { margin-top: 20px; }
.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* Layout for two columns */
.cards-container {
  display: flex;
  gap: 20px;
}
.card-writeoff {
  flex: 2;
}
.card-leftovers {
  flex: 1;
}

/* Row styling */
.top-row {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}
.dropdown-column {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 180px;
}
.dropdown-label {
  font-weight: bold;
  color: #555;
}
.dropdown-select {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

/* Tables */
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
  border: 1px solid #ddd;
  padding: 8px;
  text-align: center;
}
.table-select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}
.table-input {
  width: 70px;
  padding: 6px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  text-align: right;
}

/* Buttons */
.action-btn {
  background-color: #0288d1;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 10px 14px;
  cursor: pointer;
  font-size: 14px;
}
.action-btn:hover {
  background-color: #0270a0;
}
.save-btn {
  width: 100%;
}
.remove-btn {
  background-color: #f44336;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 8px 10px;
  cursor: pointer;
}
.remove-btn:hover {
  background-color: #d32f2f;
}

/* Feedback */
.feedback-message {
  margin-top: 16px;
  text-align: center;
  font-weight: bold;
  padding: 10px;
  border-radius: 6px;
}
.feedback-message.success {
  background-color: #d4edda;
  color: #155724;
}
.feedback-message.error {
  background-color: #f8d7da;
  color: #721c24;
}
</style>
