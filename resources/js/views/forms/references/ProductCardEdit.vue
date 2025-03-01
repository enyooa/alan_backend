<template>
   <div class="edit-form">
     <h3>Редактировать карточку товара</h3>
     <form @submit.prevent="save">
       <div class="form-group">
         <label for="name">Название товара:</label>
         <input type="text" v-model="form.name_of_products" id="name" />
       </div>
       <div class="form-group">
         <label for="description">Описание:</label>
         <textarea v-model="form.description" id="description"></textarea>
       </div>
       <div class="form-group">
         <label for="country">Страна:</label>
         <input type="text" v-model="form.country" id="country" />
       </div>
       <!-- Add other fields as needed -->
       <div class="buttons">
         <button type="submit">Сохранить</button>
         <button type="button" @click="$emit('close')">Отмена</button>
       </div>
     </form>
   </div>
 </template>
 
 <script>
 import axios from 'axios';
 
 export default {
   name: 'ProductCardEdit',
   props: {
     operation: {
       type: Object,
       default: () => ({}),
     },
   },
   data() {
     return {
       // Prepopulate the form with the operation values
       form: {
         name_of_products: this.operation.name_of_products || '',
         description: this.operation.description || '',
         country: this.operation.country || '',
         // ... add any additional fields here
       },
     };
   },
   methods: {
     async save() {
       try {
         const token = localStorage.getItem('token');
         const response = await axios.patch(
           `/api/references/productCard/${this.operation.id}`,
           this.form,
           { headers: { Authorization: `Bearer ${token}` } }
         );
         // Emit the updated record back to the parent component
         this.$emit('saved', response.data);
       } catch (error) {
         console.error('Ошибка при сохранении:', error);
         alert('Ошибка при сохранении данных.');
       }
     },
   },
 };
 </script>
 
 <style scoped>
 .edit-form {
   padding: 20px;
 }
 .form-group {
   margin-bottom: 15px;
 }
 label {
   display: block;
   margin-bottom: 5px;
 }
 input,
 textarea {
   width: 100%;
   padding: 8px;
   box-sizing: border-box;
 }
 .buttons {
   display: flex;
   gap: 10px;
 }
 </style>
 