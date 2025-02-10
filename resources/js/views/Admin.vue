<template>
   <div class="dashboard-container">
      <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />

      <div class="main-content">
         <Header />

         <main class="content">
            <router-view />
         </main>
      </div>
   </div>
</template>

<script>
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue"; // Ensure the correct path

export default {
   components: {
      Sidebar,
      Header, // Ensure Header is correctly included
   },
   data() {
      return {
         isSidebarOpen: true,
      };
   },
   methods: {
      toggleSidebar() {
         this.isSidebarOpen = !this.isSidebarOpen;
      },
   },
};
</script>

<style scoped>
.dashboard-container {
   display: flex;
   min-height: 100vh;
}
.main-content {
   flex: 1;
   display: flex;
   flex-direction: column;
   background-color: #f5f5f5;
}
.content {
   flex-grow: 1;
   padding: 20px;
   background-color: #f5f5f5;
}
</style>
