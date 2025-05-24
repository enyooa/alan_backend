<template>
  <div class="expense-page">
    <!-- ▸ TOP-BAR -->
    <header class="topbar">
      <h1>Ценовые предложения</h1>

      <div class="actions">
        <input v-model.trim="search"
               @input="applyFilter"
               placeholder="🔍 Поиск..."
               class="search" />
        <button class="reload" @click="load">⟳</button>
      </div>
    </header>

    <!-- ▸ TABLE -->
    <table class="orders">
      <thead>
        <tr>
          <th>№</th>
          <th>Клиент</th>
          <th>Адрес</th>
          <th>Склад</th>
          <th>Период</th>
          <th class="num">Сумма, ₸</th>
          <th class="num">Действия</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="(p,idx) in view"
            :key="p.id"
            class="click-row"
            @click="openEdit(p)">
          <td>{{ (currentPage-1)*perPage + idx + 1 }}</td>
          <td class="title">{{ p.clientFull }}</td>
          <td>{{ p.addrName }}</td>
          <td>{{ p.whName }}</td>
          <td>{{ period(p) }}</td>
          <td class="num">{{ money(p.totalsum) }}</td>
          <td class="num actions" @click.stop>
            <button class="icon-btn" @click="openEdit(p)">✏️</button>
            <button class="icon-btn danger" @click="remove(p,idx)">🗑</button>
          </td>
        </tr>

        <tr v-if="!view.length">
          <td colspan="7" class="empty">Данных нет</td>
        </tr>
      </tbody>
    </table>

    <!-- ▸ CREATE BTN -->
    <button class="create-btn" @click="showCreate=true">
      ➕ Создать ценовое предложение
    </button>

    <!-- ▸ CREATE MODAL -->
    <div v-if="showCreate" class="modal-overlay">
      <div class="modal-container">
        <button class="close-btn" @click="showCreate=false">×</button>
        <PriceOfferPage @saved="onSaved" @close="showCreate=false" />
      </div>
    </div>

    <!-- ▸ EDIT MODAL -->
    <ModalShell v-if="showEdit" @close="closeEdit">
      <EditPriceOfferModal :record="editDoc"
                           @close="closeEdit"
                           @saved="onSaved"/>
    </ModalShell>
  </div>
</template>

<script>
import axios                from '@/plugins/axios'
import PriceOfferPage       from '../../views/PriceOfferPage.vue'
import EditPriceOfferModal  from '../../views/forms/products/PriceOfferEdit.vue'
import ModalShell           from '../../views/forms/products/ModalShell.vue'

export default {
  components:{ PriceOfferPage, EditPriceOfferModal, ModalShell },

  data(){
    return{
      raw:[], view:[], search:'',
      showCreate:false, showEdit:false, editDoc:null,
      currentPage:1, perPage:25
    }
  },

  created(){ this.load() },

  methods:{
    async load(){
      try{
        const { data } = await axios.get('/api/price-offers',{ params:{ paginate:1 } })

        const list = Array.isArray(data) ? data : (data.data || [])

        this.raw = list.map(r=>({
          ...r,
          clientFull : r.client ? [r.client.first_name, r.client.last_name]
                                   .filter(Boolean).join(' ') : '—',
          addrName   : r.address?.name   ?? '—',
          whName     : r.warehouse?.name ?? '—',
          totalsum   : +r.totalsum
        }))

        if(Array.isArray(data.data)){
          this.currentPage = +data.current_page
          this.perPage     = +data.per_page
        }else{
          this.currentPage = 1
          this.perPage     = this.raw.length || 1
        }

        this.applyFilter()
      }catch(e){ console.error(e); alert('Не удалось загрузить данные') }
    },

    applyFilter(){
      const q=this.search.toLowerCase()
      this.view = !q ? this.raw
                     : this.raw.filter(p=>
                         [p.clientFull,p.addrName,p.whName,this.period(p)]
                         .join(' ').toLowerCase().includes(q))
    },

    money(v){ return (+v||0).toLocaleString('ru-RU') },
    period(p){
      const f=d=>d?new Date(d).toLocaleDateString():'—'
      return `${f(p.start_date)} – ${f(p.end_date)}`
    },

    onSaved(){ this.showCreate=false; this.closeEdit(); this.load() },
    openEdit(p){ this.editDoc=p; this.showEdit=true },
    closeEdit(){ this.showEdit=false; this.editDoc=null },

    async remove(p,idx){
      if(!confirm('Удалить предложение?')) return
      try{
        await axios.delete(`/api/price-offers/${p.id}`)
        this.raw.splice(idx,1); this.applyFilter()
      }catch(e){ alert('Не удалось удалить') }
    }
  }
}
</script>



<style scoped>
/* базовый интерфейс (цвета и стили FinancialExpenseOrders) */
.expense-page{font-family:Inter,sans-serif;padding:18px}

/* topbar */
.topbar{display:flex;align-items:center;gap:14px;
       background:linear-gradient(90deg,#03b4d1,#3dc1ff);
       color:#fff;padding:10px 18px;border-radius:14px;margin-bottom:16px;
       box-shadow:0 4px 12px rgba(0,0,0,.18)}
.topbar h1{margin:0;font-size:20px;font-weight:600}
.actions{margin-left:auto;display:flex;gap:8px;align-items:center}
.search{height:34px;font-size:14px;padding:0 10px;border-radius:8px;border:none;min-width:180px}
.reload{border:none;background:none;color:#c8ff55;font-size:24px;cursor:pointer;line-height:1}

/* table */
table.orders{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;
             overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.orders th,.orders td{padding:11px 10px;font-size:14px;text-align:center}
.orders thead{background:#f2faff;font-weight:600}
.orders tbody tr+tr{border-top:1px solid #e2e8f0}
.title{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:240px}
.num{text-align:right}
.empty{text-align:center;color:#7c7c7c;padding:14px 0}
.click-row{cursor:pointer;transition:background .15s}
.click-row:hover{background:#f7fdff}
.actions{display:flex;gap:6px;justify-content:center}

/* buttons */
.icon-btn{background:#03b4d1;color:#fff;border:none;border-radius:6px;
          padding:4px 8px;font-size:16px;cursor:pointer;transition:filter .15s}
.icon-btn.danger{background:#f44336}
.icon-btn:hover{filter:brightness(.9)}

/* floating create btn */
.create-btn{position:fixed;right:22px;bottom:22px;display:flex;align-items:center;gap:6px;
            padding:0 20px;height:48px;background:linear-gradient(90deg,#18bdd7,#5fd0e5);
            border:none;border-radius:30px;box-shadow:0 4px 14px rgba(0,0,0,.28);
            color:#fff;font-size:15px;font-weight:600;cursor:pointer;transition:.25s;z-index:900}
.create-btn:hover{filter:brightness(1.08);transform:translateY(-2px)}

/* modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);
               display:flex;align-items:center;justify-content:center;
               z-index:1000;padding:18px}
.modal-container{background:#fff;border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,.25);
                 width:100%;max-width:1100px;max-height:100vh;overflow:auto;
                 padding:28px 24px 24px;position:relative}
.close-btn{position:absolute;top:12px;right:12px;width:36px;height:36px;
           border-radius:50%;border:none;background:#f44336;color:#fff;
           font-size:22px;line-height:36px;cursor:pointer;
           display:flex;align-items:center;justify-content:center;
           box-shadow:0 2px 6px rgba(0,0,0,.3)}
.close-btn:hover{filter:brightness(1.1)}
</style>
