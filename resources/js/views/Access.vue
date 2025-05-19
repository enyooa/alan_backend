<!-- resources/js/views/Access.vue -->
<template>
    <div class="access-popup">
      <!-- ▸ header -->
      <header class="popup__header">
        <h2 class="popup__title">Роли и доступы</h2>
        <button class="icon-btn close" @click="$emit('close')">✕</button>
      </header>

      <!-- ▸ body -->
      <section class="popup__body">
        <!-- ── роли ───────────────────── -->
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

        <!-- ── permissions ────────────── -->
        <details open class="block">
          <summary class="block__title">Права (permissions)</summary>

          <ul class="list">
            <li v-for="perm in allPerms" :key="perm.code" class="list__row">
              <span class="list__label">{{ perm.name }}</span>

              <label class="switch">
                <input type="checkbox"
                       :checked="userPermCodes.includes(perm.code)"
                       @change="togglePerm(perm.code)">
                <span class="slider"></span>
              </label>
            </li>
          </ul>
        </details>
      </section>

      <!-- ▸ footer -->
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

    data:() => ({
      allRoles       : [],            // ['admin', …]
      allPerms       : [],            // [{code,name}, …]

      userRoles      : [],            // роли текущего юзера
      userPermCodes  : [],            // int-codes текущего юзера

      saving:false
    }),

    /* ---------------- created ---------------- */
    async created () {
      try {
        /* 1. загружаем единый «словарь» */
        const { data: groups } = await axios.get('/api/stuff')

        /* ---------- 2. соберём ВСЕ роли ---------- */
        const roleSet = new Set()
        groups.forEach(g => {
          if (g.role)               roleSet.add(g.role)          // роль самой группы
          g.users?.forEach(u => u.roles.forEach(r => roleSet.add(r)))
        })
        this.allRoles = [...roleSet].sort()

        /* ---------- 3. соберём ВСЕ permissions ---- */
        const permMap = new Map()                               // code → name
        groups.forEach(g => {
          g.permissions?.forEach(p => permMap.set(+p.code, p.name))
          g.users?.forEach(u => u.permissions.forEach(
            p => permMap.set(+p.code, p.name)
          ))
        })
        this.allPerms = [...permMap]
          .map(([code,name]) => ({ code, name }))
          .sort((a,b)=>a.code-b.code)

        /* ---------- 4. найдём выбранного пользователя */
        let current = null
        let currentGroupRole = null

        for (const g of groups) {
          current = g.users?.find(u => u.id === this.userId)
          if (current) { currentGroupRole = g.role; break }
        }

        if (!current) throw new Error('user not found in /stuff')

        /* роли юзера + «родительская» роль группы */
        const roles = new Set(current.roles)
        if (currentGroupRole && currentGroupRole !== 'Без ролей')
          roles.add(currentGroupRole)

        this.userRoles     = [...roles]
        this.userPermCodes = current.permissions.map(p => +p.code)

      } catch (e) {
        console.error('Access.vue init', e)
        this.$toast?.error('❌ Не удалось загрузить данные')
      }
    },

    /* ---------------- methods ---------------- */
    methods:{
      prettify (r){ return r==='Без ролей' ? r : r[0].toUpperCase()+r.slice(1) },

      toggleRole (r){
        const i = this.userRoles.indexOf(r)
        i === -1 ? this.userRoles.push(r) : this.userRoles.splice(i,1)
      },
      togglePerm (code){
        const i = this.userPermCodes.indexOf(code)
        i === -1 ? this.userPermCodes.push(code) : this.userPermCodes.splice(i,1)
      },

      async save(){
        this.saving = true
        try{
          await axios.put(`/api/users/${this.userId}/roles-permissions`,{
            roles       : this.userRoles,
            permissions : this.userPermCodes        // int-codes !
          })
          this.$toast?.success('✅ Сохранено')
          this.$emit('close')
        }catch(e){
          console.error('Access.vue save', e)
          this.$toast?.error('❌ Ошибка сохранения')
        }finally{ this.saving = false }
      }
    }
  }
  </script>


  <style scoped>
  /* —— стили без изменений —— */
  :root{--from:#18BDD7;--to:#6BC6DA;font-family:Inter,sans-serif}
  .access-popup{display:flex;flex-direction:column;width:100%;max-width:480px;
               max-height:90vh;background:#fff;border-radius:18px;
               box-shadow:0 6px 22px rgba(0,0,0,.2);overflow:hidden}
  .popup__header{display:flex;align-items:center;justify-content:space-between;
                 padding:14px 20px;background:linear-gradient(135deg,var(--from),var(--to));
                 color:#fff}
  .popup__title{margin:0;font-size:18px;font-weight:600}
  .icon-btn{background:none;border:none;font-size:22px;color:#fff;cursor:pointer}
  .popup__body{flex:1;overflow-y:auto;padding:18px}
  .popup__footer{padding:16px;border-top:1px solid #eee}

  .block{margin-bottom:22px;border:1px solid #eceff1;border-radius:14px;overflow:hidden}
  .block__title{margin:0;padding:10px 16px;background:#f3f7fa;cursor:pointer;
                font-size:15px;font-weight:600;list-style:none}
  .block[open]>.block__title{border-bottom:1px solid #e0e4e7}

  .list{margin:0;padding:0;list-style:none}
  .list__row{display:flex;align-items:center;justify-content:space-between;
             padding:10px 16px;border-bottom:1px solid #f1f1f1;gap:10px}
  .list__row:last-child{border:none}
  .list__label{flex:1;min-width:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

  .switch{position:relative;width:44px;height:24px}
  .switch input{opacity:0;width:0;height:0}
  .slider{position:absolute;inset:0;border-radius:999px;background:#c7c9cc;transition:.3s}
  .slider::before{content:'';position:absolute;width:18px;height:18px;left:3px;top:3px;
                  border-radius:50%;background:#fff;transition:.3s}
  input:checked + .slider{background:#00c4e7}
  input:checked + .slider::before{transform:translateX(20px)}

  .save-btn{width:100%;padding:14px;border:none;border-radius:14px;font-size:16px;
            color:#fff;background:linear-gradient(135deg,var(--from),var(--to));
            box-shadow:0 3px 8px rgba(0,0,0,.18);cursor:pointer;transition:.15s}
  .save-btn:disabled{opacity:.6;cursor:progress}
  .save-btn:not(:disabled):active{transform:scale(.97)}
  </style>
  <style scoped>
  /* …ваши переменные */
  :root{
    --from:#18BDD7;
    --to:#6BC6DA;
    font-family:Inter,sans-serif;

    /*   ↓ новый цвет для основного текста  */
    --txt:#222;          /* можно #333 / #444 – как вам комфортно */
  }

  /* ====== header остаётся как был (белый текст на голубом) ====== */

  /* тело поп-апа */
  .popup__body{
    /* если хочется лёгкий фон вместо абсолютного белого: */
    background:#fafcfd;          /* очень светло-серо-голубой */
    color:var(--txt);            /* ← текст теперь тёмный */
  }

  /* блоки + строки: задаём текстовый цвет и чередование фона
     (зебра улучшает читаемость) */
  .list__row{
    color:var(--txt);
    background:#fff;             /* базовый белый */
  }
  .list__row:nth-child(2n){      /* каждая 2-я строка чуть серая */
    background:#f6f9fa;
  }

  /* подписи внутри строки (на всякий случай) */
  .list__label{
    color:inherit;               /* берёт --txt из .list__row */
  }

  /* переключатели не трогаем – они уже контрастные */

  /* footer */
  .popup__footer{
    background:#f0f4f7;          /* чтобы кнопка не «плавала» на белом */
  }

  /* кнопка сохранения — всё как было, только текст стал читабельным */
  .save-btn{
    color:#fff;                  /* белый на градиенте «сине-бирюза» */
  }
  </style>
