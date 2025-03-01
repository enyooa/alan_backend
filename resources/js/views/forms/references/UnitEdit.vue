<template>
   <div class="edit-form">
     <h3>Редактировать единицу измерения</h3>
     <form @submit.prevent="save">
       <div class="form-group">
         <label for="name">Название единицы измерения:</label>
         <input type="text" v-model="form.name" id="name" />
       </div>
       <div class="form-group">
         <label for="tare">Тара (г/кг/л):</label>
         <input type="number" v-model="form.tare" id="tare" />
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
   name: "UnitEdit",
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
         tare: this.operation.tare || "",
       },
     };
   },
   methods: {
     async save() {
       try {
         const token = localStorage.getItem("token");
         const response = await axios.patch(
           `/api/references/unit/${this.operation.id}`,
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
           this.form.tare = newVal.tare;
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
 