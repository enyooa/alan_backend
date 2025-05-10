<!-- resources/js/components/financial_orders/ExpenseOrderCreate.vue -->
<template>
    <div class="page">
      <!-- ▸ top-bar ---------------------------------------------------- -->
      <header class="appbar">
        <button class="icon" @click="$router.back()">←</button>
        <img src="/assets/img/logo.png" class="logo" alt="logo">
        <span class="title">Создать расходный ордер</span>
        <span class="info">❕</span>
      </header>

      <!-- ▸ Информация ------------------------------------------------- -->
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
            <option v-for="p in parties.providers"
                    :key="p.id"
                    :value="toVal(p,'provider')">
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

      <!-- ▸ Дата ------------------------------------------------------- -->
      <section class="card">
        <h2>Дата чека</h2>

        <FieldRow label="Выберите дату">
          <input v-model="form.date" type="date">
        </FieldRow>
      </section>

      <!-- ▸ Фото ------------------------------------------------------- -->
      <section class="photo card">
        <input ref="file" hidden type="file" accept="image/*" @change="pick">
        <button class="drop" @click="$refs.file.click()">
          <span v-if="!photoName">⬇<br>Загрузить фото</span>
          <span v-else>📎 {{ photoName }}</span>
        </button>
      </section>

      <!-- ▸ bottom actions -------------------------------------------- -->
      <footer class="fab-bar">
        <button class="fab"
                :class="{ disabled: saving }"
                :disabled="saving"
                @click="send">
          {{ saving ? '⏳ Сохраняю…' : '✔️ Создать' }}
        </button>
      </footer>
    </div>
  </template>

  <script>
  import axios from '@/plugins/axios'

  export const FieldRow = {
    functional: true,
    props: { label: String },
    render (h, { props, children }) {
      return h('div', { staticClass: 'row' }, [
        h('span', { staticClass: 'lbl' }, props.label),
        children
      ])
    }
  }

  export default {
    name: 'ExpenseOrderCreate',
    components: { FieldRow },

    data: () => ({
      cashes: [], elements: [],
      parties: { providers: [] },

      form: {
        cashId: '', elementId: '', amount: '',
        date: new Date().toISOString().substr(0, 10),
        counterparty: null            // {id,type}
      },

      file: null, saving: false
    }),

    computed: { photoName () { return this.file?.name || '' } },

    created () { this.fetchRefs() },

    methods: {
      async fetchRefs () {
        try {
          const [c, e, p] = await Promise.all([
            axios.get('/api/references/cash'),
            axios.get('/api/financial-elements/expense'),
            axios.get('/api/counterparty')
          ])
          this.cashes   = c.data
          this.elements = e.data
          this.parties.providers = p.data.filter(x => x.type === 'provider')
        } catch (err) { console.error(err) }
      },
      toVal (it, type) { return { id: it.id, type } },
      pick (e) { this.file = e.target.files[0] },

      async send () {
        const { cashId, elementId, amount, counterparty, date } = this.form
        if (!cashId || !elementId || !amount || !counterparty) {
          return alert('Заполните все поля')
        }
        const fd = new FormData()
        fd.append('type', 'expense')
        fd.append('admin_cash_id',        cashId)
        fd.append('financial_element_id', elementId)
        fd.append('summary_cash',         amount)
        fd.append('date_of_check',        date)
        fd.append('counterparty_id',      counterparty.id)
        fd.append('counterparty_type',    counterparty.type)
        if (this.file) fd.append('photo_of_check', this.file)

        this.saving = true
        try {
          await axios.post('/api/financial-orders', fd)
          this.$toast?.success('✅ Сохранено')
          this.$emit('created')          // сообщим родителю
        } catch (e) {
          console.error(e)
          this.$toast?.error('❌ Ошибка')
        } finally {
          this.saving = false
        }
      }
    }
  }
  </script>

  <style scoped>
  /* (CSS тот же, что и у компонента прихода) */
  :root{--c1:#18BDD7;--c2:#6BC6DA;--r:14px;font-family:Inter,sans-serif}
  .page{padding:10px;max-width:420px;height:600px;margin:0 auto}
  .appbar{display:flex;align-items:center;gap:10px;
         background:linear-gradient(90deg,var(--c1),var(--c2));
         color:#fff;border-radius:18px;padding:8px 14px;margin-bottom:18px;
         box-shadow:0 3px 10px rgba(0,0,0,.22)}
  .icon{background:none;border:none;font-size:22px;color:#baff55;cursor:pointer}
  .logo{width:38px}.title{flex:1;font-size:18px;font-weight:600}
  .info{font-size:20px;color:#baff55}

  .card{background:#eef3f5;border-radius:var(--r);padding:16px;margin-bottom:18px;
        box-shadow:0 2px 6px rgba(0,0,0,.08)}
  .card h2{margin:0 0 14px;color:var(--c1);font-size:17px}

  .row{display:flex;justify-content:space-between;align-items:center;
       padding:11px 0;border-bottom:1px solid #d4dee4}
  .row:last-child{border:none}.lbl{font-size:15px}
  select,input{border:none;background:none;font-size:15px;color:var(--c1);
               text-align:right;max-width:60%}
  select:invalid{color:#888}

  .photo .drop{width:100%;height:190px;border-radius:var(--r);background:#c5f1fb;
               display:flex;flex-direction:column;align-items:center;justify-content:center;
               font-size:20px;color:#0089b1;border:none;cursor:pointer}

  .fab-bar{height:90px}
  .fab{position:fixed;bottom:28px;left:50%;transform:translateX(-50%);
       display:flex;align-items:center;justify-content:center;gap:6px;font-weight:600;
       padding:0 28px;height:60px;border-radius:34px;border:none;background:white;
       color:#6BC6DA;font-size:16px;box-shadow:0 4px 14px rgba(0,0,0,.35);cursor:pointer;
       transition:.25s;}
  .fab:hover:not(.disabled){filter:brightness(1.08);transform:translate(-50%,-3px)}
  .fab.disabled,.fab:disabled{background:#9ddde7;box-shadow:none;cursor:progress;
                              transform:translateX(-50%)}
  </style>
