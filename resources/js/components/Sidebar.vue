<template>
    <aside :class="['sidebar', { closed: !isSidebarOpen }]">
      <ul class="nav">
        <li v-for="link in links" :key="link.key">
          <!-- panel-trigger buttons -->
          <button
            v-if="link.openPanel"
            class="nav-link"
            @click="$emit('openPanel', link.panel)"
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
    name: 'Sidebar',
    props: { isSidebarOpen: { type: Boolean, default: true } },
    data() {
      return {
        links: [
          /* panel triggers */
          {
            key: 'create',
            label: 'Создание\nдокумента',
            icon: 'pi-plus',
            openPanel: true,
            panel: 'docs',
          },
          {
            key: 'directory',
            label: 'Справочник',
            icon: 'pi-info-circle',
            openPanel: true,
            panel: 'directory',
          },
          {
            key: 'reports',
            label: 'Отчёты',
            icon: 'pi-file',
            openPanel: true,
            panel: 'reports',
          },
          {
            key: 'workers',
            label: 'Сотрудники',
            icon: 'pi-users',
            openPanel: true,
            panel: 'workers',
          },

          /* ordinary links (still navigate immediately) */
          { key: 'tariff',  label: 'Тарифный план', icon: 'pi-money-bill',    to: '/tariff-plan' },
          { key: 'receive', label: 'Товары',        icon: 'pi-shopping-cart', to: '/receive' },
        ],
      };
    },
  };
  </script>

  <style scoped>
  .sidebar{
    width:80px; min-height:100vh; flex-shrink:0;
    display:flex; flex-direction:column; align-items:center; padding-top:24px;
    color:#fff;
    background:linear-gradient(var(--brand-from, #03b4de), var(--brand-to, #6ec7db));
    transition:transform .3s;
  }
  .sidebar.closed{ transform:translateX(-100%); }

  .nav{ list-style:none; margin:0; padding:0; width:100%; }
  .nav li{ display:flex; justify-content:center; width:100%; }

  .nav-link{
    display:flex; flex-direction:column; align-items:center; gap:6px;
    padding:12px 0; font-size:13px; line-height:1.1;
    color:#fff; text-decoration:none; transition:filter .2s;
  }
  .nav-link:hover,.active-link{ filter:brightness(1.25); }

  .icon-wrapper{
    --sz:48px; width:var(--sz); height:var(--sz); border-radius:50%;
    display:grid; place-items:center; background:rgba(255,255,255,.15);
    border:2px solid #b7ff4e; transition:transform .2s;
  }
  .nav-link:hover .icon-wrapper{ transform:scale(1.08); }
  .icon-wrapper i{ font-size:22px; color:#b7ff4e; }

  .label{ white-space:pre-line; text-align:center; }

  .separator{
    width:40px; height:1px; margin:26px auto 0;
    border-bottom:1px dashed rgba(0,162,255,.6);
  }
  </style>
