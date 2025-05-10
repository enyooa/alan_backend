<!-- resources/js/views/Access.vue -->
<template>
    <div class="access-popup">
      <!-- ▸ Header ------------------------------------------------------------ -->
      <header class="popup__header">
        <h2 class="popup__title">Роли и доступы</h2>
        <button class="icon-btn close" @click="$emit('close')">✕</button>
      </header>

      <!-- ▸ Body (scrollable) ------------------------------------------------- -->
      <section class="popup__body">
        <!-- ◂ Роли -->
        <details open class="block">
          <summary class="block__title">Роли пользователя</summary>

          <ul class="list">
            <li v-for="role in allRoles" :key="role" class="list__row">
              <span class="list__label">{{ prettify(role) }}</span>

              <label class="switch">
                <input type="checkbox"
                       :checked="userRoles.includes(role)"
                       @change="toggleRole(role)">
                <span class="slider"></span>
              </label>
            </li>
          </ul>
        </details>

        <!-- ◂ Permissions -->
        <details class="block" open>
          <summary class="block__title">Permissions</summary>

          <ul class="list">
            <li v-for="perm in allPerms" :key="perm.code" class="list__row">
              <span class="list__label">{{ perm.name }}</span>

              <label class="switch">
                <input type="checkbox"
                       :checked="userPerms.includes(perm.code)"
                       @change="togglePerm(perm.code)">
                <span class="slider"></span>
              </label>
            </li>
          </ul>
        </details>
      </section>

      <!-- ▸ Footer ------------------------------------------------------------ -->
      <footer class="popup__footer">
        <button class="save-btn"
                :disabled="saving"
                @click="save">
          {{ saving ? '⏳ Сохраняю…' : '💾 Сохранить' }}
        </button>
      </footer>
    </div>
  </template>

  <script>
  import axios from '@/plugins/axios'

  export default {
    name : 'Access',
    props:{ userId:{type:String,required:true} },
    emits:['close'],

    data:()=>({
      allRoles:[], allPerms:[],
      userRoles:[], userPerms:[],
      saving:false
    }),

    created(){ this.fetchInitial() },

    methods:{
      /* ───── API ---------------------------------------------------------------- */
      async fetchInitial(){
        try{
          const {data:groups} = await axios.get('/api/stuff')

          /* роли */
          const roleSet = new Set()
          groups.forEach(g=>{
            if(g.role) roleSet.add(g.role)
            g.users?.forEach(u=>u.roles.forEach(r=>roleSet.add(r)))
          })
          this.allRoles = Array.from(roleSet).sort()

          /* permissions */
          const map = new Map()
          groups.forEach(g=>{
            g.permissions?.forEach(p=>map.set(p.code,p.name))
            g.users?.forEach(u=>u.permissions.forEach(p=>map.set(p.code,p.name)))
          })
          this.allPerms = Array.from(map,([code,name])=>({code:Number(code),name}))
                               .sort((a,b)=>a.code-b.code)

          /* выбранный пользователь */
          const user = groups.flatMap(g=>g.users).find(u=>u.id===this.userId)
          if(user){
            const group = groups.find(g=>g.users?.some(u=>u.id===user.id))
            const roles = new Set(user.roles)
            if(group?.role && group.role!=='Без ролей') roles.add(group.role)
            this.userRoles = [...roles]
            this.userPerms = user.permissions.map(p=>Number(p.code))
          }
        }catch(e){ console.error('Access.vue → fetchInitial',e) }
      },

      /* ───── helpers ------------------------------------------------------------ */
      prettify(r){ return r==='Без ролей' ? r : r[0].toUpperCase()+r.slice(1) },

      toggleRole(r){
        const i=this.userRoles.indexOf(r)
        i===-1?this.userRoles.push(r):this.userRoles.splice(i,1)
      },
      togglePerm(code){
        const i=this.userPerms.indexOf(code)
        i===-1?this.userPerms.push(code):this.userPerms.splice(i,1)
      },

      /* ───── save --------------------------------------------------------------- */
      async save(){
        this.saving=true
        try{
          await axios.put(`/api/users/${this.userId}/roles-permissions`,{
            roles:this.userRoles, permissions:this.userPerms
          })
          this.$toast?.success('✅ Сохранено')
          this.$emit('close')
        }catch(e){
          console.error('Access.vue → save',e)
          this.$toast?.error('❌ Ошибка сохранения')
        }finally{ this.saving=false }
      }
    }
  }
  </script>

  <style scoped>
  /* ----- layout of popup ---------------------------------------------------- */
  .access-popup{
    display:flex;flex-direction:column;
    width:100%;max-width:480px;
    max-height:90vh;        /* помещается в окно, даже на мобилках   */
    background:#fff;border-radius:18px;
    box-shadow:0 6px 22px rgba(0,0,0,.2);
    overflow:hidden;
    font-family:'Inter',sans-serif;
  }

  /* header */
  .popup__header{
    display:flex;align-items:center;justify-content:space-between;
    padding:14px 20px;
    background:linear-gradient(135deg,#18BDD7 0%,#6BC6DA 100%);
    color:#fff;
  }
  .popup__title{margin:0;font-size:18px;font-weight:600}
  .icon-btn{background:none;border:none;font-size:22px;color:#fff;cursor:pointer}

  /* body (scrollable) */
  .popup__body{flex:1;overflow-y:auto;padding:18px}

  /* footer */
  .popup__footer{padding:16px;border-top:1px solid #eee}

  /* ----- content blocks ----------------------------------------------------- */
  .block{margin-bottom:22px;border:1px solid #eceff1;border-radius:14px;overflow:hidden}
  .block__title{
    margin:0;padding:10px 16px;background:#f3f7fa;cursor:pointer;
    font-size:15px;font-weight:600;list-style:none;
  }
  .block[open]>.block__title{border-bottom:1px solid #e0e4e7}

  /* list (rows) */
  .list{margin:0;padding:0;list-style:none}
  .list__row{display:flex;align-items:center;justify-content:space-between;
             gap:10px;padding:10px 16px;border-bottom:1px solid #f1f1f1}
  .list__row:last-child{border:none}
  .list__label{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

  /* ----- switch ------------------------------------------------------------- */
  .switch{position:relative;width:44px;height:24px}
  .switch input{opacity:0;width:0;height:0}
  .slider{position:absolute;inset:0;border-radius:999px;background:#c7c9cc;
          transition:.3s}
  .slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;
                  border-radius:50%;background:#fff;transition:.3s}
  input:checked + .slider{background:#00c4e7}
  input:checked + .slider::before{transform:translateX(20px)}

  /* ----- buttons ------------------------------------------------------------ */
  .save-btn{
    width:100%;padding:14px;border:none;border-radius:14px;font-size:16px;
    color:#fff;background:linear-gradient(135deg,#18BDD7 0%,#6BC6DA 100%);
    box-shadow:0 3px 8px rgba(0,0,0,.18);cursor:pointer;
    transition:transform .15s;
  }
  .save-btn:disabled{opacity:.6;cursor:progress}
  .save-btn:not(:disabled):active{transform:scale(.97)}
  </style>
