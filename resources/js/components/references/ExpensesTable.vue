<!-- resources/js/components/reference/ExpensesTable.vue -->
<template>
  <div class="expense-page">
    <!-- ▸ TOP-BAR -->
    <header class="topbar">
      <h1>Расходы</h1>

      <div class="actions">
        <input
          v-model.trim="search"
          @input="applyFilter"
          placeholder="🔍 Поиск…"
          class="search"
        />
        <button class="reload" @click="load">⟳</button>
      </div>
    </header>

    <!-- ▸ TABLE -->
    <table class="orders">
      <thead>
        <tr>
          <th>№</th>
          <th>Наименование</th>
          <th class="num">Действия</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="(row, idx) in view" :key="row.id">
          <td>{{ idx + 1 }}</td>
          <td class="title">{{ row.name }}</td>

          <td class="num actions">
            <button class="icon-btn" @click="openEdit(row)">✏️</button>
            <button class="icon-btn danger" @click="remove(row, idx)">🗑</button>
          </td>
        </tr>

        <tr v-if="!view.length">
          <td colspan="3" class="empty">Данных нет</td>
        </tr>
      </tbody>
    </table>

    <!-- ▸ CREATE BTN -->
    <button class="create-btn" @click="openCreate">
      ➕ Добавить расход
    </button>

    <!-- ▸ MODAL -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-container">
        <button class="close-btn" @click="closeModal">×</button>

        <h3 class="modal-title">
          {{ modalMode === 'create' ? 'Создать расход' : 'Редактировать расход' }}
        </h3>

        <div class="modal-body">
          <label class="field-label">Наименование</label>
          <input
            v-model.trim="form.name"
            type="text"
            class="modal-input"
            placeholder="Напр. «Транспорт»"
          />

          <button
            class="action-btn save-btn"
            :disabled="saving"
            @click="save"
          >
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
  name: 'ExpensesTable',

  data () {
    return {
      raw: [],          // полный список
      view: [],         // после фильтра
      search: '',

      /* modal */
      showModal: false,
      modalMode: 'create',   // create | edit
      form: { id: null, name: '' },

      saving: false,
      msg: '',
      msgType: ''
    }
  },

  created () { this.load() },

  methods: {
    /* ─────────── загрузка списка ─────────── */
    async load () {
      try {
        const { data } = await axios.get('/api/reference/expense-name')
        this.raw = Array.isArray(data) ? data : []
        this.applyFilter()
      } catch (e) {
        console.error(e)
        alert('Не удалось загрузить расходы')
      }
    },

    /* ─────────── сохранить (create / edit) ― */
    async save () {
      if (!this.form.name) { alert('Введите название'); return }

      this.saving = true; this.msg = ''
      try {
        if (this.modalMode === 'create') {
          await axios.post('/api/reference/expense-name', { name: this.form.name })
        } else {
          await axios.patch(`/api/reference/expense-name/${this.form.id}`, { name: this.form.name })
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

    /* ─────────── удалить ─────────── */
    async remove (row, idx) {
      if (!confirm(`Удалить «${row.name}»?`)) return
      try {
        await axios.delete(`/api/reference/expense-name/${row.id}`)
        this.raw.splice(idx, 1); this.applyFilter()
      } catch (e) { alert('Не удалось удалить') }
    },

    /* ─────────── фильтр ─────────── */
    applyFilter () {
      const q = this.search.toLowerCase()
      this.view = q ? this.raw.filter(r => r.name.toLowerCase().includes(q)) : this.raw
    },

    /* ─────────── modal helpers ─────────── */
    openCreate () {
      this.modalMode = 'create'
      this.form = { id: null, name: '' }
      this.showModal = true
    },
    openEdit (row) {
      this.modalMode = 'edit'
      this.form = { id: row.id, name: row.name }
      this.showModal = true
    },
    closeModal () { this.showModal = false }
  }
}
</script>

<style scoped>
/* — базовые стили те же, что и во всех остальных таблицах — */
.expense-page{font-family:Inter,sans-serif;padding:18px}
/* top-bar */
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
.title{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:300px}
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
                 width:100%;max-width:440px;padding:28px 24px 24px;position:relative}
.close-btn{position:absolute;top:12px;right:12px;width:36px;height:36px;border-radius:50%;
           border:none;background:#f44336;color:#fff;font-size:22px;cursor:pointer}
.close-btn:hover{filter:brightness(1.1)}
.modal-title{margin:0 0 12px;font-size:18px;font-weight:600;text-align:center}
.field-label{font-weight:600;margin-bottom:6px}
.modal-input{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;margin-bottom:18px}
.action-btn.save-btn{width:100%;background:#03b4d1;color:#fff;font-weight:600}
.feedback-message{margin-top:14px;text-align:center;font-weight:bold;padding:8px;border-radius:6px}
.feedback-message.success{background:#d4edda;color:#155724}
.feedback-message.error  {background:#f8d7da;color:#721c24}
</style>
