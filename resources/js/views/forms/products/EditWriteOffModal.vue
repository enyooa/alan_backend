<template>
    <div class="edit-writeoff-modal">
      <div class="modal-header">
        <h2>Редактировать «Списание» (ID: {{ docHeader.id }})</h2>
        <button class="close-btn" @click="$emit('close')">✖</button>
      </div>

      <div class="modal-body">
        <!-- 1) Write-off Header -->
        <div class="card">
          <div class="card-header">
            <h3>Основная информация</h3>
          </div>
          <div class="card-body">
            <div class="form-row">
              <!-- from_warehouse_id -->
              <div class="form-group">
                <label>Склад (from_warehouse_id)</label>
                <select v-model="docHeader.from_warehouse_id" class="form-control">
                  <option disabled value="">— Выберите склад —</option>
                  <option
                    v-for="wh in warehouses"
                    :key="wh.id"
                    :value="wh.id"
                  >
                    {{ wh.name }}
                  </option>
                </select>
              </div>
              <!-- Document date -->
              <div class="form-group">
                <label>Дата</label>
                <input
                  type="date"
                  v-model="docHeader.document_date"
                  class="form-control"
                />
              </div>
            </div>
          </div>
        </div>

        <!-- 2) Document Items table -->
        <div class="card mt-2">
          <div class="card-header flex-between">
            <h3>Позиции для списания</h3>
            <button class="action-btn" @click="addItemRow">
              ➕ Добавить строку
            </button>
          </div>
          <div class="card-body">
            <table class="styled-table">
              <thead>
                <tr>
                  <th>Товар</th>
                  <th>Кол-во</th>
                  <th>Ед.изм</th>
                  <th>Удалить</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="(row, idx) in itemRows"
                  :key="row._key"
                >
                  <td>
                    <select
                      v-model="row.product_subcard_id"
                      class="form-control"
                    >
                      <option disabled value="">
                        — Товар —
                      </option>
                      <option
                        v-for="p in products"
                        :key="p.id"
                        :value="p.id"
                      >
                        {{ p.name }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <input
                      type="number"
                      class="form-control"
                      v-model.number="row.quantity"
                    />
                  </td>
                  <td>
                    <select
                      v-model="row.unit_measurement"
                      class="form-control"
                    >
                      <option disabled value="">— Ед.изм —</option>
                      <option
                        v-for="u in units"
                        :key="u.id"
                        :value="u.name"
                      >
                        {{ u.name }}
                      </option>
                    </select>
                  </td>
                  <td>
                    <button
                      class="remove-btn"
                      @click="removeItemRow(idx)"
                    >
                      ❌
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 3) Modal Footer -->
      <div class="modal-footer">
        <button
          class="action-btn save-btn"
          @click="saveWriteOffDoc"
          :disabled="isSaving"
        >
          {{ isSaving ? "Сохранение..." : "Сохранить" }}
        </button>
        <button
          class="cancel-btn"
          @click="$emit('close')"
        >
          Отмена
        </button>

        <div
          v-if="message"
          :class="['feedback-message', messageType]"
        >
          {{ message }}
        </div>
      </div>
    </div>
  </template>

  <script>
  import { ref, onMounted } from "vue";
  import axios from "axios";

  export default {
    name: "EditWriteOffModal",
    props: {
      // The doc ID to edit
      documentId: {
        type: Number,
        required: true
      }
    },
    setup(props, { emit }) {
      // 1) Document header fields
      const docHeader = ref({
        id: null,
        from_warehouse_id: "",
        document_date: ""
      });

      // 2) The item rows (from document_items)
      const itemRows = ref([]);

      // 3) Reference data (warehouses, products, units)
      const warehouses = ref([]);
      const products = ref([]);
      const units = ref([]);

      // 4) UI states
      const isSaving = ref(false);
      const message = ref("");
      const messageType = ref("");

      // On mount, fetch references + load doc data
      onMounted(() => {
        fetchReferences();
        loadDocumentData(props.documentId);
      });

      // A) fetch references
      async function fetchReferences() {
        try {
          const resp = await axios.get("/api/getWarehouseDetails");
          warehouses.value = resp.data.warehouses || [];
          products.value = resp.data.product_sub_cards || [];
          units.value = resp.data.unit_measurements || [];
        } catch (err) {
          console.error("Error loading references:", err);
        }
      }

      // B) load doc data from /api/documents/{docId}
      async function loadDocumentData(docId) {
        try {
          const resp = await axios.get(`/api/documents/${docId}`);
          // Example shape:
          // {
          //   id, from_warehouse_id, document_date,
          //   document_items: [ {...}, {...} ],
          //   document_type: { code: "write_off" }
          // }
          const data = resp.data;

          docHeader.value.id = data.id;
          docHeader.value.from_warehouse_id = data.from_warehouse_id || "";
          if (data.document_date && data.document_date.length >= 10) {
            docHeader.value.document_date = data.document_date.substring(0, 10);
          }

          // parse doc items into itemRows
          itemRows.value = data.document_items.map(it => ({
            _key: it.id,
            id: it.id,
            product_subcard_id: it.product_subcard_id,
            quantity: it.quantity,
            unit_measurement: it.unit_measurement
          }));
        } catch (err) {
          console.error("Error loading doc data:", err);
        }
      }

      // C) add/remove item row
      function addItemRow() {
        itemRows.value.push({
          _key: Date.now(),
          id: null,
          product_subcard_id: "",
          quantity: 0,
          unit_measurement: ""
        });
      }
      function removeItemRow(idx) {
        itemRows.value.splice(idx, 1);
      }

      // D) Save changes (PUT /api/documents/{id})
      async function saveWriteOffDoc() {
        isSaving.value = true;
        message.value = "";
        messageType.value = "";

        try {
          // Build "products" payload for doc items
          const productsPayload = itemRows.value.map(r => ({
            id: r.id,  // if existing => update, else create
            product_subcard_id: r.product_subcard_id,
            quantity: r.quantity,
            unit_measurement: r.unit_measurement,
            // brutto, etc. if needed
          }));

          // Build the top-level doc payload
          const payload = {
            // We are following your "update" method’s example:
            // 'assigned_warehouse_id' might be for "to_warehouse_id" in income,
            // but for a write-off doc, you might rename it "from_warehouse_id" in the backend.
            // If you do all in the same update method, you might handle this logic in the controller.
            // For now, let's pass it as "from_warehouse_id" or "assigned_warehouse_id" depending on how your backend works

            // We'll do "assigned_warehouse_id" but you can rename if you prefer a separate from_warehouse_id field.
            assigned_warehouse_id: docHeader.value.from_warehouse_id,
            document_date: docHeader.value.document_date,

            // The update() method expects "products" => [...]
            products: productsPayload,
            // if you have "expenses", you can pass them too, or skip
            expenses: []
          };

          // PUT /api/documents/{id}
          await axios.put(`/api/writeoff_update/${docHeader.value.id}`, payload);

          message.value = "Списание успешно сохранено!";
          messageType.value = "success";
          // notify parent
          emit("saved");
        } catch (err) {
          console.error("Error saving write-off:", err);
          message.value = "Ошибка при сохранении списания.";
          messageType.value = "error";
        } finally {
          isSaving.value = false;
        }
      }

      return {
        // data
        docHeader,
        itemRows,
        warehouses,
        products,
        units,

        // UI
        isSaving,
        message,
        messageType,

        // methods
        fetchReferences,
        loadDocumentData,
        addItemRow,
        removeItemRow,
        saveWriteOffDoc
      };
    }
  };
  </script>

  <style scoped>
  .edit-writeoff-modal {
    background-color: #fff;
    width: 900px;
    max-width: 90%;
    border-radius: 10px;
    margin: 20px auto;
    box-shadow: 0 5px 20px rgba(0,0,0,0.2);
  }
  .modal-header {
    background-color: #f44336;
    color: #fff;
    padding: 16px;
    position: relative;
  }
  .modal-header h2 {
    margin: 0;
    font-size: 1.2rem;
  }
  .close-btn {
    position: absolute;
    top: 12px;
    right: 16px;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 20px;
    cursor: pointer;
  }
  .modal-body {
    padding: 16px;
  }
  .modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 16px;
    border-top: 1px solid #ddd;
  }

  .card {
    border: 1px solid #ddd;
    border-radius: 6px;
    margin-bottom: 12px;
    background-color: #fefefe;
  }
  .card-header {
    background-color: #f1f1f1;
    padding: 8px 12px;
  }
  .mt-2 {
    margin-top: 10px;
  }
  .flex-between {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .styled-table {
    width: 100%;
    border-collapse: collapse;
  }
  .styled-table thead {
    background-color: #0288d1;
    color: #fff;
  }
  .styled-table th,
  .styled-table td {
    border: 1px solid #ddd;
    text-align: center;
    padding: 8px;
  }
  .form-row {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
  }
  .form-group {
    flex: 1;
    min-width: 180px;
  }
  .form-control {
    width: 100%;
    padding: 6px;
    border: 1px solid #ddd;
    border-radius: 4px;
  }
  .action-btn {
    background-color: #f44336;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    cursor: pointer;
  }
  .save-btn {
    background-color: #f44336;
  }
  .remove-btn {
    background-color: #f44336;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 6px 8px;
    cursor: pointer;
  }
  .cancel-btn {
    background-color: #9e9e9e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    cursor: pointer;
  }
  .feedback-message {
    margin-left: auto;
    font-weight: bold;
    padding: 6px 8px;
    border-radius: 4px;
  }
  .success {
    background-color: #d4edda;
    color: #155724;
  }
  .error {
    background-color: #f8d7da;
    color: #721c24;
  }
  </style>
