<template>
    <div class="layout">
      <!-- sidebar ------------------------------------------------------- -->
      <Sidebar
        :isSidebarOpen="true"
        @openPanel="panelOpen = true"
      />

      <!-- sliding document squares ------------------------------------- -->
      <DocumentPanel
        :open="panelOpen"
        :items="panelItems"
        @navigate="goto"
        @close="panelOpen = false"
      />

      <!-- child routes -------------------------------------------------- -->
      <div class="page">
        <Header />
        <router-view/>
      </div>
    </div>
  </template>

  <script>
  import Sidebar       from "../components/Sidebar.vue";
  import Header        from "../components/Header.vue";
  import DocumentPanel from "../components/DocumentPanel.vue";

  export default {
    name: "MainLayout",
    components: { Sidebar, Header, DocumentPanel },
    data() {
      return {
        panelOpen: false,
        panelItems: [
          { label:"Приходный ордер",     icon:"pi-paperclip",     color:"#7EBF52", to:"/income-order" },
          { label:"Расходный ордер",     icon:"pi-paperclip",     color:"#6CB8C6", to:"/outcome-order" },
          { label:"Заявки",              icon:"pi-envelope",      color:"#F4AA1C", to:"/requests" },
          { label:"Накладная",           icon:"pi-file",          color:"#41C9BD", to:"/invoice" },
          { label:"Поступление",         icon:"pi-shopping-cart", color:"#4A26E2", to:"/receive" },
          { label:"Списание",            icon:"pi-shopping-cart", color:"#6D6DDE", to:"/write-off" },
          { label:"Ценовые предложения", icon:"pi-dollar",        color:"#00CCB1", to:"/quotes" },
          { label:"Продажи",             icon:"pi-shopping-cart", color:"#D143F2", to:"/sales" },
        ],
      };
    },
    methods:{
      goto(path){
        this.panelOpen = false;
        if (this.$route.path !== path) this.$router.push(path);
      },
    },
  };
  </script>

  <style scoped>
  .layout { display:flex }
  .page   { flex:1; min-height:100vh }
</style>
