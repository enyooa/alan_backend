<template>
   <div class="unit-form">
     <h2 class="form-title">Создать единицу измерения</h2>
     <form @submit.prevent="submitUnit">
       <div class="form-group">
         <label>Наименование единицы</label>
         <input
           type="text"
           v-model="unitName"
           placeholder="Введите наименование единицы"
           required
         />
       </div>
       <div v-if="errors.name" class="error-message">
        {{ errors.name.join(', ') }}
      </div>
       <div class="form-group">
         <label>Тара (г/кг/л)</label>
         <input
           type="number"
           v-model="tare"
           placeholder="Введите тару"
           
         />
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
   name: "UnitFormPage",
   data() {
     return {
       unitName: "",
       tare: "",
       loading: false,
       errors: {} 

     };
   },
   methods: {
    async submitUnit() {
  if (!this.unitName) {
    alert("Пожалуйста, заполните название");
    return;
  }
  this.loading = true;
  this.errors = {}; // reset errors
  try {
    const token = localStorage.getItem("token");
    if (!token) {
      alert("Отсутствует токен. Пожалуйста, войдите в систему.");
      return;
    }
    const response = await axios.post(
      "/api/unit-measurements",
      { name: this.unitName, tare: this.tare },
      { headers: { Authorization: `Bearer ${token}` } }
    );
    alert("Единица измерения успешно создана!");
    this.unitName = "";
    this.tare = "";
  } catch (error) {
    console.error("Ошибка при создании единицы измерения:", error);
    if (
      error.response &&
      error.response.data &&
      error.response.data.errors
    ) {
      // Assign errors to the errors property
      this.errors = error.response.data.errors;
    } else {
      alert("Ошибка при создании единицы измерения.");
    }
  } finally {
    this.loading = false;
  }
}

   },
 };
 </script>
 
 <style scoped>
 .unit-form {
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
 .form-group input {
   padding: 10px;
   border: 1px solid #ddd;
   border-radius: 5px;
   font-size: 14px;
 }
 .form-actions {
   display: flex;
   justify-content: center;
   margin-top: 20px;
 }
 .submit-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   font-size: 14px;
 }
 .submit-btn:hover {
   background-color: #026ca0;
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
 