<template>
    <aside :class="['sidebar', { closed: !isSidebarOpen }]">
      <ul class="nav">
        <li v-for="link in links" :key="link.key">
          <!-- green “+” opens the animated panel -->
          <button
            v-if="link.openPanel"
            class="nav-link"
            @click="$emit('openPanel')"
          >
            <span class="icon-wrapper">
              <i :class="['pi', link.icon]" />
            </span>
            <span class="label" v-if="isSidebarOpen">{{ link.label }}</span>
          </button>

          <!-- normal route links -->
          <router-link
            v-else
            :to="link.to"
            class="nav-link"
            active-class="active-link"
          >
            <span class="icon-wrapper">
              <i :class="['pi', link.icon]" />
            </span>
            <span class="label" v-if="isSidebarOpen">{{ link.label }}</span>
          </router-link>
        </li>

        <li class="separator" aria-hidden="true" />
      </ul>
    </aside>
  </template>

  <script>
  export default {
    name: "Sidebar",
    props: { isSidebarOpen: Boolean },
    data() {
      return {
        links: [
          { key: "create",  label: "Создание\nдокумента", icon: "pi-plus", openPanel: true },

          { key: "cards",   label: "Справочник",    icon: "pi-info-circle", to: "/product-cards" },
          { key: "reports", label: "Отчёты",        icon: "pi-file",        to: "/reports" },
          { key: "workers", label: "Сотрудники",    icon: "pi-users",       to: "/employees" },
          { key: "tariff",  label: "Тарифный план", icon: "pi-money-bill",  to: "/tariff-plan" },
          { key: "receive", label: "Товары",        icon: "pi-shopping-cart", to: "/receive" },
        ],
      };
    },
  };
  </script>

  <style scoped>
  /* neon gradient bar ---------------------------------------------------- */
  .sidebar{
    --from:#03b4de; --to:#6ec7db;
    width:80px;height:100vh;
    background:linear-gradient(var(--from),var(--to));
    padding-top:24px;
    display:flex;flex-direction:column;align-items:center;
    color:#fff;transition:transform .3s;
  }
  .sidebar.closed{transform:translateX(-100%)}

  /* list ----------------------------------------------------------------- */
  .nav{list-style:none;margin:0;padding:0;width:100%}
  .nav li{display:flex;justify-content:center;width:100%}

  /* buttons / links ------------------------------------------------------ */
  .nav-link{
    display:flex;flex-direction:column;align-items:center;gap:6px;
    padding:12px 0;color:#fff;text-decoration:none;font-size:13px;
    line-height:1.1;transition:filter .2s;
  }
  .nav-link:hover,.active-link{filter:brightness(1.25)}

  /* round neon icon ------------------------------------------------------ */
  .icon-wrapper{
    --sz:48px;width:var(--sz);height:var(--sz);border-radius:50%;
    display:grid;place-items:center;background:rgba(255,255,255,.15);
    border:2px solid #b7ff4e;transition:transform .2s;
  }
  .nav-link:hover .icon-wrapper{transform:scale(1.08)}
  .icon-wrapper i{font-size:22px;color:#b7ff4e}

  /* label */
  .label{white-space:pre-line;text-align:center}

  /* dotted line ---------------------------------------------------------- */
  .separator{
    width:40px;height:1px;border-bottom:1px dashed rgba(0,162,255,.6);
    margin:26px auto 0;
  }
  </style>
