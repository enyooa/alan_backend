<template>
   <div class="address-form">
     <h2 class="form-title">Создать адрес</h2>
     <form @submit.prevent="submitAddress">
       <div class="form-group">
         <label>Выберите пользователя</label>
         <select v-model="selectedUserId" class="form-select" required>
           <option disabled value="">— Выберите пользователя —</option>
           <option v-for="user in users" :key="user.id" :value="user.id">
             {{ user.first_name }} {{ user.last_name }}
           </option>
         </select>
       </div>
       <div class="form-group">
         <label>Название адреса</label>
         <input
           type="text"
           v-model="addressName"
           placeholder="Введите название адреса"
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
   name: "AddressPage",
   data() {
     return {
       users: [],
       selectedUserId: "",
       addressName: "",
       loading: false,
     };
   },
   async created() {
     await this.fetchUsers();
   },
   methods: {
     async fetchUsers() {
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           alert("Отсутствует токен. Пожалуйста, войдите в систему.");
           return;
         }
         const response = await axios.get("/api/users", {
           headers: { Authorization: `Bearer ${token}` },
         });
         this.users = response.data;
       } catch (error) {
         console.error("Ошибка при загрузке пользователей:", error);
       }
     },
     async submitAddress() {
       if (!this.selectedUserId || !this.addressName) {
         alert("Пожалуйста, заполните все поля");
         return;
       }
       this.loading = true;
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           alert("Отсутствует токен. Пожалуйста, войдите в систему.");
           return;
         }
         // Adjust the endpoint if needed. Example: /api/address/{userId}
         const response = await axios.post(
           `/api/storeAdress/${this.selectedUserId}`,
           { name: this.addressName },
           { headers: { Authorization: `Bearer ${token}` } }
         );
         alert("Адрес успешно создан!");
         this.addressName = "";
         this.selectedUserId = "";
       } catch (error) {
         console.error("Ошибка при создании адреса:", error);
         alert("Ошибка при создании адреса.");
       } finally {
         this.loading = false;
       }
     },
   },
 };
 </script>
 
 <style scoped>
 .address-form {
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
 .form-group input,
 .form-group select {
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
 