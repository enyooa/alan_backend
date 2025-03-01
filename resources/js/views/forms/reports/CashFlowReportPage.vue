<template>
   <div class="dashboard-container">
     <div class="main-content">
       <main class="content">
         <h2 class="page-title">Отчет по кассе</h2>
 
         <!-- Loading/Error State -->
         <div v-if="loading" class="loading-indicator">
           <p>Загрузка...</p>
         </div>
         <div v-else-if="error" class="error-message">
           <p>{{ error }}</p>
         </div>
 
         <!-- Report Table -->
         <div v-else>
           <table class="report-table">
             <thead>
               <tr>
                 <th>Название кассы</th>
                 <th>Начальный остаток</th>
                 <th>Приход</th>
                 <th>Расход</th>
                 <th>Конечный остаток</th>
                 <th>Дата отчёта</th>
               </tr>
             </thead>
             <tbody>
               <tr
                 v-for="(order, index) in financialOrders"
                 :key="index"
               >
                 <!-- Название кассы: берем из order.admin_cash.name, если есть -->
                 <td>
                   {{ order.admin_cash ? order.admin_cash.name : '—' }}
                 </td>
 
                 <!-- Начальный остаток (пока поставим 0 или логику) -->
                 <td>0</td>
 
                 <!-- Если type === 'income' => пишем в приход, иначе 0 -->
                 <td v-if="order.type === 'income'">
                   {{ order.summary_cash }}
                 </td>
                 <td v-else>0</td>
 
                 <!-- Если type === 'expense' => пишем в расход, иначе 0 -->
                 <td v-if="order.type === 'expense'">
                   {{ order.summary_cash }}
                 </td>
                 <td v-else>0</td>
 
                 <!-- Конечный остаток (пока пусть 0 или своя логика) -->
                 <td>0</td>
 
                 <!-- Дата отчёта: date_of_check -->
                 <td>{{ order.date_of_check }}</td>
               </tr>
             </tbody>
           </table>
 
           <!-- Export Buttons (по желанию) -->
           <div class="export-buttons">
             <button class="export-btn pdf-btn" @click="exportToPdf">
               Экспорт в PDF
             </button>
             <button class="export-btn excel-btn" @click="exportToExcel">
               Экспорт в Excel
             </button>
           </div>
         </div>
       </main>
     </div>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   name: "CashFlowReportPage",
   data() {
     return {
       loading: false,
       error: "",
       financialOrders: [],
     };
   },
   async created() {
     this.fetchFinancialOrders();
   },
   methods: {
     async fetchFinancialOrders() {
       this.loading = true;
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           this.error = "Ошибка: Токен не найден.";
           return;
         }
         const response = await axios.get("/api/financial-order", {
           headers: { Authorization: `Bearer ${token}` },
         });
         if (response.status === 200) {
           this.financialOrders = response.data;
         } else {
           this.error = "Не удалось получить данные по операциям.";
         }
       } catch (e) {
         this.error = "Ошибка: " + e.message;
       } finally {
         this.loading = false;
       }
     },
     exportToExcel() {
       // TODO: Реализовать экспорт в Excel
       alert("Экспорт в Excel не реализован.");
     },
     exportToPdf() {
       // TODO: Реализовать экспорт в PDF
       alert("Экспорт в PDF не реализован.");
     },
   },
 };
 </script>
 
 <style scoped>
 .dashboard-container {
   display: flex;
   flex-direction: column;
   min-height: 100vh;
   background-color: #f5f5f5;
 }
 
 .main-content {
   flex: 1;
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
 .report-table {
   width: 100%;
   border-collapse: collapse;
   margin-bottom: 20px;
 }
 .report-table th,
 .report-table td {
   padding: 10px;
   border: 1px solid #ddd;
   text-align: center;
 }
 .report-table thead {
   background-color: #0288d1;
   color: white;
 }
 
 .export-buttons {
   display: flex;
   justify-content: flex-end;
   gap: 10px;
 }
 .export-btn {
   padding: 10px 20px;
   border: none;
   border-radius: 5px;
   color: white;
   cursor: pointer;
 }
 .pdf-btn {
   background-color: #d32f2f;
 }
 .excel-btn {
   background-color: #388e3c;
 }
 </style>
 