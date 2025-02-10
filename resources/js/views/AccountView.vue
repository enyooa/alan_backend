<template>
   <div class="account-container">
     <h1 class="heading">👤 Профиль</h1>

     <div v-if="loading">
       <p>Загрузка...</p>
     </div>

     <div v-else-if="user" class="profile-card">
       <!-- Profile Photo Upload -->
       <div class="profile-picture">
         <label for="upload-photo">
           <img :src="user.photo || defaultPhoto" alt="Profile" class="profile-img"/>
         </label>
         <input id="upload-photo" type="file" @change="uploadPhoto" />
       </div>

       <h2 class="title">{{ fullName }}</h2>
       <p class="subtitle"><strong>📱 WhatsApp:</strong> {{ user.whatsapp_number }}</p>
       <p class="subtitle"><strong>📍 Адрес:</strong> {{ user.address || "Не указан" }}</p>

       <!-- Logout -->
       <button class="logout-btn" @click="logout">🚪 Выйти</button>
     </div>

     <div v-else>
       <p class="error-message">⚠️ Ошибка загрузки данных.</p>
     </div>
   </div>
</template>

<script>
import axios from "axios";

export default {
  data() {
    return {
      user: null,
      loading: true,
      defaultPhoto: "/default-profile.png",
    };
  },
  computed: {
    fullName() {
      return this.user
        ? `${this.user.first_name || ""} ${this.user.surname || ""} ${this.user.last_name || ""}`.trim()
        : "Нет данных";
    },
  },
  async created() {
    await this.fetchUserData();
  },
  methods: {
    async fetchUserData() {
      try {
        const response = await axios.get("/api/user");
        this.user = response.data;
        this.loading = false;
      } catch (error) {
        console.error("Ошибка загрузки пользователя", error);
        this.loading = false;
      }
    },
    async logout() {
      try {
        await axios.post("/api/logout");
        localStorage.removeItem("token");
        delete axios.defaults.headers.common["Authorization"];
        this.$router.push("/login");
      } catch (error) {
        console.error("Ошибка выхода", error);
      }
    },
  },
};
</script>

<style scoped>
.account-container {
  max-width: 450px;
  margin: auto;
  padding: 20px;
  text-align: center;
  background: white;
  border-radius: 10px;
  box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
}
.profile-picture {
  margin-bottom: 15px;
}
.profile-img {
  width: 120px;
  height: 120px;
  border-radius: 50%;
  object-fit: cover;
  border: 3px solid #0288d1;
}
.logout-btn {
  padding: 12px;
  background: #ff4d4d;
  color: white;
  border: none;
  cursor: pointer;
}
.logout-btn:hover {
  background: #cc0000;
}
</style>
