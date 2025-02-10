<template>
   <header class="header">
      <h1>Админ Панель</h1>
      <div class="user-info">
         Добро пожаловать, <span class="user-name">{{ user ? user.first_name : "Пользователь" }}!</span>
         <button class="logout-btn" @click="logout">🚪 Выйти</button>
      </div>
   </header>
</template>

<script>
// Header.vue (Logout functionality)
import axios from "axios";

export default {
  data() {
    return {
      user: null,
    };
  },
  async created() {
    await this.fetchUserData();
  },
  methods: {
    async fetchUserData() {
      try {
        const response = await axios.get("/api/user");
        this.user = response.data;
      } catch (error) {
        console.error("Ошибка загрузки пользователя", error);
      }
    },
    // In your logout method (Header.vue)
async logout() {
   try {
      const token = localStorage.getItem("token");
      console.log("here is "+token);
      if (token) {
         axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
      }

      await axios.post("/api/logout"); // Call logout endpoint

      // Remove token and user data
      localStorage.removeItem("token");
      localStorage.removeItem("user");

      // Clear the Authorization header
      delete axios.defaults.headers.common["Authorization"];

      // Redirect to login page
      this.$router.replace("/login").then(() => window.location.reload());
   } catch (error) {
      console.error("❌ Ошибка выхода", error);
   }
}
,
  },
};

</script>

<style scoped>
.header {
   display: flex;
   justify-content: space-between;
   align-items: center;
   padding: 15px 20px;
   background-color: #0288d1;
   color: white;
   box-shadow: 0px 2px 5px rgba(0, 0, 0, 0.1);
}
.header h1 {
   font-size: 24px;
   font-weight: 600;
   margin: 0;
}
.user-info {
   display: flex;
   align-items: center;
   gap: 10px;
}
.user-name {
   font-weight: bold;
}
.logout-btn {
   padding: 10px 15px;
   background-color: #b00020;
   color: white;
   border: none;
   border-radius: 5px;
   font-size: 14px;
   cursor: pointer;
}
.logout-btn:hover {
   background-color: #b71c1c;
}
</style>
