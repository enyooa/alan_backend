<template>
   <div class="dashboard-container">
     <!-- Sidebar -->
     <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
 
     <!-- Main Content -->
     <div class="main-content">
       <!-- Header -->
       <Header />
 
       <main class="content">
         <!-- Filter Section -->
         <div class="filters">
           <button class="filter-btn">Дата с</button>
           <button class="filter-btn">Дата по</button>
           <button class="filter-btn">Контрагент</button>
           <button class="filter-btn">Наименование товара</button>
         </div>
 
         <!-- Table -->
         <table class="debt-table">
           <thead>
             <tr>
               <th>Дата</th>
               <th>Наименование документа</th>
               <th>Сальдо на начало периода</th>
               <th>Приобретено</th>
               <th>Оплачено</th>
               <th>Сальдо на конец периода</th>
             </tr>
           </thead>
           <tbody>
             <tr v-for="item in debtData" :key="item.id">
               <td>{{ item.date }}</td>
               <td>{{ item.documentName }}</td>
               <td>{{ item.balanceStart }}</td>
               <td>{{ item.acquired }}</td>
               <td>{{ item.paid }}</td>
               <td>{{ item.balanceEnd }}</td>
             </tr>
           </tbody>
         </table>
 
         <!-- Footer or Buttons -->
         <div class="actions">
           <button class="action-btn">Сформировать отчет</button>
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
       // Sample data to represent customer debt report
       debtData: [
         {
           id: 1,
           date: '12.02.2024',
           documentName: 'Накладная 123',
           balanceStart: 0,
           acquired: 250000,
           paid: 100000,
           balanceEnd: 150000,
         },
         {
           id: 2,
           date: '15.03.2024',
           documentName: 'Оплата',
           balanceStart: 100000,
           acquired: 0,
           paid: 100000,
           balanceEnd: 0,
         },
       ],
     };
   },
 
   methods: {
     toggleSidebar() {
       this.isSidebarOpen = !this.isSidebarOpen;
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
 
 .filters {
   display: flex;
   justify-content: space-around;
   margin-bottom: 20px;
 }
 
 .filter-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px 20px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
 }
 
 .filter-btn:hover {
   background-color: #0277bd;
 }
 
 .debt-table {
   width: 100%;
   border-collapse: collapse;
   margin-bottom: 20px;
 }
 
 .debt-table th,
 .debt-table td {
   padding: 10px;
   border: 1px solid #ddd;
   text-align: left;
 }
 
 .debt-table th {
   background-color: #0288d1;
   color: white;
 }
 
 .debt-table tbody tr:nth-child(even) {
   background-color: #f2f2f2;
 }
 
 .actions {
   display: flex;
   justify-content: center;
   margin-top: 20px;
 }
 
 .action-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px 20px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
 }
 
 .action-btn:hover {
   background-color: #0277bd;
 }
 </style>
 