<!-- eslint-disable vue/no-template-key -->
<template>
  <div class="sales-report-page">
    <h2>Отчёт по продажам</h2>

    <!-- ▸ Фильтры -->
    <div class="filters">
      <label>С даты:</label><input type="date" v-model="dateFrom">
      <label>по:</label><input type="date" v-model="dateTo">

      <label>Контрагент:</label>
      <select v-model="client">
        <option value="">— все —</option>
        <option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option>
      </select>

      <label>Товар:</label>
      <select v-model="product">
        <option value="">— все —</option>
        <option v-for="p in products" :key="p.id" :value="p.id">{{ p.name }}</option>
      </select>

      <button @click="fetchReport">Сформировать</button>
    </div>

    <!-- ▸ Статус -->
    <div v-if="loading">Загрузка…</div>
    <div v-else-if="error" class="error">Ошибка: {{ error }}</div>

    <!-- ▸ Таблица -->
    <div v-else>
      <table class="report-table">
        <thead>
          <tr>
            <th>Контрагент / Товар</th>
            <th>Ед.</th>
            <th>Кол-во</th>
            <th>Сумма продажи</th>
            <th>Ср. себестоимость</th>
            <th>Себестоимость (сумма)</th>
            <th>Прибыль</th>
          </tr>
        </thead>

        <tbody>
          <!-- группы -->
          <template v-for="group in rows">
            <!-- строка клиента -->
            <tr :key="'g-'+group.client_id" class="group-row">
              <td><strong>{{ group.client_name }}</strong></td>
              <td></td>
              <td><strong>{{ fmt(group.quantity) }}</strong></td>
              <td><strong>{{ fmt(group.sale_sum) }}</strong></td>
              <td></td>
              <td><strong>{{ fmt(group.cost_sum) }}</strong></td>
              <td :class="{neg: group.profit<0}">
                <strong>{{ fmt(group.profit) }}</strong>
              </td>
            </tr>

            <!-- товары -->
            <tr
              v-for="p in group.products"
              :key="'p-'+group.client_id+'-'+p.product_id+'-'+p.unit"
              class="product-row"
            >
              <td>— {{ p.product_name }}</td>
              <td>{{ p.unit }}</td>
              <td>{{ fmt(p.quantity) }}</td>
              <td>{{ fmt(p.sale_sum) }}</td>
              <td>{{ fmt(p.avg_cost) }}</td>
              <td>{{ fmt(p.cost_sum) }}</td>
              <td :class="{neg: p.profit<0}">{{ fmt(p.profit) }}</td>
            </tr>
          </template>

          <!-- общий итог -->
          <tr class="total-row">
            <td><strong>Итого</strong></td>
            <td></td>
            <td><strong>{{ fmt(total.quantity) }}</strong></td>
            <td><strong>{{ fmt(total.sale_sum) }}</strong></td>
            <td></td>
            <td><strong>{{ fmt(total.cost_sum) }}</strong></td>
            <td :class="{neg: total.profit<0}">
              <strong>{{ fmt(total.profit) }}</strong>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SalesReportPage',
  data() {
    return {
      rows: [], total: {quantity:0,sale_sum:0,cost_sum:0,profit:0},
      dateFrom:'', dateTo:'', client:'', product:'',
      clients:[], products:[], loading:false, error:null,
    };
  },
  created(){ this.loadDicts(); },
  methods:{
    loadDicts(){
      axios.get('/api/clients').then(r=>this.clients=r.data).catch(()=>{});
      axios.get('/api/products').then(r=>this.products=r.data).catch(()=>{});
    },
    fetchReport(){
      this.loading=true; this.error=null;
      axios.get('/api/report-sales',{params:{
        date_from:this.dateFrom||null,
        date_to:  this.dateTo  ||null,
        client:   this.client  ||null,
        product:  this.product ||null,
      }}).then(r=>{
        this.rows = r.data.data; this.total = r.data.total;
      }).catch(e=>{
        this.error = e.response?.data?.message || e.message;
      }).finally(()=>{ this.loading=false; });
    },
    /* 1 знак после запятой */
    fmt(v){
      if(v===null||v===undefined) return '—';
      return (+v).toLocaleString('ru-RU',{minimumFractionDigits:1, maximumFractionDigits:1});
    },
  }
};
</script>

<style scoped>
.sales-report-page{max-width:1200px;margin:0 auto;}
.filters{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px;}
.filters select,.filters input[type='date']{padding:4px 6px;}
.filters button{padding:6px 12px;background:#0288d1;color:#fff;border:none;cursor:pointer;}
.filters button:hover{background:#0277bd;}

.report-table{width:100%;border-collapse:collapse;}
.report-table th,.report-table td{border:1px solid #ccc;padding:6px 8px;}
.report-table thead th{background:#0288d1;color:#fff;}
.group-row{background:#f1f1f1;}
.product-row td:first-child{padding-left:24px;}
.total-row{background:#e0e0e0;font-weight:700;}
.neg{color:red;}
.error{color:red;margin-top:10px;}
</style>
