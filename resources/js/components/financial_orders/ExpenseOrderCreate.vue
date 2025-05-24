<!-- resources/js/components/financial_orders/ExpenseOrderCreate.vue -->
<template>
  <div class="page">
    <!-- ▸ TOP-BAR -->
    <header class="appbar">
      <button class="icon" @click="$router.back()">←</button>
      <img src="/assets/img/logo.png" class="logo" alt="logo">
      <span class="title">Создать расходный ордер</span>
      <span class="info">❕</span>
    </header>

    <!-- ▸ INFO -->
    <section class="card">
      <h2>Информация</h2>

      <FieldRow label="Счёт кассы">
        <select v-model="form.cashId">
          <option disabled value="">Выберите…</option>
          <option v-for="c in cashes" :key="c.id" :value="c.id">{{ c.name }}</option>
        </select>
      </FieldRow>

      <FieldRow label="Поставщик">
        <select v-model="form.counterparty">
          <option disabled value="">Выберите…</option>
          <option v-for="p in providers" :key="p.id" :value="toVal(p,'provider')">
            {{ p.name }}
          </option>
        </select>
      </FieldRow>

      <FieldRow label="Статья расхода">
        <select v-model="form.elementId">
          <option disabled value="">Выберите…</option>
          <option v-for="e in elements" :key="e.id" :value="e.id">{{ e.name }}</option>
        </select>
      </FieldRow>

      <FieldRow label="Сумма">
        <input v-model.number="form.amount" type="number" placeholder="0.0" step="100">
      </FieldRow>
    </section>

    <!-- ▸ DATE -->
    <section class="card">
      <h2>Дата чека</h2>
      <FieldRow label="Выберите дату">
        <input v-model="form.date" type="date">
      </FieldRow>
    </section>

    <!-- ▸ PHOTO -->
    <section class="photo card">
      <input ref="file" hidden type="file" accept="image/*" @change="pick">
      <button class="drop" @click="$refs.file.click()">
        <span v-if="!photoName">⬇<br>Загрузить фото</span>
        <span v-else>📎 {{ photoName }}</span>
      </button>
    </section>

    <!-- ▸ FAB -->
    <footer class="fab-bar">
      <button class="fab"
              :class="{ disabled:saving }"
              :disabled="saving"
              @click="send">
        {{ saving ? '⏳ Сохраняю…' : '✔️ Создать' }}
      </button>
    </footer>
  </div>
</template>

<script>
import axios from '@/plugins/axios'

/* — маленький функциональный компонент строки — */
export const FieldRow = {
  functional:true,
  props:{ label:String },
  render(h,{ props, children }){
    return h('div',{ staticClass:'row' },[
      h('span',{ staticClass:'lbl' },props.label), children
    ])
  }
}

export default {
  name:'ExpenseOrderCreate',
  components:{ FieldRow },

  data(){
    return{
      cashes   :[],
      elements :[],
      providers:[],

      form:{
        cashId:'', elementId:'', amount:'',
        date: new Date().toISOString().slice(0,10),
        counterparty:null      // {id,type}
      },

      file   : null,
      saving : false
    }
  },

  computed:{
    photoName(){ return this.file?.name || '' }
  },

  created(){ this.fetchRefs() },

  methods:{
    async fetchRefs(){
      try{
        const [{data:c},{data:e},{data:p}] = await Promise.all([
          axios.get('/api/references/cash'),
          axios.get('/api/financial-elements/expense'),
          axios.get('/api/counterparty')
        ])
        this.cashes    = c
        this.elements  = e
        this.providers = p.filter(x=>x.type==='provider')
      }catch(err){ console.error(err) }
    },
    toVal(it,type){ return { id:it.id, type } },
    pick(e){ this.file = e.target.files[0] },

    /* ---------- отправка ---------- */
    async send(){
      const {cashId,elementId,amount,date,counterparty} = this.form
      if(!cashId||!elementId||!amount||!counterparty){
        return alert('Заполните все поля')
      }
      const fd = new FormData()
      fd.append('type','expense')
      fd.append('admin_cash_id',cashId)
      fd.append('financial_element_id',elementId)
      fd.append('summary_cash',amount)
      fd.append('date_of_check',date)
      fd.append('counterparty_id',counterparty.id)
      fd.append('counterparty_type',counterparty.type)
      if(this.file) fd.append('photo_of_check',this.file)

      this.saving = true
      try{
        await axios.post('/api/financial-orders',fd)
        this.$toast?.success('✅ Сохранено')
        this.$emit('created')
      }catch(e){
        console.error(e); this.$toast?.error('❌ Ошибка')
      }finally{ this.saving=false }
    }
  }
}
</script>

<style scoped>
/* ===== базовые токены ===== */
:root{
  --c1:#18BDD7;--c2:#6BC6DA;--r:14px;
  font-family:Inter,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,
               "Helvetica Neue",Arial,sans-serif;
}

/* ===== layout ===== */
.page{padding:10px;max-width:420px;height:600px;margin:0 auto}

/* ▸ app-bar */
.appbar{display:flex;align-items:center;gap:10px;
       background:linear-gradient(90deg,var(--c1),var(--c2));
       color:#fff;border-radius:18px;padding:8px 14px;margin-bottom:18px;
       box-shadow:0 3px 10px rgba(0,0,0,.22)}
.icon{background:none;border:none;font-size:22px;color:#baff55;cursor:pointer}
.logo{width:38px}.title{flex:1;font-size:18px;font-weight:600}
.info{font-size:20px;color:#baff55}

/* ▸ card */
.card{background:#eef3f5;border-radius:var(--r);padding:16px;margin-bottom:18px;
      box-shadow:0 2px 6px rgba(0,0,0,.08)}
.card h2{margin:0 0 14px;color:var(--c1);font-size:17px}

/* ▸ row */
.row{display:flex;justify-content:space-between;align-items:center;
     padding:11px 0;border-bottom:1px solid #d4dee4}
.row:last-child{border:none}.lbl{font-size:15px}

/* ▸ inputs / selects (кросс-браузер) */
select,
input[type="date"]{
  -webkit-appearance:none;-moz-appearance:none;appearance:none;
  border:none;background:none;font-size:15px;color:var(--c1);
  text-align:right;max-width:60%;line-height:1.4em;padding-right:22px;
  background-image:url("data:image/svg+xml;utf8,\
      <svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' \
           viewBox='0 0 10 6' fill='%2318BDD7'><path d='M0 0l5 6 5-6z'/></svg>");
  background-repeat:no-repeat;background-position:right 4px center;
  background-size:10px 6px;
}
select:invalid{color:#888}

/* убираем number-стрелки в Safari */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button{-webkit-appearance:none;margin:0}

/* ▸ photo */
.photo .drop{width:100%;height:190px;border-radius:var(--r);background:#c5f1fb;
             display:flex;flex-direction:column;align-items:center;justify-content:center;
             font-size:20px;color:#0089b1;border:none;cursor:pointer}

/* ▸ fab */
.fab-bar{height:90px}
.fab{
  position:fixed;bottom:calc(env(safe-area-inset-bottom,0px)+24px);
  left:50%;transform:translateX(-50%);
  display:flex;align-items:center;justify-content:center;gap:6px;
  padding:0 28px;height:60px;border-radius:34px;border:none;
  background:#fff;color:#6BC6DA;font-size:16px;font-weight:600;
  box-shadow:0 4px 14px rgba(0,0,0,.35);cursor:pointer;transition:.25s;
}
.fab:hover:not(.disabled){filter:brightness(1.08);transform:translate(-50%,-3px)}
.fab.disabled,
.fab:disabled{background:#9ddde7;box-shadow:none;cursor:progress;transform:translateX(-50%)}
</style>
