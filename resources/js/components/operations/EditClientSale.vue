<!-- resources/js/components/operations/forms/EditClientSaleModal.vue -->
<template>
  <div class="edit-sale-modal">
    <div class="modal-header">
      <h2>Редактировать продажу (ID: {{ record.id.slice(0,8) }})</h2>
      <button class="close-btn" @click="$emit('close')">✖</button>
    </div>

    <div class="modal-body">
      <!-- header -->
      <div class="card">
        <div class="card-header"><h3>Покупатель, склад, дата</h3></div>
        <div class="card-body top-row">
          <div class="dropdown-column">
            <label class="dropdown-label">Покупатель</label>
            <select v-model="form.client_id" class="dropdown-select">
              <option v-for="c in buyers" :key="c.client_id" :value="c.client_id">
                {{ c.client_name }}
              </option>
            </select>
          </div>
          <div class="dropdown-column">
            <label class="dropdown-label">Склад</label>
            <select v-model="form.warehouse_id" class="dropdown-select">
              <option v-for="w in warehouses" :key="w.id" :value="w.id">{{ w.name }}</option>
            </select>
          </div>
          <div class="dropdown-column">
            <label class="dropdown-label">Дата</label>
            <input type="date" v-model="form.sale_date" class="dropdown-select">
          </div>
        </div>
      </div>

      <!-- products table -->
      <div class="card mt-2">
        <div class="card-header flex-between">
          <h3>Товары</h3>
          <button class="action-btn" @click="addRow">➕ строка</button>
        </div>
        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr><th>Товар</th><th>Кол-во</th><th>Ед.</th><th>Цена</th><th>Удалить</th></tr>
            </thead>
            <tbody>
              <tr v-for="(r,i) in rows" :key="r._k">
                <td>
                  <select v-model="r.product.product_subcard_id" class="table-select">
                    <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
                  </select>
                </td>
                <td><input type="number" v-model.number="r.amount" class="table-input"></td>
                <td>
                  <select v-model="r.unit.name" class="table-select">
                    <option v-for="u in units" :key="u.id" :value="u.name">{{ u.name }}</option>
                  </select>
                </td>
                <td><input type="number" v-model.number="r.price" class="table-input"></td>
                <td><button class="remove-btn" @click="rows.splice(i,1)">❌</button></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- footer -->
    <div class="modal-footer">
      <button class="action-btn save-btn" :disabled="saving" @click="save">
        {{ saving?'⏳':'💾 Сохранить' }}
      </button>
      <button class="cancel-btn" @click="$emit('close')">Отмена</button>
      <div v-if="msg" :class="['feedback-message', msgType]">{{ msg }}</div>
    </div>
  </div>
</template>

<script>
import axios from '@/plugins/axios'

export default {
  name:'EditClientSaleModal',
  props:{ record:{ type:Object, required:true } },

  data(){return{
    form:{ client_id:'', warehouse_id:'', sale_date:'' },
    rows:[],
    buyers:[], warehouses:[], products:[], units:[],
    saving:false, msg:'', msgType:''
  }},

  created(){
    this.initFromRecord()
    this.fetchRefs()
  },

  methods:{
    initFromRecord(){
      const r=this.record
      this.form.client_id  = r.client_id
      this.form.warehouse_id = r.warehouse_id
      this.form.sale_date    = r.sale_date
      this.rows = r.items.map(it=>({
        _k:it.id, id:it.id,
        product:{ product_subcard_id:it.product_subcard_id },
        unit   :{ name:it.unit_measurement },
        amount :Number(it.amount),
        price  :Number(it.price)
      }))
    },
    async fetchRefs(){
      try{
        const [{data:buyers},{data:ware},{data:prod},{data:units}] = await Promise.all([
          axios.get('/api/getClientAdresses'),
          axios.get('/api/getWarehouses'),
          axios.get('/api/product_subcards'),
          axios.get('/api/reference/unit')
        ])
        this.buyers     = buyers.data || buyers
        this.warehouses = ware
        this.products   = prod
        this.units      = units.map(u=>({id:u.id,name:u.name}))
      }catch(e){console.error(e)}
    },
    addRow(){
      this.rows.push({_k:Date.now()+Math.random(),
        product:{product_subcard_id:''}, unit:{name:''}, amount:0, price:0})
    },
    async save(){
      /* базовая валидация */
      if(!this.rows.length){alert('Добавьте товары');return}
      this.saving=true; this.msg=''; this.msgType=''
      try{
        await axios.put(`/api/sales/${this.record.id}`,{
          client_id   : this.form.client_id,
          warehouse_id: this.form.warehouse_id,
          sale_date   : this.form.sale_date,
          products    : this.rows
        })
        this.msg='Сохранено'; this.msgType='success'; this.$emit('saved')
      }catch(e){ console.error(e); this.msg='Ошибка'; this.msgType='error'}
      finally{ this.saving=false }
    }
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
