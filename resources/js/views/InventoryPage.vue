<template>
  <div class="transfer-page-container">
    <h2 class="page-title">Перемещение</h2>

    <!-- Карточка: "Из склада" / "В склад" / "Дата" -->
    <div class="card">
      <div class="card-header">
        <h3>Из какого склада, в какой склад и дата</h3>
      </div>
      <div class="card-body">
        <div class="top-row">
          <!-- Из склада (source) -->
          <div class="dropdown-column">
            <label class="dropdown-label">Из склада:</label>
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

          <!-- В склад (destination) -->
          <div class="dropdown-column">
            <label class="dropdown-label">В склад:</label>
            <select 
              v-model="selectedDestinationWarehouse" 
              class="dropdown-select"
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

    <!-- Две части: слева таблица формирования перемещения, справа - остатки -->
    <div class="cards-container mt-3">
      <!-- Левая карточка: формируем строки для перемещения -->
      <div class="card card-transfer">
        <div class="card-header flex-between">
          <h3>Товары для Перемещения</h3>
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
                <!-- Dropdown товаров (с остатков) -->
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

                <!-- Кол-во -->
                <td>
                  <input
                    type="number"
                    class="table-input"
                    v-model.number="row.quantity"
                    @change="onQuantityChange(row)"
                  />
                </td>

                <!-- unit_measurement -->
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

                <!-- remove btn -->
                <td>
                  <button class="remove-btn" @click="removeProductRow(idx)">
                    ❌
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <!-- Save Button & Message -->
        <div class="mt-2">
          <button class="action-btn save-btn" @click="saveTransfer">
            Сохранить перемещение
          </button>
        </div>
        <div v-if="message" :class="['feedback-message', messageType]">
          {{ message }}
        </div>
      </div>

      <!-- Правая карточка: справочные остатки (источник) -->
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
                <!-- Use formatNumber here too -->
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
import { ref, onMounted, computed } from "vue";
import axios from "axios";

export default {
  name: "TransferPage",
  setup() {
    // Warehouses
    const warehouses = ref([]);
    // Leftover items in the source warehouse
    const leftovers = ref([]);
    // Unit measurements
    const units = ref([]);

    // Selected from & to warehouse IDs + date
    const selectedSourceWarehouse = ref("");
    const selectedDestinationWarehouse = ref("");
    const selectedDate = ref("");

    // Product rows to transfer
    const productRows = ref([
      {
        _key: Date.now(),
        product_subcard_id: "",
        quantity: 0,
        unit_measurement: "",
      }
    ]);

    // Feedback
    const message = ref("");
    const messageType = ref("");

    // Format function to strip trailing zeros (10.000 -> 10, etc.)
    function formatNumber(value) {
      if (value === null || value === undefined) return "";
      const num = Number(value);
      if (Number.isNaN(num)) return value; // if not a valid number, return as-is

      // If it's effectively an integer, no decimals:
      if (Number.isInteger(num)) {
        return num.toString();
      }
      // Otherwise, show up to 3 decimals (customize if you like)
      return num.toFixed(3);
    }

    // Computed: show the name of the source warehouse
    const sourceWarehouseName = computed(() => {
      if (!selectedSourceWarehouse.value) return "—";
      const found = warehouses.value.find(w => w.id == selectedSourceWarehouse.value);
      if (!found) return "???";
      return found.name;
    });

    onMounted(() => {
      fetchWarehouses();
      fetchUnits();
    });

    async function fetchWarehouses() {
      try {
        const resp = await axios.get("/api/getWarehouses");
        warehouses.value = resp.data;
      } catch (error) {
        console.error("Error fetching warehouses:", error);
      }
    }
    async function fetchUnits() {
      try {
        const resp = await axios.get("/api/unit-measurements");
        units.value = resp.data;
      } catch (error) {
        console.error("Error fetching units:", error);
      }
    }

    // When user picks a source warehouse, load that warehouse's leftover stock
    async function onSourceWarehouseChange() {
      if (!selectedSourceWarehouse.value) {
        leftovers.value = [];
        return;
      }
      try {
        // Example: /api/warehouse-items?warehouse_id=XXX
        const resp = await axios.get("/api/warehouse-items", {
          params: { warehouse_id: selectedSourceWarehouse.value }
        });
        leftovers.value = resp.data;
      } catch (error) {
        console.error("Error fetching leftover items:", error);
      }
    }

    function addProductRow() {
      productRows.value.push({
        _key: Date.now() + Math.random(),
        product_subcard_id: "",
        quantity: 0,
        unit_measurement: "",
      });
    }
    function removeProductRow(idx) {
      productRows.value.splice(idx, 1);
    }

    // If user changes product, reset quantity & unit
    function onProductChange(row) {
      row.quantity = 0;
      row.unit_measurement = "";
    }

    // Don’t allow quantity > leftover
    function onQuantityChange(row) {
      const maxQty = getBalance(row.product_subcard_id);
      if (row.quantity > maxQty) {
        alert(`Нельзя переместить больше, чем ${maxQty}.`);
        row.quantity = maxQty;
      }
    }

    function getBalance(product_subcard_id) {
      const item = leftovers.value.find(l => l.product_subcard_id === product_subcard_id);
      return item ? item.balance : 0;
    }

    // Save the transfer
    async function saveTransfer() {
      if (!selectedSourceWarehouse.value) {
        alert("Укажите склад 'из которого' отправляем");
        return;
      }
      if (!selectedDestinationWarehouse.value) {
        alert("Укажите склад 'в который' отправляем");
        return;
      }
      if (selectedSourceWarehouse.value == selectedDestinationWarehouse.value) {
        alert("Нельзя перемещать между одним и тем же складом!");
        return;
      }
      if (!selectedDate.value) {
        alert("Укажите дату");
        return;
      }
      if (!productRows.value.length) {
        alert("Нет ни одной строки товара для перемещения");
        return;
      }

      const products = productRows.value.map(r => ({
        product_subcard_id: r.product_subcard_id,
        quantity: r.quantity,
        unit_measurement: r.unit_measurement,
      }));

      try {
        await axios.post("/api/transfer/store", {
          source_warehouse_id: selectedSourceWarehouse.value,
          destination_warehouse_id: selectedDestinationWarehouse.value,
          document_date: selectedDate.value,
          products,
        });

        message.value = "Перемещение успешно сохранено!";
        messageType.value = "success";

        // Reset
        selectedSourceWarehouse.value = "";
        selectedDestinationWarehouse.value = "";
        selectedDate.value = "";
        productRows.value = [
          {
            _key: Date.now(),
            product_subcard_id: "",
            quantity: 0,
            unit_measurement: "",
          }
        ];
        leftovers.value = [];
      } catch (err) {
        console.error("Ошибка при сохранении перемещения:", err);
        message.value = "Ошибка при сохранении перемещения.";
        messageType.value = "error";
      }
    }

    return {
      // Reactive refs
      warehouses,
      leftovers,
      units,
      selectedSourceWarehouse,
      selectedDestinationWarehouse,
      selectedDate,
      productRows,
      message,
      messageType,

      // Computed
      sourceWarehouseName,

      // Methods / functions
      fetchWarehouses,
      fetchUnits,
      onSourceWarehouseChange,
      addProductRow,
      removeProductRow,
      onProductChange,
      onQuantityChange,
      getBalance,
      saveTransfer,
      formatNumber, // make sure to return so we can use in template
    };
  },
};
</script>

<style scoped>
.transfer-page-container {
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

/* Two columns */
.cards-container {
  display: flex;
  gap: 20px;
}
.card-transfer {
  flex: 2;
}
.card-leftovers {
  flex: 1;
}

/* Top row */
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

/* Feedback Message */
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
