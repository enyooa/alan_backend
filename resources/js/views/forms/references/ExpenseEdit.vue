<template>
   <div class="expense-edit">
     <h2 class="form-title">Редактировать расход</h2>
     <form @submit.prevent="updateExpense">
       <div class="form-group">
         <label>Наименование расхода</label>
         <input
           type="text"
           v-model="editableExpense.name"
           required
         />
       </div>
       <div class="form-group">
         <label>Сумма</label>
         <input
           type="number"
           step="0.01"
           v-model="editableExpense.amount"
           required
         />
       </div>
 
       <div class="form-actions">
         <button type="submit" class="submit-btn" :disabled="loading">
           {{ loading ? "⏳ Сохранение..." : "Сохранить" }}
         </button>
         <button
           type="button"
           class="close-btn"
           @click="$emit('close')"
         >
           Закрыть
         </button>
       </div>
     </form>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   name: "ExpenseEdit",
   props: {
     // 'operation' is the existing expense object from parent
     // containing fields like { id, name, amount, ... }
     operation: {
       type: Object,
       required: true
     }
   },
   data() {
     return {
       loading: false,
       editableExpense: {
         name: "",
         amount: 0
       }
     };
   },
   created() {
     // Copy data from the parent prop so we can edit locally
     this.editableExpense.name = this.operation.name || "";
     this.editableExpense.amount = this.operation.amount || 0;
   },
   methods: {
     async updateExpense() {
       this.loading = true;
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           alert("Отсутствует токен. Пожалуйста, войдите в систему.");
           return;
         }
 
         // We'll send a PATCH request to /api/references/expense/:id
         const endpoint = `/api/references/expense/${this.operation.id}`;
 
         const response = await axios.patch(
           endpoint,
           {
             name: this.editableExpense.name,
             amount: this.editableExpense.amount
           },
           {
             headers: { Authorization: `Bearer ${token}` }
           }
         );
 
         // If successful, let user know
         alert("Расход успешно обновлен!");
 
         // Emit event to parent so it can update the record in the list
         this.$emit("saved", response.data);
 
       } catch (error) {
         console.error("Ошибка при обновлении расхода:", error);
         alert("Не удалось обновить расход.");
       } finally {
         this.loading = false;
       }
     }
   }
 };
 </script>
 
 <style scoped>
 .expense-edit {
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
 </style>
 