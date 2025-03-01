<template>
  <div class="price-offer-container">
    <h2 class="page-title">Перемещение</h2>

    <!-- Card for Storager, Address & Date -->
    <div class="card">
      <div class="card-header">
        <h3>Складовщик, Адрес и Дата</h3>
      </div>
      <div class="card-body">
        <div class="top-row">
          <!-- Storager Dropdown -->
          <div class="dropdown-column">
            <label class="dropdown-label">Складовщик</label>
            <select v-model="selectedStorager" class="dropdown-select">
              <option value="">— Выберите складовщика —</option>
              <option
                v-for="storager in storageUsers"
                :key="storager.user_id"
                :value="storager.user_id.toString()"
              >
                {{ storager.user_name }}
              </option>
            </select>
          </div>

          <!-- Address Dropdown -->
          <div class="dropdown-column">
            <label class="dropdown-label">Адрес</label>
            <select v-model="selectedAddress" class="dropdown-select">
              <option value="">— Выберите адрес —</option>
              <option
                v-for="addr in getAddressesForStorager(selectedStorager)"
                :key="addr.id"
                :value="addr"
              >
                {{ addr.name }}
              </option>
            </select>
          </div>

          <!-- Single Date Picker -->
          <div class="dropdown-column">
            <label class="dropdown-label">Дата</label>
            <input type="date" v-model="selectedDate" class="dropdown-select" />
          </div>
        </div>
      </div>
    </div>

    <!-- Cards Container for Products and Cost Prices -->
    <div class="cards-container mt-3">
      <!-- Card for Product Table -->
      <div class="card product-card">
        <div class="card-header">
          <h3>Товары</h3>
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
                  <th>Кол-во</th>
                  <th>Цена</th>
                  <th>Удалить</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, index) in productRows" :key="index">
                  <!-- Product Subcard Dropdown -->
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
                        {{ batch.quantity }} {{ batch.unit_measurement }} (Себестоимость: {{ batch.cost_price || batch.price }})
                      </option>
                    </select>
                    <span v-else>-</span>
                  </td>
                  <!-- Remaining Quantity -->
                  <td>
                    <span v-if="row.selectedBatchId">
                      {{ getBatchById(row.selectedBatchId)?.quantity }}
                    </span>
                    <span v-else-if="row.product_subcard_id">
                      {{ findSubcard(row.product_subcard_id)?.total_quantity || 0 }}
                    </span>
                    <span v-else>-</span>
                  </td>
                  <!-- Unit Measurement -->
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
                  <!-- Amount -->
                  <td>
                    <input
                      type="number"
                      class="table-input"
                      v-model.number="row.amount"
                      @change="validateAmount(row)"
                    />
                  </td>
                  <!-- Price -->
                  <td>
                    <input
                      type="number"
                      class="table-input"
                      v-model.number="row.price"
                    />
                  </td>
                  <!-- Delete Button -->
                  <td>
                    <button class="remove-btn" @click="removeProductRow(index)">
                      ❌
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
            <!-- Add Row Button -->
            <div class="mt-2">
              <button class="action-btn" @click="addProductRow">
                ➕ Добавить строку
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Card for Cost Prices -->
      <div class="card cost-price-card">
        <div class="card-header">
          <h3>Стоимость товаров</h3>
        </div>
        <div class="card-body">
          <table class="cost-table">
            <thead>
              <tr>
                <th>Подкарточка</th>
                <th>Стоимость</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="sub in productSubcards" :key="sub.id">
                <td>{{ sub.name }}</td>
                <td>
                  <div v-if="sub.batches && sub.batches.length">
                    <div v-for="batch in sub.batches" :key="batch.id">
                      {{ batch.cost_price !== null ? batch.cost_price : (batch.price ? batch.price : '-') }}
                      <span v-if="batch.date"> ({{ batch.date }})</span>
                    </div>
                  </div>
                  <div v-else>
                    {{ sub.cost_price !== null ? sub.cost_price : '-' }}
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

    </div>

    <!-- Submit Button and Global Message -->
    <div class="mt-3">
      <button class="action-btn save-btn" @click="submitGeneralWarehouse">
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
  name: "GeneralWarehousePage",
  setup() {
    // Data for Storager, Address and Date
    const selectedStorager = ref("");
    const selectedAddress = ref(null);
    const selectedDate = ref("");

    // Instead of clientList, we have storageUsers
    const storageUsers = ref([]);
    const productSubcards = ref([]);
    const units = ref([]);

    // Loading states & errors
    const loadingProductSubcards = ref(false);
    const loadingUnits = ref(false);
    const productSubcardsError = ref("");
    const unitsError = ref("");

    // Product rows in the table
    const productRows = ref([]);

    // Global messages
    const globalMessage = ref("");
    const globalMessageType = ref("");

    onMounted(async () => {
      await fetchStorageUsersAndAddresses();
      await fetchProductSubcards();
      await fetchUnits();
    });

    const fetchStorageUsersAndAddresses = async () => {
      try {
        const { data: response } = await axios.get("/api/getStorageUserAddresses");
        if (response.success) {
          storageUsers.value = response.data;
        }
      } catch (err) {
        console.error("Error fetching storage users & addresses:", err);
      }
    };

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

    const addProductRow = () => {
      productRows.value.push({
        product_subcard_id: "",
        unit_measurement: "",
        amount: 0,
        price: 0,
        selectedBatchId: "",
      });
    };

    const removeProductRow = (idx) => {
      productRows.value.splice(idx, 1);
    };

    const findSubcard = (id) => {
      return productSubcards.value.find((sub) => sub.id === id) || null;
    };

    const getBatchesForSubcard = (subcardId) => {
      const subcard = findSubcard(subcardId);
      return subcard && subcard.batches ? subcard.batches : [];
    };

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

    const getAddressesForStorager = (storagerId) => {
      if (!storagerId) return [];
      const storager = storageUsers.value.find((s) => s.user_id.toString() === storagerId.toString());
      return storager ? storager.addresses : [];
    };

    const submitGeneralWarehouse = async () => {
      if (!selectedStorager || !selectedAddress || !selectedDate || productRows.value.length === 0) {
        alert("Заполните все поля (складовщик, адрес, дату и товары)!");
        return;
      }

      let totalSum = 0;
      productRows.value.forEach((row) => {
        totalSum += (row.amount || 0) * (row.price || 0);
      });

      // Map product rows into the payload format
      const rows = productRows.value.map((row) => {
        const payload = {
          product_subcard_id: row.product_subcard_id,
          unit_measurement: row.unit_measurement,
          amount: row.amount,
          price: row.price,
          address_id: selectedAddress.id,
        };
        if (row.selectedBatchId) {
          payload.batch_id = row.selectedBatchId;
        }
        return payload;
      });

      const payload = {
        storager_id: selectedStorager,
        address_id: selectedAddress,
        date: selectedDate,
        totalsum: totalSum,
        price_offers: rows,
      };

      try {
        await axios.post("/api/sendToGeneralWarehouse", payload, {
          headers: { "Content-Type": "application/json" },
        });
        globalMessage.value = `Перемещение сохранено! Итоговая сумма = ${totalSum.toFixed(2)}`;
        globalMessageType.value = "success";
        // Reset form
        selectedStorager.value = "";
        selectedAddress.value = null;
        selectedDate.value = "";
        productRows.value = [];
      } catch (err) {
        globalMessage.value = "Ошибка при сохранении перемещения.";
        globalMessageType.value = "error";
      }
    };

    return {
      selectedStorager,
      selectedAddress,
      selectedDate,
      storageUsers,
      productSubcards,
      units,
      loadingProductSubcards,
      loadingUnits,
      productSubcardsError,
      unitsError,
      productRows,
      globalMessage,
      globalMessageType,
      getAddressesForStorager,
      findSubcard,
      getBatchesForSubcard,
      getBatchById,
      onSubcardChange,
      validateAmount,
      addProductRow,
      removeProductRow,
      submitGeneralWarehouse,
    };
  },
};
</script>

<style scoped>
.price-offer-container {
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
}
.card-body {
  padding: 16px;
}
.mt-3 {
  margin-top: 20px;
}

/* Top row for storager, address & date */
.top-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}
.dropdown-column {
  flex: 1;
  min-width: 200px;
  display: flex;
  flex-direction: column;
}
.dropdown-label {
  font-weight: bold;
  color: #555;
  margin-bottom: 4px;
}
.dropdown-select {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
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

/* Messages / Loading */
.loading-indicator {
  text-align: center;
  color: #666;
}
.error-message {
  color: red;
  margin-top: 10px;
  text-align: center;
  font-weight: bold;
}
.feedback-message {
  margin-top: 20px;
  text-align: center;
  font-weight: bold;
  padding: 10px;
  border-radius: 8px;
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
