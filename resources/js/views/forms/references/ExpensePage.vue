<template>
   <div class="expense-form">
     <h2 class="form-title">Создать расход</h2>
     <form @submit.prevent="submitExpense">
       <div class="form-group">
         <label>Наименование расхода</label>
         <input
           type="text"
           v-model="expenseName"
           placeholder="Введите наименование расхода (например, Транспорт)"
           required
         />
       </div>
       <div v-if="errors.name" class="error-message">
         {{ errors.name.join(', ') }}
       </div>
 
       <div class="form-group">
         <label>Сумма (необязательно)</label>
         <input
           type="number"
           step="0.01"
           v-model="amount"
           placeholder="Введите сумму (можно пустым)"
         />
       </div>
       <div v-if="errors.amount" class="error-message">
         {{ errors.amount.join(', ') }}
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
   name: "ExpenseFormPage",
   data() {
     return {
       expenseName: "",
       amount: "",       // optional
       loading: false,
       errors: {}
     };
   },
   methods: {
     async submitExpense() {
       this.loading = true;
       this.errors = {};
 
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           alert("Отсутствует токен. Пожалуйста, войдите в систему.");
           return;
         }
 
         // POST to your chosen endpoint, e.g. /api/create_expense
         // or /api/references/expense, whichever you use
         const response = await axios.post(
           "/api/create_expense",
           {
             name: this.expenseName,
             amount: this.amount // can be blank
           },
           {
             headers: {
               Authorization: `Bearer ${token}`,
               "Content-Type": "application/json"
             }
           }
         );
 
         alert("Расход успешно создан!");
 
         // Clear fields
         this.expenseName = "";
         this.amount = "";
 
         // Notify parent
         this.$emit("saved", response.data);
       } catch (error) {
         console.error("Ошибка при создании расхода:", error);
         if (error.response && error.response.data && error.response.data.errors) {
           // If Laravel returns validation errors
           this.errors = error.response.data.errors;
         } else {
           alert("Ошибка при создании расхода.");
         }
       } finally {
         this.loading = false;
       }
     }
   }
 };
 </script>
 
 <style scoped>
 .expense-form {
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
   margin-left: 10px;
 }
 .error-message {
   color: red;
   margin-top: 5px;
 }
 </style>
 