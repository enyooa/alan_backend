<template>
   <div class="auth-container">
     <h1>📝 Регистрация</h1>
     <form @submit.prevent="register">
       <input v-model="firstName" type="text" placeholder="👤 Имя" required />
       <input v-model="lastName" type="text" placeholder="👤 Фамилия" required />
       <input v-model="whatsappNumber" type="text" placeholder="📱 WhatsApp номер" required />
       <input v-model="password" type="password" placeholder="🔑 Пароль" required />
       <input v-model="passwordConfirm" type="password" placeholder="🔑 Подтвердите пароль" required />
       <button type="submit">Зарегистрироваться</button>
     </form>
     <p v-if="errorMessage" class="error">{{ errorMessage }}</p>
     <router-link to="/login">Уже есть аккаунт? Войти</router-link>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   data() {
     return {
       firstName: "",
       lastName: "",
       whatsappNumber: "",
       password: "",
       passwordConfirm: "",
       errorMessage: "",
     };
   },
   methods: {
     async register() {
       if (this.password !== this.passwordConfirm) {
         this.errorMessage = "Пароли не совпадают!";
         return;
       }
 
       try {
         const response = await axios.post("/api/register", {
           first_name: this.firstName,
           last_name: this.lastName,
           whatsapp_number: this.whatsappNumber,
           password: this.password,
         });
 
         if (response.data.success) {
           localStorage.setItem("token", response.data.token);
           axios.defaults.headers.common["Authorization"] = `Bearer ${response.data.token}`;
 
           this.$router.push("/account").then(() => {
             window.location.reload(); // 🔄 Force Refresh
           });
         }
       } catch (error) {
         this.errorMessage = "Ошибка регистрации! Проверьте данные.";
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
   background-color: #28a745;
   color: white;
   border: none;
   cursor: pointer;
 }
 .error {
   color: red;
 }
 </style>
 