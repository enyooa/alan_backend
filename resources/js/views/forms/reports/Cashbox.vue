<template>
   <div class="dashboard-container">
     <!-- Optional Sidebar and Header -->
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
                 <th>Остаток на начало дня</th>
                 <th>Приход</th>
                 <th>Расход</th>
                 <th>Сальдо на конец дня</th>
               </tr>
             </thead>
             <tbody>
               <tr>
                 <td>{{ totalIncome.toFixed(2) }}</td>
                 <td>{{ totalExpense.toFixed(2) }}</td>
                 <td>{{ saldo.toFixed(2) }}</td>
                 <td>-</td>
               </tr>
             </tbody>
           </table>
 
           <!-- Export Buttons -->
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
   name: "CashboxReportPage",
   
   data() {
     return {
       loading: false,
       error: "",
       financialOrders: [],
       totalIncome: 0,
       totalExpense: 0,
       saldo: 0,
     };
   },
   created() {
     this.fetchFinancialOrders();
     this.requestStoragePermission();
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
         // For now, we use the route for financial orders.
         // Later, you can update this to fetch data from the desired endpoints.
         const response = await axios.get(`/api/financial-order`, {
           headers: { Authorization: `Bearer ${token}` },
         });
         if (response.status === 200) {
           const orders = response.data;
           this.financialOrders = orders;
           const incomeOrders = orders.filter(order => order.type === "income");
           const expenseOrders = orders.filter(order => order.type === "expense");
           this.totalIncome = incomeOrders.reduce((sum, order) => sum + (order.summary_cash || 0), 0);
           this.totalExpense = expenseOrders.reduce((sum, order) => sum + (order.summary_cash || 0), 0);
           this.saldo = this.totalIncome - this.totalExpense;
         } else {
           this.error = "Не удалось получить данные по операциям.";
         }
       } catch (e) {
         this.error = "Ошибка: " + e.message;
       } finally {
         this.loading = false;
       }
     },
     async requestStoragePermission() {
       // In a web app, storage permission is generally not required.
       // You might implement additional logic if needed.
       console.log("Запрос разрешения для сохранения файлов (не требуется для веб).");
     },
     exportToExcel() {
       // TODO: Integrate an Excel export library (e.g. xlsx) to implement this functionality.
       alert("Экспорт в Excel не реализован в этой демонстрации.");
     },
     exportToPdf() {
       // TODO: Integrate a PDF export library (e.g. jsPDF) to implement this functionality.
       alert("Экспорт в PDF не реализован в этой демонстрации.");
     },
   },
   computed: {
     baseUrl() {
       // Replace with your actual base URL or import it from your constants file.
       return "https://185.22.65.56/api/";
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
 