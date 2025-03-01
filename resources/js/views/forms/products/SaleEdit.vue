<template>
   <div class="modern-modal">
     <!-- Header -->
     <div class="modern-modal-header">
       <h2>Редактировать продажу (ID: {{ form.id }})</h2>
       <!-- If you want a close "×" button in the header, you can add: 
            <button class="modern-close-btn" @click="$emit('close')">×</button>
          -->
     </div>
 
     <div class="modern-modal-body">
       <form @submit.prevent="saveChanges">
         <!-- Date -->
         <div class="form-group">
           <label>Дата</label>
           <input type="date" v-model="form.date" />
         </div>
         <!-- Quantity -->
         <div class="form-group">
           <label>Количество</label>
           <input type="number" v-model.number="form.quantity" />
         </div>
         <!-- Price -->
         <div class="form-group">
           <label>Цена</label>
           <input type="number" v-model.number="form.price" />
         </div>
         <!-- Total Sum -->
         <div class="form-group">
           <label>Сумма</label>
           <input type="number" v-model.number="form.total_sum" />
         </div>
         <!-- Add any other fields needed for "sales" -->
 
         <div class="modern-modal-footer">
           <button type="submit" class="modern-save-btn">Сохранить</button>
           <button type="button" class="modern-cancel-btn" @click="$emit('close')">
             Отмена
           </button>
         </div>
       </form>
     </div>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   name: "SaleEditModal",
   props: {
     record: {
       type: Object,
       required: true,
     },
   },
   data() {
     return {
       form: {
         id: this.record.id,
         date: this.record.date || "",
         quantity: this.record.quantity || 0,
         price: this.record.price || 0,
         total_sum: this.record.total_sum || 0,
         // any other sales fields
       },
     };
   },
   methods: {
     async saveChanges() {
       try {
         // For sales, we do: PATCH /api/sales/:id
         const { data } = await axios.patch(
           `/api/sales/${this.form.id}`,
           this.form
         );
         this.$emit("saved", data); // Let the parent know we've saved
       } catch (err) {
         console.error("Error saving sale:", err);
         alert("Ошибка при сохранении.");
       }
     },
   },
 };
 </script>
 
 <style scoped>
 .modern-modal {
   background-color: #fff;
   width: 480px;
   max-width: 90%;
   border-radius: 10px;
   box-shadow: 0 10px 30px rgba(0,0,0,0.2);
   position: relative;
   overflow: hidden;
   margin: 40px auto;
 }
 
 .modern-modal-header {
   background: linear-gradient(to right, #0288d1, #026ca0);
   color: #fff;
   padding: 16px;
   position: relative;
 }
 
 .modern-modal-header h2 {
   margin: 0;
   font-size: 1.2rem;
 }
 
 .modern-close-btn {
   background: transparent;
   border: none;
   color: #fff;
   font-size: 24px;
   position: absolute;
   top: 12px;
   right: 16px;
   cursor: pointer;
 }
 
 .modern-modal-body {
   padding: 16px;
 }
 
 .form-group {
   margin-bottom: 15px;
 }
 .form-group label {
   display: block;
   font-weight: 600;
   margin-bottom: 5px;
 }
 .form-group input {
   width: 100%;
   padding: 8px;
   border: 1px solid #ddd;
   border-radius: 6px;
 }
 
 .modern-modal-footer {
   display: flex;
   justify-content: flex-end;
   gap: 10px;
   margin-top: 20px;
 }
 
 .modern-save-btn {
   background-color: #0288d1;
   color: white;
   border: none;
   padding: 10px 16px;
   border-radius: 6px;
   cursor: pointer;
 }
 .modern-save-btn:hover {
   background-color: #026ca0;
 }
 
 .modern-cancel-btn {
   background-color: #9e9e9e;
   color: white;
   border: none;
   padding: 10px 16px;
   border-radius: 6px;
   cursor: pointer;
 }
 .modern-cancel-btn:hover {
   background-color: #757575;
 }
 </style>
 