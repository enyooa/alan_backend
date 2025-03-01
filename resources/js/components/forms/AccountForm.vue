<template>
   
         <main class="content">
            <h1>Аккаунты</h1>

            <!-- Buttons for Creating Sections -->
            <div class="buttons-container">
               <button class="add-btn" @click="toggleModal('addWarehouse')">Создать склад</button>
               <button class="add-btn" @click="toggleModal('addPackaging')">Создать фасовка</button>
               <button class="add-btn" @click="toggleModal('addCashbox')">Создать касса</button>
               <button class="add-btn" @click="toggleModal('addCourier')">Создать курьер</button>
            </div>

            <!-- Section Tables -->
            <div class="sections">
               <div class="section">
                  <h2>Склады</h2>
                  <ul>
                     <li v-for="warehouse in warehouses" :key="warehouse.id">
                        {{ warehouse.name }}
                        <span class="actions">
                           <button @click="editWarehouse(warehouse.id)">редактировать</button>
                           <button @click="deleteWarehouse(warehouse.id)">удалить</button>
                        </span>
                     </li>
                  </ul>
               </div>

               <div class="section">
                  <h2>Фасовка</h2>
                  <ul>
                     <li v-for="packaging in packagings" :key="packaging.id">
                        {{ packaging.name }}
                        <span class="actions">
                           <button @click="editPackaging(packaging.id)">редактировать</button>
                           <button @click="deletePackaging(packaging.id)">удалить</button>
                        </span>
                     </li>
                  </ul>
               </div>

               <div class="section">
                  <h2>Касса</h2>
                  <ul>
                     <li v-for="cashbox in cashboxes" :key="cashbox.id">
                        {{ cashbox.name }}
                        <span class="actions">
                           <button @click="editCashbox(cashbox.id)">редактировать</button>
                           <button @click="deleteCashbox(cashbox.id)">удалить</button>
                        </span>
                     </li>
                  </ul>
               </div>

               <div class="section">
                  <h2>Курьеры</h2>
                  <ul>
                     <li v-for="courier in couriers" :key="courier.id">
                        {{ courier.name }}
                        <span class="actions">
                           <button @click="editCourier(courier.id)">редактировать</button>
                           <button @click="deleteCourier(courier.id)">удалить</button>
                        </span>
                     </li>
                  </ul>
               </div>
            </div>

            <!-- Modals -->
            <div v-if="showModal.addWarehouse" class="modal">
               <h2>Добавить склад</h2>
               <form @submit.prevent="addWarehouse">
                  <label>Наименование склада</label>
                  <input type="text" v-model="newWarehouse" required />
                  <button type="submit">Добавить</button>
                  <button type="button" @click="toggleModal('addWarehouse')">Закрыть</button>
               </form>
            </div>

            <div v-if="showModal.addPackaging" class="modal">
               <h2>Добавить фасовка</h2>
               <form @submit.prevent="addPackaging">
                  <label>Наименование фасовка</label>
                  <input type="text" v-model="newPackaging" required />
                  <button type="submit">Добавить</button>
                  <button type="button" @click="toggleModal('addPackaging')">Закрыть</button>
               </form>
            </div>

            <div v-if="showModal.addCashbox" class="modal">
               <h2>Добавить касса</h2>
               <form @submit.prevent="addCashbox">
                  <label>Наименование касса</label>
                  <input type="text" v-model="newCashbox" required />
                  <button type="submit">Добавить</button>
                  <button type="button" @click="toggleModal('addCashbox')">Закрыть</button>
               </form>
            </div>

            <div v-if="showModal.addCourier" class="modal">
               <h2>Добавить курьер</h2>
               <form @submit.prevent="addCourier">
                  <label>Наименование курьер</label>
                  <input type="text" v-model="newCourier" required />
                  <button type="submit">Добавить</button>
                  <button type="button" @click="toggleModal('addCourier')">Закрыть</button>
               </form>
            </div>
         </main>
      
</template>

<script>

export default {
   data() {
      return {
         warehouses: [],
         packagings: [],
         cashboxes: [],
         couriers: [],
         newWarehouse: "",
         newPackaging: "",
         newCashbox: "",
         newCourier: "",
         showModal: {
            addWarehouse: false,
            addPackaging: false,
            addCashbox: false,
            addCourier: false,
         },
      };
   },
   methods: {
      toggleModal(modal) {
         this.showModal[modal] = !this.showModal[modal];
      },
      addWarehouse() {
         this.warehouses.push({ id: Date.now(), name: this.newWarehouse });
         this.newWarehouse = "";
         this.toggleModal("addWarehouse");
      },
      addPackaging() {
         this.packagings.push({ id: Date.now(), name: this.newPackaging });
         this.newPackaging = "";
         this.toggleModal("addPackaging");
      },
      addCashbox() {
         this.cashboxes.push({ id: Date.now(), name: this.newCashbox });
         this.newCashbox = "";
         this.toggleModal("addCashbox");
      },
      addCourier() {
         this.couriers.push({ id: Date.now(), name: this.newCourier });
         this.newCourier = "";
         this.toggleModal("addCourier");
      },
      deleteWarehouse(id) {
         this.warehouses = this.warehouses.filter(item => item.id !== id);
      },
      deletePackaging(id) {
         this.packagings = this.packagings.filter(item => item.id !== id);
      },
      deleteCashbox(id) {
         this.cashboxes = this.cashboxes.filter(item => item.id !== id);
      },
      deleteCourier(id) {
         this.couriers = this.couriers.filter(item => item.id !== id);
      },
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
   padding: 20px;
   background-color: #f5f5f5;
}

.content {
   max-width: 1200px;
   margin: auto;
}

h1 {
   text-align: center;
   margin-bottom: 20px;
}

.buttons-container {
   display: flex;
   gap: 10px;
   justify-content: center;
   margin-bottom: 20px;
}

.add-btn {
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   background-color: #0288d1;
   color: white;
   cursor: pointer;
}

.sections {
   display: grid;
   grid-template-columns: repeat(2, 1fr);
   gap: 20px;
}

.section {
   background: white;
   border-radius: 8px;
   padding: 15px;
   box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.actions button {
   margin-left: 10px;
   padding: 5px 10px;
   border: none;
   border-radius: 5px;
   background-color: #d32f2f;
   color: white;
   cursor: pointer;
}

.modal {
   position: fixed;
   top: 50%;
   left: 50%;
   transform: translate(-50%, -50%);
   background: white;
   padding: 20px;
   border-radius: 8px;
   box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
   z-index: 1000;
}

.modal h2 {
   margin-bottom: 15px;
}

button {
   margin-top: 10px;
}
</style>
