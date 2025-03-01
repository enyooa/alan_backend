<template>
  <div class="write-off-container">
    <h2 class="page-title">Списание</h2>

    <!-- Cards Container for Write-Off Form and Inventory Summary -->
    <div class="cards-container">
      <!-- Write-Off Form Card -->
      <div class="card product-card">
        <div class="card-header">
          <h3>Товары для списания</h3>
        </div>
        <div class="card-body">
          <div v-if="loadingProductSubcards || loadingUnits" class="loading-indicator">
            <p>Загрузка...</p>
          </div>
          <div v-else-if="productSubcardsError || unitsError" class="error-message">
            <p>{{ productSubcardsError || unitsError }}</p>
          </div>
          <div v-else>
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Подкарточка</th>
                  <th>Партия</th>
                  <th>Остаток (ед. изм.)</th>
                  <th>Ед. изм.</th>
                  <th>Кол-во списываемое</th>
                  <th>Удалить</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, index) in writeOffRows" :key="row._key || index">
                  <!-- Product Dropdown -->
                  <td>
                    <select
                      v-model="row.product_subcard_id"
                      class="table-select"
                      @change="onSubcardChange(row)"
                    >
                      <option value="">—</option>
                      <option
                        v-for="subcard in productSubcards"
                        :key="subcard.id"
                        :value="subcard.id"
                      >
                        {{ subcard.name }}
                      </option>
                    </select>
                  </td>
                  <!-- Batch Dropdown -->
                  <td>
                    <select
                      v-if="row.product_subcard_id && getBatchesForSubcard(row.product_subcard_id).length"
                      v-model="row.selectedBatchId"
                      class="table-select"
                    >
                      <option value="">— Выберите партию —</option>
                      <option
                        v-for="batch in getBatchesForSubcard(row.product_subcard_id)"
                        :key="batch.id"
                        :value="batch.id"
                      >
                        {{ batch.quantity }} {{ batch.unit_measurement }} ({{ batch.date }})
                      </option>
                    </select>
                    <span v-else>-</span>
                  </td>
                  <!-- Remaining Inventory -->
                  <td>
                    <span v-if="row.selectedBatchId">
                      {{ getBatchById(row.selectedBatchId)?.quantity }}
                    </span>
                    <span v-else-if="row.product_subcard_id">
                      {{ findSubcard(row.product_subcard_id)?.total_quantity || 0 }}
                    </span>
                    <span v-else>-</span>
                  </td>
                  <!-- Unit Measurement Dropdown -->
                  <td>
                    <select v-model="row.unit_measurement" class="table-select">
                      <option value="">—</option>
                      <option
                        v-for="unit in units"
                        :key="unit.id"
                        :value="unit.name"
                      >
                        {{ unit.name }}
                      </option>
                    </select>
                  </td>
                  <!-- Write-Off Quantity -->
                  <td>
                    <input
                      type="number"
                      class="table-input"
                      v-model.number="row.amount"
                      @change="validateAmount(row)"
                    />
                  </td>
                  <!-- Delete Button -->
                  <td>
                    <button class="remove-btn" @click="removeWriteOffRow(index)">❌</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <!-- Add New Row Button -->
            <div class="mt-2">
              <button class="action-btn" @click="addWriteOffRow">
                ➕ Добавить строку
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Inventory Summary Card (Right Side) -->
      <div class="card cost-price-card">
        <div class="card-header">
          <h3>Остаток товаров</h3>
        </div>
        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr>
                <th>Подкарточка</th>
                <th>Остаток</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sub in productSubcards" :key="sub.id">
                <td>{{ sub.name }}</td>
                <td>
                  {{ sub.total_quantity !== null ? sub.total_quantity + ' ' + (sub.unit_measurement || '') : '-' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Submit Button and Global Message -->
    <div class="mt-3">
      <button class="action-btn save-btn" @click="submitWriteOff">
        Сохранить
      </button>
    </div>
    <div v-if="globalMessage" :class="['feedback-message', globalMessageType]">
      {{ globalMessage }}
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from "vue";
import axios from "axios";

export default {
  name: "WriteOffPage",
  setup() {
    const writeOffRows = ref([]);
    const productSubcards = ref([]);
    const units = ref([]);
    const globalMessage = ref("");
    const globalMessageType = ref("");
    const loadingProductSubcards = ref(false);
    const loadingUnits = ref(false);
    const productSubcardsError = ref("");
    const unitsError = ref("");

    onMounted(async () => {
      await fetchProductSubcards();
      await fetchUnits();
      addWriteOffRow();
    });

    const fetchProductSubcards = async () => {
      loadingProductSubcards.value = true;
      try {
        const { data } = await axios.get("/api/product_subcards");
        productSubcards.value = data;
      } catch (err) {
        productSubcardsError.value = "Ошибка загрузки подкарточек.";
      } finally {
        loadingProductSubcards.value = false;
      }
    };

    const fetchUnits = async () => {
      loadingUnits.value = true;
      try {
        const { data } = await axios.get("/api/unit-measurements");
        units.value = data;
      } catch (err) {
        unitsError.value = "Ошибка загрузки единиц измерения.";
      } finally {
        loadingUnits.value = false;
      }
    };

    const addWriteOffRow = () => {
      writeOffRows.value.push({
        product_subcard_id: "",
        unit_measurement: "",
        amount: 0,
        selectedBatchId: "",
      });
    };

    const removeWriteOffRow = (idx) => {
      writeOffRows.value.splice(idx, 1);
    };

    const findSubcard = (id) => {
      return productSubcards.value.find((sub) => sub.id === id) || null;
    };

    // Returns all batches for the selected product subcard.
    const getBatchesForSubcard = (subcardId) => {
      const subcard = findSubcard(subcardId);
      return subcard && subcard.batches ? subcard.batches : [];
    };

    // Returns batch details by batch ID.
    const getBatchById = (batchId) => {
      for (const sub of productSubcards.value) {
        if (sub.batches) {
          const found = sub.batches.find((batch) => batch.id === batchId);
          if (found) return found;
        }
      }
      return null;
    };

    const onSubcardChange = (row) => {
      // Reset batch selection and amount when the product changes.
      row.selectedBatchId = "";
      row.amount = 0;
    };

    const validateAmount = (row) => {
      if (row.selectedBatchId) {
        const batch = getBatchById(row.selectedBatchId);
        if (batch && row.amount > batch.quantity) {
          alert(`Количество для выбранной партии не может превышать ${batch.quantity}.`);
          row.amount = batch.quantity;
        }
      } else {
        const sub = findSubcard(row.product_subcard_id);
        if (sub && row.amount > (sub.total_quantity || 0)) {
          alert(`Количество для "${sub.name}" не может превышать общий остаток (${sub.total_quantity || 0}).`);
          row.amount = sub.total_quantity || 0;
        }
      }
    };

    const submitWriteOff = async () => {
      if (!writeOffRows.value.length) {
        alert("Заполните таблицу перед отправкой");
        return;
      }
      // Prepare payload. For each row, send product_subcard_id, unit_measurement, amount, and optional batch_id.
      const payload = writeOffRows.value.map((row) => ({
        product_subcard_id: row.product_subcard_id,
        unit_measurement: row.unit_measurement,
        amount: row.amount,
        batch_id: row.selectedBatchId || null,
      }));
      try {
        await axios.post("/api/bulkWriteOff", { writeoffs: payload }, {
          headers: { "Content-Type": "application/json" },
        });
        globalMessage.value = "Списание сохранено!";
        globalMessageType.value = "success";
        writeOffRows.value = [];
      } catch (err) {
        globalMessage.value = "Ошибка при сохранении списания.";
        globalMessageType.value = "error";
      }
    };

    return {
      writeOffRows,
      productSubcards,
      units,
      globalMessage,
      globalMessageType,
      loadingProductSubcards,
      loadingUnits,
      productSubcardsError,
      unitsError,
      findSubcard,
      getBatchesForSubcard,
      getBatchById,
      onSubcardChange,
      validateAmount,
      addWriteOffRow,
      removeWriteOffRow,
      submitWriteOff,
    };
  },
};
</script>

<style scoped>
.write-off-container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 20px;
}
.page-title {
  text-align: center;
  color: #0288d1;
  margin-bottom: 20px;
}

/* Cards Container for Side-by-Side Layout */
.cards-container {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}
.product-card {
  flex: 2;
}
.cost-price-card {
  flex: 1;
}

/* Cards */
.card {
  background-color: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
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
  font-size: 16px;
}
.card-body {
  padding: 16px;
}

/* Styled Table for Products */
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

/* Table for Cost Prices */
.cost-table {
  width: 100%;
  border-collapse: collapse;
}
.cost-table th,
.cost-table td {
  padding: 10px;
  border: 1px solid #ddd;
  text-align: center;
  font-size: 14px;
}
.cost-table thead tr {
  background-color: #0288d1;
  color: #fff;
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

/* Table selects & inputs */
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
