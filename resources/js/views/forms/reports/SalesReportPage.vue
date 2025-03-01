<template>
   <div>
     <h2>Отчет по продажам</h2>
     <table class="report-table">
       <thead>
         <tr>
           <th>Количество</th>
           <th>Сумма продаж</th>
           <th>Себестоимость</th>
           <th>Прибыль</th>
           <th>Дата отчёта</th>
         </tr>
       </thead>
       <tbody>
         <tr v-for="(row, index) in salesData" :key="index">
           <td>{{ row.quantity }}</td>
           <td>{{ row.sale_amount }}</td>
           <td>{{ row.cost_price }}</td>
           <td>{{ row.profit }}</td>
           <td>{{ row.report_date }}</td>
         </tr>
       </tbody>
     </table>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   name: "SalesReportPage",
   data() {
     return {
       salesData: [],
     };
   },
   async created() {
     try {
       const response = await axios.get("/api/reports/sales");
       this.salesData = response.data;
     } catch (err) {
       console.error("Failed to fetch sales data:", err);
     }
   },
 };
 </script>
 
 <style scoped>
 .report-table {
   width: 100%;
   border-collapse: collapse;
 }
 
 .report-table th,
 .report-table td {
   border: 1px solid #ddd;
   padding: 8px;
 }
 
 .report-table th {
   background-color: #0288d1;
   color: #fff;
 }
 </style>
 