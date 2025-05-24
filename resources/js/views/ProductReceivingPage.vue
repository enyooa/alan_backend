<template>
  <div class="full-page">
    <main class="content">
      <h2 class="page-title">Поступление товара</h2>

      <!-- ▸ Поставщик / Дата / Склад -->
      <div class="card">
        <div class="card-header"><h3>Выберите поставщика, дату и склад</h3></div>
        <div class="card-body">
          <div class="flex-row">
            <!-- Поставщик -->
            <div class="dropdown">
              <label class="field-label" for="provider">Поставщик</label>
              <select v-model="selectedProviderId" id="provider" class="dropdown-select">
                <option disabled value="">— Выберите поставщика —</option>
                <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
              </select>
            </div>

            <!-- Дата -->
            <div class="dropdown">
              <label class="field-label" for="date">Дата</label>
              <input type="date" v-model="selectedDate" id="date" class="dropdown-select" />
            </div>

            <!-- Склад -->
            <div class="dropdown">
              <label class="field-label" for="warehouse">Склад поступления</label>
              <select v-model="selectedWarehouseId" id="warehouse" class="dropdown-select">
                <option disabled value="">— Выберите склад —</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- ▸ Таблица товаров -->
      <div class="card mt-3">
        <div class="card-header flex-between">
          <h3>Товары</h3>
          <button class="action-btn add-row-btn" @click="addProductRow">➕ Добавить строку</button>
        </div>

        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr>
                <th>Товар</th><th>Кол-во тары</th><th>Ед. изм / тара</th>
                <th>Брутто</th><th>Нетто</th><th>Цена</th>
                <th>Сумма</th><th>Доп. расход</th><th>Себестоимость</th><th></th>
              </tr>
            </thead>

            <tbody>
              <tr v-for="(row, idx) in productRows" :key="row._key">
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
                  <input v-model.number="row.quantity"
                         type="number"
                         class="table-input"
                         :disabled="isKg(row.unit_measurement)"
                         :class="{disabled: isKg(row.unit_measurement)}" />
                </td>

                <!-- Ед. изм -->
                <td>
                  <select v-model="row.unit_measurement" class="table-select">
                    <option disabled value="">Ед. изм / тара</option>
                    <option v-for="u in units" :key="u.id" :value="u.name">
                      {{ u.name }} ({{ u.tare }} г)
                    </option>
                  </select>
                </td>

                <!-- Брутто -->
                <td>
                  <input v-model.number="row.brutto"
                         type="number"
                         class="table-input"
                         :disabled="disableBrutto(row)"
                         :class="{disabled: disableBrutto(row)}" />
                </td>

                <!-- Нетто -->
                <td>{{ calcNetto(row).toFixed(2) }}</td>

                <!-- Цена -->
                <td><input v-model.number="row.price" type="number" class="table-input" /></td>

                <!-- Сумма -->
                <td>{{ calcTotal(row).toFixed(2) }}</td>

                <!-- Доп. расход -->
                <td>{{ calcRowExpense(row).toFixed(2) }}</td>

                <!-- Себестоимость -->
                <td>{{ calcCostPrice(row).toFixed(2) }}</td>

                <!-- Удалить -->
                <td>
                  <button class="remove-btn" @click="removeProductRow(idx)">❌</button>
                </td>
              </tr>

              <!-- Итоги -->
              <tr class="summary-row">
                <td colspan="4" class="summary-label">ИТОГО</td>
                <td>{{ totalNetto.toFixed(2) }}</td>
                <td></td>
                <td>{{ totalSum.toFixed(2) }}</td>
                <td>{{ totalExpenses.toFixed(2) }}</td>
                <td colspan="2"></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ▸ Доп. расходы -->
      <div class="card mt-3">
        <div class="card-header flex-between">
          <h3>Дополнительные расходы</h3>
          <button class="action-btn add-row-btn" @click="addExpenseRow">➕ Добавить расход</button>
        </div>

        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr><th>Наименование</th><th>Поставщик</th><th>Сумма</th><th></th></tr>
            </thead>
            <tbody>
              <tr v-for="(ex,i) in expenses" :key="ex._key">
                <td>
                  <select v-model="ex.expense_id" class="table-select" @change="onExpenseSelect(ex)">
                    <option disabled value="">---</option>
                    <option v-for="e in allExpenses" :key="e.id" :value="e.id">{{ e.name }}</option>
                  </select>
                </td>
                <td>
                  <select v-model="ex.provider_id" class="table-select">
                    <option disabled value="">— Поставщик —</option>
                    <option v-for="p in providers" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </select>
                </td>
                <td><input v-model.number="ex.amount" type="number" class="table-input" /></td>
                <td><button class="remove-btn" @click="removeExpense(i)">❌</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- ▸ Save -->
      <button class="action-btn save-btn" :disabled="isSubmitting" @click="submitData">
        {{ isSubmitting ? '⏳ Сохранение…' : 'Сохранить' }}
      </button>
      <div v-if="message" :class="['feedback-message', messageType]">{{ message }}</div>
    </main>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import axios from '@/plugins/axios'

export default {
  setup () {
    /* ─── reactive state ─── */
    const selectedProviderId  = ref('')
    const selectedDate        = ref('')
    const selectedWarehouseId = ref('')

    const providers   = ref([])
    const products    = ref([])
    const units       = ref([])   // { id, name, tare }
    const allExpenses = ref([])
    const warehouses  = ref([])

    /* helpers */
    const isKg = n => /кг|килограмм/i.test(n || '')

    /* rows */
    const newProdRow = () => ({
      _key: Date.now() + Math.random(),
      product_subcard_id: '',
      unit_measurement  : '',
      quantity          : 0,
      brutto            : 0,
      price             : 0
    })
    const productRows = ref([newProdRow()])
    const expenses = ref([])

    /* ─── fetch reference lists ─── */
    const plain = d => Array.isArray(d) ? d : (d.data ?? [])

    const fetchAll = async () => {
      providers.value   = plain((await axios.get('/api/references/provider')).data)
      products.value    = plain((await axios.get('/api/references/subproductCard')).data)

      const u = plain((await axios.get('/api/references/unit')).data)
      units.value = u.map(x => ({ id: x.id, name: x.name, tare: +x.tare || 0 }))

      allExpenses.value = plain((await axios.get('/api/references/expense')).data)
      warehouses.value  = plain((await axios.get('/api/getWarehouses')).data)
    }
    onMounted(fetchAll)

    /* === calculations ======================================== */
    const unitByName = n => units.value.find(x => x.name === n) || { tare: 0 }

    const calcNetto = r => {
      if (isKg(r.unit_measurement)) return +r.brutto || 0
      const u = unitByName(r.unit_measurement)
      return Math.max((+r.brutto || 0) - (+r.quantity || 0) * (u.tare / 1000), 0)
    }

    const calcTotal = r => {
      const n = calcNetto(r)
      if (isKg(r.unit_measurement)) return (+r.price || 0) * n
      if ((+r.brutto || 0) === 0 && n === 0) return (+r.price || 0) * (+r.quantity || 0)
      return (+r.price || 0) * n
    }

    /* disable brutto if tare==0 & not Kg */
    const disableBrutto = r => {
      const u = unitByName(r.unit_measurement)
      return u.tare === 0 && !isKg(u.name)
    }

    /* extra expenses per unit */
    const expPerUnit = computed(() => {
      const tare = productRows.value.reduce((s, r) => s + (+r.quantity || 0), 0)
      const kilo = productRows.value.reduce((s, r) =>
        isKg(r.unit_measurement) ? s + (+r.brutto || 0) : s, 0)
      const sum  = expenses.value.reduce((s, e) => s + (+e.amount || 0), 0)
      const all  = tare + kilo
      return all ? sum / all : 0
    })

    const calcRowExpense = r => {
      const base = isKg(r.unit_measurement) ? (+r.brutto || 0) : (+r.quantity || 0)
      return +(base * expPerUnit.value).toFixed(2)
    }

    const calcCostPrice = r => {
      const base = isKg(r.unit_measurement) ? (+r.brutto || 0) : (+r.quantity || 0)
      return base ? +((calcTotal(r) + calcRowExpense(r)) / base).toFixed(2) : 0
    }

    /* totals */
    const totalNetto    = computed(() => productRows.value.reduce((s, r) => s + calcNetto(r), 0))
    const totalSum      = computed(() => productRows.value.reduce((s, r) => s + calcTotal(r), 0))
    const totalExpenses = computed(() => expenses.value.reduce((s, e) => s + (+e.amount || 0), 0))

    /* === rows handlers ======================================= */
    const addProductRow    = () => productRows.value.push(newProdRow())
    const removeProductRow = idx => productRows.value.splice(idx, 1)

    const addExpenseRow    = () =>
      expenses.value.push({ _key: Date.now() + Math.random(), expense_id: '', provider_id: '', name: '', amount: 0 })
    const removeExpense    = idx => expenses.value.splice(idx, 1)

    const onExpenseSelect = row => {
      const f = allExpenses.value.find(x => x.id === row.expense_id)
      row.name = f ? f.name : ''
    }

    /* === submit ============================================== */
    const isSubmitting = ref(false)
    const message      = ref('')
    const messageType  = ref('')

    const submitData = async () => {
      isSubmitting.value = true
      try {
        await axios.post('/api/receivingBulkStore', {
          provider_id          : selectedProviderId.value,
          document_date        : selectedDate.value,
          assigned_warehouse_id: selectedWarehouseId.value,

          products: productRows.value.map(r => ({
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

          expenses: expenses.value.map(e => ({
            expense_id : e.expense_id,
            provider_id: e.provider_id,
            amount     : e.amount
          }))
        })

        message.value     = 'Данные успешно сохранены!'
        messageType.value = 'success'
        /* reset формы */
        productRows.value = [newProdRow()]
        expenses.value    = []
      }
      catch (err) {
        console.error(err)
        message.value     = 'Ошибка при сохранении'
        messageType.value = 'error'
      }
      finally { isSubmitting.value = false }
    }

    /* expose */
    return {
      selectedProviderId, selectedDate, selectedWarehouseId,
      providers, products, units, allExpenses, warehouses,

      productRows, expenses,
      addProductRow, removeProductRow,
      addExpenseRow, removeExpense, onExpenseSelect,

      isKg, disableBrutto,
      calcNetto, calcTotal, calcRowExpense, calcCostPrice,

      totalNetto, totalSum, totalExpenses,

      isSubmitting, submitData,
      message, messageType
    }
  }
}
</script>

<!-- Стили те же, что присылались ранее (ниже оставлены) -->
<style scoped>
/* Full Page Container */
.full-page{width:100vw;min-height:100vh;background:#f5f5f5}
/* Content */
.content{max-width:1100px;margin:0 auto;padding:20px;width:100%}
.page-title{text-align:center;color:#0288d1;margin-bottom:20px;font-size:1.5rem}

/* Cards */
.card{background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.1);margin-bottom:20px;overflow:hidden}
.card-header{background:#f1f1f1;padding:12px 16px;border-bottom:1px solid #ddd}
.card-header h3{margin:0;color:#333}
.card-body{padding:16px}
.mt-3{margin-top:20px}
.flex-between{display:flex;justify-content:space-between;align-items:center}
.flex-row{display:flex;flex-wrap:wrap;gap:20px}

/* Inputs */
.field-label{font-weight:bold;color:#555;margin-bottom:6px;display:inline-block}
.dropdown-select,.table-select{width:100%;padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px}
.table-input{width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:14px}
.table-input.disabled{background:#f5f5f5;color:#888;cursor:not-allowed}

/* Table */
.styled-table{width:100%;border-collapse:collapse}
.styled-table thead{background:#0288d1;color:#fff}
.styled-table th,.styled-table td{padding:10px;border:1px solid #ddd;text-align:center;font-size:14px}
.summary-row td{background:#f8f8f8;font-weight:bold}
.summary-label{text-align:right}

/* Buttons */
.action-btn{display:inline-flex;align-items:center;justify-content:center;
            background:#0288d1;color:#fff;border:none;border-radius:8px;padding:10px 18px;
            cursor:pointer;font-size:14px;transition:.3s}
.action-btn:hover{background:#026ca0}
.save-btn{width:100%;margin-top:8px}
.add-row-btn{font-size:15px}
.remove-btn{background:#f44336;color:#fff;border:none;border-radius:6px;padding:8px 10px;cursor:pointer;font-size:14px}
.remove-btn:hover{background:#d32f2f}

/* Messages */
.feedback-message{margin-top:20px;text-align:center;font-weight:bold;padding:10px;border-radius:8px}
.feedback-message.success{background:#d4edda;color:#155724}
.feedback-message.error  {background:#f8d7da;color:#721c24}
</style>
