<template>
   <div class="price-offer-container">
     <h2 class="page-title">Редактировать ценовое предложение</h2>
 
     <!-- Card for Client, Address & Dates -->
     <div class="card">
       <div class="card-header">
         <h3>Клиент, Адрес, Даты</h3>
       </div>
       <div class="card-body">
         <div class="top-row">
           <!-- Client Dropdown -->
           <div class="dropdown-column">
             <label class="dropdown-label">Клиент</label>
             <select v-model="selectedClient" class="dropdown-select">
               <option value="">— Выберите клиента —</option>
               <option
                 v-for="client in clientList"
                 :key="client.client_id"
                 :value="client.client_id.toString()"
               >
                 {{ client.client_name }}
               </option>
             </select>
           </div>
 
           <!-- Address Dropdown -->
           <div class="dropdown-column">
             <label class="dropdown-label">Адрес</label>
             <select v-model="selectedAddress" class="dropdown-select">
               <option value="">— Выберите адрес —</option>
               <option
                 v-for="addr in getAddressesForClient(selectedClient)"
                 :key="addr.id"
                 :value="addr"
               >
                 {{ addr.name }}
               </option>
             </select>
           </div>
 
           <!-- Date Pickers -->
           <div class="dropdown-column">
             <label class="dropdown-label">Начальная дата</label>
             <input type="date" v-model="startDate" class="dropdown-select" />
           </div>
           <div class="dropdown-column">
             <label class="dropdown-label">Конечная дата</label>
             <input type="date" v-model="endDate" class="dropdown-select" />
           </div>
         </div>
       </div>
     </div>
 
     <!-- Cards Container for Products -->
     <div class="cards-container mt-3">
       <!-- Card for Product Table -->
       <div class="card product-card">
         <div class="card-header">
           <h3>Товары</h3>
         </div>
         <div class="card-body">
           <div v-if="loadingProductSubcards || loadingUnits" class="loading-indicator">
             <p>Загрузка...</p>
           </div>
           <div v-else-if="productSubcardsError || unitsError" class="error-message">
             <p>{{ productSubcardsError || unitsError }}</p>
           </div>
           <div v-else>
             <table class="styled-table">
               <thead>
                 <tr>
                   <th>Подкарточка</th>
                   <th>Партия</th>
                   <th>Остаток (ед. изм.)</th>
                   <th>Ед. изм.</th>
                   <th>Кол-во</th>
                   <th>Цена</th>
                   <th>Удалить</th>
                 </tr>
               </thead>
               <tbody>
                 <tr v-for="(row, index) in productRows" :key="index">
                   <!-- Product Subcard Dropdown -->
                   <td>
                     <select
                       v-model="row.product_subcard_id"
                       class="table-select"
                       @change="onSubcardChange(row)"
                     >
                       <option value="">—</option>
                       <option
                         v-for="subcard in productSubcards"
                         :key="subcard.id"
                         :value="subcard.id"
                       >
                         {{ subcard.name }}
                       </option>
                     </select>
                   </td>
                   <!-- Batch Dropdown -->
                   <td>
                     <select
                       v-if="row.product_subcard_id && getBatchesForSubcard(row.product_subcard_id).length"
                       v-model="row.selectedBatchId"
                       class="table-select"
                     >
                       <option value="">— Выберите партию —</option>
                       <option
                         v-for="batch in getBatchesForSubcard(row.product_subcard_id)"
                         :key="batch.id"
                         :value="batch.id"
                       >
                         {{ batch.quantity }} {{ batch.unit_measurement }} (Себестоимость: {{ batch.cost_price || batch.price }})
                       </option>
                     </select>
                     <span v-else>-</span>
                   </td>
                   <!-- Remaining Quantity -->
                   <td>
                     <span v-if="row.selectedBatchId">
                       {{ getBatchById(row.selectedBatchId)?.quantity }}
                     </span>
                     <span v-else-if="row.product_subcard_id">
                       {{ findSubcard(row.product_subcard_id)?.total_quantity || 0 }}
                     </span>
                     <span v-else>-</span>
                   </td>
                   <!-- Unit Measurement -->
                   <td>
                     <select v-model="row.unit_measurement" class="table-select">
                       <option value="">—</option>
                       <option
                         v-for="unit in units"
                         :key="unit.id"
                         :value="unit.name"
                       >
                         {{ unit.name }}
                       </option>
                     </select>
                   </td>
                   <!-- Amount -->
                   <td>
                     <input
                       type="number"
                       class="table-input"
                       v-model.number="row.amount"
                       @change="validateAmount(row)"
                     />
                   </td>
                   <!-- Price -->
                   <td>
                     <input
                       type="number"
                       class="table-input"
                       v-model.number="row.price"
                     />
                   </td>
                   <!-- Delete Button -->
                   <td>
                     <button class="remove-btn" @click="removeProductRow(index)">
                       ❌
                     </button>
                   </td>
                 </tr>
               </tbody>
             </table>
             <!-- "Добавить строку" Button -->
             <div class="mt-2">
               <button class="action-btn" @click="addProductRow">
                 ➕ Добавить строку
               </button>
             </div>
           </div>
         </div>
       </div>
 
       <!-- (Optional) Card for Cost Prices can remain here if needed -->
     </div>
 
     <!-- Submit Button and Global Message -->
     <div class="mt-3">
       <button class="action-btn save-btn" @click="updatePriceOffer">
         Сохранить изменения
       </button>
     </div>
     <div v-if="globalMessage" :class="['feedback-message', globalMessageType]">
       {{ globalMessage }}
     </div>
   </div>
 </template>
 
 <script>
 import { ref, onMounted } from "vue";
 import axios from "axios";
 
 export default {
   name: "PriceOfferEdit",
   props: {
     record: {
       type: Object,
       required: true,
     },
   },
   setup(props) {
     // Prepopulate fields from the record prop
     const selectedClient = ref(props.record.client_id);
     const selectedAddress = ref(props.record.address_id);
     const startDate = ref(props.record.start_date);
     const endDate = ref(props.record.end_date);
     // Assume record.price_offers is an array of product rows
     const productRows = ref(props.record.price_offers ? [...props.record.price_offers] : []);
     const globalMessage = ref("");
     const globalMessageType = ref("");
 
     // Data for clients, product subcards, and units
     const clientList = ref([]);
     const productSubcards = ref([]);
     const units = ref([]);
     const loadingProductSubcards = ref(false);
     const loadingUnits = ref(false);
     const productSubcardsError = ref("");
     const unitsError = ref("");
 
     // Fetch supporting data
     const fetchClientsAndAddresses = async () => {
       try {
         const { data: response } = await axios.get("/api/getClientAdresses");
         if (response.success) {
           clientList.value = response.data;
         }
       } catch (err) {
         console.error("Error fetching client & address data:", err);
       }
     };
 
     const fetchProductSubcards = async () => {
       loadingProductSubcards.value = true;
       try {
         const { data } = await axios.get("/api/product_subcards");
         productSubcards.value = data;
       } catch (err) {
         productSubcardsError.value = "Ошибка загрузки подкарточек.";
       } finally {
         loadingProductSubcards.value = false;
       }
     };
 
     const fetchUnits = async () => {
       loadingUnits.value = true;
       try {
         const { data } = await axios.get("/api/unit-measurements");
         units.value = data;
       } catch (err) {
         unitsError.value = "Ошибка загрузки единиц измерения.";
       } finally {
         loadingUnits.value = false;
       }
     };
 
     onMounted(async () => {
       await fetchClientsAndAddresses();
       await fetchProductSubcards();
       await fetchUnits();
     });
 
     // Product row helpers
     const addProductRow = () => {
       productRows.value.push({
         product_subcard_id: "",
         unit_measurement: "",
         amount: 0,
         price: 0,
         selectedBatchId: "",
       });
     };
 
     const removeProductRow = (idx) => {
       productRows.value.splice(idx, 1);
     };
 
     const findSubcard = (id) => {
       return productSubcards.value.find((sub) => sub.id === id) || null;
     };
 
     const getBatchesForSubcard = (subcardId) => {
       const subcard = findSubcard(subcardId);
       return subcard && subcard.batches ? subcard.batches : [];
     };
 
     const getBatchById = (batchId) => {
       for (const sub of productSubcards.value) {
         if (sub.batches) {
           const found = sub.batches.find((batch) => batch.id === batchId);
           if (found) return found;
         }
       }
       return null;
     };
 
     const onSubcardChange = (row) => {
       row.selectedBatchId = "";
       row.amount = 0;
     };
 
     const validateAmount = (row) => {
       if (row.selectedBatchId) {
         const batch = getBatchById(row.selectedBatchId);
         if (batch && row.amount > batch.quantity) {
           alert(`Количество для выбранной партии не может превышать ${batch.quantity}.`);
           row.amount = batch.quantity;
         }
       } else {
         const sub = findSubcard(row.product_subcard_id);
         if (sub && row.amount > (sub.total_quantity || 0)) {
           alert(`Количество для "${sub.name}" не может превышать общий остаток (${sub.total_quantity || 0}).`);
           row.amount = sub.total_quantity || 0;
         }
       }
     };
 
     const getAddressesForClient = (clientId) => {
       if (!clientId) return [];
       const client = clientList.value.find(
         (c) => c.client_id.toString() === clientId.toString()
       );
       return client ? client.addresses : [];
     };
 
     // Submit updated price offer
     const updatePriceOffer = async () => {
       if (
         !selectedClient.value ||
         !selectedAddress.value ||
         !startDate.value ||
         !endDate.value ||
         productRows.value.length === 0
       ) {
         alert("Заполните все поля (клиент, адрес, даты и товары)!");
         return;
       }
 
       let totalSum = 0;
       productRows.value.forEach((row) => {
         totalSum += (row.amount || 0) * (row.price || 0);
       });
 
       const rows = productRows.value.map((row) => {
         const payload = {
           product_subcard_id: row.product_subcard_id,
           unit_measurement: row.unit_measurement,
           amount: row.amount,
           price: row.price,
           address_id: selectedAddress.value.id,
         };
         if (row.selectedBatchId) {
           payload.batch_id = row.selectedBatchId;
         }
         return payload;
       });
 
       const payload = {
         client_id: selectedClient.value,
         address_id: selectedAddress.value,
         start_date: startDate.value,
         end_date: endDate.value,
         totalsum: totalSum,
         price_offers: rows,
       };
 
       try {
         // Update endpoint – adjust if necessary.
         await axios.patch(`/api/priceOffers/${props.record.id}`, payload, {
           headers: { "Content-Type": "application/json" },
         });
         globalMessage.value = `Изменения сохранены! Итоговая сумма = ${totalSum.toFixed(2)}`;
         globalMessageType.value = "success";
       } catch (err) {
         globalMessage.value = "Ошибка при сохранении изменений.";
         globalMessageType.value = "error";
       }
     };
 
     return {
       selectedClient,
       selectedAddress,
       startDate,
       endDate,
       clientList,
       productSubcards,
       units,
       loadingProductSubcards,
       loadingUnits,
       productSubcardsError,
       unitsError,
       productRows,
       globalMessage,
       globalMessageType,
       getAddressesForClient,
       findSubcard,
       getBatchesForSubcard,
       getBatchById,
       onSubcardChange,
       validateAmount,
       addProductRow,
       removeProductRow,
       updatePriceOffer,
     };
   },
 };
 </script>
 
 <style scoped>
 /* Use the same styles as in your PriceOfferPage, for example: */
 
 .price-offer-container {
   max-width: 1100px;
   margin: 0 auto;
   padding: 20px;
 }
 .page-title {
   text-align: center;
   color: #0288d1;
   margin-bottom: 20px;
 }
 
 /* Card styles */
 .card {
   background-color: #fff;
   border-radius: 10px;
   box-shadow: 0 4px 12px rgba(0,0,0,0.1);
   margin-bottom: 20px;
   overflow: hidden;
 }
 .card-header {
   background-color: #f1f1f1;
   padding: 12px 16px;
   border-bottom: 1px solid #ddd;
 }
 .card-header h3 {
   margin: 0;
   color: #333;
 }
 .card-body {
   padding: 16px;
 }
 .mt-3 {
   margin-top: 20px;
 }
 
 /* Top row */
 .top-row {
   display: flex;
   flex-wrap: wrap;
   gap: 10px;
 }
 .dropdown-column {
   flex: 1;
   min-width: 200px;
   display: flex;
   flex-direction: column;
 }
 .dropdown-label {
   font-weight: bold;
   color: #555;
   margin-bottom: 4px;
 }
 .dropdown-select {
   padding: 10px;
   border: 1px solid #ddd;
   border-radius: 6px;
   font-size: 14px;
 }
 
 /* Table styles */
 .table-container {
   background-color: #fff;
   padding: 20px;
   border-radius: 8px;
   overflow-x: auto;
   box-shadow: 0 3px 8px rgba(0,0,0,0.1);
 }
 .styled-table {
   width: 100%;
   border-collapse: collapse;
 }
 .styled-table thead tr {
   background-color: #0288d1;
   color: #fff;
 }
 .styled-table th,
 .styled-table td {
   padding: 10px;
   border: 1px solid #ddd;
   text-align: center;
 }
 
 /* Buttons */
 .action-btn {
   background-color: #0288d1;
   color: #fff;
   border: none;
   border-radius: 8px;
   padding: 10px 14px;
   cursor: pointer;
   font-size: 14px;
 }
 .action-btn:hover {
   background-color: #026ca0;
 }
 .remove-btn {
   background-color: #f44336;
   color: #fff;
   border: none;
   border-radius: 6px;
   padding: 8px 10px;
   cursor: pointer;
   font-size: 14px;
 }
 .remove-btn:hover {
   background-color: #d32f2f;
 }
 .save-btn {
   width: 100%;
   margin-top: 10px;
 }
 
 /* Table inputs/selects */
 .table-select,
 .table-input {
   width: 100%;
   padding: 8px;
   border: 1px solid #ddd;
   border-radius: 6px;
   font-size: 14px;
 }
 
 /* Feedback messages */
 .feedback-message {
   margin-top: 20px;
   text-align: center;
   font-weight: bold;
   padding: 10px;
   border-radius: 8px;
 }
 .feedback-message.success {
   background-color: #d4edda;
   color: #155724;
 }
 .feedback-message.error {
   background-color: #f8d7da;
   color: #721c24;
 }
 </style>
 