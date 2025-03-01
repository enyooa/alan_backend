<template>
   <div class="provider-form">
     <h2 class="form-title">Создать поставщика</h2>
     <form @submit.prevent="submitProvider">
       <div class="form-group">
         <label>Наименование поставщика</label>
         <input
           type="text"
           v-model="providerName"
           placeholder="Введите наименование поставщика"
           required
         />
       </div>
       <div class="form-actions">
         <button type="submit" class="submit-btn" :disabled="loading">
           {{ loading ? '⏳ Создание...' : 'Создать' }}
         </button>
         <button type="button" class="close-btn" @click="$emit('close')">
            Закрыть
          </button>
       </div>
     </form>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   name: "ProviderPage",
   data() {
     return {
       providerName: "",
       loading: false,
     };
   },
   methods: {
     async submitProvider() {
       if (!this.providerName) {
         alert("Введите наименование поставщика");
         return;
       }
       this.loading = true;
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           alert("Отсутствует токен. Пожалуйста, войдите в систему.");
           return;
         }
         // Adjust the endpoint as needed
         const response = await axios.post(
           "/api/create_providers",
           { name: this.providerName },
           { headers: { Authorization: `Bearer ${token}` } }
         );
         alert("Поставщик успешно создан!");
         this.providerName = "";
       } catch (error) {
         console.error("Ошибка при создании поставщика:", error);
         alert("Ошибка при создании поставщика.");
       } finally {
         this.loading = false;
       }
     },
   },
 };
 </script>
 
 <style scoped>
 .provider-form {
   max-width: 500px;
   margin: 0 auto;
   padding: 20px;
   background: #ffffff;
   border-radius: 10px;
   box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
 }
 .form-title {
   text-align: center;
   color: #0288d1;
   margin-bottom: 20px;
   font-size: 1.5rem;
 }
 .form-group {
   margin-bottom: 15px;
   display: flex;
   flex-direction: column;
 }
 .form-group label {
   margin-bottom: 5px;
   font-weight: bold;
   color: #555;
 }
 .form-group input {
   padding: 10px;
   border: 1px solid #ddd;
   border-radius: 5px;
   font-size: 14px;
 }
 .form-actions {
   display: flex;
   justify-content: center;
   margin-top: 20px;
 }
 .submit-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   font-size: 14px;
 }
 .submit-btn:hover {
   background-color: #026ca0;
 }
 .close-btn {
   background-color: #f44336;
   color: #fff;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   flex: 1;
 }
 </style>
 