<!-- src/components/Header.vue -->
<template>
    <header class="header">
      <h1 class="app-title">Админ Панель</h1>

      <div class="user-info">
        <span class="greeting">
          Добро пожаловать,
          <strong class="user-name">{{ user ? user.first_name : 'Пользователь' }}</strong>!
        </span>

        <!-- ───── новый блок тарифа ───── -->
        <span class="tariff">
          Ваш тариф: <strong>Оптовик</strong>
        </span>

        <!-- Кнопка выхода -->
        <button class="logout-btn" @click="logout">🚪 Выйти</button>
      </div>
    </header>
  </template>

  <script>
  import axios from 'axios';

  export default {
    name: 'Header',
    data() {
      return { user: null };
    },
    async created() {
      await this.fetchUserData();
    },
    methods: {
      async fetchUserData() {
        try {
          const { data } = await axios.get('/api/user');
          this.user = data;
        } catch (e) {
          console.error('Ошибка загрузки пользователя', e);
        }
      },
      async logout() {
        try {
          const token = localStorage.getItem('token');
          if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`;

          await axios.post('/api/logout');
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          delete axios.defaults.headers.common.Authorization;

          this.$router.replace('/login').then(() => window.location.reload());
        } catch (e) {
          console.error('❌ Ошибка выхода', e);
        }
      },
    },
  };
  </script>

  <style scoped>
  /* ------------------------------------------------------------------
     КЛЮЧЕВОЕ: объявляем переменные ГРАДИЕНТА прямо здесь, в .header
  -------------------------------------------------------------------*/
  .header {
    /* локальные CSS-переменные — доступны только внутри компонента */
    --grad-from: #07bcd7;
    --grad-to:   #6fc6da;

    display: flex;
    justify-content: space-between;
    align-items: center;

    padding: 14px 24px;
    color: #fff;

    background: linear-gradient(90deg, var(--grad-from), var(--grad-to));
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.06);
  }

  /* название приложения */
  .app-title {
    font-size: 22px;
    font-weight: 600;
    margin: 0;
  }

  /* блок «Добро пожаловать … Выйти» */
  .user-info {
    display: flex;
    align-items: center;
    gap: 12px;
  }

  .greeting {
    font-size: 14px;
  }

  .user-name {
    font-weight: 600;
  }

  /* кнопка выхода */
  .logout-btn {
    padding: 8px 20px;
    font-size: 14px;
    cursor: pointer;

    border: none;
    border-radius: 28px;

    /* тот же приём с локальными переменными, чтобы не конфликтовать */
    --btn-from: #c0fb63;
    --btn-to:   #72953b;
    background: linear-gradient(90deg, var(--btn-from), var(--btn-to));

    color: #fff;
    transition: filter 0.2s;
  }

  .logout-btn:hover {
    filter: brightness(0.93);
  }

  .tariff {
    margin: 0 6px;
    font-size: 14px;
    color: #e7f8ff;
    white-space: nowrap;
  }
  </style>
