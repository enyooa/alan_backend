<template>
  <div class="edit-sale-modal">
    <!-- ▸ HEADER ----------------------------------------------------------- -->
    <div class="modal-header">
      <h2>Редактировать «Продажу» (ID: {{ header.id || '...' }})</h2>
      <button class="close-btn" @click="$emit('close')">✖</button>
    </div>

    <!-- ▸ BODY -------------------------------------------------------------- -->
    <div class="modal-body">
      <!-- Шапка документа -->
      <div class="card">
        <div class="card-header"><h3>Покупатель, дата, склад-отгрузки</h3></div>
        <div class="card-body form-row">
          <!-- покупатель -->
          <div class="form-group">
            <label class="field-label">Кому продаём</label>
            <select v-model="counterpartyId" class="form-control">
              <option value="">— выберите —</option>
              <option
                v-for="c in counterparties"
                :key="c.id"
                :value="c.id"
              >
                {{ c.name || (c.first_name + ' ' + (c.last_name || '')) }}
                ({{ typeRu(c.type) }})
              </option>
            </select>
          </div>

          <!-- дата -->
          <div class="form-group">
            <label class="field-label">Дата</label>
            <input type="date" v-model="header.document_date" class="form-control">
          </div>

          <!-- склад -->
          <div class="form-group">
            <label class="field-label">Склад-отгрузки</label>
            <select v-model="header.from_warehouse_id" class="form-control">
              <option value="">— склад —</option>
              <option v-for="w in warehouses" :key="w.id" :value="w.id">
                {{ w.name }}
              </option>
            </select>
          </div>
        </div>
      </div>

      <!-- Таблица товаров -->
      <div class="card mt-2">
        <div class="card-header flex-between">
          <h3>Товары</h3>
          <button class="action-btn" @click="addRow">➕ строка</button>
        </div>

        <div class="card-body">
          <table class="styled-table">
            <thead>
              <tr>
                <th>Товар</th><th>Кол-во тары</th><th>Ед.</th>
                <th>Брутто</th><th>Нетто</th><th>Цена</th>
                <th>Сумма</th><th></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(r,i) in rows" :key="r._k">
                <!-- товар -->
                <td>
                  <select v-model="r.product_subcard_id" class="form-control">
                    <option value="">— товар —</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">
                      {{ p.name }}
                    </option>
                  </select>
                </td>

                <!-- qty -->
                <td><input type="number" min="0" v-model.number="r.qtyTare" class="form-control"></td>

                <!-- unit -->
                <td>
                  <select v-model="r.unit_measurement" class="form-control">
                    <option value="">— ед. —</option>
                    <option v-for="u in units" :key="u.name" :value="u.name">
                      {{ u.name }} ({{ u.tare }} г)
                    </option>
                  </select>
                </td>

                <!-- brutto -->
                <td><input type="number" min="0" v-model.number="r.brutto" class="form-control"></td>

                <!-- netto / price / total -->
                <td>{{ netto(r).toFixed(3) }}</td>
                <td><input type="number" min="0" step="0.01" v-model.number="r.price" class="form-control"></td>
                <td>{{ total(r).toFixed(2) }}</td>

                <!-- delete -->
                <td><button class="remove-btn" @click="rows.splice(i,1)">❌</button></td>
              </tr>

              <!-- итог -->
              <tr class="summary-row">
                <td colspan="4" class="summary-label">ИТОГО</td>
                <td>{{ totalNetto.toFixed(3) }}</td><td>-</td>
                <td>{{ totalSum.toFixed(2) }}</td><td>-</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ▸ FOOTER ------------------------------------------------------------ -->
    <div class="modal-footer">
      <button class="save-btn" :disabled="submitting" @click="save">
        {{ submitting ? '⏳…' : '💾 Сохранить' }}
      </button>
      <button class="cancel-btn" @click="$emit('close')">Отмена</button>
      <div v-if="msg" :class="['feedback-message', msgType]">{{ msg }}</div>
    </div>
  </div>
</template>

<script>
import axios       from '@/plugins/axios'
import { v4 as uuid } from 'uuid'

export default {
  name : 'EditSaleModal',
  props: { documentId: { type: String, required: true } },

  data () {
    return {
      counterparties: [],
      warehouses    : [],
      products      : [],
      units         : [],

      header: {
        id                : null,
        client_id         : null,
        to_organization_id: null,
        document_date     : '',
        from_warehouse_id : null
      },

      rows       : [],
      submitting : false,
      msg        : '',
      msgType    : ''
    }
  },

  computed: {
    counterpartyId: {
      get () { return this.header.client_id || this.header.to_organization_id || '' },
      set (id) {
        this.header.client_id = this.header.to_organization_id = null
        const c = this.counterparties.find(x => x.id === id)
        if (!c) return
        if (c.type === 'client')        this.header.client_id          = c.id
        if (c.type === 'organization')  this.header.to_organization_id = c.id
      }
    },
    totalNetto () { return this.rows.reduce((s,r)=>s + this.netto(r), 0) },
    totalSum   () { return this.rows.reduce((s,r)=>s + this.total(r), 0) }
  },

  created () {
    this.fetchRefs().then(this.loadDoc)
  },

  methods: {
    /* ───────── helpers ───────── */
    typeRu (t) { return t==='client'?'клиент':t==='organization'?'организация':'прочее' },
    isKg (n) { return /кг|килограмм/i.test(n||'') },
    unitByName (n) { return this.units.find(u=>u.name===n) || { tare:0 } },
    netto (r) {
      return this.isKg(r.unit_measurement)
        ? (+r.brutto||0)
        : (+r.brutto||0) - (+r.qtyTare||0)*(this.unitByName(r.unit_measurement).tare/1000)
    },
    total (r) { return this.netto(r)*(+r.price||0) },

    makeRow () { return {
      _k : uuid(), id:null, product_subcard_id:'', unit_measurement:'',
      qtyTare:0, brutto:0, price:0
    }},
    addRow () { this.rows.push(this.makeRow()) },

    pushIfMissing (arr, obj, key='id') {
      if (!obj || !obj[key]) return
      if (!arr.find(x => x[key] === obj[key])) arr.push(obj)
    },

    /* ───────── данные ─────────── */
    async fetchRefs () {
      const [ct, wh, pr, un] = await Promise.all([
        axios.get('/api/counterparty'),
        axios.get('/api/getWarehouses'),
        axios.get('/api/reference/subproductCard'),
        axios.get('/api/reference/unit')
      ])
      this.counterparties = ct.data
      this.warehouses     = wh.data
      this.products       = pr.data
      this.units          = (un.data||[]).map(u=>({ id:u.id,name:u.name,tare:+u.tare||0 }))
    },

    async loadDoc () {
      const { data } = await axios.get(`/api/documents/${this.documentId}`)

      /* 1. подмешиваем контрагента */
      if (data.client_info) {
        this.pushIfMissing(this.counterparties, {
          id  : data.client_info.id,
          type: 'client',
          name: `${data.client_info.first_name} ${data.client_info.last_name || ''}`.trim()
        })
        this.header.client_id = data.client_info.id
      }
      if (data.organization_info) {
        this.pushIfMissing(this.counterparties, {
          id  : data.organization_info.id,
          type: 'organization',
          name: data.organization_info.name
        })
        this.header.to_organization_id = data.organization_info.id
      }

      /* 2. склад */
      if (data.from_warehouse) {
        this.pushIfMissing(this.warehouses, data.from_warehouse)
        this.header.from_warehouse_id = data.from_warehouse.id
      }

      /* 3–4. товары и единицы */
      (data.items||[]).forEach(it => {
        if (it.product) this.pushIfMissing(this.products, it.product)
        if (it.unit_by_name)
          this.pushIfMissing(
            this.units,
            { id: it.unit_by_name.id||uuid(), name: it.unit_by_name.name, tare:+(it.unit_by_name.tare||0) },
            'name'  // единицы уникальны по имени
          )
      })

      /* 5. остальные поля заголовка */
      this.header.id            = data.id
      this.header.document_date = (data.document_date||'').slice(0,10)

      /* 6. строки таблицы */
      this.rows = (data.items||[]).map(it => ({
        _k      : it.id,
        id      : it.id,
        product_subcard_id: it.product_subcard_id,
        unit_measurement : it.unit_measurement,
        qtyTare : +it.quantity,
        brutto  : +it.brutto,
        price   : +it.price
      }))
    },

    /* ───────── сохранение ───────── */
    async save () {
      if (!this.counterpartyId || !this.header.from_warehouse_id || !this.header.document_date) {
        alert('Заполните покупателя, склад и дату'); return
      }
      this.submitting = true
      try {
        await axios.put(`/api/documents/${this.header.id}`, {
          client_id             : this.header.client_id,
          to_organization_id    : this.header.to_organization_id,
          assigned_warehouse_id : this.header.from_warehouse_id,
          document_date         : this.header.document_date,
          products: this.rows.map(r=>({
            id               : r.id,
            product_subcard_id: r.product_subcard_id,
            unit_measurement : r.unit_measurement,
            quantity         : r.qtyTare,
            brutto           : r.brutto,
            netto            : this.netto(r),
            price            : r.price,
            total_sum        : this.total(r)
          }))
        })
        this.msg='Сохранено'; this.msgType='success'
        this.$emit('saved')
      } catch(e){
        console.error(e)
        this.msg='Ошибка при сохранении'; this.msgType='error'
      } finally {
        this.submitting=false
        setTimeout(()=>{this.msg=''},3000)
      }
    }
  }
}
</script>

<style scoped>
/* стили без изменений */
.edit-sale-modal{background:#fff;width:900px;max-width:95%;border-radius:10px;
                 box-shadow:0 5px 20px rgba(0,0,0,.2);margin:20px auto;position:relative}
.modal-header{background:#0288d1;color:#fff;padding:16px;position:relative}
.close-btn{position:absolute;top:12px;right:16px;background:none;border:none;
           color:#fff;font-size:20px;cursor:pointer}
.modal-body{padding:16px}
.modal-footer{display:flex;justify-content:flex-end;gap:10px;padding:16px;border-top:1px solid #ddd}

.card{border:1px solid #ddd;border-radius:6px;margin-bottom:12px;background:#fefefe}
.card-header{background:#f1f1f1;padding:8px 12px}
.form-row{display:flex;gap:10px;flex-wrap:wrap}
.form-group{flex:1;min-width:180px}
.form-control{width:100%;padding:6px;border:1px solid #ddd;border-radius:4px}

.styled-table{width:100%;border-collapse:collapse}
.styled-table thead{background:#0288d1;color:#fff}
.styled-table th,.styled-table td{border:1px solid #ddd;padding:8px;text-align:center}
.summary-row td{background:#fafafa;font-weight:bold}
.summary-label{text-align:right}

.action-btn{background:#0288d1;color:#fff;border:none;border-radius:6px;padding:6px 12px;cursor:pointer}
.remove-btn{background:#f44336;color:#fff;border:none;border-radius:4px;padding:6px 8px}
.save-btn{background:#0288d1;color:#fff;border:none;border-radius:6px;padding:8px 14px}
.cancel-btn{background:#9e9e9e;color:#fff;border:none;border-radius:6px;padding:8px 14px}

.feedback-message{margin-left:auto;font-weight:bold;padding:6px 8px;border-radius:4px}
.success{background:#d4edda;color:#155724}.error{background:#f8d7da;color:#721c24}
</style>
