<template>
    <section class="tariff2">
      <h2 class="title">Тарифные планы</h2>

      <div class="cards">
        <TariffCard
          v-for="plan in plans"
          :key="plan.id"
          :plan="plan"
          :is-current="plan.id === currentTariffId"
          @select="handleSelect"
          @pay="handlePay"
        />
      </div>
    </section>
  </template>

  <script>
  import TariffCard from './TariffCard.vue';

  export default {
    name: 'TariffPlan',
    components: { TariffCard },

    data() {
      return {
        plans: [
          {
            id: 1, name: 'Клиент',  price: 0,       popular: false,
            services: [
              'Оборот безлимитный', 'ИИ-голосовой набор', 'Подача заявки',
              'Подтверждение поступления товара', 'Оплату внести',
              'Отчеты (только по долгам)',
            ],
          },
          {
            id: 2, name: 'Посредник', price: 100000, popular: false,
            services: [
              'Оборот до 1 млрд', '1С интеграция', 'ИИ-голосовой набор',
              'Приход', 'Продажа', 'Склад – списание', 'Оплата', 'Расход',
              'Отчеты (по умолчанию)', 'До 5 пользователей',
            ],
          },
          {
            id: 3, name: 'Розница', price: 200000, popular: true,
            services: [
              'Оборот до 5 млрд', '1С интеграция', 'ИИ-голосовой набор',
              'ИИ-автоветчик', 'Чат с клиентами', 'Доп расходы',
              'Склад – перемещение', 'Коммерческое предложение', 'Заявка',
              'Фасовка', 'Курьер-экспедитор', 'До 10 пользователей',
            ],
          },
          {
            id: 4, name: 'Оптовик', price: 500000, popular: false,
            services: [
              'Оборот до 5 млрд', '1С интеграция', 'ИИ-голосовой набор',
              'ИИ-автоветчик', 'Чат с клиентами', 'Доп расходы',
              'Склад – перемещение', 'Заявка', 'Фасовка', 'Курьер-экспедитор',
              'Реклама свежий товар (по локации)', 'До 50 пользователей',
            ],
          },
          {
            id: 5, name: 'Гранды', price: 1000000, popular: false,
            services: [
              'Оборот безлимитный', 'Реклама (по РК)', 'Все новые фишки',
              'Безлимитные пользователи', 'Оборот до 5 млрд', '1С интеграция',
              'ИИ-голосовой набор', 'ИИ-автоветчик', 'Чат с клиентами',
              'Доп расходы', 'Склад – перемещение', 'Коммерческое предложение',
              'Заявка', 'Фасовка', 'Курьер-экспедитор',
            ],
          },
        ],

        currentTariffId: 4,   // mock: user’s active plan
      };
    },

    methods: {
      handleSelect(plan) {
        alert(`Вы выбрали бесплатный тариф «${plan.name}»`);
      },
      handlePay(plan) {
        alert(`Оплата тарифа «${plan.name}» на ${plan.price.toLocaleString()} ₸`);
      },
    },
  };
  </script>

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
