<template>
   <div class="auth-container">
     <h1>🔑 Восстановление пароля</h1>
     <form @submit.prevent="sendResetLink">
       <input v-model="whatsappNumber" type="text" placeholder="📱 Введите ваш WhatsApp номер" required />
       <button type="submit">Отправить код</button>
     </form>
     <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
     <router-link to="/login">Назад ко входу</router-link>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   data() {
     return {
       whatsappNumber: "",
       errorMessage: "",
     };
   },
   methods: {
     async sendResetLink() {
       try {
         await axios.post("/api/password/forgot", { whatsapp_number: this.whatsappNumber });
         alert("🔹 Код для сброса пароля отправлен на ваш WhatsApp!");
         this.$router.push("/reset-password");
       } catch (error) {
         this.errorMessage = "Ошибка! Проверьте номер.";
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
   background-color: #ff9800;
   color: white;
   border: none;
   cursor: pointer;
 }
 .error {
   color: red;
 }
 </style>
 