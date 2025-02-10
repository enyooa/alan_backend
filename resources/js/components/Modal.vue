<template>
   <div v-if="isVisible" class="modal-backdrop" @click.self="close">
     <div class="modal">
       <header class="modal-header">
         <slot name="header">
           <h2>Default Header</h2>
         </slot>
         <button type="button" class="close-btn" @click="close">×</button>
       </header>
       <section class="modal-body">
         <slot name="body">
           <p>Default body content</p>
         </slot>
       </section>
     </div>
   </div>
 </template>
 
 <script>
 export default {
   props: {
     isVisible: {
       type: Boolean,
       required: true,
     },
   },
   methods: {
     close() {
       this.$emit("close");
     },
   },
 };
 </script>
 
 <style scoped>
 .modal-backdrop {
   position: fixed;
   top: 0;
   left: 0;
   right: 0;
   bottom: 0;
   background-color: rgba(0, 0, 0, 0.5);
   display: flex;
   align-items: center;
   justify-content: center;
   z-index: 1000;
 }
 
 .modal {
   background: white;
   padding: 20px;
   border-radius: 8px;
   width: 90%;
   max-width: 500px;
   box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
 }
 
 .modal-header {
   display: flex;
   justify-content: space-between;
   align-items: center;
 }
 
 .close-btn {
   background: none;
   border: none;
   font-size: 1.5rem;
   cursor: pointer;
 }
 </style>
 