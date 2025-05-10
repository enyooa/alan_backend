<template>
   <div class="product-card-form">
     <h2 class="form-title">Создать карточку товара</h2>
     <form @submit.prevent="submitForm">
       <div class="form-group">
         <label>Наименование товара</label>
         <input type="text" v-model="form.name_of_products" required />
       </div>
       <div class="form-group">
         <label>Описание</label>
         <input type="text" v-model="form.description" />
       </div>
       <div class="form-group">
         <label>Страна</label>
         <input type="text" v-model="form.country" />
       </div>
       <div class="form-group">
         <label>Тип</label>
         <input type="text" v-model="form.type" />
       </div>
       <div class="form-group">
         <label>Фото товара</label>
         <input type="file" name="photo_product"
         accept="image/*"
         @change="handleFileUpload" />
       </div>
       <div class="form-actions">
         <button type="submit" class="submit-btn" :disabled="loading">
           {{ loading ? '⏳ Создание...' : 'Создать' }}
         </button>
         <button type="button" class="close-btn" @click="$emit('close')">
           Закрыть
         </button>
       </div>
     </form>
   </div>
 </template>

 <script>
 import axios from "axios";
 export default {
   name: "ProductCardPage",
   data() {
     return {
       form: {
         name_of_products: "",
         description: "",
         country: "",
         type: "",
       },
       photoFile: null,
       loading: false,
     };
   },
   methods: {
     handleFileUpload(event) {
       const files = event.target.files;
       if (files && files.length > 0) {
         this.photoFile = files[0];
       }
     },
     async submitForm() {
       // Basic validation
       if (!this.form.name_of_products) {
         alert("Введите наименование товара.");
         return;
       }
       this.loading = true;
       try {
         const token = localStorage.getItem("token");
         if (!token) {
           alert("Отсутствует токен. Войдите в систему.");
           return;
         }
         // Create FormData for file upload
         const formData = new FormData();
         formData.append("name_of_products", this.form.name_of_products);
         formData.append("description", this.form.description);
         formData.append("country", this.form.country);
         formData.append("type", this.form.type);
         if (this.photoFile) {
            formData.append("photo_product", this.photoFile);
         }
         // Replace '/api/product_cards' with your API endpoint if needed.
         const response = await axios.post("/api/product_card_create", formData, {
           headers: {
             Authorization: `Bearer ${token}`,
             "Content-Type": "multipart/form-data",
           },
         });
         // Emit saved event with the new product card data
         this.$emit("saved", response.data);
       } catch (error) {
         console.error("Ошибка при создании карточки товара:", error);
         alert("Ошибка при создании карточки товара.");
       } finally {
         this.loading = false;
       }
     },
   },
 };
 </script>

 <style scoped>
 .product-card-form {
   max-width: 500px;
   margin: 0 auto;
   padding: 20px;
 }
 .form-title {
   text-align: center;
   color: #0288d1;
   margin-bottom: 20px;
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
 .form-group input[type="file"] {
   padding: 10px;
   border: 1px solid #ddd;
   border-radius: 5px;
 }
 .form-actions {
   display: flex;
   justify-content: space-between;
   gap: 10px;
   margin-top: 20px;
 }
 .submit-btn {
   background-color: #0288d1;
   color: #fff;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   flex: 1;
 }
 .close-btn {
   background-color: #f44336;
   color: #fff;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   flex: 1;
 }
 </style>
