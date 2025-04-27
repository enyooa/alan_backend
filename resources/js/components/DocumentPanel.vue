<template>
    <transition name="slide">
      <aside v-if="open" class="doc-panel" @mouseleave="$emit('close')">
        <ul>
          <li v-for="item in items" :key="item.to">
            <button class="doc-btn"
                    :style="{ '--clr': item.color }"
                    @click="$emit('navigate', item.to)">
              <i :class="['pi', item.icon]" />
              <span>{{ item.label }}</span>
            </button>
          </li>
        </ul>
      </aside>
    </transition>
  </template>

  <script>
  export default {
    name: "DocumentPanel",
    props: { open: Boolean, items: Array },
  };
  </script>

  <style scoped>
  .doc-panel{
    position:fixed;top:0;left:0;width:240px;height:100vh;z-index:2000;
    padding:60px 0 0;background:linear-gradient(180deg,#fff 0%,#e7eff6 100%);
    box-shadow:2px 0 6px rgba(0,0,0,.12);
  }
  ul{margin:0;padding:0 24px;list-style:none}
  li+li{margin-top:18px}

  /* square button */
  .doc-btn{
    all:unset;display:flex;align-items:center;gap:14px;width:100%;
    font:500 15px/1.2 Roboto,sans-serif;border-radius:12px;padding:8px 12px;
    cursor:pointer;transition:background .15s;
  }
  .doc-btn:hover{background:rgba(0,0,0,.05)}
  .doc-btn i{
    width:36px;height:36px;border-radius:10px;display:grid;place-items:center;
    font-size:18px;color:#fff;background:var(--clr,#999);
    box-shadow:0 2px 4px rgba(0,0,0,.15);
  }

  /* slide in/out */
  .slide-enter-active,.slide-leave-active{transition:transform .25s ease}
  .slide-enter,.slide-leave-to{transform:translateX(-100%)}
  </style>
