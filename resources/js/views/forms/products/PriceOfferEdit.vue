<!-- resources/js/views/forms/products/EditPriceOfferModal.vue -->
<template>
  <div class="price-offer-container">
    <h2 class="page-title">
      {{ isNew ? 'Новое ценовое предложение'
               : 'Редактировать ценовое предложение' }}
    </h2>

    <!-- (1) Склад · Клиент · Адрес · Даты -->
    <div class="card">
      <div class="card-header"><h3>Склад, Клиент, Адрес, Даты</h3></div>

      <div class="card-body top-row">
        <!-- СКЛАД -->
        <div class="dropdown-column">
          <label class="dropdown-label">Склад *</label>
          <select v-model="form.warehouse_id" class="dropdown-select">
            <option value="">— Выберите склад —</option>
            <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
              {{ wh.name }}
            </option>
          </select>
        </div>

        <!-- КЛИЕНТ -->
        <div class="dropdown-column">
          <label class="dropdown-label">Клиент *</label>
          <select v-model="form.client_id" class="dropdown-select"
                  @change="form.address_id = ''">
            <option value="">— Выберите клиента —</option>
            <option v-for="c in clients" :key="c.client_id" :value="c.client_id">
              {{ c.client_name }}
            </option>
          </select>
        </div>

        <!-- АДРЕС -->
        <div class="dropdown-column">
          <label class="dropdown-label">Адрес *</label>
          <select v-model="form.address_id" class="dropdown-select">
            <option value="">— Выберите адрес —</option>
            <option v-for="a in addressesForClient" :key="a.id" :value="a.id">
              {{ a.name }}
            </option>
          </select>
        </div>

        <!-- ДАТЫ -->
        <div class="dropdown-column">
          <label class="dropdown-label">Начало *</label>
          <input type="date" v-model="form.start_date" class="dropdown-select" />
        </div>
        <div class="dropdown-column">
          <label class="dropdown-label">Конец *</label>
          <input type="date" v-model="form.end_date" class="dropdown-select" />
        </div>
      </div>
    </div>

    <!-- (2) Таблица товаров -->
    <div class="card mt-3">
      <div class="card-header flex-between">
        <h3>Товары</h3>
        <button class="action-btn" @click="addRow">➕ строка</button>
      </div>

      <div class="card-body">
        <table class="styled-table">
          <thead>
            <tr>
              <th>Подкарточка</th>
              <th>Ед.</th>
              <th>Кол-во</th>
              <th>Цена</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(r,i) in rows" :key="r._key">
              <td>
                <select v-model="r.product_subcard_id" class="table-select">
                  <option value="">—</option>
                  <option v-for="s in subcards" :key="s.id" :value="s.id">
                    {{ s.name }}
                  </option>
                </select>
              </td>
              <td>
                <select v-model="r.unit_measurement" class="table-select">
                  <option value="">—</option>
                  <option v-for="u in units" :key="u.id" :value="u.name">
                    {{ u.name }}
                  </option>
                </select>
              </td>
              <td><input v-model.number="r.amount" type="number" min="0" class="table-input" /></td>
              <td><input v-model.number="r.price"  type="number" min="0" class="table-input" /></td>
              <td><button class="remove-btn" @click="rows.splice(i,1)">❌</button></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- (3) кнопки -->
    <button class="action-btn save-btn" :disabled="saving" @click="save">
      {{ saving ? '⏳…' : '💾 Сохранить' }}
    </button>
    <div v-if="msg" :class="['feedback-message', msgType]">{{ msg }}</div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from 'vue'
import axios from '@/plugins/axios'
import { v4 as uuid } from 'uuid'

/* ---------- props / emit ---------- */
const props = defineProps({ record: { type: Object, required: true } })
const emit  = defineEmits(['saved'])

/* ---------- reactive state ---------- */
const form = reactive({
  warehouse_id : '',
  client_id    : '',
  address_id   : '',
  start_date   : '',
  end_date     : ''
})

const rows = ref([])

/* ---------- reference lists ---------- */
const warehouses = ref([])
const clients    = ref([])
const subcards   = ref([])
const units      = ref([])

/* helper: add if not exists */
const pushIfMissing = (arr, obj, key = 'id') => {
  if (!obj || !obj[key]) return
  if (!arr.find(x => x[key] === obj[key])) arr.push(obj)
}

/* init from parent record */
watch(() => props.record, rec => {
  if (!rec) return

  Object.assign(form, {
    warehouse_id: rec.warehouse_id || '',
    client_id   : rec.client_id,
    address_id  : rec.address_id,
    start_date  : (rec.start_date || '').slice(0, 10),
    end_date    : (rec.end_date   || '').slice(0, 10)
  })

  rows.value = (rec.items || []).map(it => ({
    _key               : it.id,
    id                 : it.id,
    product_subcard_id : it.product_subcard_id,
    unit_measurement   : it.unit_measurement || it.unit?.name || '',
    amount             : +it.amount || 0,
    price              : +it.price  || 0
  }))

  ;(rec.items || []).forEach(it => {
    if (it.product) pushIfMissing(subcards.value, it.product)
    if (it.unit)
      pushIfMissing(
        units.value,
        { id: it.unit.id || uuid(), name: it.unit.name, tare: +it.unit.tare || 0 },
        'name'
      )
  })
}, { immediate: true })

/* computed: адреса клиента */
const addressesForClient = computed(() => {
  const c = clients.value.find(x => x.client_id === form.client_id)
  return c ? c.addresses : []
})

/* fetch reference data */
;(async () => {
  try {
    const [
      { data: c },
      { data: s },
      { data: u },
      { data: w }
    ] = await Promise.all([
      axios.get('/api/getClientAdresses'),
      axios.get('/api/reference/subproductCard'),
      axios.get('/api/reference/unit'),
      axios.get('/api/getWarehouses')
    ])

    clients.value    = c.data || c
    subcards.value   = s
    units.value      = (u.data || u).map(x => ({ id: x.id, name: x.name, tare: +x.tare || 0 }))
    warehouses.value = w
  } catch (e) { console.error(e) }
})()

/* ui helpers */
function addRow () {
  rows.value.push({
    _key: Date.now() + Math.random(),
    id  : null,
    product_subcard_id: '',
    unit_measurement  : '',
    amount: 0,
    price : 0
  })
}

/* save */
const saving  = ref(false)
const msg     = ref('')
const msgType = ref('')
const isNew   = computed(() => !props.record || !props.record.id)

async function save () {
  /* simple front-validation */
  if (!form.warehouse_id || !form.client_id || !form.address_id ||
      !form.start_date   || !form.end_date  ||
      !rows.value.length ||
      rows.value.some(r => !r.product_subcard_id || !r.unit_measurement))
  {
    alert('Заполните все обязательные поля')
    return
  }

  const totalsum = rows.value.reduce((s, r) => s + r.amount * r.price, 0)

  const payload = {
    warehouse_id: form.warehouse_id,
    client_id   : form.client_id,
    address_id  : form.address_id,
    start_date  : form.start_date,
    end_date    : form.end_date,
    totalsum,
    price_offer_items: rows.value.map(r => ({
      id                : r.id,
      product: { product_subcard_id: r.product_subcard_id },
      unit    : { name: r.unit_measurement },
      qtyTare : r.amount,
      price   : r.price
    }))
  }

  saving.value = true
  msg.value = ''
  msgType.value = ''

  try {
    if (isNew.value)
      await axios.post('/api/price-offers', payload)
    else
      await axios.put(`/api/price-offers/${props.record.id}`, payload)

    msg.value = 'Сохранено'
    msgType.value = 'success'
    emit('saved')
  } catch (e) {
    console.error(e)
    msg.value = 'Ошибка'
    msgType.value = 'error'
  } finally {
    saving.value = false
    setTimeout(() => { msg.value = '' }, 3000)
  }
}
</script>

<style scoped>
/* … стили оставил без изменений … */
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
.card {
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, .1);
  margin-bottom: 20px;
  overflow: hidden;
}
.card-header {
  background: #f1f1f1;
  padding: 12px 16px;
  border-bottom: 1px solid #ddd;
}
.card-header h3 { margin: 0; color: #333 }
.card-body { padding: 16px }
.top-row { display: flex; flex-wrap: wrap; gap: 10px }
.dropdown-column { flex: 1; min-width: 200px; display: flex; flex-direction: column }
.dropdown-label  { font-weight: bold; color: #555; margin-bottom: 4px }
.dropdown-select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px }

.styled-table { width: 100%; border-collapse: collapse }
.styled-table thead tr { background: #0288d1; color: #fff }
.styled-table th, .styled-table td {
  padding: 10px;
  border: 1px solid #ddd;
  text-align: center
}

.action-btn {
  background: #0288d1;
  color: #fff;
  border: none;
  border-radius: 8px;
  padding: 10px 14px;
  cursor: pointer;
  font-size: 14px;
}
.action-btn:hover { background: #026ca0 }
.remove-btn {
  background: #f44336;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 8px 10px;
  cursor: pointer;
}
.remove-btn:hover { background: #d32f2f }
.save-btn { width: 100%; margin-top: 10px }

.table-select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px }
.table-input  { width: 80px;  padding: 8px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px }

.feedback-message {
  margin-top: 20px;
  text-align: center;
  font-weight: bold;
  padding: 10px;
  border-radius: 8px;
}
.feedback-message.success { background: #d4edda; color: #155724 }
.feedback-message.error   { background: #f8d7da; color: #721c24 }
</style>
