<template>
   <div>
     <h2>Отчет по складу</h2>
     <table class="report-table">
       <thead>
         <tr>
           <th>Склад</th>
           <th>Нач. Остаток</th>
           <th>Приход</th>
           <th>Расход</th>
           <th>Кон. Остаток</th>
           <th>Сумма</th>
           <th>Дата отчёта</th>
         </tr>
       </thead>
       <tbody>
         <tr v-for="(row, index) in warehouseData" :key="index">
           <td>{{ row.warehouse_name }}</td>
           <td>{{ row.start_balance }}</td>
           <td>{{ row.arrival }}</td>
           <td>{{ row.consumption }}</td>
           <td>{{ row.end_balance }}</td>
           <td>{{ row.total_sum }}</td>
           <td>{{ row.report_date }}</td>
         </tr>
       </tbody>
     </table>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   name: "WarehouseReportPage",
   data() {
     return {
       warehouseData: [],
     };
   },
   async created() {
     try {
       const response = await axios.get("/api/reports/warehouse");
       this.warehouseData = response.data;
     } catch (err) {
       console.error("Failed to fetch warehouse data:", err);
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
 