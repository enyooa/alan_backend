<!-- resources/js/views/Reports/DebtsReportPage.vue -->
<template>
  <div class="debts-page">
    <!-- ══════════════════ Filters ══════════════════ -->
    <div class="filters">
      <label>Дата c:
        <input type="date" v-model="fromDate">
      </label>

      <label>по:
        <input type="date" v-model="toDate">
      </label>

      <label>
        На&nbsp;странице:
        <select v-model.number="perPage">
          <option v-for="n in [10,20,30,50,100]" :key="n" :value="n">{{ n }}</option>
        </select>
      </label>

      <button @click="fetchPage(1)">Сформировать</button>
    </div>

    <!-- ══════════════════ Table ═══════════════════ -->
    <table v-if="rows.length" class="modern-table">
      <thead>
        <tr>
          <th>Контрагент</th>
          <th class="right">Нач. остаток</th>
          <th class="right">Приход</th>
          <th class="right">Расход</th>
          <th class="right">Конеч. остаток</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="r in rows" :key="r.counterparty_id">
          <td>{{ r.name }}</td>
          <td class="right">{{ fmt(r.start) }}</td>
          <td class="right">{{ fmt(r.income) }}</td>
          <td class="right">{{ fmt(r.expense) }}</td>
          <td class="right" :class="{ neg: r.end < 0 }">
            {{ fmt(r.end) }}
          </td>
        </tr>
      </tbody>
    </table>

    <p v-if="!loading && rows.length === 0" class="empty">
      Нет данных по выбранным условиям
    </p>

    <!-- ══════════════════ Pagination ══════════════ -->
    <div v-if="meta.total_rows > meta.per_page" class="pager">
      <button
        :disabled="meta.current_page === 1 || loading"
        @click="fetchPage(meta.current_page - 1)"
      >
        « Prev
      </button>

      <span>
        страница {{ meta.current_page }} / {{ meta.last_page }}
      </span>

      <button
        :disabled="meta.current_page >= meta.last_page || loading"
        @click="fetchPage(meta.current_page + 1)"
      >
        Next »
      </button>
    </div>

    <!-- ══════════════════ Status ══════════════════ -->
    <p v-if="loading">Загрузка…</p>
    <p v-if="error" class="error">Ошибка: {{ error }}</p>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'DebtsReportPage',
  data() {
    return {
      fromDate: '',
      toDate: '',
      perPage: 20,

      rows: [],
      meta: {
        current_page: 1,
        last_page: 1,
        per_page: 20,
        total_rows: 0
      },

      loading: false,
      error: null,
    };
  },
  created() {
    this.fetchPage(1);
  },
  methods: {
    /** получить указанную страницу */
    async fetchPage(page) {
      this.loading = true;
      this.error   = null;
      try {
        const { data } = await axios.get('/api/report-debts', {
          params: {
            date_from: this.fromDate || null,
            date_to:   this.toDate   || null,
            page,
            per_page: this.perPage,
          }
        });
        this.rows = data.data || [];
        this.meta = data.meta || this.meta;
      } catch (e) {
        console.error(e);
        this.error = e.response?.data?.error || e.message;
      } finally {
        this.loading = false;
      }
    },
    /** формат денег */
    fmt(v) {
      return (+v).toLocaleString('ru-RU', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      });
    },
  },
  watch: {
    perPage() { this.fetchPage(1); },
  },
};
</script>

<style scoped>
.debts-page { max-width: 950px; margin: 0 auto; font-family: sans-serif; }

/* Filters */
.filters { display: flex; flex-wrap: wrap; gap: 14px; margin-bottom: 16px; align-items:center; }
.filters input, .filters select { padding: 4px 6px; }
.filters button { padding: 6px 14px; background:#0288d1; color:#fff; border:none; cursor:pointer; }
.filters button:hover { background:#0277bd; }

/* Table */
.modern-table { width: 100%; border-collapse: collapse; box-shadow:0 3px 8px rgba(0,0,0,.08); }
.modern-table thead { background:#0288d1; color:#fff; }
.modern-table th, .modern-table td { border:1px solid #ddd; padding:8px 10px; }
.right { text-align:right; }
.neg { color:red; }

/* Pager */
.pager { margin-top:14px; display:flex; gap:18px; align-items:center; }
.pager button { padding:4px 10px; }
.pager button:disabled { opacity:.4; cursor:not-allowed; }

/* Misc */
.empty { margin-top:12px; color:#666; }
.error { color:red; margin-top:12px; }
</style>
