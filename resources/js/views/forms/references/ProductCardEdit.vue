<template>
    <div class="edit-form">
      <h3>Редактировать карточку товара</h3>
      <form @submit.prevent="save">
        <div class="form-group">
          <label for="name">Название товара:</label>
          <input
            type="text"
            v-model="form.name_of_products"
            id="name"
            required
          />
        </div>

        <div class="form-group">
          <label for="description">Описание:</label>
          <textarea
            v-model="form.description"
            id="description"
          ></textarea>
        </div>

        <div class="form-group">
          <label for="country">Страна:</label>
          <input
            type="text"
            v-model="form.country"
            id="country"
          />
        </div>

        <div class="form-group">
          <label for="type">Тип:</label>
          <input
            type="text"
            v-model="form.type"
            id="type"
          />
        </div>

        <!-- File input for new photo -->
        <div class="form-group">
          <label for="photo_product">Фото товара</label>
          <input
            type="file"
            id="photo_product"
            @change="handleFileUpload"
          />
        </div>

        <div class="buttons">
          <button type="submit" :disabled="loading">
            {{ loading ? "Сохранение..." : "Сохранить" }}
          </button>
          <button type="button" @click="$emit('close')">Отмена</button>
        </div>
      </form>
    </div>
  </template>

  <script>
  import axios from "axios";

  export default {
    name: "ProductCardEdit",
    props: {
      // We pass in the existing product data
      operation: {
        type: Object,
        default: () => ({}),
      },
    },
    data() {
      return {
        // Prepopulate from operation
        form: {
          name_of_products: this.operation.name_of_products || "",
          description: this.operation.description || "",
          country: this.operation.country || "",
          type: this.operation.type || "",
        },
        photoFile: null, // For storing the new uploaded file
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
      async save() {
        try {
          this.loading = true;
          const token = localStorage.getItem("token");
          if (!token) {
            alert("Токен не найден, авторизуйтесь.");
            return;
          }

          // 1) Create a FormData object
          const formData = new FormData();

          // 2) Add _method = PATCH
          formData.append("_method", "PATCH");

          // 3) Add all your fields
          formData.append("name_of_products", this.form.name_of_products);
          formData.append("description", this.form.description);
          formData.append("country", this.form.country);
          formData.append("type", this.form.type);

          // 4) If user selected a new photo:
          if (this.photoFile) {
            formData.append("photo_product", this.photoFile);
          }

          // 5) Instead of PATCH, call axios.post(...), letting _method=PATCH do the override
          const url = `/api/references/productCard/${this.operation.id}`;
          const response = await axios.post(url, formData, {
            headers: {
              Authorization: `Bearer ${token}`,
              "Content-Type": "multipart/form-data",
            },
          });

          // 6) If successful, emit 'saved' with the updated data
          this.$emit("saved", response.data);
        } catch (error) {
          console.error("Ошибка при сохранении:", error);
          alert("Ошибка при сохранении данных.");
        } finally {
          this.loading = false;
        }
      },
    },
  };
  </script>

  <style scoped>
  .edit-form {
    padding: 20px;
    max-width: 500px;
    margin: 0 auto;
  }
  .form-group {
    margin-bottom: 15px;
  }
  label {
    display: block;
    margin-bottom: 5px;
  }
  input,
  textarea {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
  }
  .buttons {
    display: flex;
    gap: 10px;
    margin-top: 15px;
  }
  button {
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
  }
  button[type="submit"] {
    background-color: #0288d1;
    color: #fff;
    flex: 1;
  }
  button[type="button"] {
    background-color: #f44336;
    color: #fff;
    flex: 1;
  }
  </style>
