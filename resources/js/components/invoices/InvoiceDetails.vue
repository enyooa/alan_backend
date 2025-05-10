<template>
    <div class="details">
      <!-- top-bar -->
      <header class="appbar">
        <button class="icon" @click="$router.back()">←</button>
        <img src="/assets/img/logo.png" class="logo" alt="logo">
        <span class="title">Накладная</span>
      </header>

      <!-- общая информация -->
      <section class="card">
        <h2>Информация</h2>
        <InfoRow label="Адрес">{{ order.address }}</InfoRow>
        <InfoRow label="Статус">
          <span :class="{ done: order.done }">
            {{ order.done ? 'исполнено' : 'ожидает' }}
          </span>
        </InfoRow>
        <InfoRow label="Клиент">{{ fullName(order.client) }}</InfoRow>
        <InfoRow label="Упаковщик">{{ fullName(order.packer) }}</InfoRow>
        <InfoRow label="Курьер">{{ fullName(order.courier) }}</InfoRow>
        <InfoRow label="Мест">{{ order.place_quantity || '—' }}</InfoRow>
        <InfoRow label="Создано">
          {{ new Date(order.created_at).toLocaleString('ru-RU') }}
        </InfoRow>
      </section>

      <!-- товары -->
      <section class="card">
        <h2>Товары</h2>
        <table class="items">
          <thead>
            <tr>
              <th>Товар</th>
              <th class="num">Кол-во</th>
              <th class="num">Цена</th>
              <th class="num">Сумма</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="it in order.order_items" :key="it.id">
              <td>{{ it.product_sub_card ? it.product_sub_card.name : '—' }}</td>
              <td class="num">{{ it.quantity }}</td>
              <td class="num">{{ money(it.price) }}</td>
              <td class="num">{{ money(it.totalsum) }}</td>
            </tr>
          </tbody>
        </table>
      </section>
    </div>
  </template>

  <script>
  import axios from '@/plugins/axios'

  export const InfoRow = {
    functional:true,
    props:{label:String},
    render(h,{props,children}) {
      return h('div',{staticClass:'row'},[
        h('span',{staticClass:'lbl'},props.label), children
      ])
    }
  }

  export default {
    name : 'InvoiceDetails',
    props: { id:{ type:String, required:true } },
    components:{ InfoRow },

    data:()=>({ order:{} }),

    async created(){ await this.load() },

    methods:{
      async load(){
        try{
          const { data } = await axios.get(`/api/invoices/${this.id}`)
          this.order = data
        }catch(e){ console.error(e); alert('Ошибка загрузки') }
      },
      fullName(u){ return u ? `${u.first_name||''} ${u.last_name||''}`.trim() : '—' },
      money(v){ return Number(v||0).toLocaleString('ru-RU') }
    }
  }
  </script>

  <style scoped>
  :root{--c1:#18BDD7;--r:14px;font-family:Inter,sans-serif}
  .details{padding:18px}

  /* top-bar */
  .appbar{display:flex;align-items:center;gap:10px;background:linear-gradient(90deg,#03b4d1,#3dc1ff);
         color:#fff;border-radius:18px;padding:8px 14px;margin-bottom:20px;box-shadow:0 3px 10px rgba(0,0,0,.22)}
  .icon{background:none;border:none;font-size:22px;color:#baff55;cursor:pointer}
  .logo{width:38px}.title{flex:1;font-size:18px;font-weight:600}

  /* карточки */
  .card{background:#eef3f5;border-radius:var(--r);padding:16px;margin-bottom:18px;
        box-shadow:0 2px 6px rgba(0,0,0,.08)}
  .card h2{margin:0 0 14px;color:#03b4d1;font-size:18px}

  /* строки InfoRow */
  .row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid #d4dee4}
  .row:last-child{border:none}.lbl{font-size:15px;font-weight:500}
  .done{color:#359b2b}

  /* таблица товаров */
  table.items{width:100%;border-collapse:collapse}
  .items th,.items td{padding:10px 6px;font-size:14px}
  .items thead{background:#f2faff}
  .num{text-align:right}
  </style>
