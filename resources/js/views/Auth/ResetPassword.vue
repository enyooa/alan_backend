<template>
   <div class="auth-container">
     <h1>🔄 Новый пароль</h1>
     <form @submit.prevent="resetPassword">
       <input v-model="code" type="text" placeholder="📩 Код подтверждения" required />
       <input v-model="newPassword" type="password" placeholder="🔑 Новый пароль" required />
       <input v-model="confirmPassword" type="password" placeholder="🔑 Подтвердите пароль" required />
       <button type="submit">Сохранить</button>
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
       code: "",
       newPassword: "",
       confirmPassword: "",
       errorMessage: "",
     };
   },
   methods: {
     async resetPassword() {
       if (this.newPassword !== this.confirmPassword) {
         this.errorMessage = "Пароли не совпадают!";
         return;
       }
 
       try {
         await axios.post("/api/password/reset", {
           code: this.code,
           password: this.newPassword,
         });
 
         alert("✅ Пароль успешно изменён!");
         this.$router.push("/login");
       } catch (error) {
         this.errorMessage = "Ошибка сброса пароля!";
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
   background-color: #4caf50;
   color: white;
   border: none;
   cursor: pointer;
 }
 .error {
   color: red;
 }
 </style>
 