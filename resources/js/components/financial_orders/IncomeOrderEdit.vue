<!-- resources/js/components/financial_orders/IncomeOrderEdit.vue -->
<template>
  <div class="page">
    <!-- ▸ APP-BAR (фикс) -->
    <header class="appbar">
      <span class="title">Редактировать приходный ордер</span>
      <button class="close" @click="$emit('close')">×</button>
    </header>

    <!-- ▸ SCROLLABLE body -->
    <div class="page-scroll">
      <!-- ▸ INFO ----------------------------------------------------- -->
      <section class="card">
        <h2>Информация</h2>

        <FieldRow label="Счёт кассы">
          <select v-model="form.admin_cash_id">
            <option disabled value="">Выберите…</option>
            <option v-for="c in cashes" :key="c.id" :value="c.id">{{ c.name }}</option>
          </select>
        </FieldRow>

        <FieldRow label="Контрагент">
          <select v-model="counterpartyId">
            <option disabled value="">Выберите…</option>
            <optgroup label="Клиенты"      v-if="clients.length">
              <option v-for="c in clients"      :key="c.id" :value="c.id+'|client'">{{ c.name }}</option>
            </optgroup>
            <optgroup label="Организации"  v-if="orgs.length">
              <option v-for="o in orgs"         :key="o.id" :value="o.id+'|organization'">{{ o.name }}</option>
            </optgroup>
            <optgroup label="Поставщики"   v-if="providers.length">
              <option v-for="p in providers"    :key="p.id" :value="p.id+'|provider'">{{ p.name }}</option>
            </optgroup>
          </select>
        </FieldRow>

        <FieldRow label="Статья прихода">
          <select v-model="form.financial_element_id">
            <option disabled value="">Выберите…</option>
            <option v-for="e in elements" :key="e.id" :value="e.id">{{ e.name }}</option>
          </select>
        </FieldRow>

        <FieldRow label="Сумма">
          <input v-model.number="form.summary_cash" type="number" min="0" step="100">
        </FieldRow>
      </section>

      <!-- ▸ DATE -->
      <section class="card">
        <h2>Дата чека</h2>
        <FieldRow label="Дата">
          <input v-model="form.date_of_check" type="date">
        </FieldRow>
      </section>

      <!-- ▸ PHOTO (readonly-preview)  -->
      <section class="photo card" v-if="record.photo_of_check">
        <img :src="record.photo_of_check" class="preview">
      </section>
    </div><!-- /scroll -->

    <!-- ▸ FOOTER BUTTONS -->
    <footer class="fab-bar">
      <button class="fab" :disabled="saving" @click="save">
        {{ saving ? '⏳…' : '💾 Сохранить' }}
      </button>
      <button class="fab danger" :disabled="saving" @click="destroy">
        🗑 Удалить
      </button>
    </footer>
  </div>
</template>

<script>
import axios from '@/plugins/axios'

/* — маленький компонент строки — */
const FieldRow = {
  functional:true,
  props:{ label:String },
  render(h,{props,children}){
    return h('div',{staticClass:'row'},[
      h('span',{staticClass:'lbl'},props.label),
      children
    ])
  }
}

export default {
  name : 'IncomeOrderEdit',
  components:{ FieldRow },
  props:{ record:{ type:Object, required:true } },

  data(){
    return{
      /* формы сразу копируем из record */
      form:{
        admin_cash_id        : this.record.admin_cash_id || '',
        financial_element_id : this.record.financial_element_id || '',
        summary_cash         : this.record.summary_cash,
        date_of_check        : (this.record.date_of_check||'').slice(0,10)
      },
      /* cont-party комбинированное значение "id|type" */
      counterpartyId: this.initCounterparty(),

      cashes:[], elements:[],
      clients:[], providers:[], orgs:[],
      saving:false
    }
  },

  created(){ this.fetchRefs() },

  methods:{
    initCounterparty(){
      const { provider, user } = this.record
      if(provider) return `${provider.id}|provider`
      if(user)     return `${user.id}|client`
      return ''
    },

    async fetchRefs(){
      try{
        const [{data:c},{data:e},{data:p}] = await Promise.all([
          axios.get('/api/references/cash'),
          axios.get('/api/financial-elements/income'),
          axios.get('/api/counterparty')
        ])
        this.cashes   = c
        this.elements = e
        this.clients   = p.filter(x=>x.type==='client')
        this.providers = p.filter(x=>x.type==='provider')
        this.orgs      = p.filter(x=>x.type==='organization')
      }catch(err){ console.error(err) }
    },

    /* ---------- SAVE (PUT) ---------- */
    async save(){
      const [cpId, cpType] = this.counterpartyId.split('|')
      if(!cpId) { alert('Выберите контрагента'); return }

      const payload = {
        ...this.form,
        counterparty_id   : cpId,
        counterparty_type : cpType
      }
      this.saving=true
      try{
        await axios.put(`/api/financial-order/${this.record.id}`, payload)
        this.$toast?.success('✅ Сохранено')
        this.$emit('saved')
      }catch(e){
        console.error(e); this.$toast?.error('❌ Ошибка сохранения')
      }finally{ this.saving=false }
    },

    /* ---------- DELETE ---------- */
    async destroy(){
      if(!confirm('Удалить ордер безвозвратно?')) return
      this.saving=true
      try{
        await axios.delete(`/api/financial-order/${this.record.id}`)
        this.$toast?.success('🗑 Удалён')
        this.$emit('saved')
      }catch(e){
        console.error(e); this.$toast?.error('❌ Не удалось удалить')
      }finally{ this.saving=false }
    }
  }
}
</script>

<style scoped>
:root {
  --c1: #18bdd7;
  --c2: #6bc6da;
  --r: 14px;
  font-family: Inter, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
    'Helvetica Neue', Arial, sans-serif;
}

/* ------------- layout shell ------------- */
.page {
  padding: 10px;
  max-width: 420px;
  height: 100vh; /* full viewport */
  margin: 0 auto;
  display: flex;
  flex-direction: column;
}

/* scrollable middle */
.page-scroll {
  flex: 1 1 auto;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
  padding-bottom: 90px; /* keep FAB visible */
}

/* ------------- app-bar ------------- */
.appbar {
  display: flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(90deg, var(--c1), var(--c2));
  color: #fff;
  border-radius: 18px;
  padding: 8px 14px;
  margin-bottom: 18px;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.22);
}

.icon {
  background: none;
  border: none;
  font-size: 22px;
  color: #baff55;
  cursor: pointer;
}
.logo {
  width: 38px;
}
.title {
  flex: 1;
  font-size: 18px;
  font-weight: 600;
}
.info {
  font-size: 20px;
  color: #baff55;
}

/* ------------- card ------------- */
.card {
  background: #eef3f5;
  border-radius: var(--r);
  padding: 16px;
  margin-bottom: 18px;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
}
.card h2 {
  margin: 0 0 14px;
  color: var(--c1);
  font-size: 17px;
}

/* ------------- rows ------------- */
.row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 11px 0;
  border-bottom: 1px solid #d4dee4;
}
.row:last-child {
  border: none;
}
.lbl {
  font-size: 15px;
}

/* inputs & selects */
select,
input[type='date'] {
  -webkit-appearance: none;
  -moz-appearance: none;
  appearance: none;
  border: none;
  background: none;
  font-size: 15px;
  color: var(--c1);
  text-align: right;
  max-width: 60%;
  line-height: 1.4em;
  padding-right: 22px;
  background-image: url("data:image/svg+xml;utf8,\
     <svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' \
viewBox='0 0 10 6' fill='%2318BDD7'><path d='M0 0l5 6 5-6z'/></svg>");
  background-repeat: no-repeat;
  background-position: right 4px center;
  background-size: 10px 6px;
}
select:invalid {
  color: #888;
}
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* photo */
.photo .drop {
  width: 100%;
  height: 190px;
  border-radius: var(--r);
  background: #c5f1fb;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  color: #0089b1;
  border: none;
  cursor: pointer;
}

/* fab */
.fab-bar {
  height: 90px;
}
.fab {
  position: fixed;
  bottom: calc(env(safe-area-inset-bottom, 0px) + 24px);
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 0 28px;
  height: 60px;
  border-radius: 34px;
  border: none;
  background: #fff;
  color: #6bc6da;
  font-size: 16px;
  font-weight: 600;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
  cursor: pointer;
  transition: 0.25s;
}
.fab:hover:not(.disabled) {
  filter: brightness(1.08);
  transform: translate(-50%, -3px);
}
.fab.disabled,
.fab:disabled {
  background: #9ddde7;
  box-shadow: none;
  cursor: progress;
  transform: translateX(-50%);
}
</style>
