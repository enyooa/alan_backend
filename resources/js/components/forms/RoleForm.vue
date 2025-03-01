<template>
   <div class="role-form">
     <h2>Добавить роль</h2>
     <form @submit.prevent="addRole">
       <label for="role-name">Название роли</label>
       <input type="text" id="role-name" v-model="newRole.name" required placeholder="Введите название роли" />
 
       <button type="submit" class="submit-btn">Добавить</button>
       <button type="button" class="close-btn" @click="$emit('close')">Закрыть</button>
     </form>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   data() {
     return {
       newRole: { name: "" },
     };
   },
   methods: {
     async addRole() {
       if (!this.newRole.name) {
         alert("❌ Введите название роли!");
         return;
       }
 
       try {
         const token = localStorage.getItem("token");
         await axios.post("/api/roles", this.newRole, {
           headers: { Authorization: `Bearer ${token}` },
         });
         alert("✅ Роль успешно добавлена!");
         this.newRole.name = "";
         this.$emit("close");
       } catch (error) {
         console.error("Ошибка при добавлении роли:", error);
         alert("❌ Ошибка при добавлении роли!");
       }
     },
   },
 };
 </script>
 
 <style scoped>
 .role-form {
   text-align: center;
 }
 label {
   display: block;
   margin-top: 10px;
   font-weight: bold;
 }
 input {
   width: 100%;
   padding: 10px;
   margin-top: 5px;
   border: 1px solid #ddd;
   border-radius: 5px;
 }
 .submit-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   width: 100%;
   margin-top: 15px;
 }
 .submit-btn:hover {
   background-color: #026ca0;
 }
 .close-btn {
   background-color: #ff4d4d;
   color: white;
   padding: 10px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   width: 100%;
   margin-top: 10px;
 }
 .close-btn:hover {
   background-color: #d32f2f;
 }
 </style>
 