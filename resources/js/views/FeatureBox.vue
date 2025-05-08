<template>
    <article class="feature">
      <div class="icon-wrapper">
        <i :class="['pi', icon]" />
      </div>

      <h3 class="title">{{ title }}</h3>

      <p class="text">
        <slot />
      </p>
    </article>
  </template>

  <script>
  export default {
    name: 'FeatureBox',
    props: {
      /* PrimeIcons class name, e.g. "pi-shopping-cart" */
      icon:  { type: String, default: 'pi-info-circle' },

      /* Card headline */
      title: { type: String, required: true },
    },
  };
  </script>

  <!--  not scoped – inherits global vars  -->
  <style>
  .feature{
    background:#fff;
    border-radius:16px;
    padding:28px 24px;
    text-align:center;
    box-shadow:0 6px 15px rgba(0,0,0,.05);
    display:flex;
    flex-direction:column;
    align-items:center;
    transition:transform .25s;
  }
  .feature:hover{ transform:translateY(-4px); }

  .icon-wrapper{
    width:64px; height:64px;
    border-radius:50%;
    background:var(--glass-bg, rgba(255,255,255,.45));
    backdrop-filter:var(--glass-blur, blur(8px));
    display:grid; place-items:center;
    margin-bottom:16px;
  }
  .icon-wrapper i{
    font-size:28px;
    color:var(--brand-from);
  }

  .title{
    font-size:18px;
    font-weight:700;
    margin:0 0 12px;
  }

  .text{
    font-size:14px;
    color:#4a5568;
    line-height:1.45;
  }
  </style>
