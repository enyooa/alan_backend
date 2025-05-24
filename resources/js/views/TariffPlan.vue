<template>
  <section class="tariff2">
    <h2 class="title">Тарифные планы</h2>

    <div class="cards" v-if="!loading">
      <TariffCard
        v-for="plan in plans"
        :key="plan.id"
        :plan="plan"
        :is-current="plan.id === currentTariffId"
        @select="handleSelect"
        @pay="handlePay"
      />
    </div>

    <p v-else>⌛ Загружаем тарифы…</p>
  </section>
</template>

<script>
import axios      from '@/plugins/axios'   // ваш Axios-инстанс
import TariffCard from './TariffCard.vue'

export default {
  name:'TariffPlan',
  components:{ TariffCard },

  data(){ return{
    plans:            [],
    currentTariffId:  null,
    loading:          true
  }},

  mounted(){
    Promise.all([
      axios.get('/api/plans'),       // ← теперь уже с permissions
      axios.get('/api/my/plan')      // current plan (можно не авторизованным не звать)
    ])
    .then(([all, cur])=>{
      this.plans           = all.data
      this.currentTariffId = cur.data ? cur.data.id : null
    })
    .catch(err=>{
      console.error(err)
      this.$toast?.error('Не удалось загрузить тарифы')
    })
    .finally(()=> this.loading = false)
  },

  methods:{
    handleSelect(plan){
      /* бесплатный → сразу активируем */
      axios.post(`/api/my/plan/${plan.id}`)
        .then(()=> { this.currentTariffId = plan.id })
        .catch(()=> this.$toast?.error('Ошибка переключения тарифа'))
    },
    handlePay(plan){
      // откройте форму оплаты / redirect на кассу
      alert(`Оплата тарифа «${plan.name}» – ${plan.price.toLocaleString()} ₸`)
    }
  }
}
</script>

<style>
.tariff2{
  max-width:1200px; margin:0 auto; padding:32px 24px;
}
.title{
  text-align:center; font-size:32px; font-weight:800; margin-bottom:40px;
}
.cards{
  display:flex; flex-wrap:wrap; gap:24px; justify-content:center;
}
</style>


  <!--  NOT scoped – inherits global wallpaper / vars  -->
  <style>
  .tariff2{
    max-width:1200px;
    margin:0 auto;
    padding:32px 24px;
    background:white;
  }

  .title{
    text-align:center;
    font-size:32px;
    font-weight:800;
    margin-bottom:40px;
    color:#333;
  }

  .cards{
    display:flex;
    flex-wrap:wrap;
    gap:24px;
    justify-content:center;
  }
  </style>
