<template>
    <div class="price-offer-container">
      <h2 class="page-title">Ценовое предложение</h2>

      <!-- (1) Card: Warehouse, Client, Address on first row; Dates on second row -->
      <div class="card">
        <div class="card-header">
          <h3>Склад, Клиент, Адрес, Даты</h3>
        </div>
        <div class="card-body">
          <!-- FIRST ROW: Warehouse, Client, Address -->
          <div class="top-row">
            <!-- (a) Warehouse Dropdown -->
            <div class="dropdown-column">
              <label class="dropdown-label">Склад</label>
              <select
                v-model="selectedWarehouse"
                class="dropdown-select"
                @change="onWarehouseChange"
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

            <!-- (b) Client Dropdown -->
            <div class="dropdown-column">
              <label class="dropdown-label">Клиент</label>
              <select v-model="selectedClient" class="dropdown-select">
                <option value="">— Выберите клиента —</option>
                <option
                  v-for="client in clientList"
                  :key="client.client_id"
                  :value="client.client_id.toString()"
                >
                  {{ client.client_name }}
                </option>
              </select>
            </div>

            <!-- (c) Address Dropdown -->
            <div class="dropdown-column">
              <label class="dropdown-label">Адрес</label>
              <select v-model="selectedAddress" class="dropdown-select">
                <option value="">— Выберите адрес —</option>
                <option
                  v-for="addr in getAddressesForClient(selectedClient)"
                  :key="addr.id"
                  :value="addr"
                >
                  {{ addr.name }}
                </option>
              </select>
            </div>
          </div> <!-- /top-row -->

          <!-- SECOND ROW: Start & End Dates -->
          <div class="dates-row mt-2">
            <div class="dropdown-column">
              <label class="dropdown-label">Начальная дата</label>
              <input type="date" v-model="startDate" class="dropdown-select" />
            </div>
            <div class="dropdown-column">
              <label class="dropdown-label">Конечная дата</label>
              <input type="date" v-model="endDate" class="dropdown-select" />
            </div>
          </div>
        </div>
      </div>

      <!-- (2) Cards container for Product Table (left) and Leftovers (right) -->
      <div class="cards-container mt-3">
        <!-- (2a) Product Table Card -->
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
                    <!-- "Остаток" column removed here -->
                    <th>Ед. изм.</th>
                    <th>Кол-во</th>
                    <th>Цена</th>
                    <th>Удалить</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(row, idx) in productRows" :key="idx">
                    <!-- Subcard selection -->
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

                    <!-- Remove Btn -->
                    <td>
                      <button class="remove-btn" @click="removeProductRow(idx)">❌</button>
                    </td>
                  </tr>
                </tbody>
              </table>
              <!-- Add Row Btn -->
              <div class="mt-2">
                <button class="action-btn" @click="addProductRow">
                  ➕ Добавить строку
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- (2b) Leftover Card -->
        <div class="card leftover-card">
          <div class="card-header">
            <h3>Остатки на складе "{{ warehouseName }}"</h3>
          </div>
          <div class="card-body">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Товар</th>
                  <th>Остаток</th>
                  <th>Ед. изм.</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="item in leftovers" :key="item.product_subcard_id">
                  <td>{{ item.name }}</td>
                  <td>{{ formatNumber(item.balance) }}</td>
                  <td>{{ item.unit_measurement || '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div> <!-- /cards-container -->

      <!-- (3) SUBMIT & GLOBAL MESSAGE -->
      <div class="mt-3">
        <button class="action-btn save-btn" @click="submitPriceOffer">
          Сохранить
        </button>
      </div>
      <div v-if="globalMessage" :class="['feedback-message', globalMessageType]">
        {{ globalMessage }}
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted, computed } from "vue";
  import axios from "axios";

  export default {
    name: "PriceOfferPage",
    setup() {
      // ========== Basic Data ==========
      // Warehouse selection
      const warehouses = ref([]);
      const selectedWarehouse = ref("");
      const leftovers = ref([]);

      // Client & Address & Dates
      const selectedClient = ref("");
      const selectedAddress = ref(null);
      const startDate = ref("");
      const endDate = ref("");

      // For the client dropdown
      const clientList = ref([]);

      // Product subcards & units
      const productSubcards = ref([]);
      const units = ref([]);

      // Product Rows
      const productRows = ref([]);

      // Flags / errors / messages
      const loadingProductSubcards = ref(false);
      const loadingUnits = ref(false);
      const productSubcardsError = ref("");
      const unitsError = ref("");
      const globalMessage = ref("");
      const globalMessageType = ref("");

      // onMounted
      onMounted(() => {
        fetchClientsAndAddresses();
        fetchWarehouses();
        fetchProductSubcards();
        fetchUnits();
      });

      // ========== Fetch Methods ==========
      async function fetchClientsAndAddresses() {
        try {
          const { data: response } = await axios.get("/api/getClientAdresses");
          if (response.success) {
            clientList.value = response.data;
          }
        } catch (err) {
          console.error("Error fetching client & address data:", err);
        }
      }

      async function fetchWarehouses() {
        try {
          const resp = await axios.get("/api/getWarehouses");
          warehouses.value = resp.data;
        } catch (err) {
          console.error("Error fetching warehouses:", err);
        }
      }

      async function fetchProductSubcards() {
        loadingProductSubcards.value = true;
        try {
          const { data } = await axios.get("/api/product_subcards");
          productSubcards.value = data;
        } catch (err) {
          productSubcardsError.value = "Ошибка загрузки подкарточек.";
        } finally {
          loadingProductSubcards.value = false;
        }
      }

      async function fetchUnits() {
        loadingUnits.value = true;
        try {
          const { data } = await axios.get("/api/unit-measurements");
          units.value = data;
        } catch (err) {
          unitsError.value = "Ошибка загрузки единиц измерения.";
        } finally {
          loadingUnits.value = false;
        }
      }

      // ========== Leftovers for a specific warehouse ==========
      async function onWarehouseChange() {
        if (!selectedWarehouse.value) {
          leftovers.value = [];
          return;
        }
        try {
          // e.g. GET /api/warehouse-items?warehouse_id=XX
          const resp = await axios.get("/api/warehouse-items", {
            params: { warehouse_id: selectedWarehouse.value },
          });
          leftovers.value = resp.data; // array of leftover objects
        } catch (err) {
          console.error("Ошибка при загрузке остатков:", err);
        }
      }

      // ========== Add / Remove Product Rows ==========
      function addProductRow() {
        productRows.value.push({
          product_subcard_id: "",
          unit_measurement: "",
          amount: 0,
          price: 0,
        });
      }
      function removeProductRow(idx) {
        productRows.value.splice(idx, 1);
      }

      // ========== Subcard / Amount Logic ==========
      function onSubcardChange(row) {
        row.amount = 0;
      }

      function validateAmount(row) {
        // We might not do anything here if we're not displaying leftover in table,
        // but you could still ensure we don't exceed leftover if you want:
        const leftoverBal = getLeftoverBalance(row.product_subcard_id);
        if (row.amount > leftoverBal) {
          alert(`Нельзя указать кол-во больше, чем ${leftoverBal}.`);
          row.amount = leftoverBal;
        }
      }

      function getLeftoverBalance(subcardId) {
        const item = leftovers.value.find((l) => l.product_subcard_id === subcardId);
        if (!item) return 0;
        return item.balance || 0;
      }

      function getAddressesForClient(clientId) {
        if (!clientId) return [];
        const found = clientList.value.find((c) => c.client_id.toString() === clientId.toString());
        return found ? found.addresses : [];
      }

      // ========== Format Number Helper ==========
      function formatNumber(value) {
        if (value === null || value === undefined) return "";
        const num = Number(value);
        if (Number.isNaN(num)) return value;
        // Round to 3 decimals, then remove trailing zeros by converting to float again
        return parseFloat(num.toFixed(3)).toString();
      }

      // Computed name for the leftover card
      const warehouseName = computed(() => {
        if (!selectedWarehouse.value) return "—";
        const w = warehouses.value.find((wh) => wh.id == selectedWarehouse.value);
        return w ? w.name : "???";
      });

      // ========== SUBMIT Price Offer ==========
      async function submitPriceOffer() {
        if (!selectedWarehouse.value) {
          alert("Выберите склад!");
          return;
        }
        if (!selectedClient.value || !selectedAddress.value || !startDate.value || !endDate.value) {
          alert("Заполните Клиент, Адрес, Начальная дата, Конечная дата!");
          return;
        }
        if (!productRows.value.length) {
          alert("Нет товаров для ценового предложения!");
          return;
        }

        let totalSum = 0;
        productRows.value.forEach((row) => {
          totalSum += (row.amount || 0) * (row.price || 0);
        });

        const offers = productRows.value.map((r) => ({
          product_subcard_id: r.product_subcard_id,
          unit_measurement: r.unit_measurement,
          amount: r.amount,
          price: r.price,
        }));

        const payload = {
          warehouse_id: selectedWarehouse.value,
          client_id: selectedClient.value,
          address_id: selectedAddress.value.id,
          start_date: startDate.value,
          end_date: endDate.value,
          totalsum: totalSum,
          price_offer_items: offers,
        };

        try {
          await axios.post("/api/bulkPriceOffers", payload, {
            headers: { "Content-Type": "application/json" },
          });
          globalMessage.value = `Ценовое предложение сохранено! Итоговая сумма = ${totalSum.toFixed(2)}`;
          globalMessageType.value = "success";

          // Reset
          selectedWarehouse.value = "";
          selectedClient.value = "";
          selectedAddress.value = null;
          startDate.value = "";
          endDate.value = "";
          productRows.value = [];
          leftovers.value = [];
        } catch (err) {
          console.error("Ошибка при сохранении ценового предложения:", err);
          globalMessage.value = "Ошибка при сохранении ценового предложения.";
          globalMessageType.value = "error";
        }
      }

      return {
        // Warehouse
        warehouses,
        selectedWarehouse,
        leftovers,

        // Client & Address & Dates
        selectedClient,
        selectedAddress,
        startDate,
        endDate,
        clientList,

        // Product Subcards & Units
        productSubcards,
        units,

        // Loading / error states
        loadingProductSubcards,
        loadingUnits,
        productSubcardsError,
        unitsError,

        // Product rows
        productRows,

        // Global message
        globalMessage,
        globalMessageType,

        // Computed
        warehouseName,

        // Methods
        fetchClientsAndAddresses,
        fetchWarehouses,
        fetchProductSubcards,
        fetchUnits,

        onWarehouseChange,
        addProductRow,
        removeProductRow,
        onSubcardChange,
        validateAmount,
        getLeftoverBalance,
        getAddressesForClient,

        formatNumber,
        submitPriceOffer,
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
  .leftover-card {
    flex: 1;
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
  .mt-2 {
    margin-top: 8px;
  }
  .mt-3 {
    margin-top: 20px;
  }

  /* top-row & dates-row */
  .top-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
  }
  .dates-row {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
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
    width: 80px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
  }

  /* Loading & Errors */
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

  /* Global Message */
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
  </style>
