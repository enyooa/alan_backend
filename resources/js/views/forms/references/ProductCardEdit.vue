<!-- src/pages/forms/references/ProductCardEdit.vue -->
<template>
    <div class="edit-form">
      <h3 class="title">Редактировать карточку товара</h3>

      <!-- Превью (старое или новое) -->
      <img
        v-if="previewUrl"
        :src="previewUrl"
        class="thumb"
        alt="Фото товара"
      />

      <form @submit.prevent="save">
        <!-- ───── название ───── -->
        <div class="form-group">
          <label for="name_of_products">Название товара</label>
          <input
            id="name_of_products"
            v-model="form.name_of_products"
            required
          />
        </div>

        <!-- ───── описание ───── -->
        <div class="form-group">
          <label for="description">Описание</label>
          <textarea id="description" v-model="form.description" rows="3" />
        </div>

        <!-- ───── страна ───── -->
        <div class="form-row">
          <div class="form-group">
            <label for="country">Страна</label>
            <input id="country" v-model="form.country" />
          </div>

          <div class="form-group">
            <label for="type">Тип</label>
            <input id="type" v-model="form.type" />
          </div>
        </div>

        <!-- ───── новое фото ───── -->
        <div class="form-group">
          <label for="photo_product">Фото товара (JPEG / PNG)</label>
          <input
            id="photo_product"
            type="file"
            accept="image/*"
            @change="handleFile"
          />
        </div>

        <!-- ───── кнопки ───── -->
        <div class="buttons">
          <button
            type="submit"
            class="btn primary"
            :disabled="loading"
          >
            {{ loading ? "⏳ Сохранение…" : "💾 Сохранить" }}
          </button>
          <button type="button" class="btn danger" @click="$emit('close')">
            Отмена
          </button>
        </div>
      </form>
    </div>
  </template>

  <script>
  import axios from "axios";
  import { ref } from "vue";

  export default {
    name: "ProductCardEdit",
    props: {
      operation: { type: Object, required: true },
    },
    setup(props, { emit }) {
      /* реактивные данные */
      const form = ref({
        name_of_products: props.operation.name_of_products ?? "",
        description: props.operation.description ?? "",
        country: props.operation.country ?? "",
        type: props.operation.type ?? "",
      });

      const photoFile = ref(null);
      const previewUrl = ref(props.operation.photo_url || null);
      const loading = ref(false);

      /* ─── handlers ─── */
      function handleFile(e) {
        const file = e.target.files?.[0];
        if (!file) return;
        photoFile.value = file;
        previewUrl.value = URL.createObjectURL(file); // локальный превью
      }

      async function save() {
        loading.value = true;
        try {
          const fd = new FormData();
          fd.append("_method", "PATCH");
          Object.entries(form.value).forEach(([k, v]) => fd.append(k, v));
          if (photoFile.value) fd.append("photo_product", photoFile.value);

          const { data } = await axios.post(
            `/api/references/productCard/${props.operation.id}`,
            fd,
            {
              headers: {
                Authorization: `Bearer ${localStorage.getItem("token")}`,
                "Content-Type": "multipart/form-data",
              },
            }
          );

          emit("saved", data); // вернём изменённую запись
        } catch (err) {
          console.error(err);
          alert("Не удалось сохранить изменения");
        } finally {
          loading.value = false;
        }
      }

      return { form, photoFile, previewUrl, loading, handleFile, save };
    },
  };
  </script>

  <style scoped>
  .edit-form {
    padding: 24px;
    max-width: 520px;
    margin: 0 auto;
  }
  .title {
    margin-bottom: 20px;
    text-align: center;
    color: #0288d1;
  }
  .form-group {
    margin-bottom: 15px;
    display: flex;
    flex-direction: column;
  }
  .form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
  }
  label {
    margin-bottom: 4px;
    font-weight: 600;
  }
  input,
  textarea {
    padding: 8px 10px;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    font-size: 14px;
    resize: vertical;
  }
  .thumb {
    max-width: 130px;
    max-height: 130px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 15px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.12);
  }
  .buttons {
    display: flex;
    gap: 12px;
    margin-top: 8px;
  }
  .btn {
    flex: 1;
    padding: 10px 0;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    cursor: pointer;
  }
  .primary {
    background: #0288d1;
    color: #fff;
  }
  .danger {
    background: #e53935;
    color: #fff;
  }
  .btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  </style>
