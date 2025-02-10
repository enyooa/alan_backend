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
           <button class="filter-btn" @click="applyFilter('dateFrom')">Дата с</button>
           <button class="filter-btn" @click="applyFilter('dateTo')">Дата по</button>
           <button class="filter-btn" @click="applyFilter('client')">Контрагент</button>
           <button class="filter-btn" @click="applyFilter('category')">Категория товара</button>
           <button class="filter-btn" @click="applyFilter('product')">Наименование товара</button>
         </div>
 
         <!-- Sales Table -->
         <div v-if="salesData.length > 0">
           <table class="sales-table">
             <thead>
               <tr>
                 <th>Контрагент</th>
                 <th>Категория товара</th>
                 <th>Наименование товара</th>
                 <th>ед. из</th>
                 <th>кол-во</th>
                 <th>цена</th>
                 <th>сумма</th>
               </tr>
             </thead>
             <tbody>
               <tr v-for="item in salesData" :key="item.id">
                 <td>{{ item.sub_card.product_card.name_of_products }}</td>
                 <td>{{ item.sub_card.product_card.type }}</td>
                 <td>{{ item.sub_card.name }}</td>
                 <td>{{ item.unit_measurement }}</td>
                 <td>{{ item.amount }}</td>
                 <td>{{ item.price }}</td>
                 <td>{{ item.totalsum }}</td>
               </tr>
             </tbody>
           </table>
         </div>
 
         <!-- Footer or Buttons -->
         <div v-if="salesData.length > 0" class="actions">
           <button class="action-btn" @click="generateReport">Сформировать отчет</button>
         </div>
 
         <!-- No Data Available -->
         <div v-else>
           <p>Нет данных для отображения.</p>
         </div>
       </main>
     </div>
   </div>
 </template>

<script>
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue";
import axios from "axios";

export default {
   components: { Sidebar, Header },
 
   data() {
     return {
       isSidebarOpen: true,
       salesData: [],
       filters: {
         dateFrom: null,
         dateTo: null,
         client: null,
         category: null,
         product: null,
       },
     };
   },
 
   methods: {
     // Fetch sales data from the backend directly
     async fetchSalesData() {
         try {
            // Retrieve token from localStorage
            const token = localStorage.getItem("token");
            console.log("Token: " + token);
            if (token) {
               // Set the Authorization header with the token for the request
               axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
            }

            // Make the API call to get sales data
            const response = await axios.get("/api/getSalesWithDetails");

            console.log("Sales Data Response:", response.data);
            if (response.data) {
               this.salesData = response.data;
            } else {
               throw new Error("No data received from the server.");
            }
         } catch (error) {
            console.error("Error fetching sales data:", error.response ? error.response.data : error);
            this.salesData = [];
            alert("Ошибка при загрузке данных. Пожалуйста, попробуйте снова.");
         }
         }
         ,

     // Toggle Sidebar
     toggleSidebar() {
       this.isSidebarOpen = !this.isSidebarOpen;
     },
 
     // Apply selected filters (placeholder for now)
     applyFilter(filterType) {
       console.log(`Applying filter: ${filterType}`);
       // Logic to apply filters to the sales data
     },
 
     // Generate report (can implement custom logic)
     generateReport() {
       console.log("Generating report...");
       // Logic for generating the sales report
     },
   },
 
   mounted() {
     this.fetchSalesData(); // Fetch data when the component is mounted
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
.sales-table {
   width: 100%;
   border-collapse: collapse;
   margin-bottom: 20px;
}
.sales-table th,
.sales-table td {
   padding: 10px;
   border: 1px solid #ddd;
   text-align: left;
}
.sales-table th {
   background-color: #0288d1;
   color: white;
}
.sales-table tbody tr:nth-child(even) {
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
