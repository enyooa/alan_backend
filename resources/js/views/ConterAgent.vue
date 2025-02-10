<template>
   <div class="dashboard-container">
     <!-- Sidebar -->
     <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
 
     <!-- Main Content -->
     <div class="main-content">
       <!-- Header -->
       <Header />
 
       <main class="content">
         <!-- Client List Section -->
         <div class="client-list">
           <button class="add-btn" @click="toggleModal('addClient')">Добавить контрагента</button>
 
           <table class="styled-table">
             <thead>
               <tr>
                 <th>ФИО клиента</th>
                 <th>Тип клиента</th>
                 <th>Номер телефона</th>
                 <th>Адрес доставки</th>
                 <th>Действие</th>
               </tr>
             </thead>
             <tbody>
               <tr v-for="client in clients" :key="client.id">
                 <td>{{ client.name }}</td>
                 <td>{{ client.type }}</td>
                 <td>{{ client.phone }}</td>
                 <td>{{ client.address }}</td>
                 <td><button class="delete-btn" @click="deleteClient(client.id)">Удалить</button></td>
               </tr>
             </tbody>
           </table>
         </div>
 
         <!-- Add Client Modal -->
         <div v-if="showModal.addClient" class="add-client-modal stylish-form">
           <h2>Добавить контрагента</h2>
           <form @submit.prevent="addClient">
             <label>ФИО клиента</label>
             <input type="text" v-model="newClient.name" placeholder="Введите ФИО клиента" />
             <label>Номер телефона</label>
             <input type="text" v-model="newClient.phone" placeholder="Введите номер телефона" />
             <label>Наименование организации</label>
             <input type="text" v-model="newClient.organization" placeholder="Введите организацию" />
             <label>Адрес поставки</label>
             <input type="text" v-model="newClient.address" placeholder="Введите адрес доставки" />
             <button type="submit" class="submit-btn">Добавить контрагента</button>
           </form>
           <button class="close-btn" @click="toggleModal('addClient')">Закрыть</button>
         </div>
 
         <!-- Instructions/Info -->
         <div class="info">
           <p>Контрагенты отображаются автоматически (самостоятельная регистрация)</p>
         </div>
       </main>
     </div>
   </div>
 </template>
 
 <script>
 import Sidebar from "../components/Sidebar.vue";
 import Header from "../components/Header.vue";
 
 export default {
   components: { Sidebar, Header },
 
   data() {
     return {
       isSidebarOpen: true,
       clients: [
         { id: 1, name: "Максимов Канат", type: "клиент кафе", phone: "8700 999 11 11", address: "город Астана улица Сауран 5г магазин Асем" },
         { id: 2, name: "Иванов Иван", type: "Клиент магазина", phone: "8700 999 11 11", address: "город Астана улица Сауран 5г магазин Асем" },
         { id: 3, name: "Касеке Масеке Масеке", type: "клиент", phone: "8700 999 11 11", address: "город Астана улица Сауран 5г магазин Асем" },
       ],
       newClient: { name: "", phone: "", organization: "", address: "" },
       showModal: { addClient: false },
     };
   },
 
   methods: {
     toggleSidebar() {
       this.isSidebarOpen = !this.isSidebarOpen;
     },
     toggleModal(modalName) {
       this.showModal[modalName] = !this.showModal[modalName];
     },
     addClient() {
       if (this.newClient.name && this.newClient.phone && this.newClient.address) {
         this.clients.push({ ...this.newClient, id: this.clients.length + 1 });
         this.newClient = { name: "", phone: "", organization: "", address: "" };
         this.toggleModal("addClient");
       } else {
         alert("Пожалуйста, заполните все поля");
       }
     },
     deleteClient(id) {
       this.clients = this.clients.filter(client => client.id !== id);
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
   display: flex;
   flex-direction: column;
   background-color: #f5f5f5;
 }
 
 .content {
   flex: 1;
   padding: 20px;
   display: flex;
   flex-direction: column;
   align-items: center;
 }
 
 .client-list {
   margin-bottom: 20px;
   width: 100%;
   max-width: 800px;
 }
 
 .add-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   margin-bottom: 10px;
   width: 100%;
   max-width: 200px;
 }
 
 .styled-table {
   width: 100%;
   border-collapse: collapse;
   background-color: #ffffff;
   box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
   border-radius: 8px;
   overflow: hidden;
 }
 
 .styled-table th,
 .styled-table td {
   padding: 12px;
   text-align: left;
   border-bottom: 1px solid #ddd;
 }
 
 .styled-table tbody tr:nth-child(even) {
   background-color: #f2f2f2;
 }
 
 .delete-btn {
   background-color: #ff4d4d;
   color: white;
   border: none;
   border-radius: 5px;
   padding: 8px 12px;
   cursor: pointer;
 }
 
 .delete-btn:hover {
   background-color: #d32f2f;
 }
 
 .add-client-modal {
   position: fixed;
   top: 50%;
   left: 50%;
   transform: translate(-50%, -50%);
   background: white;
   padding: 20px;
   border-radius: 12px;
   box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
   z-index: 1000;
   width: 100%;
   max-width: 600px;
 }
 
 .add-client-modal h2 {
   color: #0288d1;
 }
 
 .submit-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
 }
 
 .close-btn {
   margin-top: 15px;
   background-color: #ff4d4d;
   color: white;
   padding: 8px 12px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
 }
 
 .info {
   margin-top: 20px;
   font-size: 14px;
   color: #0288d1;
   text-align: center;
 }
 </style>
 