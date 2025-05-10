<!-- src/pages/SalePage.vue -->
<template>
    <div class="full-page">
      <main class="content">
        <h2 class="page-title">Продажа</h2>

        <!-- ▸ карточка выбора клиента / даты / склада-отгрузки -->
        <div class="card">
          <div class="card-header"><h3>Покупатель, дата, склад-отгрузки</h3></div>
          <div class="card-body flex-row">
            <!-- клиент / организация -->
            <div class="dropdown">
              <label class="field-label" for="customer">Покупатель / Организация</label>
              <select v-model="selectedCustomerId" id="customer" class="dropdown-select">
                <option disabled value="">— Выберите —</option>
                <option v-for="c in customers" :key="c.id" :value="c.id">
                  {{ c.name }}
                </option>
              </select>
            </div>

            <!-- дата -->
            <div class="dropdown">
              <label class="field-label" for="date">Дата продажи</label>
              <input type="date" v-model="selectedDate" id="date" class="dropdown-select" />
            </div>

            <!-- склад-отгрузки -->
            <div class="dropdown">
              <label class="field-label" for="wh">Склад-отгрузки</label>
              <select v-model="selectedWarehouseId" id="wh" class="dropdown-select">
                <option disabled value="">— Выберите склад —</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                  {{ w.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- ▸ таблица товаров -->
        <div class="card mt-3">
          <div class="card-header flex-between">
            <h3>Товары</h3>
            <button class="action-btn add-row-btn" @click="addProductRow">➕ Добавить строку</button>
          </div>

          <div class="card-body">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Товар</th><th>Кол-во тары</th><th>Ед. изм / Тара</th>
                  <th>Брутто</th><th>Нетто</th><th>Цена</th><th>Сумма</th><th>Удалить</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(row, idx) in productRows" :key="row._key">
                  <!-- товар -->
                  <td>
                    <select v-model="row.product_subcard_id" class="table-select">
                      <option disabled value="">Выберите товар</option>
                      <option v-for="p in products" :key="p.id" :value="p.id">
                        {{ p.name }}
                      </option>
                    </select>
                  </td>

                  <!-- кол-во -->
                  <td><input v-model.number="row.quantity" type="number" class="table-input" /></td>

                  <!-- единица -->
                  <td>
                    <select v-model="row.unit_measurement" class="table-select">
                      <option disabled value="">—</option>
                      <option v-for="u in units" :key="u.id" :value="u.name">
                        {{ u.name }} ({{ u.tare }} г)
                      </option>
                    </select>
                  </td>

                  <!-- брутто -->
                  <td><input v-model.number="row.brutto" type="number" class="table-input" /></td>

                  <!-- нетто -->
                  <td>{{ calcNetto(row).toFixed(2) }}</td>

                  <!-- цена -->
                  <td><input v-model.number="row.price" type="number" class="table-input" /></td>

                  <!-- сумма -->
                  <td>{{ calcTotal(row).toFixed(2) }}</td>

                  <!-- удалить -->
                  <td><button class="remove-btn" @click="removeProductRow(idx)">❌</button></td>
                </tr>

                <!-- итог -->
                <tr class="summary-row">
                  <td colspan="4" class="summary-label"><strong>ИТОГО</strong></td>
                  <td>{{ totalNetto.toFixed(2) }}</td>
                  <td>-</td>
                  <td>{{ totalSum.toFixed(2) }}</td>
                  <td>-</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ▸ сохранить -->
        <div class="mt-3">
          <button class="action-btn save-btn" :disabled="isSubmitting" @click="submitSale">
            {{ isSubmitting ? "⏳ Сохранение…" : "Сохранить" }}
          </button>
        </div>

        <div v-if="message" :class="['feedback-message', messageType]">{{ message }}</div>
      </main>
    </div>
  </template>

  <script setup>
  import { ref, onMounted, computed } from 'vue'
  import axios from 'axios'

  /* ───────────────────── state ───────────────────── */
  const selectedCustomerId  = ref('')
  const selectedDate        = ref('')
  const selectedWarehouseId = ref('')

  const customers  = ref([])   // клиенты / организации
  const warehouses = ref([])
  const products   = ref([])
  const units      = ref([])

  const productRows = ref([makeEmptyRow()])

  function makeEmptyRow () {
    return {
      _key               : Date.now() + Math.random(),
      product_subcard_id : '',
      unit_measurement   : '',
      quantity           : 0,
      brutto             : 0,
      price              : 0
    }
  }

  /* ───────────────────── fetchers ────────────────── */
  const fetchCustomers = async () => {
    const { data } = await axios.get('/api/reference/client')   // ← ваш энд-поинт
    customers.value = data
  }
  const fetchWarehouses = async () => {
    const { data } = await axios.get('/api/getWarehouses')
    warehouses.value = data
  }
  const fetchProducts = async () => {
    const { data } = await axios.get('/api/reference/subproductCard')
    products.value = data
  }
  const fetchUnits = async () => {
    const { data } = await axios.get('/api/reference/unit')
    units.value = data.map(u => ({ id:u.id, name:u.name, tare:Number(u.tare)||0 }))
  }

  onMounted(() => Promise.all([
    fetchCustomers(), fetchWarehouses(), fetchProducts(), fetchUnits()
  ]))

  /* ───────────────────── helpers ─────────────────── */
  const addProductRow    = () => productRows.value.push(makeEmptyRow())
  const removeProductRow = idx => productRows.value.splice(idx,1)

  const calcNetto = r => {
    const u = units.value.find(x => x.name === r.unit_measurement) || { tare:0 }
    return (r.brutto||0) - (r.quantity||0)*(u.tare/1000)
  }
  const calcTotal = r => calcNetto(r) * (r.price||0)

  const totalNetto = computed(() => productRows.value.reduce((s,r)=>s+calcNetto(r),0))
  const totalSum   = computed(() => productRows.value.reduce((s,r)=>s+calcTotal(r),0))

  /* ───────────────────── save ────────────────────── */
  const isSubmitting = ref(false)
  const message      = ref('')
  const messageType  = ref('')

  async function submitSale () {
    isSubmitting.value = true
    try {
      const payload = {
        customer_id : selectedCustomerId.value,
        document_date : selectedDate.value,
        warehouse_id  : selectedWarehouseId.value,
        products: productRows.value.map(r => ({
          product_subcard_id : r.product_subcard_id,
          unit_measurement   : r.unit_measurement,
          quantity           : r.quantity,
          brutto             : r.brutto,
          netto              : calcNetto(r),
          price              : r.price,
          total_sum          : calcTotal(r)
        }))
      }

      await axios.post('/api/saleBulkStore', payload)   // ← смените URL при необходимости
      message.value = 'Продажа успешно сохранена!'
      messageType.value = 'success'

      /* reset form */
      selectedCustomerId.value  = ''
      selectedDate.value        = ''
      selectedWarehouseId.value = ''
      productRows.value = [makeEmptyRow()]
    }
    catch (e) {
      console.error(e)
      message.value = 'Ошибка при сохранении.'
      messageType.value = 'error'
    }
    finally { isSubmitting.value = false }
  }
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
