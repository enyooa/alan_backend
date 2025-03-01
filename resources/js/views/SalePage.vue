<template>
  <div class="dashboard-container">
    <div class="main-content">
      <main class="content">
        <h2 class="page-title">Продажа</h2>
        
        <!-- Flex Container for Sales Table & Cost Price Card -->
        <div class="cards-container">
          <!-- Card for Sales Table -->
          <div class="card sale-card">
            <div class="card-header">
              <h3>Продажа товаров</h3>
            </div>
            <div class="card-body">
              <div class="table-container">
                <table class="styled-table">
                  <thead>
                    <tr>
                      <th>Подкарточка</th>
                      <!-- New column for Batch selection -->
                      <th>Партия</th>
                      <th>Ед. изм.</th>
                      <th>Кол-во</th>
                      <th>Цена</th>
                      <th>Сумма</th>
                      <th>Удалить</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-for="(row, index) in saleRows" :key="index">
                      <!-- Product Subcard Dropdown -->
                      <td>
                        <select 
                          v-model="row.product_subcard_id" 
                          class="table-select"
                          @change="onSubcardChange(row)"
                        >
                          <option disabled value="">Выберите товар</option>
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
                          <option value="">—</option>
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
                      <!-- Unit Measurement Dropdown -->
                      <td>
                        <select v-model="row.unit_measurement" class="table-select">
                          <option disabled value="">Выберите ед. изм.</option>
                          <option
                            v-for="unit in units"
                            :key="unit.id"
                            :value="unit.name"
                          >
                            {{ unit.name }}
                          </option>
                        </select>
                      </td>
                      <!-- Amount Input -->
                      <td>
                        <input
                          type="number"
                          v-model.number="row.amount"
                          class="table-input"
                          placeholder="Кол-во"
                        />
                      </td>
                      <!-- Price Input -->
                      <td>
                        <input
                          type="number"
                          v-model.number="row.price"
                          class="table-input"
                          placeholder="Цена"
                        />
                      </td>
                      <!-- Calculated Total -->
                      <td>
                        {{ (row.amount * row.price).toFixed(2) }}
                      </td>
                      <!-- Delete Row Button -->
                      <td>
                        <button class="remove-btn" @click="removeSaleRow(index)">❌</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
                <button class="add-row-btn" @click="addSaleRow">
                  ➕ Добавить строку
                </button>
              </div>
            </div>
          </div>

          <div class="card cost-card">
            <div class="card-header">
              <h3>Остаток и себестоимость</h3>
            </div>
            <div class="card-body">
              <table class="cost-table">
                <thead>
                  <tr>
                    <th>Подкарточка</th>
                    <th>Остаток</th>
                    <th>Себестоимость</th>
                  </tr>
                </thead>
                <tbody>
                  <!-- Loop through each подкарточка -->
                  <tr v-for="sub in productSubcards" :key="sub.id">
                    <!-- Подкарточка Name -->
                    <td>{{ sub.name }}</td>
          
                    <!-- Остаток (loop through each batch if needed) -->
                    <td>
                      <!-- If there are batches -->
                      <div v-if="sub.batches && sub.batches.length">
                        <!-- Show each batch's leftover on a new line -->
                        <div v-for="batch in sub.batches" :key="batch.id">
                          <!-- If your API returns batch.ostatok, use that, otherwise batch.quantity -->
                          Остаток: 
                          {{ batch.ostatok !== undefined 
                              ? batch.ostatok 
                              : batch.quantity 
                          }}
                          <span v-if="batch.unit_measurement">
                            {{ batch.unit_measurement }}
                          </span>
                          <!-- Optional: show batch date or any extra info -->
                          <span v-if="batch.date">
                            ({{ batch.date }})
                          </span>
                        </div>
                      </div>
                      <div v-else>-</div>
                    </td>
          
                    <!-- Себестоимость (loop through each batch if needed) -->
                    <td>
                      <div v-if="sub.batches && sub.batches.length">
                        <div v-for="batch in sub.batches" :key="batch.id">
                          <!-- cost_price if available, else price, else '-' -->
                          {{ batch.cost_price !== null 
                               ? batch.cost_price 
                               : (batch.price ? batch.price : '-') 
                          }}
                          <span v-if="batch.date">
                            ({{ batch.date }})
                          </span>
                        </div>
                      </div>
                      <div v-else>-</div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
          

        </div>

        <!-- Submit Sales Button -->
        <button class="submit-btn" @click="submitSalesData" :disabled="isSubmitting">
          {{ isSubmitting ? '⏳ Сохранение...' : 'Сохранить' }}
        </button>
      </main>
    </div>
  </div>
</template>

<script>
import axios from "axios";

export default {
  name: "ProductSalePage",
  data() {
    return {
      saleRows: [
        { 
          product_subcard_id: null, 
          selectedBatchId: "", // Added for batch selection
          unit_measurement: null, 
          amount: 0, 
          price: 0 
        }
      ],
      productSubcards: [],
      units: [],
      isSubmitting: false,
      token: localStorage.getItem("token") || ""
    };
  },
  created() {
    this.fetchSubcardsAndUnits();
  },
  methods: {
    async fetchSubcardsAndUnits() {
      try {
        if (!this.token) {
          alert("Отсутствует токен. Пожалуйста, войдите в систему.");
          this.$router.push("/login");
          return;
        }
        // Use your endpoint that returns subcards with batches (see getSubCards)
        const [subcardsResponse, unitsResponse] = await Promise.all([
          axios.get("/api/product_subcards", { headers: { Authorization: `Bearer ${this.token}` } }),
          axios.get("/api/unit-measurements", { headers: { Authorization: `Bearer ${this.token}` } })
        ]);
        this.productSubcards = subcardsResponse.data;
        this.units = unitsResponse.data;
      } catch (error) {
        console.error("Ошибка при загрузке данных:", error);
      }
    },
    addSaleRow() {
      this.saleRows.push({ 
        product_subcard_id: null, 
        selectedBatchId: "", 
        unit_measurement: null, 
        amount: 0, 
        price: 0 
      });
    },
    removeSaleRow(index) {
      this.saleRows.splice(index, 1);
    },
    // Reset batch selection when product subcard changes
    onSubcardChange(row) {
      row.selectedBatchId = "";
      row.amount = 0;
    },
    // Returns batches for the given product subcard id
    getBatchesForSubcard(subcardId) {
      const subcard = this.productSubcards.find(sub => sub.id === subcardId);
      return subcard && subcard.batches ? subcard.batches : [];
    },
    async submitSalesData() {
      if (this.saleRows.length === 0) {
        alert("Заполните таблицу перед отправкой");
        return;
      }
      const updatedSales = this.saleRows.map(row => {
        const payload = {
          product_subcard_id: row.product_subcard_id,
          unit_measurement: row.unit_measurement,
          amount: row.amount,
          price: row.price,
          totalsum: row.amount * row.price
        };
        // If a batch is selected, include its ID
        if (row.selectedBatchId) {
          payload.batch_id = row.selectedBatchId;
        }
        return payload;
      });
      try {
        this.isSubmitting = true;
        const response = await axios.post(
          "/api/bulk_sales",
          { sales: updatedSales },
          {
            headers: {
              "Content-Type": "application/json",
              Authorization: `Bearer ${this.token}`
            }
          }
        );
        if (response.status === 201) {
          alert("Продажи успешно отправлены!");
          this.saleRows = [{ product_subcard_id: null, selectedBatchId: "", unit_measurement: null, amount: 0, price: 0 }];
        } else {
          alert("Ошибка при отправке данных");
        }
      } catch (error) {
        console.error("Ошибка при отправке данных:", error);
        alert("Ошибка: " + error);
      } finally {
        this.isSubmitting = false;
      }
    }
  }
};
</script>

<style scoped>
.dashboard-container {
  display: flex;
  min-height: 100vh;
}
.main-content {
  flex: 1;
  background-color: #f5f5f5;
}
.content {
  padding: 20px;
}
.page-title {
  text-align: center;
  color: #0288d1;
  margin-bottom: 20px;
}

/* Flex Container for Side-by-Side Cards */
.cards-container {
  display: flex;
  gap: 20px;
  margin-bottom: 20px;
}
.sale-card {
  flex: 2;
}
.cost-card {
  flex: 1;
}

/* Card Styles */
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
}
.card-body {
  padding: 16px;
}

/* Table Styles */
.table-container {
  width: 100%;
  overflow-x: auto;
  background: #fff;
  border-radius: 8px;
  box-shadow: 0 3px 8px rgba(0,0,0,0.1);
  padding: 10px;
}
.styled-table {
  width: 100%;
  border-collapse: collapse;
}
.styled-table th,
.styled-table td {
  padding: 10px;
  border: 1px solid #ddd;
  text-align: center;
}
.styled-table thead {
  background-color: #0288d1;
  color: #fff;
}

/* Cost Table Styles */
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
.cost-table thead {
  background-color: #0288d1;
  color: #fff;
}

/* Buttons */
.add-row-btn {
  background-color: transparent;
  color: #0288d1;
  border: 1px solid #0288d1;
  border-radius: 5px;
  padding: 8px 12px;
  cursor: pointer;
  margin-top: 10px;
}
.add-row-btn:hover {
  background-color: #0288d1;
  color: #fff;
}
.submit-btn {
  background-color: #0288d1;
  color: white;
  border: none;
  padding: 12px 20px;
  border-radius: 8px;
  cursor: pointer;
  width: 100%;
  font-size: 16px;
  margin-top: 10px;
}
.submit-btn:hover {
  background-color: #026ca0;
}
.remove-btn {
  background-color: #f44336;
  color: #fff;
  border: none;
  padding: 8px 12px;
  border-radius: 5px;
  cursor: pointer;
}
.remove-btn:hover {
  background-color: #d32f2f;
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
</style>
