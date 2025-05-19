<template>
  <div class="price-offer-container">
    <!-- HEADER -->
    <div class="modal-header">
      <h2>{{ isNew ? 'Новое ценовое предложение'
                   : 'Редактировать ценовое предложение' }}</h2>
      <button class="close-btn" @click="$emit('close')">✖</button>
    </div>

    <!-- BODY -->
    <div class="modal-body">
      <!-- card: клиент / адрес / даты -->
      <div class="card">
        <div class="card-header"><h3>Клиент, адрес и даты</h3></div>
        <div class="card-body">
          <div class="top-row">
            <!-- client -->
            <div class="dropdown-column">
              <label class="dropdown-label">Клиент</label>
              <select v-model="form.client_id"
                      class="dropdown-select"
                      @change="form.address_id = ''">
                <option value="">— Выберите клиента —</option>
                <option v-for="c in clients" :key="c.client_id" :value="c.client_id">
                  {{ c.client_name }}
                </option>
              </select>
            </div>
            <!-- address -->
            <div class="dropdown-column">
              <label class="dropdown-label">Адрес</label>
              <select v-model="form.address_id" class="dropdown-select">
                <option value="">— Выберите адрес —</option>
                <option v-for="a in addressesForClient" :key="a.id" :value="a.id">
                  {{ a.name }}
                </option>
              </select>
            </div>
            <!-- dates -->
            <div class="dropdown-column">
              <label class="dropdown-label">Начало</label>
              <input type="date" v-model="form.start_date" class="dropdown-select">
            </div>
            <div class="dropdown-column">
              <label class="dropdown-label">Конец</label>
              <input type="date" v-model="form.end_date" class="dropdown-select">
            </div>
          </div>
        </div>
      </div>

      <!-- card: товары -->
      <div class="card mt-2">
        <div class="card-header flex-between">
          <h3>Товары</h3>
          <button class="action-btn" @click="addRow">➕ Добавить строку</button>
        </div>
        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr>
                <th>Подкарточка</th><th>Партия</th><th>Остаток</th>
                <th>Ед. изм</th><th>Кол-во</th><th>Цена</th><th>Удалить</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r,i) in rows" :key="r._key">
                <!-- subcard -->
                <td>
                  <select v-model="r.product_subcard_id" class="table-select"
                          @change="onSubChange(r)">
                    <option value="">—</option>
                    <option v-for="s in subcards" :key="s.id" :value="s.id">
                      {{ s.name }}
                    </option>
                  </select>
                </td>
                <!-- batch -->
                <td>
                  <select v-if="batches(r).length" v-model="r.batch_id" class="table-select">
                    <option value="">—</option>
                    <option v-for="b in batches(r)" :key="b.id" :value="b.id">
                      {{ b.quantity }} {{ b.unit_measurement }}
                    </option>
                  </select>
                  <span v-else>-</span>
                </td>
                <!-- remain -->
                <td>
                  <span v-if="r.batch_id">{{ batchById(r.batch_id).quantity }}</span>
                  <span v-else-if="r.product_subcard_id">
                    {{ subcardById(r.product_subcard_id).total_quantity || 0 }}
                  </span>
                  <span v-else>-</span>
                </td>
                <!-- unit -->
                <td>
                  <select v-model="r.unit_measurement" class="table-select">
                    <option value="">—</option>
                    <option v-for="u in units" :key="u.id" :value="u.name">
                      {{ u.name }}
                    </option>
                  </select>
                </td>
                <!-- amount -->
                <td><input type="number" v-model.number="r.amount"
                           class="table-input" @change="checkQty(r)"></td>
                <!-- price -->
                <td><input type="number" v-model.number="r.price" class="table-input"></td>
                <!-- delete -->
                <td><button class="remove-btn" @click="rows.splice(i,1)">❌</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="modal-footer">
      <button class="action-btn save-btn" @click="save" :disabled="saving">
        {{ saving ? 'Сохранение...' : 'Сохранить' }}
      </button>
      <button class="cancel-btn" @click="$emit('close')">Отмена</button>
      <div v-if="msg" :class="['feedback-message', msgType]">{{ msg }}</div>
    </div>
  </div>
</template>

<script>
import axios from '@/plugins/axios'

export default {
  name: 'EditPriceOfferModal',
  props: { record: { type: Object, required: true } },

  data () {
    return {
      /* form */
      form: { client_id:'', address_id:'', start_date:'', end_date:'' },

      /* rows */
      rows: [],

      /* references */
      clients : [],  subcards: [],  units: [],

      /* ui */
      saving:false, msg:'', msgType:''
    }
  },

  computed:{
    isNew () { return !this.record || !this.record.id },
    addressesForClient () {
      const c = this.clients.find(c=>c.client_id===this.form.client_id)
      return c ? c.addresses : []
    }
  },

  created () {
    /* сразу заполняем тем, что пришло */
    if (this.record) this.initFromRecord(this.record)
    /* подтягиваем справочники */
    this.fetchRefs()
  },

  methods:{
    /* ----------- init from parent ---------------- */
    initFromRecord (rec){
      this.form.client_id  = rec.client_id
      this.form.address_id = rec.address_id
      this.form.start_date = rec.start_date
      this.form.end_date   = rec.end_date
      this.rows = (rec.items||[]).map(it=>({
        _key:it.id, id:it.id,
        product_subcard_id:it.product_subcard_id,
        unit_measurement  :it.unit_measurement,
        amount            :Number(it.amount),
        price             :Number(it.price),
        batch_id:''          // если появится поле `batch_id` — подставьте сюда
      }))
    },

    /* ----------- references ---------------------- */
    async fetchRefs () {
      try{
        const [{data:c},{data:s},{data:u}] = await Promise.all([
          axios.get('/api/getClientAdresses'),
          axios.get('/api/product_subcards'),
          axios.get('/api/unit-measurements')
        ])
        this.clients  = c.data || c
        this.subcards = s
        this.units    = u
      }catch(e){console.error(e)}
    },

    /* ----------- helpers ------------------------- */
    subcardById(id){return this.subcards.find(x=>x.id===id)||{}},
    batches(r){const s=this.subcardById(r.product_subcard_id);return s.batches||[]},
    batchById(id){
      for(const s of this.subcards){const b=(s.batches||[]).find(x=>x.id===id);if(b)return b}
      return {}
    },
    onSubChange(r){r.batch_id='';r.amount=0},
    checkQty(r){
      if(r.batch_id){
        const b=this.batchById(r.batch_id)
        if(r.amount>b.quantity)r.amount=b.quantity
      }else{
        const q=this.subcardById(r.product_subcard_id).total_quantity||0
        if(r.amount>q)r.amount=q
      }
    },
    addRow(){
      this.rows.push({_key:Date.now()+Math.random(),id:null,
        product_subcard_id:'',unit_measurement:'',amount:0,price:0,batch_id:''})
    },

    /* ----------- save ---------------------------- */
    async save(){
      if(!this.form.client_id||!this.form.address_id||!this.form.start_date||
         !this.form.end_date||!this.rows.length){
        alert('Заполните все поля');return }
      const totalsum=this.rows.reduce((s,r)=>s+r.amount*r.price,0)

      const payload={
        client_id:this.form.client_id,
        address_id:this.form.address_id,
        start_date:this.form.start_date,
        end_date:this.form.end_date,
        totalsum,
        price_offer_items:this.rows.map(r=>({
          id:r.id,product_subcard_id:r.product_subcard_id,
          unit_measurement:r.unit_measurement,amount:r.amount,price:r.price,
          batch_id:r.batch_id||undefined
        }))
      }

      this.saving=true;this.msg='';this.msgType=''
      try{
        if(this.isNew)
          await axios.post('/api/price-offers',payload)
        else
          await axios.patch(`/api/price-offers/${this.record.id}`,payload)
        this.msg='Сохранено';this.msgType='success'
        this.$emit('saved')
      }catch(e){
        console.error(e);this.msg='Ошибка';this.msgType='error'
      }finally{this.saving=false}
    }
  }
}
</script>

  <style scoped>
  /* Example styles */
  .edit-income-modal {
    background-color: #fff;
    width: 900px;
    max-width: 90%;
    border-radius: 10px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
    margin: 20px auto;
    position: relative;
  }
  .modal-header {
    background-color: #0288d1;
    color: #fff;
    padding: 16px;
    position: relative;
  }
  .close-btn {
    position: absolute;
    top: 12px;
    right: 16px;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 20px;
    cursor: pointer;
  }
  .modal-body {
    padding: 16px;
  }
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px;
    border-top: 1px solid #ddd;
  }

  .card {
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-bottom: 12px;
    background-color: #fefefe;
  }
  .card-header {
    background-color: #f1f1f1;
    padding: 8px 12px;
  }
  .mt-2 {
    margin-top: 10px;
  }
  .flex-between {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .styled-table {
    width: 100%;
    border-collapse: collapse;
  }
  .styled-table thead {
    background-color: #0288d1;
    color: #fff;
  }
  .styled-table th,
  .styled-table td {
    border: 1px solid #ddd;
    text-align: center;
    padding: 8px;
  }
  .summary-row td {
    background-color: #fafafa;
    font-weight: bold;
  }
  .summary-label {
    text-align: right;
  }

  .form-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }
  .form-group {
    flex: 1;
    min-width: 180px;
  }
  .form-control {
    width: 100%;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
  }

  .action-btn {
    background-color: #0288d1;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    cursor: pointer;
  }
  .remove-btn {
    background-color: #f44336;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 6px 8px;
    cursor: pointer;
  }
  .save-btn {
    background-color: #0288d1;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    cursor: pointer;
  }
  .cancel-btn {
    background-color: #9e9e9e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    cursor: pointer;
  }

  .feedback-message {
    margin-left: auto;
    font-weight: bold;
    padding: 6px 8px;
    border-radius: 4px;
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
