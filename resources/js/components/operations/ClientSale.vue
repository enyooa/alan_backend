<!-- resources/js/pages/ClientSalePage.vue -->
<template>
    <div class="write-off-page-container">
      <h2 class="page-title">Продажа клиенту / организации</h2>

      <!-- ▸ Шапка -->
      <div class="card">
        <div class="card-header"><h3>Покупатель, склад и дата</h3></div>
        <div class="card-body top-row">
          <!-- Клиент -->
          <div class="dropdown-column">
            <label class="dropdown-label">Кому продаём</label>
            <select v-model="clientId" class="dropdown-select">
              <option value="">— выберите —</option>
              <option
                v-for="b in buyers"
                :key="b.client_id"
                :value="b.client_id"
                :title="makeAddressHint(b)"
              >
                {{ b.client_name }}
              </option>
            </select>
          </div>

          <!-- Склад -->
          <div class="dropdown-column">
            <label class="dropdown-label">Со склада</label>
            <select v-model="warehouseId" class="dropdown-select" @change="loadLeftovers">
              <option value="">— склад —</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">
                {{ w.name }}
              </option>
            </select>
          </div>

          <!-- Дата -->
          <div class="dropdown-column">
            <label class="dropdown-label">Дата продажи</label>
            <input type="date" v-model="saleDate" class="dropdown-select" />
          </div>
        </div>
      </div>

      <!-- ▸ Две карточки -->
      <div class="cards-container mt-3">
        <!-- ▸ Таблица продажи -->
        <div class="card card-writeoff">
          <div class="card-header flex-between">
            <h3>Таблица продажи</h3>
            <button class="action-btn" @click="addRow">➕ строка</button>
          </div>

          <div class="card-body">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Товар&nbsp;(ост.)</th><th>Кол-во тары</th><th>Ед. изм</th>
                  <th>Брутто</th><th>Нетто</th><th>Цена</th><th>Сумма</th><th></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(r,i) in rows" :key="r._k">
                  <!-- товар -->
                  <td>
                    <select v-model="r.product.product_subcard_id"
                            class="table-select"
                            @change="resetRow(r)">
                      <option value="">— товар —</option>
                      <option v-for="l in leftovers"
                              :key="l.product_subcard_id"
                              :value="l.product_subcard_id">
                        {{ l.name }} ({{ format(l.balance) }})
                      </option>
                    </select>
                  </td>

                  <!-- qty tare -->
                  <td><input type="number" min="0"
                             v-model.number="r.qtyTare"
                             class="table-input" /></td>

                  <!-- unit -->
                  <td>
                    <select v-model="r.unit.name" class="table-select">
                      <option value="">—</option>
                      <option v-for="u in units" :key="u.id" :value="u.name">
                        {{ u.name }} ({{ u.tare }} г)
                      </option>
                    </select>
                  </td>

                  <!-- brutto -->
                  <td><input type="number" min="0"
                             v-model.number="r.brutto"
                             class="table-input" /></td>

                  <!-- netto -->
                  <td>{{ netto(r).toFixed(3) }}</td>

                  <!-- price -->
                  <td><input type="number" min="0" step="0.01"
                             v-model.number="r.price"
                             class="table-input" /></td>

                  <!-- sum -->
                  <td>{{ (netto(r)*r.price).toFixed(2) }}</td>

                  <!-- delete -->
                  <td><button class="remove-btn" @click="rows.splice(i,1)">❌</button></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-2">
            <button class="action-btn save-btn" :disabled="saving" @click="save">
              {{ saving ? '⏳…' : '💾 Сохранить' }}
            </button>
          </div>
          <div v-if="msg" :class="['feedback-message', msgType]">{{ msg }}</div>
        </div>

        <!-- ▸ Остатки -->
        <div class="card card-leftovers">
          <div class="card-header"><h3>Остатки ({{ warehouseName }})</h3></div>
          <div class="card-body">
            <table class="styled-table">
              <thead><tr><th>Товар</th><th>Остаток</th></tr></thead>
              <tbody>
                <tr v-for="l in leftovers" :key="l.product_subcard_id">
                  <td>{{ l.name }}</td>
                  <td>{{ format(l.balance) }} {{ l.unit_measurement }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /cards-container -->
    </div>
  </template>

  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import axios from '@/plugins/axios'

  /* ───── справочники ───── */
  const buyers     = ref([])
  const warehouses = ref([])
  const units      = ref([])

  /* ───── выбранные значения ───── */
  const clientId    = ref('')
  const warehouseId = ref('')
  const saleDate    = ref('')

  /* ───── остатки ───── */
  const leftovers = ref([])

  /* ───── строки продажи ───── */
  const rows = ref([makeRow()])
  function makeRow () {
    return {
      _k : Date.now()+Math.random(),
      product : { product_subcard_id : '' },
      unit    : { name : '' },
      qtyTare : 0,
      brutto  : 0,
      price   : 0
    }
  }

  /* ───── первичная загрузка ───── */
  onMounted(() => Promise.all([
    axios.get('/api/getClientAdresses')
         .then(r => buyers.value = r.data?.data || []),
    axios.get('/api/getWarehouses')
         .then(r => warehouses.value = r.data),
    axios.get('/api/reference/unit')
         .then(r => units.value = r.data.map(u => ({
           id:u.id, name:u.name, tare:+u.value||0
         })))
  ]))

  /* ───── остатки склада ───── */
  const warehouseName = computed(() => {
    const w = warehouses.value.find(x => x.id === warehouseId.value)
    return w ? w.name : '—'
  })
  async function loadLeftovers () {
    leftovers.value = []
    if (!warehouseId.value) return
    const { data } = await axios.get('/api/warehouse-items',
                                     { params:{ warehouse_id: warehouseId.value }})
    leftovers.value = data
  }

  /* ───── расчёты ───── */
  const isKg = n => /кг|килограмм/i.test(n ?? '')
  const format = v => (+v).toFixed(3).replace(/\.?0+$/,'')
  function netto (r) {
    const u = units.value.find(x => x.name === r.unit.name) || { tare:0 }
    if (isKg(u.name)) return +r.brutto || 0
    return (+r.brutto || 0) - (+r.qtyTare || 0)*(u.tare/1000)
  }

  /* ───── helpers ───── */
  function addRow ()   { rows.value.push(makeRow()) }
  function resetRow(r) { r.qtyTare=0; r.brutto=0; r.unit.name='' }
  function makeAddressHint (b){
    return b.addresses?.length ? b.addresses.map(a=>a.name).join(' / ') : 'Адресов нет'
  }

  /* ───── save ───── */
  const saving  = ref(false)
  const msg     = ref('')
  const msgType = ref('')

  async function save () {
    if (!clientId.value || !warehouseId.value || !saleDate.value) {
      alert('Заполните покупателя, склад и дату'); return
    }

    const products = rows.value.map(r => ({
      product : { product_subcard_id : r.product.product_subcard_id },
      unit    : { name : r.unit.name },
      amount      : +netto(r).toFixed(3),
      price       : r.price,
      total_sum   : +(netto(r)*r.price).toFixed(2)
    }))

    saving.value = true
    try {
      await axios.post('/api/sales', {
        client_id   : clientId.value,
        warehouse_id: warehouseId.value,
        sale_date   : saleDate.value,
        products
      })
      msg.value='Сохранено'; msgType.value='success'
      rows.value=[makeRow()]; loadLeftovers()
    }
    catch (e) {
      console.error(e); msg.value='Ошибка'; msgType.value='error'
    }
    finally {
      saving.value=false; setTimeout(()=>msg.value='',3000)
    }
  }
  </script>

  <style scoped>
  /* — стили (без изменений) — */
  .write-off-page-container {max-width:1200px;margin:0 auto;padding:20px}
  .page-title{text-align:center;color:#0288d1;margin-bottom:20px;font-size:1.4rem}
  .card{background:#fff;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.1);margin-bottom:20px;overflow:hidden}
  .card-header{background:#f1f1f1;padding:12px 16px;border-bottom:1px solid #ddd}
  .card-header h3{margin:0;color:#333}
  .card-body{padding:16px}
  .top-row{display:flex;gap:20px;flex-wrap:wrap}
  .dropdown-column{display:flex;flex-direction:column;gap:6px;min-width:180px}
  .dropdown-label{font-weight:bold;color:#555}
  .dropdown-select{padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;width:100%}
  .cards-container{display:flex;gap:20px}
  .card-writeoff{flex:2}
  .card-leftovers{flex:1}
  .styled-table{width:100%;border-collapse:collapse}
  .styled-table thead tr{background:#0288d1;color:#fff}
  .styled-table th,.styled-table td{border:1px solid #ddd;padding:8px;text-align:center}
  .table-select{width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:14px}
  .table-input{width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:14px;text-align:right}
  .action-btn{background:#0288d1;color:#fff;border:none;border-radius:6px;padding:10px 14px;cursor:pointer;font-size:14px}
  .action-btn:hover{background:#0270a0}
  .save-btn{width:100%}
  .remove-btn{background:#f44336;color:#fff;border:none;border-radius:6px;padding:8px 10px;cursor:pointer}
  .remove-btn:hover{background:#d32f2f}
  .mt-2{margin-top:12px}
  .mt-3{margin-top:20px}
  .flex-between{display:flex;justify-content:space-between;align-items:center}
  .feedback-message{margin-top:16px;text-align:center;font-weight:bold;padding:10px;border-radius:6px}
  .feedback-message.success{background:#d4edda;color:#155724}
  .feedback-message.error  {background:#f8d7da;color:#721c24}
  </style>
