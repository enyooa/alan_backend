<!-- resources/js/components/reference/AdminCashesTable.vue -->
<template>
  <div class="expense-page">
    <!-- ▸ TOP-BAR ---------------------------------------------------- -->
    <header class="topbar">
      <h1>Счета / Кассы</h1>

      <div class="actions">
        <input v-model.trim="search"
               @input="applyFilter"
               placeholder="🔍 Поиск…"
               class="search"/>
        <button class="reload" @click="load">⟳</button>
      </div>
    </header>

    <!-- ▸ TABLE ------------------------------------------------------ -->
    <table class="orders">
      <thead>
        <tr>
          <th>№</th>
          <th>Название</th>
          <th>IBAN / № счёта</th>
          <th class="num">Действия</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="(c, idx) in view" :key="c.id" class="click-row">
          <td>{{ idx + 1 }}</td>
          <td class="title">{{ c.name }}</td>
          <td>{{ c.iban || '—' }}</td>

          <td class="num actions">
            <button class="icon-btn" @click="openEdit(c)">✏️</button>
            <button class="icon-btn danger" @click="remove(c, idx)">🗑</button>
          </td>
        </tr>

        <tr v-if="!view.length">
          <td colspan="4" class="empty">Данных нет</td>
        </tr>
      </tbody>
    </table>

    <!-- ▸ CREATE BTN ------------------------------------------------- -->
    <button class="create-btn" @click="openCreate">
      ➕ Добавить счёт / кассу
    </button>

    <!-- ▸ MODAL (create | edit) ------------------------------------- -->
    <div v-if="showModal" class="modal-overlay">
      <div class="modal-container">
        <button class="close-btn" @click="closeModal">×</button>

        <h3 class="modal-title">
          {{ modalMode === 'create' ? 'Создать счёт / кассу' : 'Редактировать счёт / кассу' }}
        </h3>

        <div class="modal-body">
          <label class="field-label">Название *</label>
          <input v-model.trim="form.name"
                 type="text"
                 class="modal-input"/>

          <!-- IBAN редактируется ТОЛЬКО при создании -->
          <label class="field-label">
            IBAN / Номер счёта {{ modalMode==='edit' ? '(нельзя изменить)' : '' }}
          </label>
          <input v-model.trim="form.iban"
                 type="text"
                 class="modal-input"
                 :readonly="modalMode==='edit'"/>

          <button class="action-btn save-btn"
                  :disabled="saving"
                  @click="save">
            {{ saving ? '⏳…' : '💾 Сохранить' }}
          </button>

          <div v-if="msg"
               :class="['feedback-message', msgType]">
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
  name: 'AdminCashesTable',

  data () {
    return {
      raw: [],               // полный список
      view: [],              // после фильтра
      search: '',

      /* modal */
      showModal: false,
      modalMode: 'create',   // create | edit
      form:   { id:null, name:'', iban:'' },

      saving: false,
      msg: '',
      msgType: ''
    }
  },

  created () { this.load() },

  methods: {
    /* ------------------------------------------------------------------
       API
    ------------------------------------------------------------------ */
    async load () {
      try {
        const { data } = await axios.get('/api/references/cash')   // GET список
        this.raw = Array.isArray(data) ? data : []
        this.applyFilter()
      } catch (e) {
        console.error(e)
        alert('Не удалось загрузить счета')
      }
    },

    async save () {
      if (!this.form.name) { alert('Введите название'); return }
      if (this.modalMode === 'create' && !this.form.iban) {
        alert('Введите IBAN / номер счёта'); return
      }

      this.saving = true
      this.msg = ''

      try {
        if (this.modalMode === 'create') {
          /* ----------  CREATE  ---------- */
          await axios.post('/api/references', {
            type: 'cash',
            name: this.form.name,
            iban: this.form.iban
          })
        } else {
          /* ----------  EDIT  ------------ */
          await axios.patch(`/api/references/${this.form.id}`, {
            name: this.form.name   // <- бек разрешает менять только name
          })
        }

        this.msg = 'Сохранено'
        this.msgType = 'success'
        this.closeModal()
        this.load()
      } catch (e) {
        console.error(e)
        this.msg = 'Ошибка'
        this.msgType = 'error'
      } finally {
        this.saving = false
        setTimeout(() => (this.msg = ''), 3000)
      }
    },

    async remove (cash, idx) {
      if (!confirm(`Удалить «${cash.name}»?`)) return
      try {
        await axios.delete(`/api/references/${cash.id}`)
        this.raw.splice(idx, 1)
        this.applyFilter()
      } catch (e) { alert('Не удалось удалить') }
    },

    /* ------------------------------------------------------------------
       Filtering & helpers
    ------------------------------------------------------------------ */
    applyFilter () {
      const q = this.search.toLowerCase()
      this.view = q
        ? this.raw.filter(r =>
            [r.name, r.iban]
              .join(' ')
              .toLowerCase()
              .includes(q))
        : this.raw
    },

    /* ------------------------------------------------------------------
       Modal helpers
    ------------------------------------------------------------------ */
    openCreate () {
      this.modalMode = 'create'
      this.form = { id:null, name:'', iban:'' }
      this.showModal = true
    },

    openEdit (c) {
      this.modalMode = 'edit'
      this.form = { id:c.id, name:c.name, iban:c.iban }
      this.showModal = true
    },

    closeModal () { this.showModal = false }
  }
}
</script>

<style scoped>
/* базовые стили ⇣ (тождественны ProvidersTable.vue / AddressesTable.vue) */
.expense-page{font-family:Inter,sans-serif;padding:18px}

.topbar{display:flex;align-items:center;gap:14px;
       background:linear-gradient(90deg,#03b4d1,#3dc1ff);
       color:#fff;padding:10px 18px;border-radius:14px;margin-bottom:16px;
       box-shadow:0 4px 12px rgba(0,0,0,.18)}
.topbar h1{margin:0;font-size:20px;font-weight:600}
.actions{margin-left:auto;display:flex;gap:8px;align-items:center}
.search{height:34px;font-size:14px;padding:0 10px;border:none;border-radius:8px;min-width:180px}
.reload{border:none;background:none;color:#c8ff55;font-size:24px;cursor:pointer;line-height:1}

table.orders{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;
             overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)}
.orders th,.orders td{padding:11px 10px;font-size:14px;text-align:center}
.orders thead{background:#f2faff;font-weight:600}
.orders tbody tr+tr{border-top:1px solid #e2e8f0}
.title{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:260px}
.num{text-align:center}
.empty{text-align:center;color:#7c7c7c;padding:14px 0}
.click-row:hover{background:#f7fdff}
.actions{display:flex;gap:6px;justify-content:center}

.icon-btn{background:#03b4d1;color:#fff;border:none;border-radius:6px;
          padding:4px 8px;font-size:16px;cursor:pointer}
.icon-btn.danger{background:#f44336}
.icon-btn:hover{filter:brightness(.9)}

.create-btn{position:fixed;right:22px;bottom:22px;display:flex;align-items:center;gap:6px;
            padding:0 20px;height:48px;background:linear-gradient(90deg,#18bdd7,#5fd0e5);
            border:none;border-radius:30px;box-shadow:0 4px 14px rgba(0,0,0,.28);
            color:#fff;font-size:15px;font-weight:600;cursor:pointer;transition:.25s;z-index:900}
.create-btn:hover{filter:brightness(1.08);transform:translateY(-2px)}

/* modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);
               display:flex;align-items:center;justify-content:center;z-index:1000;padding:18px}
.modal-container{background:#fff;border-radius:16px;box-shadow:0 6px 18px rgba(0,0,0,.25);
                 width:100%;max-width:460px;padding:28px 24px 24px;position:relative}
.close-btn{position:absolute;top:12px;right:12px;width:36px;height:36px;border-radius:50%;
           border:none;background:#f44336;color:#fff;font-size:22px;cursor:pointer}
.close-btn:hover{filter:brightness(1.1)}
.modal-title{margin:0 0 12px;font-size:18px;font-weight:600;text-align:center}
.field-label{font-weight:600;margin-bottom:6px}
.modal-input{width:100%;padding:10px;border:1px solid #ddd;border-radius:6px;font-size:14px;margin-bottom:14px}
.action-btn.save-btn{width:100%;background:#03b4d1;color:#fff;font-weight:600}
.feedback-message{margin-top:14px;text-align:center;font-weight:bold;padding:8px;border-radius:6px}
.feedback-message.success{background:#d4edda;color:#155724}
.feedback-message.error  {background:#f8d7da;color:#721c24}
</style>
