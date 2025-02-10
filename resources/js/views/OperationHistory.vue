<template>
   <div class="dashboard-container">
     <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
 
     <div class="main-content">
       <Header />
 
       <main class="content">
         <h2 class="page-title">История операций</h2>
 
         <!-- Search & Filter -->
         <div class="search-filter">
           <input v-model="searchQuery" @input="filterOperations" type="text" placeholder="Поиск..." class="search-box" />
           <select v-model="selectedFilter" @change="filterOperations" class="filter-select">
             <option value="">Все</option>
             <option value="Карточка товара">Карточка товара</option>
             <option value="Подкарточка товара">Подкарточка товара</option>
             <option value="Ценовое предложение">Ценовое предложение</option>
             <option value="Продажа">Продажа</option>
             <option value="Ед измерения">Ед измерения</option>
             <option value="Присвоить роль">Присвоить роль</option>
             <option value="Присвоить адрес">Присвоить адрес</option>
             <option value="Перемещение в склад">Перемещение в склад</option>
             <option value="Поставщик">Поставщик</option>
           </select>
         </div>
 
         <!-- Operations Table -->
         <table class="operation-table">
           <thead>
             <tr>
               <th>Операция</th>
               <th>Тип</th>
               <th>Действия</th>
             </tr>
           </thead>
           <tbody>
             <tr v-for="operation in filteredOperations" :key="operation.id">
               <td>{{ operation.operation }}</td>
               <td>{{ operation.type }}</td>
               <td>
                 <button @click="editOperation(operation)" class="edit-btn">✏️ Редактировать</button>
                 <button @click="confirmDelete(operation)" class="delete-btn">🗑 Удалить</button>
               </td>
             </tr>
           </tbody>
         </table>
 
         <!-- No Data Message -->
         <div v-if="filteredOperations.length === 0" class="no-data">Нет данных для отображения.</div>
 
         <!-- Edit Dialog -->
         <div v-if="showEditDialog" class="modal">
           <div class="modal-content">
             <h3>Редактировать {{ currentOperation.type }}</h3>
             <div v-for="field in editFields" :key="field">
               <label>{{ field }}:</label>
               <input v-model="editData[field]" type="text" class="modal-input" />
             </div>
             <button @click="saveEdit" class="save-btn">Сохранить</button>
             <button @click="closeEditDialog" class="cancel-btn">Отмена</button>
           </div>
         </div>
 
         <!-- Delete Confirmation -->
         <div v-if="showDeleteDialog" class="modal">
           <div class="modal-content">
             <h3>Удалить операцию?</h3>
             <p>Вы уверены, что хотите удалить "{{ currentOperation.operation }}"?</p>
             <button @click="deleteOperation" class="delete-btn">Удалить</button>
             <button @click="closeDeleteDialog" class="cancel-btn">Отмена</button>
           </div>
         </div>
 
       </main>
     </div>
   </div>
 </template>
 
 <script>
 import { ref, onMounted } from "vue";
 import axios from "axios";
 import Sidebar from "../components/Sidebar.vue";
 import Header from "../components/Header.vue";
 
 export default {
   components: { Sidebar, Header },
   setup() {
     const isSidebarOpen = ref(true);
     const searchQuery = ref("");
     const selectedFilter = ref("");
     const operations = ref([]);
     const filteredOperations = ref([]);
     const showEditDialog = ref(false);
     const showDeleteDialog = ref(false);
     const currentOperation = ref(null);
     const editFields = ref([]);
     const editData = ref({});
     const token = localStorage.getItem("token");
 
     // Fetch Operations on Mounted
     onMounted(fetchOperations);
 
     async function fetchOperations() {
       try {
         const response = await axios.get("/api/operations-history", {
           headers: { Authorization: `Bearer ${token}` },
         });
         operations.value = response.data;
         filteredOperations.value = response.data;
       } catch (error) {
         console.error("Error fetching operations:", error);
       }
     }
 
     function filterOperations() {
       filteredOperations.value = operations.value.filter((operation) => {
         const matchesQuery =
           operation.operation.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
           operation.type.toLowerCase().includes(searchQuery.value.toLowerCase());
         const matchesFilter = !selectedFilter.value || operation.type === selectedFilter.value;
         return matchesQuery && matchesFilter;
       });
     }
 
     function editOperation(operation) {
       currentOperation.value = operation;
       editFields.value = getEditFields(operation.type);
       editData.value = { ...operation };
       showEditDialog.value = true;
     }
 
     function getEditFields(type) {
       switch (type) {
         case "Продажа":
           return ["amount", "price"];
         case "Карточка товара":
           return ["name_of_products", "description"];
         case "Подкарточка товара":
           return ["name", "brutto", "netto"];
         case "Ценовое предложение":
           return ["amount", "price"];
         default:
           return [];
       }
     }
 
     async function saveEdit() {
       try {
         await axios.put(`/api/operations/${currentOperation.value.id}/${currentOperation.value.type}`, editData.value, {
           headers: { Authorization: `Bearer ${token}` },
         });
         showEditDialog.value = false;
         fetchOperations();
       } catch (error) {
         console.error("Error editing operation:", error);
       }
     }
 
     function closeEditDialog() {
       showEditDialog.value = false;
     }
 
     function confirmDelete(operation) {
       currentOperation.value = operation;
       showDeleteDialog.value = true;
     }
 
     async function deleteOperation() {
       try {
         await axios.delete(`/api/operations/${currentOperation.value.id}/${currentOperation.value.type}`, {
           headers: { Authorization: `Bearer ${token}` },
         });
         showDeleteDialog.value = false;
         fetchOperations();
       } catch (error) {
         console.error("Error deleting operation:", error);
       }
     }
 
     function closeDeleteDialog() {
       showDeleteDialog.value = false;
     }
 
     return {
       isSidebarOpen,
       searchQuery,
       selectedFilter,
       operations,
       filteredOperations,
       showEditDialog,
       showDeleteDialog,
       currentOperation,
       editFields,
       editData,
       filterOperations,
       editOperation,
       saveEdit,
       closeEditDialog,
       confirmDelete,
       deleteOperation,
       closeDeleteDialog,
     };
   },
 };
 </script>
 
 <style scoped>
 .search-filter {
   display: flex;
   gap: 10px;
   margin-bottom: 20px;
 }
 
 .search-box,
 .filter-select {
   padding: 10px;
   border: 1px solid #ddd;
   border-radius: 5px;
 }
 
 .operation-table {
   width: 100%;
   border-collapse: collapse;
 }
 
 .operation-table th,
 .operation-table td {
   padding: 10px;
   text-align: center;
   border: 1px solid #ddd;
 }
 
 .edit-btn,
 .delete-btn {
   padding: 8px;
   margin: 5px;
   border-radius: 5px;
   cursor: pointer;
 }
 
 .edit-btn {
   background-color: blue;
   color: white;
 }
 
 .delete-btn {
   background-color: red;
   color: white;
 }
 
 .modal {
   position: fixed;
   background: rgba(0, 0, 0, 0.5);
   width: 100%;
   height: 100%;
   display: flex;
   justify-content: center;
   align-items: center;
 }
 </style>
 