<!-- resources/js/components/RolesModal.vue -->
<template>
    <transition name="fade">
      <div v-if="open" class="overlay" @click.self="$emit('close')">

        <div class="card">
          <h2>Роли</h2>

          <!-- ───── Roles (radio: only ONE) ───── -->
          <div class="list">
            <label v-for="r in roles" :key="r">
              <span>{{ r }}</span>
              <input type="radio"
                     name="role"
                     :value="r"
                     v-model="pickedRole">
              <span class="switch"></span>
            </label>
          </div>

          <h2>Доступ</h2>

          <!-- ───── Permissions (many) ───── -->
          <div class="list perm">
            <label v-for="p in permissions" :key="p.code">
              <span>{{ p.name }}</span>
              <input type="checkbox"
                     :value="p.code"
                     v-model="pickedPerms">
              <span class="switch"></span>
            </label>
          </div>

          <button class="btn" @click="save" :disabled="loading">
            {{ loading ? '...' : 'Сохранить' }}
          </button>
        </div>

      </div>
    </transition>
  </template>

  <script>
  import axios from 'axios'

  export default {
    name : 'RolesModal',
    props:{
      open        : Boolean,
      user        : Object,      // выбранный сотрудник (из Employees.vue)
      roles       : Array,       // ['admin','cashbox',...]
      permissions : Array        // [{code:1101,name:'Поступления'},...]
    },

    data:()=>({
      pickedRole  : '',          // radio
      pickedPerms : [],          // checkboxes
      loading     : false
    }),

    watch:{
      /* при смене user заполняем текущие значения */
      user:{
        immediate:true,
        handler(u){
          if(!u) return
          this.pickedRole  = u.roles[0] || ''
          this.pickedPerms = u.permissions.map(p=>+p[1])   // ['Отчёт', '1002'] → 1002
        }
      }
    },

    methods:{
      async save(){
        this.loading = true
        try{
          await axios.put(`/api/users/${this.user.id}/roles-permissions`,{
            role : this.pickedRole,
            perms: this.pickedPerms
          })
          this.$emit('saved')            // чтобы Employees.vue перезагрузил список
          this.$emit('close')
        }catch(e){
          alert('Ошибка сохранения')
        }finally{ this.loading=false }
      }
    }
  }
  </script>

  <style scoped>
  /* simple overlay + glass-card (подгон по макету из скрина) */
  .overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);display:flex;justify-content:center;align-items:center;z-index:9999}
  .card{width:320px;max-width:90vw;background:#e8eaea;border-radius:22px;padding:20px 24px;box-shadow:0 8px 32px rgba(0,0,0,.25)}
  h2{margin:12px 0 10px;font-size:17px;color:#0097b2}

  .list label{display:flex;justify-content:space-between;align-items:center;padding:8px 0}
  .list label+.list label{border-top:1px solid #d1d5db}
  .switch{width:44px;height:24px;background:#9ca3af;border-radius:9999px;position:relative;transition:.2s}
  .switch::after{content:'';position:absolute;top:2px;left:2px;width:20px;height:20px;border-radius:50%;background:#fff;transition:.2s}
  input:checked+.switch{background:#06aed5}
  input:checked+.switch::after{transform:translateX(20px)}
  input{display:none}

  .btn{width:100%;margin-top:18px;padding:10px 0;border:none;border-radius:18px;background:#06aed5;color:#fff;font-size:15px;cursor:pointer}
  .fade-enter-active,.fade-leave-active{transition:opacity .2s}
  .fade-enter,.fade-leave-to{opacity:0}
  </style>
