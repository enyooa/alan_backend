<!-- src/pages/SalePage.vue -->
<template>
    <div class="full-page">
      <main class="content">
        <h2 class="page-title">Продажа</h2>

        <!-- ▸ Шапка -->
        <div class="card">
          <div class="card-header"><h3>Покупатель, дата, склад-отгрузки</h3></div>
          <div class="card-body flex-row">

            <!-- контрагент (клиент или организация) -->
            <div class="dropdown">
              <label class="field-label">Кому продаём</label>
              <select
                class="dropdown-select"
                :value="selectedCounterpartyId"
                @change="onCounterpartySelect($event.target.value)"
              >
                <option value="">— выберите —</option>
                <option
                  v-for="c in counterparties"
                  :key="c.id"
                  :value="c.id"
                >
                  {{ c.name }} ({{ typeRu(c.type) }})
                </option>
              </select>
            </div>

            <!-- дата -->
            <div class="dropdown">
              <label class="field-label">Дата продажи</label>
              <input type="date" v-model="docDate" class="dropdown-select" />
            </div>

            <!-- склад -->
            <div class="dropdown">
              <label class="field-label">Склад-отгрузки</label>
              <select
                v-model="warehouseId"
                class="dropdown-select"
                @change="loadLeftovers"
              >
                <option value="">— склад —</option>
                <option v-for="w in warehouses" :key="w.id" :value="w.id">
                  {{ w.name }}
                </option>
              </select>
            </div>
          </div>
        </div>

        <!-- ▸ Две карточки: продажа + остатки -->
        <div class="cards-container mt-3">

          <!-- левая: таблица продажи -->
          <div class="card card-sale">
            <div class="card-header flex-between">
              <h3>Товары</h3>
              <button class="action-btn add-row-btn" @click="addRow">➕ строка</button>
            </div>

            <div class="card-body">
              <table class="styled-table">
                <thead>
                  <tr>
                    <th>Товар / партия (остаток)</th><th>Кол-во тары</th>
                    <th>Ед. изм</th><th>Брутто</th><th>Нетто</th>
                    <th>Цена</th><th>Сумма</th><th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="(r,i) in rows" :key="r._k">
                    <!-- партия -->
                    <td>
                      <select
                        class="table-select"
                        v-model="r._selected"
                        @change="onBatchSelect(r)"
                      >
                        <option value="">— партия —</option>
                        <option
                          v-for="b in leftoversForSelect"
                          :key="b.key"
                          :value="b.key"
                        >
                          {{ b.label }}
                        </option>
                      </select>
                    </td>

                    <!-- qtyTare -->
                    <td>
                      <input
                        type="number" min="0" :max="r.balance"
                        v-model.number="r.qtyTare"
                        class="table-input"
                      />
                    </td>

                    <!-- unit -->
                    <td>
                      <input
                        class="table-input readonly"
                        :value="r.product.unit_measurement"
                        readonly
                      />
                    </td>

                    <!-- brutto -->
                    <td><input type="number" min="0" v-model.number="r.brutto" class="table-input" /></td>
                    <!-- netto -->
                    <td>{{ netto(r).toFixed(3) }}</td>
                    <!-- price -->
                    <td><input type="number" min="0" step="0.01" v-model.number="r.price" class="table-input" /></td>
                    <!-- total -->
                    <td>{{ total(r).toFixed(2) }}</td>
                    <!-- delete -->
                    <td><button class="remove-btn" @click="rows.splice(i,1)">❌</button></td>
                  </tr>

                  <!-- итог -->
                  <tr class="summary-row">
                    <td colspan="4" class="summary-label"><strong>ИТОГО</strong></td>
                    <td>{{ totalNetto.toFixed(3) }}</td><td>-</td>
                    <td>{{ totalSum.toFixed(2) }}</td><td>-</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- правая: остатки -->
          <div class="card card-leftovers">
            <div class="card-header"><h3>Остатки ({{ whName }})</h3></div>
            <div class="card-body">
              <table class="styled-table">
                <thead><tr><th>Товар</th><th>Остаток</th></tr></thead>
                <tbody>
                  <tr
                    v-for="l in leftovers"
                    :key="l.product_subcard_id + l.unit_measurement"
                  >
                    <td>{{ l.name }}</td>
                    <td>{{ format(l.balance) }} {{ l.unit_measurement }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div><!-- /cards-container -->

        <!-- сохранить -->
        <div class="mt-3">
          <button
            class="action-btn save-btn"
            :disabled="submitting"
            @click="save"
          >
            {{ submitting ? '⏳…' : '💾 Сохранить' }}
          </button>
        </div>

        <div v-if="msg" :class="['feedback-message', msgType]">{{ msg }}</div>
      </main>
    </div>
  </template>

  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import axios from '@/plugins/axios'

  /* ─────────── справочники ─────────── */
  const counterparties = ref([])
  const warehouses     = ref([])
  const products       = ref([])
  const units          = ref([])

  /* ─────────── выборы ─────────── */
  const clientId       = ref('')   // ID клиента-покупателя
  const organizationId = ref('')   // ID организации-покупателя
  const docDate        = ref('')
  const warehouseId    = ref('')

  /* текущее значение select-а */
  const selectedCounterpartyId = computed(() => clientId.value || organizationId.value)

  /* ─────────── остатки ─────────── */
  const leftovers  = ref([])
  const whName = computed(() =>
    warehouses.value.find(w => w.id === warehouseId.value)?.name || '—'
  )

  function loadLeftovers () {
    leftovers.value = []
    if (!warehouseId.value) return
    axios.get('/api/warehouse-items', { params:{ warehouse_id: warehouseId.value } })
         .then(r => leftovers.value = r.data)
  }

  const leftoversForSelect = computed(() => leftovers.value.map(l => ({
    key  : l.product_subcard_id + '|' + l.unit_measurement,
    label: `${l.name} ▸ ${format(l.balance)} ${l.unit_measurement}`,
    ...l
  })))

  /* ─────────── строки таблицы ─────────── */
  function makeRow () {
    return {
      _k       : Date.now() + Math.random(),
      _selected: '',
      product  : { product_subcard_id:'', unit_measurement:'' },
      balance  : 0,
      qtyTare  : 0,
      brutto   : 0,
      price    : 0
    }
  }
  const rows = ref([makeRow()])
  const addRow = () => rows.value.push(makeRow())

  function onBatchSelect (row) {
    const found = leftoversForSelect.value.find(b => b.key === row._selected)
    if (!found) return
    row.product.product_subcard_id = found.product_subcard_id
    row.product.unit_measurement   = found.unit_measurement
    row.balance = +found.balance
    row.qtyTare = 0
    row.brutto  = 0
  }

  /* ─────────── fetch on mount ─────────── */
  onMounted(() => Promise.all([
    axios.get('/api/counterparty').then(r => counterparties.value = r.data),
    axios.get('/api/getWarehouses').then(r => warehouses.value = r.data),
    axios.get('/api/reference/subproductCard').then(r => products.value = r.data),
    axios.get('/api/reference/unit')
         .then(r => units.value = r.data.map(u => ({
           id:u.id, name:u.name, tare:+u.value||0
         })))
  ]))

  /* ─────────── расчёты ─────────── */
  const isKg = n => /кг|килограмм/i.test(n || '')
  function netto (r) {
    const u = units.value.find(x => x.name === r.product.unit_measurement) || { tare:0 }
    return isKg(u.name)
      ? (+r.brutto || 0)
      : (+r.brutto || 0) - (+r.qtyTare || 0) * (u.tare / 1000)
  }
  const total      = r => netto(r) * (+r.price || 0)
  const totalNetto = computed(() => rows.value.reduce((s,r) => s + netto(r), 0))
  const totalSum   = computed(() => rows.value.reduce((s,r) => s + total(r), 0))
  const format     = v => (+v).toFixed(3).replace(/\.?0+$/,'')
  const typeRu     = t => t === 'client' ? 'клиент' : t === 'provider' ? 'поставщик' : 'организация'

  /* ─────────── контрагент выбран ─────────── */
  function onCounterpartySelect (id) {
    clientId.value       = ''
    organizationId.value = ''
    const found = counterparties.value.find(c => c.id === id)
    if (!found) return
    if (found.type === 'client')       clientId.value       = found.id
    if (found.type === 'organization') organizationId.value = found.id
  }

  /* ─────────── сохранение ─────────── */
  const submitting = ref(false)
  const msg = ref(''), msgType = ref('')
  async function save () {
    if (!selectedCounterpartyId.value || !warehouseId.value || !docDate.value) {
      alert('Заполните покупателя, склад и дату'); return
    }
    for (const r of rows.value) {
      if (r.qtyTare > r.balance) {
        alert('Количество превышает остаток по одной из партий'); return
      }
    }

    submitting.value = true
    try {
      await axios.post('/api/sales-products-web', {
        client_id            : clientId.value || undefined,
        to_organization_id   : organizationId.value || undefined,
        assigned_warehouse_id: warehouseId.value,
        docDate              : docDate.value,
        products: rows.value.map(r => ({
          product: {
            product_subcard_id : r.product.product_subcard_id,
            unit_measurement   : r.product.unit_measurement
          },
          qtyTare  : r.qtyTare,
          brutto   : r.brutto,
          netto    : netto(r),
          price    : r.price,
          total_sum: total(r)
        }))
      })
      msg.value = 'Сохранено'; msgType.value = 'success'
      rows.value = [makeRow()]; loadLeftovers()
    } catch (e) {
      console.error(e)
      msg.value = 'Ошибка: ' + (e.response?.data?.error || 'неизвестно')
      msgType.value = 'error'
    } finally {
      submitting.value = false
      setTimeout(() => (msg.value = ''), 3000)
    }
  }
  </script>



  <style scoped>
  /* базовые стили */
  .full-page{width:100vw;min-height:100vh;background:#f5f5f5}
  .content{max-width:1100px;margin:0 auto;padding:20px}
  .page-title{text-align:center;color:#0288d1;margin-bottom:20px;font-size:1.5rem}

  .card{background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.1);margin-bottom:20px;overflow:hidden}
  .card-header{background:#f1f1f1;padding:12px 16px;border-bottom:1px solid #ddd}
  .card-body{padding:16px}
  .flex-row{display:flex;gap:20px;flex-wrap:wrap}

  .dropdown{display:flex;flex-direction:column;gap:6px;min-width:220px}
  .field-label{font-weight:bold;color:#555}
  .dropdown-select{padding:12px;border:1px solid #ddd;border-radius:6px;font-size:14px}

  .cards-container{display:flex;gap:20px}
  .card-sale{flex:2}.card-leftovers{flex:1}

  .styled-table{width:100%;border-collapse:collapse}
  .styled-table th,.styled-table td{border:1px solid #ddd;padding:8px;text-align:center}
  .styled-table thead{background:#0288d1;color:#fff}
  .table-select,.table-input{width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:14px}
  .table-input.readonly{background:#f7f7f7}

  .action-btn{background:#0288d1;color:#fff;border:none;border-radius:6px;padding:10px 14px;cursor:pointer}
  .action-btn:hover{background:#0270a0}.save-btn{width:100%}
  .remove-btn{background:#f44336;color:#fff;border:none;border-radius:6px;padding:8px 10px}

  .summary-row td{background:#f8f8f8;font-weight:bold}
  .summary-label{text-align:right}

  .feedback-message{margin-top:20px;text-align:center;font-weight:bold;padding:10px;border-radius:8px}
  .feedback-message.success{background:#d4edda;color:#155724}
  .feedback-message.error  {background:#f8d7da;color:#721c24}
  </style>
