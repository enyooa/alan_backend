<template>
    <div class="tariff-wrapper">
      <!-- Headline -->
      <h2 class="title">Тарифные планы</h2>

      <!-- Cards row -->
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
    </div>
  </template>

  <script setup>
  import { ref } from 'vue';
  import TariffCard from './TariffCard.vue';

  /* -----------------------------------------------------------
   * Static demo data – remove or replace with API data as needed
   * ----------------------------------------------------------*/
  const plans = ref([
    {
      id: 1,
      name: 'Клиент',
      price: 0,
      popular: false,
      services: [
        'Оборот безлимитный',
        'ИИ‑голосовой набор',
        'Подача заявки',
        'Подтверждение поступления товара',
        'Оплату внести',
        'Отчеты (только по долгам)',
      ],
    },
    {
      id: 2,
      name: 'Посредник',
      price: 100000,
      popular: false,
      services: [
        'Оборот до 1 млрд',
        '1С интеграция',
        'ИИ‑голосовой набор',
        'Приход',
        'Продажа',
        'Склад – списание',
        'Оплата',
        'Расход',
        'Отчеты (по умолчанию)',
        'До 5 пользователей',
      ],
    },
    {
      id: 3,
      name: 'Розница',
      price: 200000,
      popular: true, // ⭐
      services: [
        'Оборот до 5 млрд',
        '1С интеграция',
        'ИИ‑голосовой набор',
        'ИИ‑автоветчик',
        'Чат с клиентами',
        'Доп расходы',
        'Склад – перемещение',
        'Коммерческое предложение',
        'Заявка',
        'Фасовка',
        'Курьер‑экспедитор',
        'До 10 пользователей',
      ],
    },
    {
      id: 4,
      name: 'Оптовик',
      price: 500000,
      popular: false,
      services: [
        'Оборот до 5 млрд',
        '1С интеграция',
        'ИИ‑голосовой набор',
        'ИИ‑автоветчик',
        'Чат с клиентами',
        'Доп расходы',
        'Склад – перемещение',
        'Заявка',
        'Фасовка',
        'Курьер‑экспедитор',
        'Реклама свежий товар в виде статуса по локации',
        'До 50ти пользователей',
      ],
    },
    {
      id: 5,
      name: 'Гранды',
      price: 1000000,
      popular: false,
      services: [
        'Оборот безлимитный',
        'Реклама свежий товар в виде статуса по РК',
        'Все новые фишки при обновлении',
        'Безлимитные пользователи',
        'Оборот до 5 млрд',
        '1С интеграция',
        'ИИ‑голосовой набор',
        'ИИ‑автоветчик',
        'Чат с клиентами',
        'Доп расходы',
        'Склад – перемещение',
        'Коммерческое предложение',
        'Заявка',
        'Фасовка',
        'Курьер‑экспедитор',
        'Реклама свежий товар в виде статуса по локации',
      ],
    },
  ]);

  /* The user’s current tariff (mock) */
  const currentTariffId = ref(4);

  /* Handlers – replace with real API calls */
  function handleSelect(plan) {
    alert(`Вы выбрали бесплатный тариф «${plan.name}»`);
  }
  function handlePay(plan) {
    alert(`Оплата тарифа «${plan.name}» на ${plan.price.toLocaleString()} ₸`);
  }
  </script>

  <style scoped>
  .tariff-wrapper {
    max-width: 1200px;
    margin: 0 auto;
    padding: 24px;
  }
  .title {
    text-align: center;
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 28px;
    color: #333;
  }

  .cards {
    display: flex;
    flex-wrap: wrap;
    gap: 24px;
    justify-content: center;
  }
  </style>
