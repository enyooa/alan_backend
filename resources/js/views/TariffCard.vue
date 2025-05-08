<template>
    <div :class="['tariff-card', { popular: plan.popular, current: isCurrent }]">
      <div class="head">
        <h3 class="name">{{ plan.name }}</h3>
        <span v-if="plan.popular" class="badge">Популярный</span>
      </div>

      <p class="price">
        <strong v-if="plan.price">{{ plan.price.toLocaleString() }} ₸</strong>
        <strong v-else>Бесплатно</strong>
        <small v-if="!plan.price"> / навсегда</small>
      </p>

      <ul class="services">
        <li v-for="s in plan.services" :key="s">
          <i class="pi pi-check" /> {{ s }}
        </li>
      </ul>

      <button
        v-if="plan.price === 0 && !isCurrent"
        class="btn select"
        @click="$emit('select', plan)"
      >
        Выбрать
      </button>

      <button
        v-else-if="plan.price && !isCurrent"
        class="btn pay"
        @click="$emit('pay', plan)"
      >
        Оплатить
      </button>

      <span v-else class="current-label">Ваш тариф</span>
    </div>
  </template>

  <script>
  export default {
    name: 'TariffCard',
    props: {
      plan:       { type: Object, required: true },
      isCurrent:  { type: Boolean, default: false },
    },
  };
  </script>

  <style>
  .tariff-card{
    width:260px;
    padding:24px 20px;
    border-radius:16px;
    background:var(--glass-bg);
    backdrop-filter:var(--glass-blur);
    box-shadow:0 12px 20px rgba(0,0,0,.06);
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    transition:transform .25s;
  }
  .tariff-card:hover{ transform:translateY(-4px); }

  /* header */
  .head{ display:flex; justify-content:space-between; align-items:center; }
  .name{ font-size:20px; font-weight:700; margin:0; }
  .badge{
    background:var(--brand-from);
    color:#fff; font-size:12px; padding:4px 8px; border-radius:12px;
  }

  /* price */
  .price{
    font-size:24px; font-weight:700; margin:20px 0 16px;
    color:var(--brand-from);
  }

  /* services */
  .services{
    flex:1;
    list-style:none; padding:0; margin:0 0 20px;
    font-size:14px; color:#333;
  }
  .services li{ margin-bottom:6px; display:flex; align-items:center; }
  .services i{ font-size:12px; margin-right:6px; color:var(--brand-from); }

  /* buttons */
  .btn{
    width:100%;
    padding:10px 0;
    font-size:14px;
    border:none; border-radius:24px;
    color:#fff; cursor:pointer; transition:filter .2s;
  }
  .btn.select{ background:var(--brand-from); }
  .btn.pay{    background:var(--c1, #7ebf52); }
  .btn:hover{ filter:brightness(.9); }

  /* current plan label */
  .current-label{
    display:inline-block;
    text-align:center;
    font-size:14px;
    color:#fff;
    background:var(--brand-to);
    padding:8px 0;
    border-radius:24px;
  }
  .current{ border:2px solid var(--brand-from); }
  .popular{ box-shadow:0 0 0 3px var(--brand-from); }
  </style>
