<template>
   <div class="dashboard-container">
     <!-- Sidebar -->
     <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
     <!-- Main Content -->
     <div class="main-content">
       <!-- Header -->
       <Header />
 
       <main class="content">
         <h2 class="page-title">Управление пользователями</h2>
 
         <!-- CREATE DROPDOWN -->
         <div class="create-dropdown">
           <label>Создать:</label>
           <select v-model="createSelection" @change="openCreateModal" class="dropdown-select">
             <option value="">Выберите...</option>
             <option value="employee">Сотрудники</option>
             <option value="role">Присвоить роль</option>
             <option value="account">Аккаунт</option>
           </select>
         </div>
 
         <!-- FILTERS: Search & Role Filter -->
         <div class="user-filter">
           <input
             v-model="searchQuery"
             type="text"
             class="search-box"
             placeholder="🔍 Поиск (Имя, Фамилия, Телефон)..."
             @input="filterUsers"
           />
           <!-- Filter by roles in DB (English), but show them in Russian in dropdown -->
           <select v-model="roleFilter" class="filter-select" @change="filterUsers">
             <option value="">Все роли</option>
             <option
               v-for="(displayName, dbValue) in roleMapCreate"
               :key="dbValue"
               :value="dbValue"
             >
               {{ displayName }}
             </option>
           </select>
         </div>
 
         <!-- USERS TABLE -->
         <div class="table-container">
           <table class="users-table">
             <thead>
               <tr>
                 <th>Имя</th>
                 <th>Фамилия</th>
                 <th>Отчество</th>
                 <th>Телефон</th>
                 <th>Роли</th>
                 <th>Действия</th>
               </tr>
             </thead>
             <tbody>
               <tr v-for="user in filteredUsers" :key="user.id">
                 <td>{{ user.first_name }}</td>
                 <td>{{ user.last_name }}</td>
                 <td>{{ user.surname || '—' }}</td>
                 <td>{{ user.whatsapp_number }}</td>
                 <td>
                   <!-- Display roles in Russian -->
                   {{ user.roles.map(r => rolesMap[r.name] || r.name).join(', ') }}
                 </td>
                 <td>
                   <button class="edit-btn" @click="openEditUserModal(user)">✏️</button>
                   <button class="delete-btn" @click="deleteUser(user)">🗑</button>
                 </td>
               </tr>
             </tbody>
           </table>
         </div>
 
         <div v-if="showEmployeeModal" class="modal-overlay">
           <div class="modal-container">
             <h3>Создать сотрудника</h3>
             <form @submit.prevent="createEmployee">
               <label>Имя</label>
               <input v-model="employeeForm.first_name" type="text" class="form-input" required />
               <label>Фамилия</label>
               <input v-model="employeeForm.last_name" type="text" class="form-input" />
               <label>Отчество</label>
               <input v-model="employeeForm.surname" type="text" class="form-input" />
               <label>Номер</label>
               <input v-model="employeeForm.whatsapp_number" type="text" class="form-input" required />
 
               <label>Роль</label>
               <select v-model="employeeForm.role" class="form-input" required>
                 <option value="">Выберите...</option>
                 <option value="admin">Администратор</option>
                 <option value="client">Клиент</option>
                 <option value="cashbox">Касса</option>
                 <option value="packer">Упаковщик</option>
                 <option value="storager">Кладовщик</option>
                 <option value="courier">Курьер</option>
               </select>
 
               <label>Пароль</label>
               <input v-model="employeeForm.password" type="password" class="form-input" required />
 
               <button type="submit" class="submit-btn">
                 {{ isLoading ? '⏳ Создание...' : 'Сохранить' }}
               </button>
             </form>
             <button class="close-btn" @click="closeAllModals">Закрыть</button>
 
             <p v-if="successMessage" class="success-message">{{ successMessage }}</p>
             <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
           </div>
         </div>
 
         <div v-if="showRoleModal" class="modal-overlay">
           <div class="modal-container">
             <h3>Присвоить / Удалить роль</h3>
             <div class="dropdown-block">
               <label>Выберите пользователя:</label>
               <select v-model="selectedUser" class="form-input">
                 <option disabled value="">---</option>
                 <option v-for="user in users" :key="user.id" :value="user">
                   {{ user.first_name }} {{ user.last_name }}
                 </option>
               </select>
             </div>
              <div v-if="selectedUser">
               <h4>Роли пользователя:</h4>
               <div v-if="selectedUser.roles.length === 0">
                 <p>У пользователя нет ролей.</p>
               </div>
               <ul class="role-list">
                 <li
                   v-for="role in selectedUser.roles"
                   :key="role.id"
                   class="role-item"
                 >
                   {{ rolesMap[role.name] || role.name }}
                   <button @click="removeRole(role.name)" class="remove-btn">Удалить роль</button>
                 </li>
               </ul>
             </div>
              <div class="dropdown-block">
               <label>Выберите роль для присвоения:</label>
               <select v-model="selectedRole" class="form-input">
                 <option disabled value="">---</option>
                 <option v-for="r in possibleRoles" :key="r" :value="r">
                   {{ rolesMap[r] || r }}
                 </option>
               </select>
             </div>
             <button
               class="assign-btn"
               :disabled="!selectedUser || !selectedRole"
               @click="assignRole"
             >
               Присвоить роль
             </button>
             <p v-if="roleMessage" :class="roleMessageType" class="role-feedback">
               {{ roleMessage }}
             </p>
 
             <button class="close-btn" @click="closeAllModals">Закрыть</button>
           </div>
         </div>
 
         <!-- MODAL: CREATE ACCOUNT -->
         <div v-if="showAccountModal" class="modal-overlay">
           <div class="modal-container">
             <h3>Создать аккаунт</h3>
             <form @submit.prevent="createAccount">
               <label>Имя</label>
               <input v-model="accountForm.first_name" type="text" class="form-input" required />
               <label>Фамилия</label>
               <input v-model="accountForm.last_name" type="text" class="form-input" />
               <label>Отчество</label>
               <input v-model="accountForm.surname" type="text" class="form-input" />
               <label>Номер</label>
               <input v-model="accountForm.whatsapp_number" type="text" class="form-input" required />
               <label>Роль</label>
               <select v-model="accountForm.role" class="form-input" required>
                 <option value="">Выберите...</option>
                 <option value="admin">Администратор</option>
                 <option value="client">Клиент</option>
                 <option value="cashbox">Касса</option>
                 <option value="packer">Упаковщик</option>
                 <option value="storager">Кладовщик</option>
                 <option value="courier">Курьер</option>
               </select>
               <label>Пароль</label>
               <input v-model="accountForm.password" type="password" class="form-input" required />
 
               <button type="submit" class="submit-btn">
                 {{ isLoading ? '⏳ Создание...' : 'Сохранить' }}
               </button>
             </form>
             <button class="close-btn" @click="closeAllModals">Закрыть</button>
 
             <p v-if="successMessage" class="success-message">{{ successMessage }}</p>
             <p v-if="errorMessage" class="error-message">{{ errorMessage }}</p>
           </div>
         </div>
 
         <!-- MODAL: EDIT USER -->
         <div v-if="showEditModal" class="modal-overlay">
           <div class="modal-container">
             <h3>Редактировать пользователя</h3>
             <form @submit.prevent="updateUser">
               <label>Имя</label>
               <input v-model="editForm.first_name" type="text" class="form-input" required />
               <label>Фамилия</label>
               <input v-model="editForm.last_name" type="text" class="form-input" />
               <label>Отчество</label>
               <input v-model="editForm.surname" type="text" class="form-input" />
               <label>Телефон</label>
               <input v-model="editForm.whatsapp_number" type="text" class="form-input" required />
 
               <button type="submit" class="submit-btn">Сохранить</button>
             </form>
             <button class="close-btn" @click="closeEditModal">Закрыть</button>
           </div>
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
       isSidebarOpen: true,
 
       // Users & Filtering
       users: [],
       filteredUsers: [],
       searchQuery: "",
       roleFilter: "",
 
       // Role text mapping (for display in the table)
       rolesMap: {
         admin: 'Администратор',
         client: 'Клиент',
         cashbox: 'Касса',
         packer: 'Упаковщик',
         storager: 'Кладовщик',
         courier: 'Курьер',
       },
       // For the role filter dropdown
       roleMapCreate: {
         admin: 'Администратор',
         client: 'Клиент',
         cashbox: 'Касса',
         packer: 'Упаковщик',
         storager: 'Кладовщик',
         courier: 'Курьер',
       },
 
       // Create selection
       createSelection: "",
       showEmployeeModal: false,
       showRoleModal: false,
       showAccountModal: false,
 
       // Loading / messages
       isLoading: false,
       successMessage: "",
       errorMessage: "",
 
       // CREATE EMPLOYEE
       employeeForm: {
         first_name: "",
         last_name: "",
         surname: "",
         whatsapp_number: "",
         role: "",
         password: "",
       },
 
       // ASSIGN/REMOVE ROLE
       selectedUser: null,
       selectedRole: null,
       possibleRoles: ['admin','client','cashbox','packer','storager','courier'],
       roleMessage: "",
       roleMessageType: "",
 
       // CREATE ACCOUNT
       accountForm: {
         first_name: "",
         last_name: "",
         surname: "",
         whatsapp_number: "",
         role: "",
         password: "",
       },
 
       // EDIT USER
       showEditModal: false,
       editForm: {
         id: null,
         first_name: "",
         last_name: "",
         surname: "",
         whatsapp_number: "",
       },
     };
   },
   async created() {
     await this.fetchUsers();
   },
   methods: {
     toggleSidebar() {
       this.isSidebarOpen = !this.isSidebarOpen;
     },
 
     // LOAD USERS
     async fetchUsers() {
       try {
         const response = await axios.get("/api/users");
         this.users = response.data;
         this.filteredUsers = response.data;
       } catch (error) {
         console.error("Ошибка при загрузке пользователей:", error);
       }
     },
 
     // FILTER
     filterUsers() {
       let results = [...this.users];
       const q = this.searchQuery.toLowerCase();
 
       // Text search (by first_name, last_name, phone, etc.)
       if (q) {
         results = results.filter(u =>
           (u.first_name + u.last_name + (u.whatsapp_number || '')).toLowerCase().includes(q)
         );
       }
 
       // Role filter
       if (this.roleFilter) {
         results = results.filter(u =>
           u.roles?.some(r => r.name === this.roleFilter)
         );
       }
 
       this.filteredUsers = results;
     },
 
     // CREATE DROPDOWN -> open correct modal
     openCreateModal() {
       this.closeAllModals();
       if (this.createSelection === 'employee') {
         this.showEmployeeModal = true;
       } else if (this.createSelection === 'role') {
         this.showRoleModal = true;
       } else if (this.createSelection === 'account') {
         this.showAccountModal = true;
       }
     },
     closeAllModals() {
       this.showEmployeeModal = false;
       this.showRoleModal = false;
       this.showAccountModal = false;
       this.successMessage = "";
       this.errorMessage = "";
       this.isLoading = false;
     },
 
     // CREATE EMPLOYEE
     async createEmployee() {
       this.isLoading = true;
       this.successMessage = "";
       this.errorMessage = "";
       try {
         await axios.post("/api/users", this.employeeForm);
         this.successMessage = "✅ Сотрудник успешно создан!";
         // Reset form
         this.employeeForm = {
           first_name: "",
           last_name: "",
           surname: "",
           whatsapp_number: "",
           role: "",
           password: "",
         };
         // Reload user table
         await this.fetchUsers();
         this.filterUsers();
       } catch (error) {
         console.error("Ошибка при создании сотрудника:", error);
         this.errorMessage = "❌ Ошибка при создании сотрудника. Попробуйте снова.";
       } finally {
         this.isLoading = false;
       }
     },
 
     // ASSIGN ROLE
     async assignRole() {
       if (!this.selectedUser || !this.selectedRole) return;
       try {
         await axios.put(`/api/users/${this.selectedUser.id}/assign-roles`, {
           role: this.selectedRole
         });
         // Update local roles
         this.selectedUser.roles.push({ name: this.selectedRole });
         this.showRoleMessage("Роль успешно присвоена!", "success");
         // Refresh user table
         await this.fetchUsers();
         this.filterUsers();
       } catch (error) {
         console.error("Ошибка при присвоении роли:", error);
         this.showRoleMessage("Ошибка при присвоении роли", "error");
       }
     },
     async removeRole(roleName) {
       if (!this.selectedUser) return;
       try {
         await axios.delete(`/api/users/${this.selectedUser.id}/remove-role`, {
           data: { role: roleName }
         });
         // Remove local role
         this.selectedUser.roles = this.selectedUser.roles.filter(r => r.name !== roleName);
         this.showRoleMessage("Роль успешно удалена!", "success");
         // refresh
         await this.fetchUsers();
         this.filterUsers();
       } catch (error) {
         console.error("Ошибка при удалении роли:", error);
         this.showRoleMessage("Ошибка при удалении роли", "error");
       }
     },
     showRoleMessage(msg, type) {
       this.roleMessage = msg;
       this.roleMessageType = type;
       setTimeout(() => {
         this.roleMessage = "";
       }, 3000);
     },
 
     // CREATE ACCOUNT
     async createAccount() {
       this.isLoading = true;
       this.successMessage = "";
       this.errorMessage = "";
       try {
         await axios.post("/api/users", this.accountForm);
         this.successMessage = "✅ Аккаунт успешно создан!";
         // Reset form
         this.accountForm = {
           first_name: "",
           last_name: "",
           surname: "",
           whatsapp_number: "",
           role: "",
           password: "",
         };
         // reload table
         await this.fetchUsers();
         this.filterUsers();
       } catch (error) {
         console.error("Ошибка при создании аккаунта:", error);
         this.errorMessage = "❌ Ошибка при создании аккаунта. Проверьте данные и повторите.";
       } finally {
         this.isLoading = false;
       }
     },
 
     // EDIT USER
     openEditUserModal(user) {
       this.showEditModal = true;
       // fill editForm
       this.editForm = {
         id: user.id,
         first_name: user.first_name,
         last_name: user.last_name,
         surname: user.surname,
         whatsapp_number: user.whatsapp_number,
       };
     },
     closeEditModal() {
       this.showEditModal = false;
     },
     async updateUser() {
       try {
         await axios.put(`/api/users/${this.editForm.id}`, {
           first_name: this.editForm.first_name,
           last_name: this.editForm.last_name,
           surname: this.editForm.surname,
           whatsapp_number: this.editForm.whatsapp_number,
         });
         alert("✅ Пользователь обновлён!");
         this.showEditModal = false;
         await this.fetchUsers();
         this.filterUsers();
       } catch (error) {
         console.error("Ошибка при редактировании пользователя:", error);
         alert("❌ Ошибка при редактировании пользователя.");
       }
     },
 
     // DELETE USER
     async deleteUser(user) {
       if (!confirm(`Удалить пользователя "${user.first_name}"?`)) return;
       try {
         await axios.delete(`/api/users/${user.id}`);
         alert("✅ Пользователь удалён!");
         await this.fetchUsers();
         this.filterUsers();
       } catch (error) {
         console.error("Ошибка при удалении пользователя:", error);
         alert("❌ Ошибка при удалении пользователя.");
       }
     },
   },
 };
 </script>
 
 <style scoped>
 /* Layout */
 .dashboard-container {
   display: flex;
   min-height: 100vh;
 }
 .main-content {
   flex: 1;
   background-color: #f5f5f5;
 }
 .content {
   padding: 20px;
 }
 
 /* Page Title */
 .page-title {
   color: #0288d1;
   text-align: center;
   margin-bottom: 20px;
 }
 
 /* Create Dropdown */
 .create-dropdown {
   display: flex;
   align-items: center;
   margin-bottom: 20px;
   gap: 8px;
 }
 .dropdown-select {
   padding: 8px;
   border-radius: 5px;
   border: 1px solid #ddd;
 }
 
 /* Filter Section */
 .user-filter {
   display: flex;
   align-items: center;
   gap: 8px;
   margin-bottom: 20px;
 }
 .search-box {
   flex: 1;
   padding: 10px;
   border-radius: 5px;
   border: 1px solid #ddd;
 }
 .filter-select {
   padding: 10px;
   border-radius: 5px;
   border: 1px solid #ddd;
 }
 
 /* Users Table */
 .table-container {
   overflow-x: auto;
   background-color: #fff;
   border-radius: 8px;
   box-shadow: 0 3px 8px rgba(0,0,0,0.1);
   margin-bottom: 30px;
 }
 .users-table {
   width: 100%;
   border-collapse: collapse;
 }
 .users-table th,
 .users-table td {
   padding: 10px;
   border: 1px solid #ddd;
   text-align: center;
 }
 .users-table thead {
   background-color: #0288d1;
   color: #fff;
 }
 
 /* Buttons in Table */
 .edit-btn {
   background-color: inherit;
   color: #000;
   border: none;
   padding: 8px;
   margin: 2px;
   border-radius: 5px;
   cursor: pointer;
 }
 .delete-btn {
   background-color: #f44336;
   color: #fff;
   border: none;
   padding: 8px;
   margin: 2px;
   border-radius: 5px;
   cursor: pointer;
 }
 
 /* Modal */
 .modal-overlay {
   position: fixed;
   top: 0;
   left: 0;
   width: 100%;
   height: 100%;
   background: rgba(0,0,0,0.5);
   display: flex;
   align-items: center;
   justify-content: center;
   z-index: 999;
 }
 .modal-container {
   background: #fff;
   padding: 20px;
   border-radius: 10px;
   width: 90%;
   max-width: 500px;
   position: relative;
 }
 
 /* Form inputs */
 .form-input {
   width: 100%;
   margin-bottom: 12px;
   padding: 8px;
   border: 1px solid #ddd;
   border-radius: 5px;
 }
 
 /* Buttons */
 .submit-btn {
   background-color: #0288d1;
   color: #fff;
   border: none;
   border-radius: 5px;
   padding: 10px 12px;
   cursor: pointer;
   width: 100%;
   margin-bottom: 10px;
 }
 .close-btn {
   background-color: #f44336;
   color: #fff;
   border: none;
   border-radius: 5px;
   padding: 10px 12px;
   cursor: pointer;
   width: 100%;
 }
 .role-list {
   list-style: none;
   padding-left: 0;
 }
 .role-item {
   background-color: #eee;
   margin-bottom: 8px;
   padding: 6px 10px;
   border-radius: 5px;
   display: flex;
   justify-content: space-between;
 }
 .remove-btn {
   background-color: #f44336;
   border: none;
   border-radius: 5px;
   color: #fff;
   padding: 5px 8px;
   cursor: pointer;
 }
 
 /* Messages */
 .success-message {
   color: green;
   text-align: center;
   margin-top: 10px;
   font-weight: bold;
 }
 .error-message {
   color: red;
   text-align: center;
   margin-top: 10px;
   font-weight: bold;
 }
 .role-feedback {
   margin-top: 10px;
   text-align: center;
   font-weight: bold;
 }
 .success {
   color: green;
 }
 .error {
   color: red;
 }
 </style>
 