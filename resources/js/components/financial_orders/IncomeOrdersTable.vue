<template>
    <div class="income-page">
      <!-- ▸ Панель ------------------------------------------------------- -->
      <header class="topbar">
        <h1>Приходные ордера</h1>

        <div class="actions">
          <input v-model.trim="q" @input="applyFilter" placeholder="🔍 Поиск..." class="search"/>

          <select v-model="filter" @change="applyFilter" class="filter">
            <option value="">Все</option>
            <option value="provider">Только «Поставщик»</option>
            <option value="element" >Только «Статья прихода»</option>
          </select>

          <button class="reload" @click="load">⟳</button>
        </div>
      </header>

      <!-- ▸ Таблица ------------------------------------------------------ -->
      <table class="orders">
        <thead>
          <tr>
            <th>Поставщик / элемент</th>
            <th>Дата</th>
            <th class="num">Сумма, ₸</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="o in view" :key="o.id">
            <td class="title">
              {{ (o.provider && o.provider.name) ||
                 (o.financial_element && o.financial_element.name) || '—' }}
            </td>
            <td>{{ o.date_of_check }}</td>
            <td class="num">{{ money(o.summary_cash) }}</td>
          </tr>

          <tr v-if="view.length === 0">
            <td colspan="3" class="empty">Данных нет</td>
          </tr>
        </tbody>

        <tfoot v-if="view.length">
          <tr>
            <td><strong>Итого</strong></td><td></td>
            <td class="num"><strong>{{ money(total) }}</strong></td>
          </tr>
        </tfoot>
      </table>

      <!-- ▸ Кнопка «Создать» -------------------------------------------- -->
      <button class="create-btn" @click="openCreateModal">
        ➕ Создать приходный ордер
      </button>

      <!-- ▸ Модалка ------------------------------------------------------ -->
      <div v-if="showCreateModal" class="modal-overlay">
        <div class="modal-container income-modal">
          <!-- крестик -->
          <button class="close-btn" @click="closeCreateModal">×</button>

          <IncomeOrderCreate @close="closeCreateModal" @created="onCreated"/>
        </div>
      </div>
    </div>
  </template>

  <script>
  import axios             from '@/plugins/axios'
  import IncomeOrderCreate from './IncomeOrderCreate.vue'

  export default {
    name: 'FinancialIncomeOrders',
    components: { IncomeOrderCreate },

    data () {
      return {
        raw: [], view: [],
        q: '', filter: '',
        showCreateModal: false
      }
    },

    created () { this.load() },

    computed: {
      total () {
        return this.view.reduce((s, o) => s + Number(o.summary_cash || 0), 0)
      }
    },

    methods: {
      async load () {
        try {
          const { data } = await axios.get('/api/financial-orders/income')
          this.raw = Array.isArray(data) ? data : []
          this.applyFilter()
        } catch (e) {
          console.error(e)
          alert('Не удалось загрузить приходные ордера')
        }
      },

      applyFilter () {
        const q = this.q.toLowerCase()
        this.view = this.raw.filter(o => {
          const txt   = (o.provider?.name || '') + (o.financial_element?.name || '')
          const byTxt = txt.toLowerCase().includes(q)
          let  byType = true
          if (this.filter === 'provider') byType = !!o.provider
          if (this.filter === 'element')  byType = !!o.financial_element
          return byTxt && byType
        })
      },

      money (v) { return Number(v || 0).toLocaleString('ru-RU') },

      /* ---- модалка ---- */
      openCreateModal  () { this.showCreateModal = true  },
      closeCreateModal () { this.showCreateModal = false },
      onCreated ()       { this.closeCreateModal(); this.load() }
    }
  }
  </script>

  <style scoped>
  /* базовый интерфейс */
  .income-page{font-family:Inter,sans-serif;padding:18px}
  .topbar{display:flex;align-items:center;gap:14px;
         background:linear-gradient(90deg,#03b4d1,#3dc1ff);
         color:#fff;padding:10px 18px;border-radius:14px;margin-bottom:16px;
         box-shadow:0 4px 12px rgba(0,0,0,.18)}
  .topbar h1{margin:0;font-size:20px;font-weight:600}
  .actions{margin-left:auto;display:flex;gap:8px;align-items:center}
  .search,.filter{height:34px;font-size:14px;padding:0 10px;border-radius:8px;border:none}
  .search{min-width:180px}
  .reload{border:none;background:none;color:#c8ff55;font-size:24px;cursor:pointer;line-height:1}

  table.orders{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;
               overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
  .orders th,.orders td{padding:11px 10px;font-size:14px}
  .orders thead{background:#f2faff;font-weight:600}
  .orders tbody tr+tr{border-top:1px solid #e2e8f0}
  .title{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:340px}
  .num{text-align:right}
  .empty{text-align:center;color:#7c7c7c;padding:14px 0}
  tfoot td{background:#fafafa;font-weight:600}

  /* новая кнопка «Создать» */
  .create-btn{
    position:fixed;right:22px;bottom:22px;
    display:flex;align-items:center;gap:6px;
    padding:0 20px;height:48px;
    background:linear-gradient(90deg,#18bdd7,#5fd0e5);
    border:none;border-radius:30px;
    box-shadow:0 4px 14px rgba(0,0,0,.28);
    color:#fff;font-size:15px;font-weight:600;cursor:pointer;
    transition:.25s;
  }
  .create-btn:hover{filter:brightness(1.08);transform:translateY(-2px)}

  /* модалка */
  .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);
                 display:flex;align-items:center;justify-content:center;z-index:1000;
                 padding:18px}
  .modal-container{background:#fff;border-radius:16px;
                   box-shadow:0 6px 18px rgba(0,0,0,.25);
                   width:100%;max-width:560px;padding:28px 24px 24px;position:relative}
  .close-btn{
    position:absolute;top:12px;right:12px;
    width:36px;height:36px;border-radius:50%;border:none;
    background:#f44336;color:#fff;font-size:22px;line-height:36px;
    cursor:pointer;display:flex;align-items:center;justify-content:center;
    box-shadow:0 2px 6px rgba(0,0,0,.3);
  }
  .close-btn:hover{filter:brightness(1.1)}
  </style>
