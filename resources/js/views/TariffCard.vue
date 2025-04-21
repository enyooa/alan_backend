<template>
    <div class="card" :class="{ current: isCurrent, popular: plan.popular }">
      <!-- Card header -->
      <header class="card-head">
        <h3 class="plan-name">
          {{ plan.name }}
          <span v-if="plan.popular" class="star">★</span>
        </h3>

        <p class="price">
          <span class="val">
            {{ plan.price > 0 ? plan.price.toLocaleString('ru-RU') : '0' }}
          </span>
          <span class="period">₸/М</span>
        </p>
      </header>

      <!-- Services -->
      <ul class="service-list">
        <li v-for="s in plan.services" :key="s">{{ s }}</li>
      </ul>

      <!-- Action -->
      <button
        v-if="canSelect"
        class="main-btn"
        @click="$emit('select', plan)"
      >
        ➡️ Выбрать
      </button>

      <button
        v-else-if="canPay"
        class="main-btn"
        @click="$emit('pay', plan)"
      >
        💳 Оплатить
      </button>

      <p v-else class="paid-label">Оплачено</p>
    </div>
  </template>

  <script setup>
  import { computed } from 'vue';

  const props = defineProps({
    plan: { type: Object, required: true },
    isCurrent: { type: Boolean, default: false },
  });

  const canSelect = computed(() => props.plan.price === 0 && !props.isCurrent);
  const canPay    = computed(() => props.plan.price > 0  && !props.isCurrent);
  </script>

  <style scoped>
  .card {
    width: 220px;
    padding: 18px 20px 24px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: transform .2s;
  }
  .card:hover { transform: translateY(-4px); }

  .card.popular { border: 2px solid #00bcd4; }
  .card.current { border: 2px solid #4caf50; }

  .plan-name {
    font-size: 20px;
    margin: 0 0 6px;
    text-align: center;
    color: #333;
  }
  .star { color: #00bcd4; margin-left: 4px; }

  .price   { font-size: 22px; font-weight: 700; margin: 0 0 12px; color: #333; }
  .period  { font-size: 14px; font-weight: 400; }

  .service-list {
    flex: 1;
    padding: 0;
    margin: 0 0 16px;
    list-style: none;
    font-size: 13px;
    color: #0d8abf;
    line-height: 1.35;
  }

  .main-btn {
    width: 100%;
    padding: 10px 0;
    background: #00bcd4;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    cursor: pointer;
    transition: background .3s;
  }
  .main-btn:hover { background: #0097a7; }

  .paid-label { color: #4caf50; font-weight: 600; }
  </style>
