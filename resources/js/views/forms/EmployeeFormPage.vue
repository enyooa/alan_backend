<template>
   <div class="employee-form-container">
     <h2 class="form-title">Создать сотрудника</h2>
     <form @submit.prevent="submitForm">
       <div class="form-group">
         <label>Имя</label>
         <input type="text" v-model="form.firstName" required />
       </div>
       <div class="form-group">
         <label>Фамилия</label>
         <input type="text" v-model="form.lastName" />
       </div>
       <div class="form-group">
         <label>Отчество</label>
         <input type="text" v-model="form.surname" />
       </div>
       <div class="form-group">
         <label>Телефон (WhatsApp)</label>
         <input type="text" v-model="form.whatsappNumber" required />
       </div>
       <div class="form-group">
         <label>Роль</label>
         <select v-model="form.role" required>
           <option value="">Выберите роль</option>
           <option value="admin">Администратор</option>
           <option value="client">Клиент</option>
           <option value="cashbox">Касса</option>
           <option value="packer">Упаковщик</option>
           <option value="storager">Кладовщик</option>
           <option value="courier">Курьер</option>
         </select>
       </div>
       <div class="form-group">
         <label>Пароль</label>
         <input type="password" v-model="form.password" required />
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
 import axios from 'axios';
 export default {
   name: 'EmployeeFormPage',
   data() {
     return {
       form: {
         firstName: '',
         lastName: '',
         surname: '',
         whatsappNumber: '',
         role: '',
         password: '',
       },
       loading: false,
     };
   },
   methods: {
     async submitForm() {
       // Basic form validation
       if (!this.form.firstName || !this.form.whatsappNumber || !this.form.role || !this.form.password) {
         alert("Пожалуйста, заполните все обязательные поля.");
         return;
       }
       this.loading = true;
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           alert("Токен отсутствует. Войдите в систему.");
           return;
         }
         // Replace the endpoint URL with your API endpoint for creating an employee.
         const response = await axios.post(
           "/api/employees",
           {
             first_name: this.form.firstName,
             last_name: this.form.lastName,
             surname: this.form.surname,
             whatsapp_number: this.form.whatsappNumber,
             role: this.form.role,
             password: this.form.password,
           },
           {
             headers: { Authorization: `Bearer ${token}` },
           }
         );
         // Emit the "saved" event with the new employee record from the backend
         this.$emit('saved', response.data);
       } catch (error) {
         console.error("Ошибка при создании сотрудника:", error);
         alert("Ошибка при создании сотрудника.");
       } finally {
         this.loading = false;
       }
     },
   },
 };
 </script>
 
 <style scoped>
 .employee-form-container {
   max-width: 500px;
   margin: 0 auto;
   padding: 20px;
 }
 .form-title {
   text-align: center;
   color: #0288d1;
   margin-bottom: 20px;
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
 }
 .form-actions {
   display: flex;
   justify-content: space-between;
   gap: 10px;
   margin-top: 20px;
 }
 .submit-btn {
   background-color: #0288d1;
   color: #fff;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   flex: 1;
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
 