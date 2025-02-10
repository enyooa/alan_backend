<template>
   <aside :class="['sidebar', { open: isSidebarOpen }]">
      <button class="sidebar-toggle" @click="$emit('toggleSidebar')">
         <span v-if="isSidebarOpen">❌ Закрыть</span>
         <span v-else>☰ Меню</span>
      </button>
      <ul>
         <li v-for="(link, index) in links" :key="index">
            <router-link :to="link.to" active-class="active-link">{{ link.label }}</router-link>
         </li>
      </ul>
   </aside>
</template>

<script>
export default {
   props: {
      isSidebarOpen: Boolean,
   },
   data() {
      return {
         links: [
            { label: "Доступ", to: "/access" },
            { label: "Создать аккаунт", to: "/create" },
            { label: "Сотрудники", to: "/employees" },
            { label: "Контрагенты", to: "/clients" },
            { label: "Карточки товаров", to: "/product-cards" },
            { label: "Приходование товаров", to: "/receive" },
            { label: "Справочник", to: "/operation-history" },

            { label: "Аккаунты", to: "/accounts" },

            // { label: "Поступление ТМЗ", to: "/inventory" },
            { label: "Отчет по продажам", to: "/sales-report" },
            { label: "Отчет по задолженности клиентов", to: "/client-debts" },
            // { label: "Отчет по выручке", to: "/revenue-report" },
            // { label: "Отчет по расходам", to: "/expense-report" },
            // { label: "Отчет по прибыли", to: "/profit-report" },
            // { label: "Отчет по кассе", to: "/cash-report" },
            // { label: "Отчет по складу", to: "/warehouse-report" },
         ],
      };
   },
};
</script>

<style scoped>
.sidebar {
   background-color: #0288d1;
   color: white;
   width: 250px;
   transition: transform 0.3s ease-in-out;
   transform: translateX(0);
   padding: 20px;
   box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
}
.sidebar.closed {
   transform: translateX(-100%);
}
.sidebar ul {
   list-style-type: none;
   padding: 0;
   margin: 0;
}
.sidebar li {
   margin: 15px 0;
}
.sidebar a {
   color: white;
   text-decoration: none;
   font-size: 16px;
   display: block;
   padding: 10px;
   border-radius: 5px;
   transition: background-color 0.3s;
}
.sidebar a:hover {
   background-color: rgba(255, 255, 255, 0.2);
}
.sidebar-toggle {
   background: none;
   border: none;
   color: white;
   font-size: 16px;
   cursor: pointer;
   margin-bottom: 20px;
}
</style>
