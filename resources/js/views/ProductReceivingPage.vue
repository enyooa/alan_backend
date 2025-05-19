<!-- src/pages/ProductReceivingPage.vue -->
<template>
    <div class="full-page">
      <main class="content">
        <h2 class="page-title">Поступление товара</h2>

        <!-- ────────── Карточка выбора: поставщик • дата • склад ────────── -->
        <div class="card">
          <div class="card-header"><h3>Выберите поставщика, дату и склад</h3></div>
          <div class="card-body">
            <div class="flex-row">
              <!-- Поставщик -->
              <div class="dropdown">
                <label for="provider" class="field-label">Поставщик</label>
                <select v-model="selectedProviderId"
                        id="provider"
                        class="dropdown-select">
                  <option disabled value="">— Выберите поставщика —</option>
                  <option v-for="p in providers" :key="p.id" :value="p.id">
                    {{ p.name }}
                  </option>
                </select>
              </div>

              <!-- Дата -->
              <div class="dropdown">
                <label for="date" class="field-label">Дата</label>
                <input type="date"
                       v-model="selectedDate"
                       id="date"
                       class="dropdown-select" />
              </div>

              <!-- Склад -->
              <div class="dropdown">
                <label for="warehouse" class="field-label">Склад поступления</label>
                <select v-model="selectedWarehouseId"
                        id="warehouse"
                        class="dropdown-select">
                  <option disabled value="">— Выберите склад —</option>
                  <option v-for="w in warehouses" :key="w.id" :value="w.id">
                    {{ w.name }}
                  </option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- ────────── Карточка «Товары» ────────── -->
        <div class="card mt-3">
          <div class="card-header flex-between">
            <h3>Товары</h3>
            <button @click="addProductRow" class="action-btn add-row-btn">➕ Добавить строку</button>
          </div>

          <div class="card-body">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Товар</th><th>Кол-во тары</th><th>Ед. изм / Тара</th>
                  <th>Брутто</th><th>Нетто</th><th>Цена</th>
                  <th>Сумма</th><th>Доп. расход</th><th>Себестоимость</th><th>Удалить</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(row, idx) in productRows" :key="row._key" class="table-row">
                  <!-- Товар -->
                  <td>
                    <select v-model="row.product_subcard_id" class="table-select">
                      <option disabled value="">Выберите товар</option>
                      <option v-for="prod in products" :key="prod.id" :value="prod.id">
                        {{ prod.name }}
                      </option>
                    </select>
                  </td>

                  <!-- Кол-во тары -->
                  <td>
                    <input v-model.number="row.quantity" type="number"
                           class="table-input" placeholder="Кол-во тары" />
                  </td>

                  <!-- Ед. изм -->
                  <td>
                    <select v-model="row.unit_measurement" class="table-select">
                      <option disabled value="">Ед. изм / Тара</option>
                      <option v-for="u in units"
                              :key="u.id || (u.name + u.tare)"
                              :value="u.name">
                        {{ u.name }} ({{ u.tare }} г)
                      </option>
                    </select>
                  </td>

                  <!-- Брутто -->
                  <td>
                    <input v-model.number="row.brutto" type="number"
                           class="table-input" placeholder="Брутто" />
                  </td>

                  <!-- Нетто -->
                  <td>{{ calcNetto(row).toFixed(2) }}</td>

                  <!-- Цена -->
                  <td>
                    <input v-model.number="row.price" type="number"
                           class="table-input" placeholder="Цена" />
                  </td>

                  <!-- Сумма -->
                  <td>{{ calcTotal(row).toFixed(2) }}</td>

                  <!-- Доп. расход -->
                  <td>{{ calcRowExpense(row).toFixed(2) }}</td>

                  <!-- Себестоимость -->
                  <td>{{ formatPrice(calcCostPrice(row)) }}</td>

                  <!-- Удалить -->
                  <td>
                    <button @click="removeProductRow(idx)" class="remove-btn">❌</button>
                  </td>
                </tr>

                <!-- Итоги -->
                <tr class="summary-row">
                  <td colspan="3" class="summary-label"><strong>ИТОГО</strong></td>
                  <td>-</td>
                  <td>{{ totalNetto.toFixed(2) }}</td>
                  <td>-</td>
                  <td>{{ totalSum.toFixed(2) }}</td>
                  <td>{{ totalExpenses.toFixed(2) }}</td>
                  <td>-</td><td>-</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ────────── Карточка «Дополнительные расходы» ────────── -->
        <div class="card mt-3">
          <div class="card-header flex-between">
            <h3>Дополнительные расходы</h3>
            <button @click="addExpenseRow" class="action-btn add-row-btn">➕ Добавить расход</button>
          </div>

          <div class="card-body">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Наименование</th><th>Поставщик</th><th>Сумма</th><th>Удалить</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(ex, i) in expenses" :key="ex._key" class="table-row">
                  <!-- Наименование -->
                  <td>
                    <select v-model="ex.expense_id" class="table-select"
                            @change="onExpenseSelect(ex)">
                      <option disabled value="">--- Выберите расход ---</option>
                      <option v-for="e in allExpenses" :key="e.id" :value="e.id">
                        {{ e.name }}
                      </option>
                    </select>
                  </td>

                  <!-- Поставщик -->
                  <td>
                    <select v-model="ex.provider_id" class="table-select">
                      <option disabled value="">— Поставщик —</option>
                      <option v-for="p in providers" :key="p.id" :value="p.id">
                        {{ p.name }}
                      </option>
                    </select>
                  </td>

                  <!-- Сумма -->
                  <td>
                    <input v-model.number="ex.amount" type="number"
                           class="table-input" placeholder="Сумма" />
                  </td>

                  <!-- Удалить -->
                  <td>
                    <button @click="removeExpense(i)" class="remove-btn">❌</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ────────── Кнопка «Сохранить» ────────── -->
        <div class="mt-3">
          <button @click="submitProductReceivingData"
                  class="action-btn save-btn"
                  :disabled="isSubmitting">
            {{ isSubmitting ? "⏳ Сохранение..." : "Сохранить" }}
          </button>
        </div>

        <div v-if="message" :class="['feedback-message', messageType]">{{ message }}</div>
      </main>
    </div>
  </template>


<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

/* ─── состояние ───────────────────────────────────────────── */
const selectedProviderId  = ref('')
const selectedDate        = ref('')
const selectedWarehouseId = ref('')

const providers   = ref([])
const products    = ref([])
const units       = ref([])    // { id, name, tare (в граммах) }
const allExpenses = ref([])
const warehouses  = ref([])

/* строки товаров */
const productRows = ref([newProductRow()])
function newProductRow () {
  return {
    _key : Date.now() + Math.random(),
    product_subcard_id : null,
    unit_measurement   : null,
    quantity           : 0,      // qtyTare
    brutto             : 0,
    price              : 0
  }
}
/* строки доп-расходов */
const expenses = ref([])        // { _key, expense_id, provider_id, name, amount }

/* ─── fetch из API (как было) ─────────────────────────────── */
const flatten = res => (res.refferences ?? []).flatMap(r => r.RefferenceItem ?? [])

async function fetchProviders ()  { providers.value   = (await axios.get('/api/reference/provider')).data }
async function fetchProducts  ()  { products.value    = (await axios.get('/api/reference/subproductCard')).data }
async function fetchUnits     ()  {
  const { data } = await axios.get('/api/reference/unit')
  units.value = data.map(u => ({ id:u.id, name:u.name, tare:Number(u.value)||0 }))
}
async function fetchAllExpenses (){ allExpenses.value = (await axios.get('/api/reference/expense')).data }
async function fetchWarehouses () { warehouses.value  = (await axios.get('/api/getWarehouses')).data }

onMounted(() => Promise.all([
  fetchProviders(), fetchProducts(), fetchUnits(),
  fetchAllExpenses(), fetchWarehouses()
]))

/* ─── helpers: добавить / удалить строки ──────────────────── */
function addProductRow  () { productRows.value.push(newProductRow()) }
function removeProductRow (idx){ productRows.value.splice(idx,1) }

function addExpenseRow () {
  expenses.value.push({ _key:Date.now()+Math.random(), expense_id:'', provider_id:'', name:'', amount:0 })
}
function removeExpense (idx){ expenses.value.splice(idx,1) }
function onExpenseSelect (row){
  const found = allExpenses.value.find(e=>e.id===row.expense_id)
  row.name   = found?.name   ?? ''
  row.amount = found?.amount ?? 0
}

/* ─── общие утилиты ───────────────────────────────────────── */
const isKg = n => /кг|килограмм/i.test(n ?? '')

/* цена доп-расходов на 1 единицу (тара + кг) */
function getExpPerUnit () {
  const tareCnt = productRows.value.reduce((s,r)=>s + (+r.quantity||0), 0)
  const kiloCnt = productRows.value.reduce(
    (s,r)=> isKg(r.unit_measurement) ? s + (+r.brutto||0) : s, 0
  )
  const allCnt  = tareCnt + kiloCnt
  const expSum  = expenses.value.reduce((s,e)=>s + (+e.amount||0), 0)
  return allCnt ? expSum / allCnt : 0
}

/* ─── формулы в стиле React-Native ────────────────────────── */
function calcNetto (r){
  const u = units.value.find(x=>x.name===r.unit_measurement) || { tare:0 }
  if (isKg(u.name)) return +r.brutto || 0
  const tareKg = u.tare / 1000
  return Math.max((+r.brutto||0) - (+r.quantity||0) * tareKg, 0)
}

function calcTotal (r){
  const net = calcNetto(r)
  if (isKg(r.unit_measurement)) return (+r.price||0) * net

  if ((+r.brutto||0) === 0 && net === 0)
    return (+r.price||0) * (+r.quantity||0)

  return (+r.price||0) * net
}

function calcRowExpense (r){
  const per = getExpPerUnit()
  const base = isKg(r.unit_measurement)
    ? (+r.brutto || 0)
    : (+r.quantity || 0)
  return +(base * per).toFixed(2)
}

function calcCostPrice (r){
  const base = isKg(r.unit_measurement)
    ? (+r.brutto || 0)
    : (+r.quantity || 0)
  if (!base) return 0
  return +((calcTotal(r) + calcRowExpense(r)) / base).toFixed(2)
}
const formatPrice = v => (v??0).toFixed(2)

/* ─── итоги ───────────────────────────────────────────────── */
const totalNetto    = computed(()=> productRows.value.reduce((s,r)=>s+calcNetto(r) ,0))
const totalSum      = computed(()=> productRows.value.reduce((s,r)=>s+calcTotal(r) ,0))
const totalExpenses = computed(()=> expenses.value   .reduce((s,e)=>s+(+e.amount||0),0))

/* ─── submit (как было) ───────────────────────────────────── */
const message      = ref('')
const messageType  = ref('')
const isSubmitting = ref(false)

async function submitProductReceivingData(){
  isSubmitting.value = true
  try {
    const payload = {
      provider_id          : selectedProviderId.value,
      document_date        : selectedDate.value,
      assigned_warehouse_id: selectedWarehouseId.value,
      products: productRows.value.map(r=>({
        product_subcard_id : r.product_subcard_id,
        unit_measurement   : r.unit_measurement,
        quantity           : r.quantity,
        brutto             : r.brutto,
        netto              : calcNetto(r),
        price              : r.price,
        total_sum          : calcTotal(r),
        additional_expenses: calcRowExpense(r),
        cost_price         : calcCostPrice(r)
      })),
      expenses: expenses.value.map(e=>({
        expense_id : e.expense_id,
        provider_id: e.provider_id,
        amount     : e.amount
      }))
    }
    await axios.post('/api/receivingBulkStore', payload)
    message.value     = 'Данные успешно сохранены!'
    messageType.value = 'success'
    /* reset */
    productRows.value = [newProductRow()]
    expenses.value    = []
    selectedProviderId.value  = ''
    selectedDate.value        = ''
    selectedWarehouseId.value = ''
  }
  catch(err){
    console.error(err)
    message.value     = 'Ошибка при сохранении данных.'
    messageType.value = 'error'
  }
  finally{ isSubmitting.value = false }
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
