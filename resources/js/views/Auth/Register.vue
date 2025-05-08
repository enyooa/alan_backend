<!-- src/views/RegisterIndividual.vue -->
<template>
    <div class="wrap">
      <form class="card" @submit.prevent="submit">
        <h2>Регистрация физ. лица</h2>

        <input v-model="first_name"           :class="{error:errors.first_name}"     placeholder="Имя *" />
        <input v-model="last_name"                                                placeholder="Фамилия" />
        <input v-model="surname"                                                  placeholder="Отчество" />
        <input v-model="whatsapp_number"     :class="{error:errors.whatsapp_number}" placeholder="+7 700 000 00 00 *" />
        <input v-model="password" type="password" :class="{error:errors.password}"  placeholder="Пароль *" />
        <input v-model="password_confirmation" type="password"                     placeholder="Повторите пароль *" />

        <ul v-if="Object.keys(errors).length" class="error-list">
          <li v-for="(msg,key) in errors" :key="key">{{ msg[0] }}</li>
        </ul>

        <button class="btn-primary" :disabled="loading">
          {{ loading ? 'Сохраняем…' : 'Создать' }}
        </button>

        <router-link to="/register" class="link-back">← Назад</router-link>
      </form>
    </div>
  </template>

  <script>
  import axios from 'axios'

  export default {
    name: 'RegisterIndividual',
    data: () => ({
      first_name:'', last_name:'', surname:'',
      whatsapp_number:'', password:'', password_confirmation:'',
      loading:false, errors:{}
    }),
    methods:{
      async submit () {
        this.errors  = {};
        this.loading = true;
        try {
          await axios.post('/api/register',{
            first_name : this.first_name,
            last_name  : this.last_name,
            surname    : this.surname,
            whatsapp_number : this.whatsapp_number,
            password   : this.password,
            password_confirmation : this.password_confirmation
          });
          this.$router.push('/login');
        } catch (e) {
          this.errors = e.response?.data?.message || {};
        } finally { this.loading = false; }
      }
    }
  }
  </script>

  <style scoped>
  /* ------------- same “glass” look as Login.vue ---------------- */
  .wrap{
    min-height:100vh;
    display:flex; justify-content:center; align-items:center;
    background:var(--app-bg);
  }

  .card{
    width:320px;
    background:var(--glass-bg);
    backdrop-filter:var(--glass-blur);
    border-radius:20px;
    padding:32px 28px;
    text-align:center;
    box-shadow:0 12px 28px rgba(0,0,0,.12);
  }

  .card h2{
    margin-bottom:24px;
    color:#222;
  }

  .card input{
    width:100%;
    margin-bottom:14px;
    padding:12px 14px;
    font-size:14px;
    border:1px solid #cbd5e1;
    border-radius:12px;
  }

  input.error{
    border-color:#e11d48;
  }

  /* primary button reused from login */
  .btn-primary{
    width:100%;
    padding:12px 0;
    background:var(--brand-from);
    color:#fff;
    border:none;
    border-radius:24px;
    font-size:15px;
    cursor:pointer;
  }
  .btn-primary:disabled{ opacity:.65; }

  /* validation list */
  .error-list{
    text-align:left;
    margin:-4px 0 12px;
  }
  .error-list li{
    font-size:13px;
    color:#e11d48;
  }

  /* back link */
  .link-back{
    display:inline-block;
    margin-top:14px;
    font-size:14px;
    color:var(--brand-from);
  }
  </style>
