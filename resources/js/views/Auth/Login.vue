<!-- src/views/Auth/Login.vue -->
<template>
    <div class="login-wrap">

      <!-- glass-morphism card -->
      <form class="login-card" @submit.prevent="login">
        <h2>Добро пожаловать 👋</h2>

        <!-- WhatsApp (digits only) -->
        <input v-model="whatsappNumber"
               @input="digitsOnly"
               maxlength="11"
               type="text"
               placeholder="📱 WhatsApp номер"
               autocomplete="tel"
               required />

        <!-- password -->
        <input v-model="password"
               type="password"
               placeholder="🔑 Пароль"
               autocomplete="current-password"
               required />

        <!-- submit -->
        <button class="btn-primary" :disabled="loading">
          {{ loading ? '…' : 'Войти' }}
        </button>

        <!-- error -->
        <p v-if="errorMessage" class="error">{{ errorMessage }}</p>

        <!-- links -->
        <div class="links">
          <router-link to="/forgot-password">Забыли пароль?</router-link>
          <router-link to="/register">Создать аккаунт</router-link>
        </div>
      </form>

    </div>
  </template>

  <script>
  import axios from 'axios'

  export default {
    name : 'Login',

    data () {
      return {
        whatsappNumber : '',        //  77056055050
        password       : '',
        errorMessage   : '',
        loading        : false
      }
    },

    methods : {
      /* пропускаем только цифры */
      digitsOnly (e) {
        this.whatsappNumber = e.target.value.replace(/\D/g,'')
      },

      /* ---------- login ---------- */
      async login () {
        this.errorMessage = ''
        this.loading      = true

        try {
          const { data } = await axios.post('/api/login', {
            whatsapp_number : this.whatsappNumber,
            password        : this.password
          })

          if (!data.token) throw new Error('Token missing')

          /* save token globally */
          localStorage.setItem('token', data.token)
        //   axios.defaults.headers.common.Authorization = `Bearer ${data.token}`

          /* if guard saved ?redirect=… → go back there */
          const to = this.$route.query.redirect || '/app/dashboard'
this.$router.replace(to)

        } catch (err) {
          this.errorMessage = 'Неверный номер или пароль'
        } finally {
          this.loading = false
        }
      }
    }
  }
  </script>

  <style scoped>
  /* full-screen gradient / image is set globally as --app-bg */
  .login-wrap{
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:var(--app-bg,#f0f9ff);
  }

  /* ---------- card ---------- */
  .login-card{
    width:320px;
    padding:32px 28px;
    border-radius:20px;
    background:var(--glass-bg,rgba(255,255,255,.65));
    backdrop-filter:var(--glass-blur,blur(14px));
    box-shadow:0 12px 28px rgba(0,0,0,.12);
    text-align:center;
  }

  .login-card h2{ margin-bottom:24px; color:#222; }

  /* inputs */
  .login-card input{
    width:100%;
    padding:12px 14px;
    margin-bottom:14px;
    font-size:14px;
    border:1px solid #cbd5e1;
    border-radius:12px;
    background:#fff;
  }

  /* button */
  .btn-primary{
    width:100%;
    padding:12px 0;
    border:none;
    border-radius:24px;
    background:var(--brand-from,#0ea5e9);
    color:#fff;
    font-size:15px;
    cursor:pointer;
  }
  .btn-primary:disabled{ opacity:.65; cursor:not-allowed; }
  .btn-primary:not(:disabled):hover{ filter:brightness(.9); }

  /* misc */
  .error{ margin-top:8px; color:#e11d48; font-size:14px; }
  .links{ margin-top:18px; display:flex; flex-direction:column; gap:6px; font-size:14px; }
  .links a{ color:var(--brand-from,#0ea5e9); }
  </style>
