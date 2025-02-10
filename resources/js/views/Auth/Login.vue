<template>
   <div class="auth-container">
     <h1>🔐 Вход</h1>
     <form @submit.prevent="login">
       <input v-model="whatsappNumber" type="text" placeholder="📱 WhatsApp номер" required />
       <input v-model="password" type="password" placeholder="🔑 Пароль" required />
       <button type="submit">Войти</button>
     </form>
     <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
     <router-link to="/forgot-password">Забыли пароль?</router-link>
     <router-link to="/register">Создать аккаунт</router-link>
   </div>
</template>

<script>
// Login.vue
import axios from "axios";

export default {
  data() {
    return {
      whatsappNumber: "",
      password: "",
      errorMessage: "",
    };
  },
  methods: {
    async login() {
      try {
        const response = await axios.post("/api/login", {
          whatsapp_number: this.whatsappNumber,
          password: this.password,
        });

        console.log("✅ API Response:", response.data);

        if (response.data.token) {
          localStorage.setItem("token", response.data.token);
          localStorage.setItem("user", JSON.stringify(response.data));

          // Set global auth header
          axios.defaults.headers.common["Authorization"] = `Bearer ${response.data.token}`;

          // Redirect to dashboard after login
          this.$router.push("/dashboard").then(() => {
            window.location.reload();
          });
        } else {
          this.errorMessage = "Ошибка входа! Проверьте данные.";
        }
      } catch (error) {
        console.error("❌ Ошибка входа:", error.response ? error.response.data : error);
        this.errorMessage = "Ошибка входа! Проверьте данные.";
      }
    },
  },
};

</script>

<style scoped>
.auth-container {
  max-width: 400px;
  margin: auto;
  text-align: center;
}
input, button {
  display: block;
  width: 100%;
  padding: 10px;
  margin: 10px 0;
}
button {
  background-color: #007bff;
  color: white;
  border: none;
  cursor: pointer;
}
.error {
  color: red;
}
</style>
