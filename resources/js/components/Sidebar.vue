<template>
  <aside :class="['sidebar', { open: isSidebarOpen }]">
    <!-- Sidebar Toggle Button -->
    <button class="sidebar-toggle" @click="$emit('toggleSidebar')">
      <i v-if="isSidebarOpen" class="pi pi-angle-double-left toggle-icon"></i>
      <i v-else class="pi pi-angle-double-right toggle-icon"></i>
    </button>

    <!-- Sidebar Links -->
    <ul>
      <li v-for="(link, index) in links" :key="index">
        <router-link :to="link.to" active-class="active-link">
          <i :class="['pi', link.icon, 'menu-icon']"></i>
          <span v-if="isSidebarOpen">{{ link.label }}</span>
        </router-link>
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
        // { label: "Доступ", to: "/access", icon: "pi-home" },
        { label: "Сотрудник", to: "/employees", icon: "pi-users" },
        // { label: "Контрагенты", to: "/clients", icon: "pi-users" },
        { label: "Справочник", to: "/product-cards", icon: "pi-box" },
        { label: "Товары", to: "/receive", icon: "pi-shopping-cart" },
        // { label: "История операции", to: "/operation-history", icon: "pi-file" },
        //{ label: "Аккаунты", to: "/accounts", icon: "pi-id-card" },
        { label: "Отчеты", to: "/reports", icon: "pi-chart-line" },
        // { label: "Отчет по задолженности", to: "/client-debts", icon: "pi-money-bill" },
        { label: "Карточки", to: "/test", icon: "test" },

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
  display: flex;
  align-items: center;
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
  display: flex;
  align-items: center;
}
.menu-icon {
  font-size: 18px;
  margin-right: 10px;
}
.toggle-icon {
  font-size: 18px;
}
</style>
