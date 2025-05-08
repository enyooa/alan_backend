<template>
    <div class="layout">
      <!-- sidebar -->
      <Sidebar :isSidebarOpen="true" @openPanel="openPanel" />

      <!-- sliding panel -->
      <DocumentPanel
        :open="panelOpen"
        :items="activeItems"
        @navigate="goto"
        @close="panelOpen = false"
      />

      <!-- main area -->
      <div class="page">
        <Header />
        <router-view />
      </div>
    </div>
  </template>

  <script>
  import Sidebar       from '../components/Sidebar.vue';
  import Header        from '../components/Header.vue';
  import DocumentPanel from '../components/DocumentPanel.vue';
  import RolesModal    from '../views/forms/products/RolesModal.vue'

  export default {
    name: 'MainLayout',
    components: { Sidebar, Header, DocumentPanel,RolesModal  },

    data() {
      return {
        /* -------- all panel definitions in one place ------------------ */
        panelSets: {
          /* 1) документы */
          docs: [
            { label:'Приходный ордер',     icon:'pi-paperclip',     color:'#7EBF52', to:'/income-order' },
            { label:'Расходный ордер',     icon:'pi-paperclip',     color:'#6CB8C6', to:'/outcome-order' },
            { label:'Заявки',              icon:'pi-envelope',      color:'#F4AA1C', to:'/requests' },
            { label:'Накладная',           icon:'pi-file',          color:'#41C9BD', to:'/invoice' },
            { label:'Поступление',         icon:'pi-shopping-cart', color:'#4A26E2', to:'/receive' },
            { label:'Списание',            icon:'pi-shopping-cart', color:'#6D6DDE', to:'/write-off' },
            { label:'Ценовые предложения', icon:'pi-dollar',        color:'#00CCB1', to:'/price-offers' },
            { label:'Продажи',             icon:'pi-shopping-cart', color:'#D143F2', to:'/sales' },
          ],

          /* 2) справочник */
          directory: [
            { label:'Открыть справочник', icon:'pi-info-circle',    color:'#9C9C9C', to:'/product-cards' },
            { label:'Поставщик',          icon:'pi-dollar',         color:'#86C64E', to:'/provider' },
            { label:'Расход',             icon:'pi-dollar',         color:'#B30000', to:'/expense' },
            { label:'Карточка товара',    icon:'pi-shopping-cart',  color:'#008EA2', to:'/product-card' },
            { label:'Адрес',              icon:'pi-home',           color:'#7A7DFF', to:'/address' },
            { label:'Счет',              icon:'pi-dollar',           color:'#F4AA1C', to:'/cash' },

        ],

          /* 3) отчёты */
          reports: [
            { label:'Отчёт по кассе',   icon:'pi-clipboard',     color:'#86C64E', to:'/cash-report' },
            { label:'Отчёт по долгам',  icon:'pi-dollar',        color:'#B30000', to:'/debt-report' },
            { label:'Отчёт по продажам',icon:'pi-shopping-cart', color:'#008EA2', to:'/sales-report' },
            { label:'Отчёт по складу',  icon:'pi-home',          color:'#7A7DFF', to:'/warehouse-report' },
          ],

          /* 4) сотрудники */
          workers: [
            { label:'Список сотрудников',  icon:'pi-clipboard', color:'#86C64E', to:'/employees' },
            { label:'Управление ролями',   icon:'pi-lock',      color:'#B30000', to:'/employees-old' },
          ],
        },

        /* runtime state */
        panelOpen : false,
        panelType : null,   // current key in panelSets
      };
    },

    computed:{
      activeItems() {
        return this.panelType ? this.panelSets[this.panelType] : [];
      },
    },

    methods:{
      openPanel(type){
        this.panelType = type;
        this.panelOpen = true;
      },
      goto(path){
        this.panelOpen = false;
        if (this.$route.path !== path) this.$router.push(path);
      },
    },
  };
  </script>

  <style>
  body    { background: #fff; }
  .layout { display:flex; }

  .page{
    flex:1; min-height:100vh;
    body { background:#fff; }        /* чистый белый фон */

  }
  </style>
