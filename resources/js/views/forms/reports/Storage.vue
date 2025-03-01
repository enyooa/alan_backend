<template>
   <div class="dashboard-container">
 
     <div class="main-content">
 
       <main class="content">
         <h2 class="page-title">Отчет по складу</h2>
 
         <!-- Loading / Error state -->
         <div v-if="loading" class="loading-indicator">
           <p>Загрузка...</p>
         </div>
         <div v-else-if="error" class="error-message">
           <p>{{ error }}</p>
         </div>
 
         <!-- Storage Report Table -->
         <div v-else class="table-container">
           <table class="storage-table">
             <thead>
               <tr>
                 <th>Наименование</th>
                 <th>Ед изм</th>
                 <th>Приход</th>
                 <th>Расход</th>
                 <th>Остаток</th>
               </tr>
             </thead>
             <tbody>
               <tr v-for="item in storageData" :key="item.id">
                 <td>{{ item.product || '-' }}</td>
                 <td>{{ item.unit || '-' }}</td>
                 <td>{{ item.incoming || 0 }}</td>
                 <td>{{ item.outgoing || 0 }}</td>
                 <td>{{ item.remaining || 0 }}</td>
               </tr>
             </tbody>
           </table>
         </div>
       </main>
     </div>
   </div>
 </template>
 
 <script>
 import axios from "axios";

 
 export default {
   name: "StorageReportPage",
   
   data() {
     return {
       loading: false,
       error: "",
       storageData: [],
     };
   },
   created() {
     this.fetchStorageReport();
   },
   methods: {
     
     async fetchStorageReport() {
       this.loading = true;
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           this.error = "Ошибка: Токен не найден.";
           return;
         }
         // Fetch storage report data from your backend
         const response = await axios.get(`/api/fetchSalesReport`, {
           headers: { Authorization: `Bearer ${token}` },
         });
         if (response.status === 200) {
           // Assuming your API response structure contains a "sales" property:
           this.storageData = response.data.sales || [];
         } else {
           this.error = "Не удалось получить данные отчета по складу.";
         }
       } catch (e) {
         this.error = "Ошибка: " + e.message;
       } finally {
         this.loading = false;
       }
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
   background-color: #f5f5f5;
 }
 
 .content {
   padding: 20px;
 }
 
 .page-title {
   text-align: center;
   color: #0288d1;
   margin-bottom: 20px;
 }
 
 .loading-indicator,
 .error-message {
   text-align: center;
   margin-top: 20px;
 }
 
 .table-container {
   overflow-x: auto;
   background-color: #fff;
   border-radius: 8px;
   box-shadow: 0 3px 8px rgba(0, 0, 0, 0.1);
   margin-bottom: 20px;
 }
 
 .storage-table {
   width: 100%;
   border-collapse: collapse;
 }
 
 .storage-table th,
 .storage-table td {
   padding: 10px;
   border: 1px solid #ddd;
   text-align: center;
 }
 
 .storage-table thead {
   background-color: #0288d1;
   color: white;
 }
 </style>
 