<!-- resources/js/views/InvoiceDetails.vue -->
<template>
    <div class="details">
      <!-- ▸ App-bar -->
      <header class="appbar">
        <button class="back" @click="$router.back()">←</button>
        <img src="/assets/img/logo.png" class="logo" alt="logo" />
        <span class="title">Заявки</span>
      </header>

      <!-- ▸ Meta -->
      <div class="meta">
        <span class="pill">Адрес: <strong>{{ safe(order.address) }}</strong></span>
        <span class="pill">Статус:
          <strong :class="{ done:isDone }">{{ isDone ? 'исполнено' : 'ожидает' }}</strong>
        </span>
        <span class="pill">Клиент: <strong>{{ fullName(order.client) }}</strong></span>
        <span class="pill">Упаковщик: <strong>{{ fullName(order.packer) }}</strong></span>
        <span class="pill">Курьер: <strong>{{ fullName(order.courier) }}</strong></span>
        <span v-if="order.place_quantity" class="pill">
          Мест: <strong>{{ order.place_quantity }}</strong>
        </span>
        <span class="pill">Создано: <strong>{{ fmtDateTime(order.created_at) }}</strong></span>
      </div>

      <!-- ▸ Таблица -->
      <section class="table-wrap">
        <h2 class="section-title">Таблица товаров</h2>
        <table class="items">
          <thead>
            <tr>
              <th>Товар</th><th>Ед. изм</th><th class="num">Кол-во</th>
              <th class="num">Цена</th><th class="num">Сумма</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="it in order.order_items" :key="it.id">
              <td class="truncate">{{ productName(it) }}</td>
              <td>{{ unitName(it) }}</td>
              <td class="num">{{ it.quantity }}</td>
              <td class="num">{{ money(it.price) }}</td>
              <td class="num">{{ money(it.totalsum) }}</td>
            </tr>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="4" class="tfoot-label">Итого</td>
              <td class="num"><strong>{{ money(total) }}</strong></td>
            </tr>
          </tfoot>
        </table>
      </section>

      <!-- ▸ Export -->
      <div class="export">
        <button class="icon-btn" @click="exportPdf">
          <img src="https://api.iconify.design/mdi:file-pdf-outline.svg?color=%23e53935">
          <span>PDF</span>
        </button>
        <button class="icon-btn" @click="exportXlsx">
          <img src="https://api.iconify.design/mdi:microsoft-excel.svg?color=%23008A00">
          <span>XLSX</span>
        </button>
      </div>
    </div>
  </template>

  <script>
  import axios from '@/plugins/axios'

  export default {
    name: 'InvoiceDetails',
    props: { id: { type: String, default: null } },
    data () { return { order: {} } },

    computed: {
      isDone () { return !!this.order.done },
      total () {
        return (this.order.order_items || [])
               .reduce((s, i) => s + Number(i.totalsum || 0), 0)
      }
    },

    created () { this.load() },

    methods: {
      /* ───── API ───── */
      async load () {
        const invoiceId = this.id || this.$route.params.id
        try {
          const { data } = await axios.get(`/api/invoices/${invoiceId}`)
          this.order = data || {}
        } catch (e) { console.error(e); alert('Ошибка загрузки накладной') }
      },

      /* ───── Excel ───── */
      exportXlsx () {
        /* eslint-disable global-require */
        const XLSX = require('xlsx')
        const rows = (this.order.order_items || []).map(it => ({
          'Товар'  : this.productName(it),
          'Ед. изм': this.unitName(it),
          'Кол-во' : it.quantity,
          'Цена'   : it.price,
          'Сумма'  : it.totalsum
        }))
        const ws = XLSX.utils.json_to_sheet(rows)
        XLSX.writeFile({ SheetNames:['Товары'], Sheets:{ 'Товары': ws } },
                       `invoice-${this.order.id}.xlsx`)
      },

      /* ───── PDF ───── */
      async exportPdf () {
    /* eslint-disable global-require */
    let jsPDFlib = require('jspdf')
    jsPDFlib     = jsPDFlib.jsPDF || jsPDFlib     // sdk ≥2 / ≤1.5
    require('jspdf-autotable')

    const doc = new jsPDFlib({ unit:'pt', format:'a4' })

    /* 1. Пытаемся подтянуть Roboto из CDN */
    try {
      const url = 'https://raw.githubusercontent.com/google/fonts/main/apache/roboto/Roboto-Regular.ttf'
      const buf = await fetch(url).then(r => r.arrayBuffer())
      const b64 = btoa(String.fromCharCode(...new Uint8Array(buf)))

      doc.addFileToVFS('Roboto.ttf', b64)
      doc.addFont('Roboto.ttf', 'Roboto', 'normal')
      doc.setFont('Roboto')
    } catch (e) {
      console.warn('Roboto.ttf не загрузился — используем Helvetica', e)
      /* Helvetica встроена, работы не прервём */
    }

    /* 2. Таблица */
    doc.autoTable({
      head: [['Товар','Ед. изм','Кол-во','Цена','Сумма']],
      body: (this.order.order_items || []).map(it => [
        this.productName(it),
        this.unitName(it),
        it.quantity,
        this.money(it.price),
        this.money(it.totalsum)
      ]),
      styles: { font: 'Roboto' }    // если Roboto нет, jsPDF сам возьмёт Helvetica
    })

   /* 3. Скачать */
    doc.save(`invoice-${this.order.id}.pdf`)
  },
      /* ───── helpers ───── */
      safe (v) { return v || '—' },
      fullName (u) { return u ? `${u.first_name||''} ${u.last_name||''}`.trim() : '—' },
      productName (it) { return it && it.product_sub_card ? it.product_sub_card.name : '—' },
      unitName (it) {
        if (!it) return '—'
        if (it.unit_measurement)             return it.unit_measurement
        if (it.unit && it.unit.short_name)   return it.unit.short_name
        if (it.unit && it.unit.name)         return it.unit.name
        if (it.unit_name)                    return it.unit_name
        return '—'
      },
      money (v) { return Number(v || 0).toLocaleString('ru-RU') },
      fmtDateTime (d) {
        if (!d) return '—'
        return new Date(d).toLocaleString('ru-RU', {
          day:'2-digit', month:'2-digit', year:'numeric',
          hour:'2-digit', minute:'2-digit', second:'2-digit'
        })
      }
    }
  }
  </script>

  <style scoped>
  :root{ --c1:#03b4d1; --r:18px; font-family:Inter,sans-serif }
  .details{padding:18px;background:#f9fbfc}

  /* App-bar */
  .appbar{display:flex;align-items:center;gap:10px;
    background:linear-gradient(90deg,#03b4d1,#3dc1ff);color:#fff;
    border-radius:var(--r);padding:8px 14px;margin-bottom:16px;
    box-shadow:0 3px 10px rgba(0,0,0,.22)}
  .back{border:none;background:none;font-size:22px;color:#baff55;cursor:pointer}
  .logo{width:38px;height:38px}.title{flex:1;text-align:center;font-size:18px;font-weight:600}

  /* Meta pills */
  .meta{display:flex;gap:8px;flex-wrap:nowrap;overflow-x:auto;
    padding-bottom:10px;margin-bottom:20px}
  .pill{white-space:nowrap;flex:0 0 auto;background:#fff;color:#444;border:1px solid var(--c1);
    border-radius:999px;font-size:13px;padding:4px 12px}
  .pill strong{font-weight:600}.done{color:#359b2b}

  /* Table */
  .table-wrap{background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,.06)}
  .section-title{background:var(--c1);color:#fff;padding:10px;border-radius:10px 10px 0 0;
    margin:0;font-size:15px;font-weight:600}
  .items{width:100%;border-collapse:collapse}
  .items th,.items td{padding:10px;font-size:14px;border:1px solid #d4dfe6}
  .items th{background:#e8fbff;font-weight:600}
  .truncate{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px}
  .num{text-align:right}.tfoot-label{text-align:right;background:#f0faff;font-weight:600}

  /* Export */
  .export{display:flex;gap:24px;margin-top:22px;justify-content:center}
  .icon-btn{display:flex;flex-direction:column;align-items:center;border:none;background:none;cursor:pointer}
  .icon-btn img{width:46px;height:46px}.icon-btn span{font-size:12px;color:#777;margin-top:4px}
  </style>
