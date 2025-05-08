<template>
    <div class="edit-form">
      <h3>Редактировать счёт</h3>

      <form @submit.prevent="save">
        <div class="form-group">
          <label for="name">Наименование счёта:</label>
          <input v-model="form.name" id="name" type="text" />
        </div>

        <!-- You may want to show IBAN but keep it readonly -->
        <div class="form-group">
          <label for="iban">IBAN (только чтение):</label>
          <input :value="operation.IBAN" id="iban" type="text" disabled />
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
    name: "CashEdit",
    props: {
      operation: { type: Object, default: () => ({}) },
    },
    data() {
      return {
        form: { name: this.operation.name || "" },
      };
    },
    methods: {
      async save() {
        try {
          const token = localStorage.getItem("token");
          await axios.patch(
            `/api/cashbox/${this.operation.id}`, // PATCH endpoint
            this.form,
            { headers: { Authorization: `Bearer ${token}` } }
          );
          // Send the updated data back to parent
          this.$emit("saved", { ...this.operation, ...this.form });
        } catch (e) {
          console.error("Ошибка при сохранении:", e);
          alert("Ошибка при сохранении счёта.");
        }
      },
    },
    watch: {
      operation: {
        immediate: true,
        handler(v) {
          if (v) this.form.name = v.name;
        },
      },
    },
  };
  </script>

  <style scoped>
  .edit-form { padding: 20px; }
  .form-group { margin-bottom: 15px; }
  label { display: block; margin-bottom: 5px; }
  input { width: 100%; padding: 8px; box-sizing: border-box; }
  .buttons { display: flex; gap: 10px; }
  </style>
