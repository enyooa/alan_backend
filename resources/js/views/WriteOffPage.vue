<!-- resources/js/components/operations/WarehouseWriteOff.vue -->
<template>
    <div class="write-off-page-container">
      <h2 class="page-title">Списание (со склада)</h2>

      <!-- Шапка: склад + дата -->
      <div class="card">
        <div class="card-header"><h3>Склад и дата списания</h3></div>
        <div class="card-body top-row">
          <!-- склад -->
          <div class="dropdown-column">
            <label class="dropdown-label">Откуда (склад)</label>
            <select v-model="warehouseId"
                    class="dropdown-select"
                    @change="loadLeftovers">
              <option value="">— выберите склад —</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">
                {{ w.name }}
              </option>
            </select>
          </div>

          <!-- дата -->
          <div class="dropdown-column">
            <label class="dropdown-label">Дата</label>
            <input type="date" v-model="docDate" class="dropdown-select">
          </div>
        </div>
      </div>

      <!-- две карточки -->
      <div class="cards-container mt-3">

        <!-- левая: строки списания -->
        <div class="card card-writeoff">
          <div class="card-header flex-between">
            <h3>Товары к списанию</h3>
            <button class="action-btn" @click="addRow">➕ строка</button>
          </div>

          <div class="card-body">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Партия (остаток)</th>
                  <th>Кол-во</th>
                  <th>Ед. изм</th>
                  <th></th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(r, i) in rows" :key="r._k">
                  <!-- партия -->
                  <td>
                    <select v-model="r._selected"
                            class="table-select"
                            @change="onBatchSelect(r)">
                      <option value="">— партия —</option>
                      <option v-for="b in leftoversForSelect"
                              :key="b.key"
                              :value="b.key">
                        {{ b.label }}
                      </option>
                    </select>
                  </td>

                  <!-- qty -->
                  <td>
                    <input  type="number"
                            class="table-input"
                            min="0"
                            :max="r.maxBalance"
                            v-model.number="r.quantity"
                            @change="onQtyChange(r)">
                  </td>

                  <!-- unit (только для чтения) -->
                  <td>
                    <input class="table-input readonly"
                           :value="r.unit_measurement"
                           readonly>
                  </td>

                  <!-- delete -->
                  <td>
                    <button class="remove-btn" @click="rows.splice(i,1)">❌</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="mt-2">
            <button class="action-btn save-btn"
                    :disabled="submitting"
                    @click="save">
              {{ submitting ? '⏳…' : '💾 Сохранить' }}
            </button>
          </div>

          <div v-if="msg" :class="['feedback-message', msgType]">{{ msg }}</div>
        </div>

        <!-- правая: остатки -->
        <div class="card card-leftovers">
          <div class="card-header">
            <h3>Остатки ({{ whName }})</h3>
          </div>
          <div class="card-body">
            <table class="styled-table">
              <thead><tr><th>Товар</th><th>Остаток</th></tr></thead>
              <tbody>
                <tr v-for="l in leftovers"
                    :key="l.product_subcard_id + l.unit_measurement">
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

  <script>
  import axios from '@/plugins/axios'
  import { ref, computed, onMounted } from 'vue'

  export default {
  name:'WarehouseWriteOff',
  setup(){

  /* ─── справочники ─── */
  const warehouses = ref([])
  const leftovers  = ref([])

  /* ─── выбранные ─── */
  const warehouseId = ref('')
  const docDate     = ref('')

  /* ─── строки ─── */
  function makeRow () {
    return {
      _k              : Date.now()+Math.random(),
      _selected       : '',
      product_subcard_id : '',
      unit_measurement   : '',
      maxBalance      : 0,
      quantity        : 0
    }
  }
  const rows = ref([makeRow()])

  /* ─── computed ─── */
  const whName = computed(()=> warehouses.value.find(w=>w.id===warehouseId.value)?.name || '—')

  const leftoversForSelect = computed(()=> leftovers.value.map(l=>({
    key     : l.product_subcard_id + '|' + l.unit_measurement,
    label   : `${l.name} ▸ ${format(l.balance)} ${l.unit_measurement}`,
    ...l
  })) )

  /* ─── helpers ─── */
  const format = v => (+v).toFixed(3).replace(/\.?0+$/, '')

  /* ─── fetch ─── */
  onMounted(async()=>{
    const { data } = await axios.get('/api/getWarehouses')
    warehouses.value = data
  })

  async function loadLeftovers(){
    leftovers.value=[]
    if(!warehouseId.value) return
    const { data } = await axios.get('/api/warehouse-items',
                                     { params:{ warehouse_id: warehouseId.value }})
    leftovers.value = data
  }

  /* ─── UI actions ─── */
  const addRow = ()=> rows.value.push(makeRow())

  function onBatchSelect(row){
    const found = leftoversForSelect.value.find(b=>b.key===row._selected)
    if(!found) return
    row.product_subcard_id = found.product_subcard_id
    row.unit_measurement   = found.unit_measurement
    row.maxBalance         = +found.balance
    row.quantity           = 0
  }

  function onQtyChange(row){
    if(row.quantity > row.maxBalance){
      alert(`Нельзя списать больше, чем ${format(row.maxBalance)}.`)
      row.quantity = row.maxBalance
    }
  }

  /* ─── save ─── */
  const submitting = ref(false)
  const msg = ref(''), msgType = ref('')

  async function save(){
    if(!warehouseId.value || !docDate.value){
      alert('Укажите склад и дату'); return
    }
    if(!rows.value.every(r=>r.product_subcard_id && r.unit_measurement && r.quantity>0)){
      alert('Заполните все строки корректно'); return
    }

    submitting.value = true
    try{
      await axios.post('/api/writeoff/store',{
        warehouse_id  : warehouseId.value,
        document_date : docDate.value,
        items         : rows.value.map(r=>({
          product_subcard_id : r.product_subcard_id,
          unit_measurement   : r.unit_measurement,
          quantity           : r.quantity
        }))
      })

      msg.value='Списание сохранено'
      msgType.value='success'

      /* reset */
      rows.value=[makeRow()]
      loadLeftovers()

    }catch(e){
      console.error(e)
      msg.value = e.response?.data?.error || 'Ошибка'
      msgType.value='error'
    }finally{
      submitting.value=false
      setTimeout(()=>msg.value='',3000)
    }
  }

  return{
    /* state */
    warehouses, leftovers, warehouseId, docDate, rows,
    /* computed */
    whName, leftoversForSelect, format,
    /* methods */
    loadLeftovers, addRow, onBatchSelect, onQtyChange, save,
    /* ui feedback */
    submitting, msg, msgType
  }
  }
  }
  </script>


<style scoped>
.write-off-page-container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}
.page-title {
  text-align: center;
  margin-bottom: 20px;
  font-size: 1.4rem;
  color: #0288d1;
}

/* Cards */
.card {
  background-color: #fff;
  border-radius: 8px;
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
.mt-2 { margin-top: 12px; }
.mt-3 { margin-top: 20px; }
.flex-between {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* Layout for two columns */
.cards-container {
  display: flex;
  gap: 20px;
}
.card-writeoff {
  flex: 2;
}
.card-leftovers {
  flex: 1;
}

/* Row styling */
.top-row {
  display: flex;
  gap: 20px;
  flex-wrap: wrap;
}
.dropdown-column {
  display: flex;
  flex-direction: column;
  gap: 6px;
  min-width: 180px;
}
.dropdown-label {
  font-weight: bold;
  color: #555;
}
.dropdown-select {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}

/* Tables */
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
  border: 1px solid #ddd;
  padding: 8px;
  text-align: center;
}
.table-select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
}
.table-input {
  width: 70px;
  padding: 6px;
  border: 1px solid #ddd;
  border-radius: 6px;
  font-size: 14px;
  text-align: right;
}

/* Buttons */
.action-btn {
  background-color: #0288d1;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 10px 14px;
  cursor: pointer;
  font-size: 14px;
}
.action-btn:hover {
  background-color: #0270a0;
}
.save-btn {
  width: 100%;
}
.remove-btn {
  background-color: #f44336;
  color: #fff;
  border: none;
  border-radius: 6px;
  padding: 8px 10px;
  cursor: pointer;
}
.remove-btn:hover {
  background-color: #d32f2f;
}

/* Feedback */
.feedback-message {
  margin-top: 16px;
  text-align: center;
  font-weight: bold;
  padding: 10px;
  border-radius: 6px;
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
