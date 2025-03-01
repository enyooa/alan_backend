<template>
   <div class="product-subcard-form">
     <h2 class="form-title">Создать подкарточку товара</h2>
     <form @submit.prevent="submitForm">
       <!-- Parent Product Card Dropdown -->
       <div class="form-group">
         <label>Выберите карточку товара</label>
         <select v-model="selectedProductCardId" required>
           <option disabled value="">— Выберите карточку товара —</option>
           <option v-for="card in productCards" :key="card.id" :value="card.id">
             {{ card.name_of_products }}
           </option>
         </select>
       </div>
 
       <!-- Subcard Name Input -->
       <div class="form-group">
         <label>Название подкарточки</label>
         <input type="text" v-model="form.name" placeholder="Введите название подкарточки" required />
       </div>
 
       
 
       <!-- Action Buttons -->
       <div class="form-actions">
         <button type="submit" class="submit-btn" :disabled="loading">
           {{ loading ? '⏳ Создание...' : 'Создать' }}
         </button>
         <button type="button" class="close-btn" @click="$emit('close')">Закрыть</button>
       </div>
     </form>
   </div>
 </template>
 
 <script>
 import axios from "axios";
 
 export default {
   name: "ProductSubCardPage",
   data() {
     return {
       // Form data for subcard creation
       form: {
         name: "",
         
       },
       // Selected parent product card ID
       selectedProductCardId: "",
       // List of product cards to choose from (populated from the backend)
       productCards: [],
       loading: false,
     };
   },
   created() {
     this.fetchProductCards();
   },
   methods: {
     async fetchProductCards() {
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           alert("Отсутствует токен. Войдите в систему.");
           return;
         }
         // Fetch the list of product cards (adjust the endpoint as needed)
         const response = await axios.get("/api/product_cards", {
           headers: { Authorization: `Bearer ${token}` },
         });
         this.productCards = response.data;
       } catch (error) {
         console.error("Ошибка загрузки карточек товара:", error);
         alert("Не удалось загрузить карточки товара.");
       }
     },
     async submitForm() {
       if (!this.selectedProductCardId) {
         alert("Выберите карточку товара.");
         return;
       }
       if (!this.form.name) {
         alert("Введите название подкарточки.");
         return;
       }
       this.loading = true;
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           alert("Отсутствует токен. Войдите в систему.");
           return;
         }
         // Build the payload. Adjust field names as needed.
         const payload = {
           product_card_id: this.selectedProductCardId,
           name: this.form.name,
           
         };
         const response = await axios.post("/api/product_subcards", payload, {
           headers: {
             Authorization: `Bearer ${token}`,
             "Content-Type": "application/json",
           },
         });
         // Emit the "saved" event with new subcard data
         this.$emit("saved", response.data);
       } catch (error) {
         console.error("Ошибка при создании подкарточки товара:", error);
         alert("Ошибка при создании подкарточки товара.");
       } finally {
         this.loading = false;
       }
     },
   },
 };
 </script>
 
 <style scoped>
 .product-subcard-form {
   max-width: 500px;
   margin: 0 auto;
   padding: 20px;
   background: #ffffff;
   border-radius: 10px;
   box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
 }
 .form-title {
   text-align: center;
   color: #0288d1;
   margin-bottom: 20px;
   font-size: 1.5rem;
 }
 .form-group {
   margin-bottom: 15px;
   display: flex;
   flex-direction: column;
 }
 .form-group label {
   margin-bottom: 5px;
   font-weight: bold;
   color: #555;
 }
 .form-group input[type="text"],
 .form-group input[type="number"],
 .form-group select {
   padding: 10px;
   border: 1px solid #ddd;
   border-radius: 5px;
   font-size: 14px;
 }
 .form-actions {
   display: flex;
   justify-content: space-between;
   gap: 10px;
   margin-top: 20px;
 }
 .submit-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   flex: 1;
 }
 .close-btn {
   background-color: #f44336;
   color: white;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   flex: 1;
 }
 </style>
 