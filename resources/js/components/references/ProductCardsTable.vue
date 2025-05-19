<!-- resources/js/components/reference/ProductCardsTable.vue -->
<template>
  <div class="expense-page">
    <!-- ▸ TOP-BAR -->
    <header class="topbar">
      <h1>Карточки товаров</h1>

      <div class="actions">
        <input v-model.trim="search"
               @input="applyFilter"
               placeholder="🔍 Поиск…"
               class="search" />
        <button class="reload" @click="load">⟳</button>
      </div>
    </header>

    <!-- ▸ TABLE -->
    <table class="orders">
      <thead>
        <tr>
          <th>№</th>
          <th>Фото</th>
          <th>Наименование</th>
          <th>Страна</th>
          <th>Тип</th>
          <th class="num">Действия</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="(row, idx) in view" :key="row.id">
          <td>{{ idx + 1 }}</td>

          <td style="padding:6px">
            <img v-if="row.photo_url"
                 :src="row.photo_url"
                 class="thumb" />
          </td>

          <td class="title">{{ row.name }}</td>
          <td>{{ row.country || '—' }}</td>
          <td>{{ row.type    || '—' }}</td>

          <td class="num actions">
            <button class="icon-btn" @click="openEdit(row)">✏️</button>
            <button class="icon-btn danger" @click="remove(row, idx)">🗑</button>
          </td>
        </tr>

        <tr v-if="!view.length">
          <td colspan="6" class="empty">Данных нет</td>
        </tr>
      </tbody>
    </table>

    <!-- ▸ CREATE BTN -->
    <button class="create-btn" @click="openCreate">
      ➕ Добавить карточку
    </button>

    <!-- ▸ MODAL -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-container">
        <button class="close-btn" @click="closeModal">×</button>

        <h3 class="modal-title">
          {{ modalMode==='create' ? 'Новая карточка' : 'Редактировать карточку' }}
        </h3>

        <div class="modal-body">
          <label class="field-label">Наименование</label>
          <input v-model.trim="form.name_of_products"
                 type="text"
                 class="modal-input" />

          <label class="field-label">Страна</label>
          <input v-model.trim="form.country"
                 type="text"
                 class="modal-input" />

          <label class="field-label">Тип</label>
          <input v-model.trim="form.type"
                 type="text"
                 class="modal-input" />

          <label class="field-label">Описание</label>
          <textarea v-model.trim="form.description"
                    rows="3"
                    class="modal-input"></textarea>

          <label class="field-label">Фото (jpeg/png)</label>
          <input type="file" accept="image/*"
                 @change="onFileSelect" class="modal-input" />

          <div v-if="preview" class="preview-wrap">
            <img :src="preview" class="preview-img"/>
          </div>

          <button class="action-btn save-btn"
                  :disabled="saving"
                  @click="save">
            {{ saving ? '⏳…' : '💾 Сохранить' }}
          </button>

          <div v-if="msg" :class="['feedback-message', msgType]">
            {{ msg }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from '@/plugins/axios'

export default {
  name: 'ProductCardsTable',

  data () {
    return {
      raw: [],  view: [],  search: '',

      /* modal */
      showModal: false,
      modalMode: 'create',          // create | edit
      form: {
        id: null,
        name_of_products: '',
        description     : '',
        country         : '',
        type            : '',
        photo_product   : null      // File объект
      },
      preview: null,                // data-url для превью

      saving: false,
      msg: '', msgType: ''
    }
  },

  created () { this.load() },

  methods: {
    /* ─── загрузка списка ─────────────────────────────── */
    async load () {
      try {
        const { data } = await axios.get('/api/reference/productCard')
        this.raw = Array.isArray(data) ? data : []
        this.applyFilter()
      } catch (e) {
        console.error(e)
        alert('Не удалось загрузить карточки товаров')
      }
    },

    /* ─── сохранить (create / edit) ───────────────────── */
    async save () {
      if (!this.form.name_of_products) {
        alert('Введите наименование товара'); return
      }

      const fd = new FormData()
      fd.append('name_of_products', this.form.name_of_products)
      fd.append('country',   this.form.country   || '')
      fd.append('type',      this.form.type      || '')
      fd.append('description', this.form.description || '')
      if (this.form.photo_product) fd.append('photo_product', this.form.photo_product)

      this.saving = true; this.msg = ''
      try {
        if (this.modalMode === 'create') {
          await axios.post('/api/reference/productCard', fd,
            { headers: { 'Content-Type': 'multipart/form-data' } })
        } else {
          await axios.patch(`/api/reference/productCard/${this.form.id}`, fd,
            { headers: { 'Content-Type': 'multipart/form-data' } })
        }
        this.msg = 'Сохранено'; this.msgType = 'success'
        this.closeModal(); this.load()
      } catch (e) {
        console.error(e); this.msg = 'Ошибка'; this.msgType = 'error'
      } finally {
        this.saving = false
        setTimeout(() => (this.msg = ''), 3000)
      }
    },

    /* ─── удалить ────────────────────────────────────── */
    async remove (row, idx) {
      if (!confirm(`Удалить «${row.name_of_products}»?`)) return
      try {
        await axios.delete(`/api/reference/productCard/${row.id}`)
        this.raw.splice(idx, 1); this.applyFilter()
      } catch (e) { alert('Не удалось удалить') }
    },

    /* ─── фильтр ─────────────────────────────────────── */
    applyFilter () {
      const q = this.search.toLowerCase()
      this.view = q
        ? this.raw.filter(r =>
            (r.name || '').toLowerCase().includes(q) ||
            (r.country || '').toLowerCase().includes(q) ||
            (r.type || '').toLowerCase().includes(q)
          )
        : this.raw
    },

    /* ─── modal helpers ──────────────────────────────── */
    openCreate () {
      this.modalMode = 'create'
      this.form = { id:null,name_of_products:'',description:'',country:'',type:'',photo_product:null }
      this.preview = null
      this.showModal = true
    },
    openEdit (row) {
      this.modalMode = 'edit'
      this.form = {
        id: row.id,
        name_of_products: row.name,
        description     : row.description || '',
        country         : row.country     || '',
        type            : row.type        || '',
        photo_product   : null            // меняем только если выберут новый файл
      }
      this.preview = row.photo_url || null
      this.showModal = true
    },
    closeModal () { this.showModal = false },

    onFileSelect (e) {
      const f = e.target.files[0]
      if (!f) return
      this.form.photo_product = f
      this.preview = URL.createObjectURL(f)
    }
  }
}
</script>

<style scoped>
/* базовые стили — те же, что и в предыдущих таблицах */
.expense-page{font-family:Inter,sans-serif;padding:18px}
/* topbar */
.topbar{display:flex;align-items:center;gap:14px;
       background:linear-gradient(90deg,#03b4d1,#3dc1ff);
       color:#fff;padding:10px 18px;border-radius:14px;margin-bottom:16px;
       box-shadow:0 4px 12px rgba(0,0,0,.18)}
.topbar h1{margin:0;font-size:20px;font-weight:600}
.actions{margin-left:auto;display:flex;gap:8px;align-items:center}
.search{height:34px;font-size:14px;padding:0 10px;border:none;border-radius:8px;min-width:180px}
.reload{border:none;background:none;color:#c8ff55;font-size:24px;cursor:pointer;line-height:1}

/* table */
table.orders{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;
             overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.orders th,.orders td{padding:11px 10px;font-size:14px;text-align:center}
.orders thead{background:#f2faff;font-weight:600}
.orders tbody tr+tr{border-top:1px solid #e2e8f0}
.title{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px}
.thumb{width:48px;height:48px;object-fit:cover;border-radius:6px}
.num{text-align:center}
.empty{text-align:center;color:#7c7c7c;padding:14px 0}
.actions{display:flex;gap:6px;justify-content:center}
.icon-btn{background:#03b4d1;color:#fff;border:none;border-radius:6px;padding:4px 8px;font-size:16px;cursor:pointer}
.icon-btn.danger{background:#f44336}
.icon-btn:hover{filter:brightness(.9)}

/* floating create btn */
.create-btn{position:fixed;right:22px;bottom:22px;display:flex;align-items:center;gap:6px;
            padding:0 20px;height:48px;background:linear-gradient(90deg,#18bdd7,#5fd0e5);
            border:none;border-radius:30px;box-shadow:0 4px 14px rgba(0,0,0,.28);
            color:#fff;font-size:15px;font-weight:600;cursor:pointer;transition:.25s;z-index:900}
.create-btn:hover{filter:brightness(1.08);transform:translateY(-2px)}

/* modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);
               display:flex;align-items:center;justify-content:center;z-index:1000;padding:18px}
.modal-container{background:#fff;border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,.25);
                 width:100%;max-width:520px;padding:28px 24px 24px;position:relative}
.close-btn{position:absolute;top:12px;right:12px;width:36px;height:36px;border-radius:50%;
           border:none;background:#f44336;color:#fff;font-size:22px;cursor:pointer}
.modal-title{margin:0 0 14px;font-size:18px;font-weight:600;text-align:center}
.field-label{font-weight:600;margin-bottom:6px}
.modal-input{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;margin-bottom:18px}
.preview-wrap{text-align:center;margin-bottom:18px}
.preview-img{max-width:120px;max-height:120px;border-radius:8px;object-fit:cover}
.action-btn.save-btn{width:100%;background:#03b4d1;color:#fff;font-weight:600}
.feedback-message{margin-top:14px;text-align:center;font-weight:bold;padding:8px;border-radius:6px}
.feedback-message.success{background:#d4edda;color:#155724}
.feedback-message.error  {background:#f8d7da;color:#721c24}
</style>
