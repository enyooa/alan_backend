<!-- src/pages/PriceOfferPage.vue -->
<template>
    <div class="price-offer-container">
      <h2 class="page-title">Ценовое предложение</h2>

      <!-- (1) Склад • Клиент • Адрес • Даты -->
      <div class="card">
        <div class="card-header"><h3>Склад, Клиент, Адрес, Даты</h3></div>

        <div class="card-body">
          <!-- ряд-1 -->
          <div class="top-row">
            <!-- склад -->
            <div class="dropdown-column">
              <label class="dropdown-label">Склад</label>
              <select v-model="selectedWarehouse"
                      class="dropdown-select"
                      @change="onWarehouseChange">
                <option value="">— выберите склад —</option>
                <option v-for="wh in warehouses" :key="wh.id" :value="wh.id">
                  {{ wh.name }}
                </option>
              </select>
            </div>

            <!-- клиент -->
            <div class="dropdown-column">
              <label class="dropdown-label">Клиент</label>
              <select v-model="selectedClient" class="dropdown-select">
                <option value="">— Выберите клиента —</option>
                <option v-for="c in clientList" :key="c.client_id" :value="c.client_id">
                  {{ c.client_name }}
                </option>
              </select>
            </div>

            <!-- адрес -->
            <div class="dropdown-column">
              <label class="dropdown-label">Адрес</label>
              <select v-model="selectedAddress" class="dropdown-select">
                <option value="">— Выберите адрес —</option>
                <option v-for="a in getAddressesForClient(selectedClient)"
                        :key="a.id"
                        :value="a">
                  {{ a.name }}
                </option>
              </select>
            </div>
          </div><!-- /top-row -->

          <!-- ряд-2 -->
          <div class="dates-row mt-2">
            <div class="dropdown-column">
              <label class="dropdown-label">Начальная дата</label>
              <input type="date" v-model="startDate" class="dropdown-select">
            </div>
            <div class="dropdown-column">
              <label class="dropdown-label">Конечная дата</label>
              <input type="date" v-model="endDate" class="dropdown-select">
            </div>
          </div>
        </div>
      </div>

      <!-- (2) Таблица товаров + остатки -->
      <div class="cards-container mt-3">
        <!-- таблица -->
        <div class="card product-card">
          <div class="card-header flex-between">
            <h3>Товары</h3>
            <button class="action-btn" @click="addProductRow">➕ строка</button>
          </div>

          <div class="card-body">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Подкарточка</th>
                  <th>Остаток</th>
                  <th>Ед. изм.</th>
                  <th>Кол-во</th>
                  <th>Цена</th>
                  <th></th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="(row,idx) in productRows" :key="row._key">
                  <!-- подкарточка -->
                  <td>
                    <select v-model="row.product_subcard_id"
                            class="table-select"
                            @change="onSubcardChange(row)">
                      <option value="">—</option>
                      <option v-for="s in subcardsByWh"
                              :key="s.id"
                              :value="s.id">
                        {{ s.name }}
                      </option>
                    </select>
                  </td>

                  <!-- остаток -->
                  <td>{{ formatNumber(getLeftoverBalance(row.product_subcard_id,
                                                         row.unit_measurement)) }}</td>

                  <!-- единица -->
                  <td>
                    <select v-model="row.unit_measurement" class="table-select">
                      <option value="">—</option>
                      <option v-for="u in units" :key="u.id" :value="u.name">
                        {{ u.name }}
                      </option>
                    </select>
                  </td>

                  <!-- количество -->
                  <td>
                    <input type="number"
                           class="table-input"
                           v-model.number="row.amount"
                           min="0"
                           :max="getLeftoverBalance(row.product_subcard_id,
                                                    row.unit_measurement)"
                           @change="validateAmount(row)">
                  </td>

                  <!-- цена -->
                  <td>
                    <input type="number" class="table-input"
                           v-model.number="row.price" min="0">
                  </td>

                  <!-- удалить -->
                  <td><button class="remove-btn" @click="removeProductRow(idx)">❌</button></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- остатки -->
        <div class="card leftover-card">
          <div class="card-header"><h3>Остатки ({{ warehouseName }})</h3></div>
          <div class="card-body">
            <table class="styled-table">
              <thead><tr><th>Товар</th><th>Остаток</th><th>Ед. изм.</th></tr></thead>
              <tbody>
                <tr v-for="l in leftovers"
                    :key="l.product_subcard_id + l.unit_measurement">
                  <td>{{ l.name }}</td>
                  <td>{{ formatNumber(l.balance) }}</td>
                  <td>{{ l.unit_measurement }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- /cards-container -->

      <!-- (3) сохранить -->
      <div class="mt-3">
        <button class="action-btn save-btn"
                @click="submitPriceOffer"
                :disabled="submitting">
          {{ submitting ? '⏳…' : '💾 Сохранить' }}
        </button>
      </div>
      <div v-if="globalMessage"
           :class="['feedback-message',globalMessageType]">
        {{ globalMessage }}
      </div>
    </div>
  </template>

  <script setup>
  import { ref, computed, onMounted } from 'vue'
  import axios from '@/plugins/axios'

  /* ────────── state ────────── */
  const warehouses = ref([])
  const selectedWarehouse = ref('')
  const leftovers  = ref([])                // результат /api/warehouse-items

  /* подкарточки доступные на складе */
  const subcardsByWh = computed(()=>{
    const m = new Map()
    leftovers.value.forEach(l=>{
      if(!m.has(l.product_subcard_id)){
        m.set(l.product_subcard_id,{ id:l.product_subcard_id, name:l.name })
      }
    })
    return [...m.values()]
  })

  /* клиенты / адреса */
  const clientList = ref([])
  const selectedClient  = ref('')
  const selectedAddress = ref(null)
  const startDate = ref(''); const endDate = ref('')

  /* единицы измерения */
  const units = ref([])

  /* строки таблицы */
  const productRows = ref([])
  function addProductRow(){
    productRows.value.push({
      _key: Date.now()+Math.random(),
      product_subcard_id:'', unit_measurement:'',
      amount:0, price:0
    })
  }
  function removeProductRow(i){ productRows.value.splice(i,1) }
  function onSubcardChange(r){ r.amount=0 }

  /* ────────── fetch ────────── */
  onMounted(()=>Promise.all([
    axios.get('/api/getWarehouses')
         .then(r=>warehouses.value=r.data),
    axios.get('/api/getClientAdresses')
         .then(r=>clientList.value=r.data?.data||[]),
    axios.get('/api/unit-measurements')
         .then(r=>units.value=r.data)
  ]))

  /* загрузка остатков при выборе склада */
  async function onWarehouseChange(){
    leftovers.value = []
    if(!selectedWarehouse.value) return
    const { data } = await axios.get('/api/warehouse-items',
                                     { params:{ warehouse_id:selectedWarehouse.value }})
    leftovers.value = data
  }

  /* ────────── helpers ────────── */
  const warehouseName = computed(()=>{
    const w = warehouses.value.find(x=>x.id===selectedWarehouse.value)
    return w ? w.name : '—'
  })

  /* баланс по SKU + ед.изм. */
  function getLeftoverBalance(subId, unit){
    if(!subId) return 0
    /* если ед.изм. ещё не выбрали – показываем суммарно */
    if(!unit){
      return leftovers.value
             .filter(l=>l.product_subcard_id===subId)
             .reduce((s,l)=>s+ +l.balance,0)
    }
    const l = leftovers.value.find(
              x=>x.product_subcard_id===subId && x.unit_measurement===unit)
    return l ? +l.balance : 0
  }
  function validateAmount(r){
    const max = getLeftoverBalance(r.product_subcard_id,r.unit_measurement)
    if(r.amount > max) r.amount = max
  }
  function getAddressesForClient(id){
    const c = clientList.value.find(x=>x.client_id==id)
    return c ? c.addresses : []
  }
  const formatNumber = v=>(+v||0).toFixed(3).replace(/\.?0+$/,'')

  /* ────────── submit ────────── */
  const submitting = ref(false)
  const globalMessage = ref(''); const globalMessageType = ref('')

  async function submitPriceOffer(){
    if(!selectedWarehouse.value||!selectedClient.value||!selectedAddress.value
       ||!startDate.value||!endDate.value){
      alert('Заполните все поля'); return
    }
    const total = productRows.value
                    .reduce((s,r)=>s+ (+r.amount||0)*(+r.price||0),0)

    submitting.value=true
    try{
      await axios.post('/api/bulkPriceOffers',{
        warehouse_id : selectedWarehouse.value,
        client_id    : selectedClient.value,
        address_id   : selectedAddress.value.id,
        start_date   : startDate.value,
        end_date     : endDate.value,
        totalsum     : total,
        price_offer_items: productRows.value
      })
      globalMessage.value = '✅ Сохранено'; globalMessageType.value='success'
      /* reset */
      selectedWarehouse.value='';selectedClient.value='';
      selectedAddress.value=null; startDate.value=''; endDate.value=''
      productRows.value=[]; leftovers.value=[]
    }catch(e){
      globalMessage.value='❌ Ошибка сохранения'; globalMessageType.value='error'
    }finally{
      submitting.value=false
      setTimeout(()=>globalMessage.value='',4000)
    }
  }
  </script>

  <style scoped>
  /* — стили остались прежними — */
  .price-offer-container{max-width:1100px;margin:0 auto;padding:20px}
  .page-title{text-align:center;color:#0288d1;margin-bottom:20px}
  .cards-container{display:flex;gap:20px}.product-card{flex:2}.leftover-card{flex:1}
  .card{background:#fff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,.1);
        margin-bottom:20px;overflow:hidden}
  .card-header{background:#f1f1f1;padding:12px 16px;border-bottom:1px solid #ddd}
  .card-header h3{margin:0;color:#333}.card-body{padding:16px}
  .top-row,.dates-row{display:flex;flex-wrap:wrap;gap:10px}
  .dropdown-column{flex:1;min-width:200px;display:flex;flex-direction:column}
  .dropdown-label{font-weight:bold;color:#555;margin-bottom:4px}
  .dropdown-select{padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px}
  .styled-table{width:100%;border-collapse:collapse}
  .styled-table thead tr{background:#0288d1;color:#fff}
  .styled-table th,.styled-table td{padding:10px;border:1px solid #ddd;text-align:center}
  .action-btn{background:#0288d1;color:#fff;border:none;border-radius:8px;
              padding:10px 14px;cursor:pointer;font-size:14px}
  .action-btn:hover{background:#026ca0}.remove-btn{background:#f44336;color:#fff;
              border:none;border-radius:6px;padding:8px 10px;cursor:pointer}
  .remove-btn:hover{background:#d32f2f}.save-btn{width:100%;margin-top:10px}
  .table-select{width:100%;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:14px}
  .table-input{width:80px;padding:8px;border:1px solid #ddd;border-radius:6px;font-size:14px}
  .feedback-message{margin-top:20px;text-align:center;font-weight:bold;padding:10px;
                    border-radius:8px}
  .feedback-message.success{background:#d4edda;color:#155724}
  .feedback-message.error{background:#f8d7da;color:#721c24}
  </style>
