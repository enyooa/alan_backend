<template>
   <div class="dashboard-container">
      <!-- Sidebar -->
      <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />

      <!-- Main Content -->
      <div class="main-content">
         <!-- Header -->
         <Header />

         <!-- Page Content -->
         <main class="content">
            <form @submit.prevent="submitForm" class="account-form">
               <h2>Регистрация нового аккаунта</h2>

               <label for="firstName">Имя</label>
               <input id="firstName" type="text" v-model="formData.firstName" placeholder="Введите имя" required />

               <label for="lastName">Фамилия</label>
               <input id="lastName" type="text" v-model="formData.lastName" placeholder="Введите фамилию" required />

               <label for="whatsappNumber">WhatsApp номер</label>
               <input id="whatsappNumber" type="text" v-model="formData.whatsappNumber" placeholder="Введите WhatsApp номер" required />

               <label for="password">Пароль</label>
               <input id="password" type="password" v-model="formData.password" placeholder="Введите пароль" required />

               <label for="role">Роль</label>
               <select id="role" v-model="formData.role" required>
                  <option value="">Выберите роль</option>
                  <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
               </select>

               <button type="submit" class="submit-btn" :disabled="isLoading">
                  <span v-if="isLoading">⏳ Создание...</span>
                  <span v-else>Создать</span>
               </button>

               <!-- Show success message -->
               <p v-if="successMessage" class="success-message">{{ successMessage }}</p>
               <!-- Show error message -->
               <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
            </form>
         </main>
      </div>
   </div>
</template>

<script>
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue";
import axios from "axios";

export default {
   components: { Sidebar, Header },
   data() {
      return {
         isSidebarOpen: true,
         isLoading: false,
         successMessage: "",
         errorMessage: "",
         formData: {
            firstName: "",
            lastName: "",
            whatsappNumber: "",
            password: "",
            role: "",
         },
         roles: ["Склад", "Фасовка", "Кассир", "Курьер"],
      };
   },
   methods: {
      toggleSidebar() {
         this.isSidebarOpen = !this.isSidebarOpen;
      },
      async submitForm() {
         if (!this.formData.firstName || !this.formData.lastName || !this.formData.whatsappNumber || !this.formData.password || !this.formData.role) {
            this.errorMessage = "❌ Пожалуйста, заполните все поля";
            return;
         }

         this.isLoading = true;
         this.successMessage = "";
         this.errorMessage = "";

         try {
            await axios.post("/api/create-account", this.formData);
            this.successMessage = "✅ Аккаунт успешно создан!";
            this.formData = { firstName: "", lastName: "", whatsappNumber: "", password: "", role: "" };
         } catch (error) {
            this.errorMessage = "❌ Ошибка при создании аккаунта. Попробуйте снова.";
            console.error("Ошибка при создании аккаунта", error);
         } finally {
            this.isLoading = false;
         }
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
   flex: 1;
   display: flex;
   align-items: center;
   justify-content: center;
   padding: 20px;
}

.account-form {
   background: white;
   padding: 20px;
   border-radius: 8px;
   box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
   width: 100%;
   max-width: 400px;
}

.account-form h2 {
   color: #0288d1;
   margin-bottom: 15px;
   text-align: center;
}

.account-form label {
   display: block;
   margin: 10px 0 5px;
   font-weight: bold;
}

.account-form input,
.account-form select {
   width: 100%;
   padding: 10px;
   margin-bottom: 15px;
   border: 1px solid #ddd;
   border-radius: 5px;
}

.submit-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   width: 100%;
}

.submit-btn:hover {
   background-color: #026ca0;
}

.submit-btn:disabled {
   background-color: gray;
   cursor: not-allowed;
}

.success-message {
   color: green;
   text-align: center;
   margin-top: 10px;
   font-weight: bold;
}

.error-message {
   color: red;
   text-align: center;
   margin-top: 10px;
   font-weight: bold;
}
</style>
