<template>
   <div>
     <h2>Отчет по долгам</h2>
     <table class="report-table">
       <thead>
         <tr>
           <th>Контрагент</th>
           <th>Нач. долг</th>
           <th>Приход</th>
           <th>Расход</th>
           <th>Кон. долг</th>
           <th>Дата отчёта</th>
         </tr>
       </thead>
       <tbody>
         <tr v-for="(row, index) in debtsData" :key="index">
           <td>{{ row.counterparty_name }}</td>
           <td>{{ row.start_balance_debt }}</td>
           <td>{{ row.incoming_debt }}</td>
           <td>{{ row.outgoing_debt }}</td>
           <td>{{ row.end_balance_debt }}</td>
           <td>{{ row.report_date }}</td>
         </tr>
       </tbody>
     </table>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   name: "DebtsReportPage",
   data() {
     return {
       debtsData: [],
     };
   },
   async created() {
     try {
       const response = await axios.get("/api/reports/debts");
       this.debtsData = response.data;
     } catch (err) {
       console.error("Failed to fetch debts data:", err);
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
 