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
               <button class="add-btn" @click="toggleModal('addProduct')">Создать карточку товара</button>
               <button class="add-btn" @click="toggleModal('addCategory')">Добавить категорию товара</button>
               <button class="add-btn" @click="toggleModal('addSection')">Добавить раздел</button>
               <button class="add-btn" @click="toggleModal('addUnit')">Добавить ед. изм</button>
            </div>

            <!-- Add Product Modal -->
            <div v-if="showModal.addProduct" class="popup-modal stylish-form">
               <h2>Создать карточку товара</h2>
               <form @submit.prevent="addProduct">
                  <label>Тип</label>
                  <select v-model="newProduct.type" required class="form-input">
                     <option v-for="section in sections" :key="section" :value="section">{{ section }}</option>
                  </select>
                  <label>Наименование товара</label>
                  <input type="text" v-model="newProduct.name_of_products" placeholder="Введите наименование" required class="form-input" />
                  <label>Характеристика товара</label>
                  <input type="text" v-model="newProduct.description" placeholder="Введите характеристику" class="form-input" />
                  <label>Страна производитель</label>
                  <input type="text" v-model="newProduct.country" placeholder="Введите страну производитель" class="form-input" />
                  <button type="submit" class="submit-btn">Создать карточку</button>
               </form>
               <button class="close-btn" @click="toggleModal('addProduct')">Закрыть</button>
            </div>

            <!-- Add Category Modal -->
            <div v-if="showModal.addCategory" class="popup-modal stylish-form">
               <h2>Добавить категорию товара</h2>
               <form @submit.prevent="addCategory">
                  <label>Товар</label>
                  <select v-model="newCategory.product" required class="form-input">
                     <option v-for="product in products" :key="product.name" :value="product.name">{{ product.name }}</option>
                  </select>
                  <label>Наименование товара</label>
                  <input type="text" v-model="newCategory.name" placeholder="Введите наименование" required class="form-input" />
                  <label>Характеристика</label>
                  <input type="text" v-model="newCategory.description" placeholder="Введите характеристику" class="form-input" />
                  <label>Страна</label>
                  <input type="text" v-model="newCategory.country" placeholder="Введите страну" class="form-input" />
                  <button type="submit" class="submit-btn">Добавить категорию</button>
               </form>
               <button class="close-btn" @click="toggleModal('addCategory')">Закрыть</button>
            </div>

            <!-- Add Section Modal -->
            <div v-if="showModal.addSection" class="popup-modal stylish-form">
               <h2>Добавить раздел</h2>
               <form @submit.prevent="addSection">
                  <label>Наименование раздела</label>
                  <input type="text" v-model="newSection" placeholder="Введите наименование раздела" required class="form-input" />
                  <button type="submit" class="submit-btn">Добавить раздел</button>
               </form>
               <button class="close-btn" @click="toggleModal('addSection')">Закрыть</button>
            </div>

            <!-- Add Unit Modal -->
            <div v-if="showModal.addUnit" class="popup-modal stylish-form">
               <h2>Добавить единицу измерения</h2>
               <form @submit.prevent="addUnit">
                  <label>Наименование ед. изм</label>
                  <input type="text" v-model="newUnit.name" placeholder="Введите наименование ед. изм" class="form-input" />

                  <label>Тара (граммы, кг, литры)</label>
                  <input type="number" step="0.01" v-model="newUnit.tare" placeholder="Введите тару" class="form-input" />

                  <button type="submit" class="submit-btn">Добавить ед. изм</button>
               </form>
               <button class="close-btn" @click="toggleModal('addUnit')">Закрыть</button>
            </div>
         </main>
      </div>
   </div>
</template>

<script>
import axios from "axios";
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue";

export default {
   components: { Sidebar, Header },
   data() {
      return {
         isSidebarOpen: true,
         sections: ["Фрукты", "Овощи"],
         products: [],
         newProduct: { type: "", name_of_products: "", description: "", country: "" },
         newCategory: { product: "", name: "", description: "", country: "" },
         newSection: "",
         newUnit: { name: "", tare: "" },
         unitChoice: "name",  // Default option for unit input
         showModal: { addProduct: false, addCategory: false, addSection: false, addUnit: false },
      };
   },
   methods: {
      toggleSidebar() {
         this.isSidebarOpen = !this.isSidebarOpen;
      },
      toggleModal(modalName) {
         this.showModal[modalName] = !this.showModal[modalName];
      },
      async addProduct() {
         try {
            const response = await axios.post("/api/product_card_create", this.newProduct, {
               headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
            });
            this.products.push(response.data.data);
            this.newProduct = { type: "", name_of_products: "", description: "", country: "" };
            this.toggleModal("addProduct");
            alert("✅ Карточка товара успешно создана!");
         } catch (error) {
            console.error("Ошибка создания карточки товара:", error);
            alert("❌ Не удалось создать карточку товара.");
         }
      },
      async addCategory() {
         try {
            await axios.post("/api/categories", this.newCategory, {
               headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
            });
            alert("Категория добавлена");
            this.newCategory = { product: "", name: "", description: "", country: "" };
            this.toggleModal("addCategory");
         } catch (error) {
            console.error("Ошибка добавления категории:", error);
         }
      },
      async addSection() {
         try {
            await axios.post("/api/sections", { name: this.newSection }, {
               headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
            });
            alert("Раздел добавлен");
            this.sections.push(this.newSection);
            this.newSection = "";
            this.toggleModal("addSection");
         } catch (error) {
            console.error("Ошибка добавления раздела:", error);
         }
      },
      async addUnit() {
         try {
            if (!this.newUnit.name && this.newUnit.tare === null) {
               alert("Пожалуйста, заполните хотя бы одно поле.");
               return;
            }

            const payload = {
               name: this.newUnit.name || null,
               tare: this.newUnit.tare !== null ? parseFloat(this.newUnit.tare) : null,
            };

            await axios.post("/api/unit-measurements", payload, {
               headers: { Authorization: `Bearer ${localStorage.getItem("token")}` }
            });

            alert("Единица измерения добавлена!");
            this.newUnit = { name: "", tare: null };
            this.toggleModal("addUnit");
         } catch (error) {
            console.error("Ошибка добавления ед. изм:", error);
            alert("❌ Не удалось добавить единицу измерения.");
         }
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
   display: flex;
   flex-direction: column;
   background-color: #f5f5f5;
}

.content {
   padding: 20px;
   display: flex;
   flex-direction: column;
   align-items: center;
}

.action-buttons {
   display: flex;
   gap: 10px;
   margin-bottom: 20px;
}

.add-btn {
   background-color: #0288d1;
   color: white;
   padding: 12px 20px;
   border: none;
   border-radius: 8px;
   cursor: pointer;
   font-size: 16px;
   transition: background-color 0.3s;
}

.add-btn:hover {
   background-color: #026ca0;
}

.popup-modal {
   position: fixed;
   top: 50%;
   left: 50%;
   transform: translate(-50%, -50%);
   background: #fff;
   padding: 30px;
   border-radius: 10px;
   box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
   width: 90%;
   max-width: 600px;
   z-index: 1000;
   animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
   from { opacity: 0; }
   to { opacity: 1; }
}

.form-input {
   width: 100%;
   padding: 12px;
   margin-bottom: 15px;
   border-radius: 8px;
   border: 1px solid #ddd;
   font-size: 16px;
   box-sizing: border-box;
   transition: border-color 0.3s;
}

.form-input:focus {
   border-color: #0288d1;
   outline: none;
}

.submit-btn {
   background-color: #0288d1;
   color: white;
   padding: 12px 20px;
   border: none;
   border-radius: 8px;
   font-size: 16px;
   cursor: pointer;
   transition: background-color 0.3s;
}

.submit-btn:hover {
   background-color: #026ca0;
}

.close-btn {
   background-color: #ff4d4d;
   color: white;
   padding: 10px 20px;
   border: none;
   border-radius: 8px;
   font-size: 16px;
   cursor: pointer;
   transition: background-color 0.3s;
}

.close-btn:hover {
   background-color: #e03e3e;
}
</style>
