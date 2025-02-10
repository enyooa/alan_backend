<template>
   <div class="dashboard-container">
     <!-- Sidebar -->
     <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
 
     <!-- Main Content -->
     <div class="main-content">
       <!-- Header -->
       <Header />
 
       <main class="content">
         <h2 class="page-title">Присвоить и удалить роли</h2>
 
         <!-- User Dropdown -->
         <div class="dropdown">
           <label for="user">Выберите пользователя:</label>
           <select v-model="selectedUser" id="user" class="select">
             <option disabled value="">Выберите пользователя</option>
             <option v-for="user in users" :key="user.id" :value="user">
               {{ user.first_name }} {{ user.last_name }}
             </option>
           </select>
         </div>
 
         <!-- Display roles of selected user -->
         <div v-if="selectedUser">
           <h3>Роли пользователя:</h3>
           <div v-if="selectedUser.roles.length === 0">
             <p>У пользователя нет ролей.</p>
           </div>
           <ul class="role-list">
             <li v-for="role in selectedUser.roles" :key="role.name" class="role-item">
               {{ rolesMap[role.name] || role.name }}
               <button @click="removeRole(role.name)" class="remove-btn">Удалить роль</button>
             </li>
           </ul>
         </div>
 
         <!-- Role Assignment -->
         <div class="dropdown">
           <label for="role">Выберите роль для присвоения:</label>
           <select v-model="selectedRole" id="role" class="select">
             <option disabled value="">Выберите роль</option>
             <option v-for="role in roles" :key="role" :value="role">
               {{ rolesMap[role] }}
             </option>
           </select>
         </div>
 
         <!-- Assign Role Button -->
         <button @click="assignRole" :disabled="!selectedUser || !selectedRole" class="assign-btn">
           Присвоить роль
         </button>
 
         <!-- Feedback message -->
         <div v-if="message" :class="messageType" class="feedback-message">
           {{ message }}
         </div>
       </main>
     </div>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 import Sidebar from "../components/Sidebar.vue";
 import Header from "../components/Header.vue";
 
 export default {
   components: { Sidebar, Header },
   data() {
     return {
       users: [], // Array to store users
       selectedUser: null, // Selected user for role assignment
       selectedRole: null, // Selected role to assign
       roles: ['cashbox', 'courier', 'packer', 'client', 'admin', 'storager'], // Possible roles
       rolesMap: {
         cashbox: 'Касса',
         courier: 'Курьер',
         packer: 'Упаковщик',
         client: 'Клиент',
         admin: 'Администратор',
         storager: 'Кладовщик',
       },
       message: "", // To display success or error messages
       messageType: "", // To differentiate between success and error messages
     };
   },
   created() {
     this.fetchUsers();
   },
   methods: {
     // Fetch users from API
     async fetchUsers() {
       try {
         const response = await axios.get("/api/users");
         this.users = response.data;
       } catch (error) {
         this.showMessage("Не удалось загрузить пользователей", "error");
       }
     },
 
     // Assign role to the selected user
     async assignRole() {
       if (!this.selectedUser || !this.selectedRole) {
         return;
       }
 
       try {
         const response = await axios.put(`/api/users/${this.selectedUser.id}/assign-roles`, {
           role: this.selectedRole,
         });
         this.selectedUser.roles.push({ name: this.selectedRole }); // Update roles locally
         this.showMessage("Роль успешно присвоена!", "success");
       } catch (error) {
         this.showMessage("Ошибка при присвоении роли", "error");
       }
     },
 
     // Remove role from the selected user
     async removeRole(roleName) {
       if (!this.selectedUser) return;
 
       try {
         const response = await axios.delete(`/api/users/${this.selectedUser.id}/remove-role`, {
           data: { role: roleName },
         });
         this.selectedUser.roles = this.selectedUser.roles.filter(role => role.name !== roleName);
         this.showMessage("Роль успешно удалена!", "success");
       } catch (error) {
         this.showMessage("Ошибка при удалении роли", "error");
       }
     },
 
     // Helper to show messages
     showMessage(message, type) {
       this.message = message;
       this.messageType = type;
       setTimeout(() => {
         this.message = ""; // Clear message after 3 seconds
       }, 3000);
     },
   },
 };
 </script>
 
 <style scoped>
 .dashboard-container {
   display: flex;
   min-height: 100vh;
 }
 
 .main-content {
   flex: 1;
   padding: 20px;
   background-color: #f5f5f5;
 }
 
 .content {
   width: 100%;
   max-width: 800px;
   margin: 0 auto;
 }
 
 .page-title {
   color: #0288d1;
   text-align: center;
   font-size: 1.5rem;
   margin-bottom: 20px;
 }
 
 .dropdown {
   margin-bottom: 20px;
 }
 
 .dropdown label {
   font-weight: bold;
   display: block;
   margin-bottom: 8px;
 }
 
 .select {
   width: 100%;
   padding: 12px;
   margin-top: 8px;
   border: 1px solid #ddd;
   border-radius: 5px;
 }
 
 button {
   background-color: #0288d1;
   color: white;
   padding: 12px;
   border: none;
   border-radius: 5px;
   width: 100%;
   cursor: pointer;
 }
 
 button:disabled {
   background-color: #aaa;
 }
 
 button:hover:not(:disabled) {
   background-color: #026ca0;
 }
 
 .role-list {
   list-style-type: none;
   padding-left: 20px;
 }
 
 .role-item {
   margin-bottom: 10px;
   display: flex;
   justify-content: space-between;
 }
 
 .remove-btn {
   background-color: red;
   color: white;
   padding: 5px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
 }
 
 .feedback-message {
   margin-top: 20px;
   font-weight: bold;
   text-align: center;
 }
 
 .success {
   color: green;
 }
 
 .error {
   color: red;
 }
 
 @media (max-width: 768px) {
   .main-content {
     padding: 10px;
   }
 
   .page-title {
     font-size: 1.2rem;
   }
 
   .select, button {
     font-size: 14px;
   }
 }
 </style>
 