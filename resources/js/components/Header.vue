<!-- src/components/Header.vue -->
<template>
  <header class="header">
    <h1 class="app-title">Админ Панель</h1>

    <div class="user-info">
      <span class="greeting">
        Добро пожаловать,
        <strong class="user-name">
          {{ user ? user.first_name : 'Пользователь' }}
        </strong>!
      </span>

      <!-- тариф ──────────────── -->
      <span class="tariff" v-if="plan">
        Ваш тариф: <strong>{{ plan.name }}</strong>
      </span>
      <span class="tariff" v-else>
        Тариф не выбран
      </span>

      <!-- выход -->
      <button class="logout-btn" @click="logout">🚪 Выйти</button>
    </div>
  </header>
</template>

<script>
import axios from '@/plugins/axios'   // or just 'axios' if you don’t have a wrapper

export default {
  name : 'Header',

  data: () => ({
    user : null,
    plan : null,            // ← here we keep the current plan object
  }),

  async created () {
    await Promise.all([ this.fetchUser(), this.fetchPlan() ])
  },

  methods:{
    /* ----- user -------- */
    async fetchUser () {
      try{
        const { data } = await axios.get('/api/user')
        this.user = data
      }catch(e){ console.error('Ошибка загрузки пользователя', e) }
    },

    /* ----- plan -------- */
    async fetchPlan () {
      try{
        const { data } = await axios.get('/api/my/plan')   // may be null
        this.plan = data
      }catch(e){ console.error('Ошибка загрузки плана', e) }
    },

    /* ----- logout ------ */
    async logout () {
      try{
        const token = localStorage.getItem('token')
        if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`

        await axios.post('/api/logout')

        /* clear local storage + token */
        localStorage.removeItem('token')
        localStorage.removeItem('user')
        delete axios.defaults.headers.common.Authorization

        /* redirect to login */
        this.$router.replace('/login').then(() => window.location.reload())
      }catch(e){ console.error('❌ Ошибка выхода', e) }
    },
  }
}
</script>

<style scoped>
/* — original styles kept — */
.header{
  --grad-from:#07bcd7;
  --grad-to:#6fc6da;
  display:flex;justify-content:space-between;align-items:center;
  padding:14px 24px;color:#fff;
  background:linear-gradient(90deg,var(--grad-from),var(--grad-to));
  box-shadow:0 2px 5px rgba(0,0,0,.06);
}

.app-title{font-size:22px;font-weight:600;margin:0}

.user-info{display:flex;align-items:center;gap:12px}
.greeting{font-size:14px}.user-name{font-weight:600}

.tariff{font-size:14px;color:#e7f8ff;white-space:nowrap}

.logout-btn{
  padding:8px 20px;font-size:14px;color:#fff;cursor:pointer;border:none;
  border-radius:28px;
  --btn-from:#c0fb63;--btn-to:#72953b;
  background:linear-gradient(90deg,var(--btn-from),var(--btn-to));
  transition:filter .2s;
}
.logout-btn:hover{filter:brightness(.93)}
</style>
