<!-- resources/js/views/InvoicePage.vue -->
<template>
    <div class="invoice-page">
      <!-- ▸ Top-bar ---------------------------------------------------- -->
      <header class="appbar">
        <button class="icon" @click="$router.back()">←</button>
        <img src="/assets/img/logo.png" class="logo" alt="Логотип">
        <span class="title">Накладная</span>
        <span class="info">❕</span>
      </header>

      <!-- ▸ Список адресов -------------------------------------------- -->
      <section class="card">
        <div class="card-head">
          <h2>Список</h2>
          <button class="add-btn" @click="openForm">＋</button>
        </div>

        <div v-for="row in rows" :key="row.id" class="row">
          <div class="col">
            <div class="addr">Адрес: {{ row.address }}</div>
            <div class="status">
              Статус:
              <span :class="{ done: row.done }">
                {{ row.done ? 'исполнено' : 'ожидает' }}
              </span>
            </div>
          </div>

          <div class="sum">{{ money(row.amount) }} ₸</div>

          <button class="icon-btn del" @click="remove(row.id)">🗑</button>
          <button class="icon-btn go"  @click="open(row.id)">➜</button>
        </div>

        <div v-if="!rows.length" class="empty">Данных нет</div>
      </section>
    </div>
  </template>

  <script>
  import axios from '@/plugins/axios'

  export default {
    name: 'InvoicePage',

    data () {
      return {
        rows: []          // список строк накладной
      }
    },

    created () { this.load() },

    methods: {
      /* ───── API ───── */
      async load () {
        try {
          const { data } = await axios.get('/api/invoices')   // при необходимости замените URL
          this.rows = Array.isArray(data) ? data : []
        } catch (e) {
          console.error(e)
          alert('Не удалось загрузить накладную')
        }
      },
      async remove (id) {
        if (!confirm('Удалить запись?')) return
        try {
          await axios.delete(`/api/invoices/${id}`)
          this.rows = this.rows.filter(r => r.id !== id)
        } catch (e) {
          console.error(e)
          alert('Ошибка удаления')
        }
      },

      /* ───── переходы / формы ───── */
      open      (id) { this.$router.push(`/invoice/${id}`) }, // страница-детали, если есть
      openForm  ()  { this.$router.push('/invoice-create') }, // форма добавления

      money (v) { return Number(v || 0).toLocaleString('ru-RU') }
    }
  }
  </script>

  <style scoped>
  /* ——— базовые токены ——— */
  :root{--c1:#18BDD7;--c2:#6BC6DA;--r:14px;font-family:Inter,sans-serif}

  /* ——— layout ——— */
  .invoice-page{padding:18px}

  /* top-bar */
  .appbar{display:flex;align-items:center;gap:10px;
         background:linear-gradient(90deg,var(--c1),var(--c2));
         color:#fff;border-radius:18px;padding:8px 14px;margin-bottom:22px;
         box-shadow:0 3px 10px rgba(0,0,0,.22)}
  .icon{background:none;border:none;font-size:22px;color:#baff55;cursor:pointer}
  .logo{width:38px}.title{flex:1;font-size:18px;font-weight:600}
  .info{font-size:20px;color:#baff55}

  /* карточка-список */
  .card{background:#e5e5e5;border-radius:var(--r);padding:18px;
        box-shadow:0 2px 6px rgba(0,0,0,.08)}
  .card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
  .card-head h2{margin:0;color:#03b4d1;font-size:20px}
  .add-btn{width:38px;height:38px;border-radius:50%;border:2px solid #03b4d1;
           background:none;color:#03b4d1;font-size:24px;line-height:32px;cursor:pointer}

  /* строка списка */
  .row{display:flex;align-items:center;gap:10px;padding:12px 0;
       border-top:1px solid #ccc}
  .row:first-of-type{border-top:none}
  .col{flex:1}
  .addr{font-size:16px;font-weight:500}
  .status{font-size:14px}.status .done{color:#359b2b}

  .sum{width:90px;text-align:right;font-weight:600}

  .icon-btn{border:none;background:none;font-size:22px;cursor:pointer}
  .del{color:#a32424}.go{color:#03b4d1}

  /* пустое состояние */
  .empty{text-align:center;color:#666;padding:20px 0}
  </style>
