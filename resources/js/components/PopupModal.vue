<template>
   <div class="modal-overlay" @click.self="$emit('close')">
     <div class="modal-container">
       <h2>{{ title }}</h2>
       <slot></slot>
       <button class="close-btn" @click="$emit('close')">Закрыть</button>
     </div>
   </div>
 </template>
 
 <script>
 export default {
   props: {
     title: {
       type: String,
       required: true
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
   z-index: 1000;
 }
 
 .modal-container {
   background: white;
   padding: 20px;
   border-radius: 10px;
   box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
   width: 90%;
   max-width: 400px;
 }
 
 .close-btn {
   background-color: #ff4d4d;
   color: white;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   width: 100%;
   margin-top: 10px;
 }
 
 .close-btn:hover {
   background-color: #d32f2f;
 }
 </style>
 