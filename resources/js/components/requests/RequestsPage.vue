<template>
    <div class="invoice-page">
      <!-- ── верхняя панель ─────────────────────────────────────────── -->
      <header class="topbar">
        <h1>Заявки</h1>

        <div class="actions">
          <input v-model.trim="q"
                 @input="applyFilter"
                 placeholder="🔍 Поиск (адрес)..."
                 class="search"/>
          <select v-model="filter" @change="applyFilter" class="filter">
            <option value="">Все</option>
            <option value="done">Только «исполнено»</option>
            <option value="pending">Только «ожидает»</option>
          </select>
          <button class="reload" @click="load">⟳</button>
        </div>
      </header>

      <!-- ── таблица ────────────────────────────────────────────────── -->
      <table class="orders">
        <thead>
          <tr>
            <th>Адрес</th>
            <th>Упаковщик</th>
            <th>Курьер</th>
            <th>Статус</th>
            <th class="num">Сумма, ₸</th>
          </tr>
        </thead>

        <tbody>
          <tr v-for="inv in view"
              :key="inv.id"
              @click="open(inv.id)"
              class="click-row">
            <td class="addr">{{ inv.address }}</td>
            <td>{{ fullName(inv.packer) }}</td>
            <td>{{ fullName(inv.courier) }}</td>
            <td>
              <span :class="{ done: inv.done }">
                {{ inv.done ? 'исполнено' : 'ожидает' }}
              </span>
            </td>
            <td class="num">{{ money(inv.amount) }}</td>
          </tr>

          <tr v-if="view.length === 0">
            <td colspan="5" class="empty">Данных нет</td>
          </tr>
        </tbody>

        <tfoot v-if="view.length">
          <tr>
            <td><strong>Итого</strong></td><td></td><td></td><td></td>
            <td class="num"><strong>{{ money(total) }}</strong></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </template>

  <script>
  import axios from '@/plugins/axios'

  export default {
    name: 'InvoiceOrdersTable',

    data () {
      return { raw: [], view: [], q: '', filter: '' }
    },

    created () { this.load() },

    computed: {
      total () { return this.view.reduce((s, i) => s + Number(i.amount || 0), 0) }
    },

    methods: {
      async load () {
        try {
          const { data } = await axios.get('/api/invoices')
          this.raw = Array.isArray(data) ? data : []
          this.applyFilter()
        } catch (e) {
          console.error(e)
          alert('Не удалось загрузить накладные')
        }
      },
      applyFilter () {
        const q = this.q.toLowerCase()
        this.view = this.raw.filter(inv => {
          const byTxt = (inv.address || '').toLowerCase().includes(q)
          let byStatus = true
          if (this.filter === 'done')    byStatus = !!inv.done
          if (this.filter === 'pending') byStatus = !inv.done
          return byTxt && byStatus
        })
      },
      money (v)   { return Number(v || 0).toLocaleString('ru-RU') },
      fullName(u) { return u ? `${u.first_name || ''} ${u.last_name || ''}`.trim() : '—' },
      open (id)   { this.$router.push({ name:'request-details', params:{ id } }) }
    }
  }
  </script>

  <style scoped>
  .invoice-page{font-family:Inter,sans-serif;padding:18px}

  /* ── top-bar ───────────────── */
  .topbar{display:flex;align-items:center;gap:14px;
         background:linear-gradient(90deg,#03b4d1,#3dc1ff);
         color:#fff;padding:10px 18px;border-radius:14px;margin-bottom:16px;
         box-shadow:0 4px 12px rgba(0,0,0,.18)}
  .topbar h1{margin:0;font-size:20px;font-weight:600}
  .actions{margin-left:auto;display:flex;gap:8px;align-items:center}
  .search,.filter{height:34px;font-size:14px;padding:0 10px;border-radius:8px;border:none}
  .search{min-width:180px}
  .reload{border:none;background:none;color:#c8ff55;font-size:24px;cursor:pointer;line-height:1}

  /* ── таблица ───────────────── */
  table.orders{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;
               overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
  .orders th,.orders td{padding:11px 10px;font-size:14px}
  .orders thead{background:#f2faff;font-weight:600}
  .orders tbody tr+tr{border-top:1px solid #e2e8f0}
  .addr{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:320px}
  .num{text-align:right}.empty{text-align:center;color:#7c7c7c;padding:14px 0}
  tfoot td{background:#fafafa;font-weight:600}

  /* клик по строке */
  .click-row{cursor:pointer;transition:background .15s}
  .click-row:hover{background:#f7fdff}
  .done{color:#359b2b}
  </style>
