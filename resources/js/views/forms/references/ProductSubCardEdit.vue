<template>
   <div class="edit-form">
     <h3>Редактировать подкарточку</h3>
     <form @submit.prevent="save">
       <div class="form-group">
         <label for="product_card_id">ID карточки товара:</label>
         <input type="text" v-model="form.product_card_id" id="product_card_id" />
       </div>
       <div class="form-group">
         <label for="name">Название подкарточки:</label>
         <input type="text" v-model="form.name" id="name" />
       </div>
       <div class="form-group">
         <label for="brutto">Брутто:</label>
         <input type="number" v-model="form.brutto" id="brutto" />
       </div>
       <div class="form-group">
         <label for="netto">Нетто:</label>
         <input type="number" v-model="form.netto" id="netto" />
       </div>
       <div class="buttons">
         <button type="submit">Сохранить</button>
         <button type="button" @click="$emit('close')">Отмена</button>
       </div>
     </form>
   </div>
 </template>

 <script>
 import axios from "axios";
 export default {
   name: "ProductSubCardEdit",
   props: {
     operation: {
       type: Object,
       default: () => ({}),
     },
   },
   data() {
     return {
       form: {
         product_card_id: this.operation.product_card_id || "",
         name: this.operation.name || "",
         brutto: this.operation.brutto || "",
         netto: this.operation.netto || "",
       },
     };
   },
   methods: {
     async save() {
       try {
         const token = localStorage.getItem("token");
         const response = await axios.patch(
           `/api/references/subproductCard/${this.operation.id}`,
           this.form,
           { headers: { Authorization: `Bearer ${token}` } }
         );
         this.$emit("saved", response.data);
       } catch (error) {
         console.error("Ошибка при сохранении:", error);
         alert("Ошибка при сохранении данных.");
       }
     },
   },
   watch: {
     operation: {
       handler(newVal) {
         if (newVal) {
           this.form.product_card_id = newVal.product_card_id;
           this.form.name = newVal.name;
           this.form.brutto = newVal.brutto;
           this.form.netto = newVal.netto;
         }
       },
       immediate: true,
     },
   },
 };
 </script>

 <style scoped>
 .edit-form {
   padding: 20px;
 }
 .form-group {
   margin-bottom: 15px;
 }
 label {
   display: block;
   margin-bottom: 5px;
 }
 input {
   width: 100%;
   padding: 8px;
   box-sizing: border-box;
 }
 .buttons {
   display: flex;
   gap: 10px;
 }
 </style>
