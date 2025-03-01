<template>
   <div class="edit-form">
     <h3>Редактировать адрес</h3>
     <form @submit.prevent="save">
       <div class="form-group">
         <label for="name">Название адреса:</label>
         <input type="text" v-model="form.name" id="name" />
       </div>
       <div class="buttons">
         <button type="submit">Сохранить</button>
         <button type="button" @click="$emit('close')">Отмена</button>
       </div>
     </form>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 export default {
   name: "AddressEdit",
   props: {
     operation: {
       type: Object,
       default: () => ({}),
     },
   },
   data() {
     return {
       form: {
         name: this.operation.name || "",
       },
     };
   },
   methods: {
     async save() {
       try {
         const token = localStorage.getItem("token");
         const response = await axios.patch(
           `/api/references/address/${this.operation.id}`,
           this.form,
           { headers: { Authorization: `Bearer ${token}` } }
         );
         this.$emit("saved", response.data);
       } catch (error) {
         console.error("Ошибка при сохранении:", error);
         alert("Ошибка при сохранении данных.");
       }
     },
   },
   watch: {
     operation: {
       handler(newVal) {
         if (newVal) {
           this.form.name = newVal.name;
         }
       },
       immediate: true,
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
 input {
   width: 100%;
   padding: 8px;
   box-sizing: border-box;
 }
 .buttons {
   display: flex;
   gap: 10px;
 }
 </style>
 