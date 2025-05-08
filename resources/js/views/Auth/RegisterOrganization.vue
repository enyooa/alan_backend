<!-- src/views/RegisterOrganization.vue -->
<template>
    <div class="register-wrap">
      <form class="register-card" @submit.prevent="submit">

        <h2>Регистрация компании</h2>

        <!-- ─── organization block ─────────────────────────────── -->
        <input v-model="org_name"
               :class="{error: errors.org_name}"
               placeholder="Наименование компании *" />

        <input v-model="address"
               placeholder="Адрес (необязательно)" />

        <!-- ─── plan select ────────────────────────────────────── -->
        <select v-model="plan_id" :class="{error: errors.plan_id}">
          <option disabled value="">— Тариф —</option>
          <option v-for="p in plans" :key="p.id" :value="p.id">
            {{ p.name }} — {{ p.price.toLocaleString() }} ₸
          </option>
        </select>

        <!-- ─── admin block ────────────────────────────────────── -->
        <h3>Администратор</h3>

        <input v-model="manager.first_name"
               :class="{error: errors['manager.first_name']}"
               placeholder="Имя *" />

        <input v-model="manager.last_name"
               :class="{error: errors['manager.last_name']}"
               placeholder="Фамилия *" />

        <!-- phone with fixed +7 prefix -->
        <div class="phone-field" :class="{error: errors['manager.phone']}">
          <span class="prefix">+7</span>
          <input
            v-model="phoneBody"
            maxlength="10"
            placeholder="7000000000 *"
            @input="digitsOnly"
          />
        </div>

        <input v-model="manager.password"
               :class="{error: errors['manager.password']}"
               type="password"
               placeholder="Пароль *" />

        <!-- errors ----------------------------------------------- -->
        <ul v-if="Object.keys(errors).length" class="error-list">
          <li v-for="(msg,key) in errors" :key="key">{{ msg[0] }}</li>
        </ul>

        <button class="btn-primary" :disabled="loading">
          {{ loading ? 'Сохраняем…' : 'Создать' }}
        </button>

        <router-link to="/login" class="link-back">
          Уже есть аккаунт? Войти
        </router-link>
      </form>
    </div>
  </template>

  <script>
  import axios from 'axios'

  export default {
    name: 'RegisterOrganization',

    data () {
      return {
        org_name : '',
        address  : '',
        plan_id  : '',

        manager  : {
          first_name : '',
          last_name  : '',
          password   : ''
        },

        /* only the 10 digits after +7 */
        phoneBody : '',

        plans : [
          { id:1, name:'Client',       price:0 },
          { id:2, name:'Intermediary', price:100000 },
          { id:3, name:'Retail',       price:200000 },
          { id:4, name:'Wholesaler',   price:500000 },
          { id:5, name:'Grands',       price:1000000 }
        ],

        loading : false,
        errors  : {}
      }
    },

    computed: {
      fullPhone () {
        return this.phoneBody ? '+7' + this.phoneBody : ''
      }
    },

    methods:{
      digitsOnly (e) {
        this.phoneBody = e.target.value.replace(/\D/g, '')
      },

      async submit () {
        this.errors  = {}
        this.loading = true

        try {
          await axios.post('/api/register_organization_with_user', {
            org_name : this.org_name,
            address  : this.address,
            plan_id  : this.plan_id,
            manager  : {
              first_name : this.manager.first_name,
              last_name  : this.manager.last_name,
              phone      : this.fullPhone,
              password   : this.manager.password
            }
          })
          this.$router.push('/login')
        } catch (e) {
          this.errors = e.response?.data?.errors || {}
        } finally {
          this.loading = false
        }
      }
    }
  }
  </script>

  <style scoped>
  /* ─── layout --------------------------------------------------- */
  .register-wrap{
    min-height:100vh;
    display:flex; justify-content:center; align-items:center;
    background:var(--app-bg);
  }

  .register-card{
    width:320px;
    background:var(--glass-bg);
    backdrop-filter:var(--glass-blur);
    border-radius:20px;
    padding:32px 28px;
    box-shadow:0 12px 28px rgba(0,0,0,.12);
    text-align:center;
    overflow-y:auto;
    max-height:calc(100vh - 60px);
  }

  /* headings */
  .register-card h2{ margin-bottom:24px; color:#222; }
  .register-card h3{ text-align:left; margin:20px 0 10px; font-size:15px; color:#475569; }

  /* inputs & selects */
  .register-card input,
  .register-card select{
    width:100%; margin-bottom:14px;
    padding:12px 14px; font-size:14px;
    border:1px solid #cbd5e1; border-radius:12px;
    background:#fff;
  }
  .register-card select{ appearance:none; }

  input.error, select.error, .phone-field.error input{ border-color:#e11d48; }

  /* phone field */
  .phone-field{
    position:relative; margin-bottom:14px;
  }
  .phone-field .prefix{
    position:absolute; left:12px; top:12px;
    font-size:14px; color:#475569;
  }
  .phone-field input{
    padding-left:38px;
    width:100%; font-size:14px;
    border:1px solid #cbd5e1; border-radius:12px;
  }

  /* error list */
  .error-list{ text-align:left; margin:-4px 0 14px; }
  .error-list li{ font-size:13px; color:#e11d48; }

  /* primary button */
  .btn-primary{
    width:100%; padding:12px 0;
    background:var(--brand-from); color:#fff;
    border:none; border-radius:24px; font-size:15px; cursor:pointer;
  }
  .btn-primary:disabled{ opacity:.65; cursor:not-allowed; }

  /* back link */
  .link-back{
    display:inline-block; margin-top:16px; font-size:14px;
    color:var(--brand-from);
  }
  </style>
