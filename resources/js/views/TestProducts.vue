<template>
   <div class="dashboard-container">
     <!-- Sidebar -->
     <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
 
     <!-- Main Content -->
     <div class="main-content">
       <!-- Header -->
       <Header />
 
       <main class="content">
         <!-- Action Buttons -->
         <div class="action-buttons">
           <button class="add-btn" @click="openModal('addProduct')">Создать карточку товара</button>
           <button class="add-btn" @click="openModal('addUnit')">Добавить единицу измерения</button>
         </div>
 
         <!-- Data Table -->
         <div class="table-container">
           <h2>История операций</h2>
           <table class="styled-table">
             <thead>
               <tr>
                 <th>Тип</th>
                 <th>Наименование</th>
                 <th>Описание</th>
                 <th>Доп. информация</th>
                 <th>Дата</th>
                 <th>Действие</th>
               </tr>
             </thead>
             <tbody>
               <tr v-for="item in combinedData" :key="item.id">
                 <td>{{ item.type === 'product' ? 'Карточка товара' : 'Единица измерения' }}</td>
                 <td>{{ item.name_of_products || item.name }}</td>
                 <td>{{ item.description || '—' }}</td>
                 <td>
                   <span v-if="item.type === 'product'">{{ item.country || '—' }}</span>
                   <span v-if="item.type === 'unit'">{{ item.tare ? item.tare + ' г/кг/л' : '—' }}</span>
                 </td>
                 <td>{{ formatDate(item.created_at) }}</td>
                 <td>
                   <button class="edit-btn" @click="editItem(item)">✏️ Редактировать</button>
                   <button class="delete-btn" @click="deleteItem(item.id, item.type)">❌ Удалить</button>
                 </td>
               </tr>
             </tbody>
           </table>
         </div>
 
         <!-- Reusable Modal Component -->
         <PopupModal v-if="showModal" :title="modalTitle" @close="closeModal">
           <form @submit.prevent="handleSubmit">
             <label>Наименование</label>
             <input type="text" v-model="formData.name" required />
             <label v-if="isProduct">Описание</label>
             <input v-if="isProduct" type="text" v-model="formData.description" />
             <label v-if="isProduct">Страна</label>
             <input v-if="isProduct" type="text" v-model="formData.country" />
             <label v-if="isUnit">Тара (г/кг/л)</label>
             <input v-if="isUnit" type="number" v-model="formData.tare" />
             <button type="submit" class="submit-btn">{{ isEditing ? 'Сохранить' : 'Создать' }}</button>
           </form>
         </PopupModal>
       </main>
     </div>
   </div>
 </template>
 
 <script>
 import Sidebar from "../components/Sidebar.vue";
 import Header from "../components/Header.vue";
 import PopupModal from "../components/PopupModal.vue";
 
 export default {
   components: { Sidebar, Header, PopupModal },
   data() {
     return {
       isSidebarOpen: true,
       productCards: [],
       unitMeasurements: [],
       showModal: false,
       modalTitle: "",
       isEditing: false,
       isProduct: false,
       isUnit: false,
       formData: {},
     };
   },
   computed: {
     combinedData() {
       return [...this.productCards, ...this.unitMeasurements];
     },
   },
   methods: {
     toggleSidebar() {
       this.isSidebarOpen = !this.isSidebarOpen;
     },
     openModal(type) {
       this.isEditing = false;
       this.isProduct = type === 'addProduct';
       this.isUnit = type === 'addUnit';
       this.modalTitle = this.isProduct ? "Создать карточку товара" : "Добавить единицу измерения";
       this.formData = {};
       this.showModal = true;
     },
     closeModal() {
       this.showModal = false;
     },
     editItem(item) {
       this.isEditing = true;
       this.isProduct = item.type === 'product';
       this.isUnit = item.type === 'unit';
       this.modalTitle = this.isProduct ? "Редактировать карточку товара" : "Редактировать единицу измерения";
       this.formData = { ...item };
       this.showModal = true;
     },
     handleSubmit() {
       if (this.isEditing) {
         console.log("Editing", this.formData);
       } else {
         console.log("Adding", this.formData);
       }
       this.closeModal();
     }
   }
 };
 </script>
 
 <style scoped>
 .modal-overlay {
   position: fixed;
   top: 0;
   left: 0;
   width: 100%;
   height: 100%;
   background: rgba(0, 0, 0, 0.6);
   display: flex;
   align-items: center;
   justify-content: center;
 }
 .modal-container {
   background: white;
   padding: 20px;
   border-radius: 8px;
   width: 100%;
   max-width: 400px;
 }
 .submit-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
 }
 </style>
 