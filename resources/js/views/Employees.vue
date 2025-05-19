<!-- resources/js/views/UsersPage.vue -->
<template>
    <div class="dashboard-container">
      <!-- ← Sidebar / Header оставил закомментированными, как у вас -->

      <div class="main-content">
        <main class="content">
          <h2 class="page-title">Управление пользователями</h2>

          <!-- ▸ Create-dropdown -->
          <div class="create-dropdown">
            <label>Создать:</label>
            <select v-model="createSelection"
                    @change="openCreateModal"
                    class="dropdown-select">
              <option value="">Выберите...</option>
              <option value="employee">Сотрудника</option>
              <option value="role">Присвоить роль</option>
            </select>
          </div>

          <!-- ▸ Filters -->
          <div class="user-filter">
            <input v-model="searchQuery"
                   type="text"
                   class="search-box"
                   placeholder="🔍 Поиск (Имя, Фамилия, Телефон)…"
                   @input="filterUsers"/>

            <select v-model="roleFilter" class="filter-select" @change="filterUsers">
              <option value="">Все роли</option>
              <option v-for="(ru, en) in roleMapCreate" :key="en" :value="en">{{ ru }}</option>
            </select>
          </div>

          <!-- ▸ Users-table -->
          <div class="table-container">
            <table class="users-table">
              <thead>
                <tr>
                  <th>Имя</th><th>Фамилия</th><th>Отчество</th>
                  <th>Телефон</th><th>Роли</th><th>Действия</th>
                </tr>
              </thead>

              <tbody>
                <tr v-for="u in filteredUsers" :key="u.id">
                  <td>{{ u.first_name }}</td>
                  <td>{{ u.last_name }}</td>
                  <td>{{ u.surname || '—' }}</td>
                  <td>{{ u.whatsapp_number }}</td>
                  <td>{{ u.roles.map(r => rolesMap[r.name] || r.name).join(', ') }}</td>
                  <td>
                    <button class="edit-btn"   @click="openEditUserModal(u)">✏️</button>
                    <button class="access-btn" @click="openAccess(u)">🔑</button>
                    <button class="delete-btn" @click="deleteUser(u)">🗑</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- ▸ Access-modal -->
          <div v-if="showAccessModal" class="modal-overlay">
            <div class="modal-container access-modal">
              <Access :user-id="accessUserId" @close="closeAccessModal"/>
              <button class="close-btn" @click="closeAccessModal">Закрыть</button>
            </div>
          </div>

          <!-- ▸ остальные модалы (Employee / Role / Edit …) оставлены без изменений -->
        </main>
      </div>
    </div>
  </template>

  <script>
  import axios   from '@/plugins/axios'        // ваш инстанс
  import Access  from '@/views/Access.vue'     // актуальный путь

  export default {
    name: 'UsersPage',
    components: { Access },

    data: () => ({
      /* таблица */
      users: [],            // полный список
      filteredUsers: [],    // после фильтров
      searchQuery : '',
      roleFilter  : '',

      /* локализация ролей */
      rolesMap: {
        admin:'Администратор', client:'Клиент', cashbox:'Касса',
        packer:'Упаковщик', storager:'Кладовщик', courier:'Курьер',
        superadmin:'Superadmin', 'Без ролей':'Без ролей'
      },
      roleMapCreate:{       // то же для фильтра
        admin:'Администратор', client:'Клиент', cashbox:'Касса',
        packer:'Упаковщик', storager:'Кладовщик', courier:'Курьер',
        superadmin:'Superadmin', 'Без ролей':'Без ролей'
      },

      /* dropdown + модалы (старые) */
      createSelection:'',

      /* Access-modal */
      showAccessModal:false,
      accessUserId   :null,
    }),

    created () { this.fetchUsers() },

    methods:{
      /* ---------- загрузка из /api/stuff ---------- */
      async fetchUsers () {
        const { data: groups } = await axios.get('/api/stuff')

        const list = []

        groups.forEach(g => {
          g.users?.forEach(u => {
            /* roles: берём массив из пользователя + роль самой группы */
            const roles = new Set(u.roles)
            if (g.role && g.role !== 'Без ролей') roles.add(g.role)

            list.push({
              ...u,
              whatsapp_number: u.whatsapp,              // для единого имени поля
              roles: [...roles].map(r => ({ name: r })),// превратили в объекты {name:'…'}
              /* permissions оставляем как пришли (нужны Access.vue) */
            })
          })
        })

        this.users = this.filteredUsers = list
      },

      /* ---------- фильтры ---------- */
      filterUsers () {
        let res = [...this.users]
        const q = this.searchQuery.toLowerCase()

        if (q) res = res.filter(u =>
          (u.first_name + u.last_name + (u.whatsapp_number || '')).toLowerCase().includes(q)
        )

        if (this.roleFilter)
          res = res.filter(u => u.roles?.some(r => r.name === this.roleFilter))

        this.filteredUsers = res
      },

      /* ---------- Access-modal ---------- */
      openAccess (u) {
        this.accessUserId  = u.id
        this.showAccessModal = true
      },
      closeAccessModal () {
        this.showAccessModal = false
        this.accessUserId = null
        /* после закрытия обновляем список, чтобы таблица отразила изменения */
        this.fetchUsers().then(this.filterUsers)
      },

      /* ---------- прочие методы (edit / delete / create) — без изменений ---------- */
    }
  }
  </script>

<style scoped>
/* ---------- design tokens ---------- */
:root{
  --c-grad-start:#18BDD7;
  --c-grad-end  :#6BC6DA;
  --c-primary   :#0288d1;
  --c-danger    :#f44336;
  --c-bg        :#f5f7fa;
  --c-card      :rgba(255,255,255,.75);
  --radius      :12px;
  --shadow      :0 6px 12px rgba(0,0,0,.08);
  --blur        :8px;
  font-family:'Inter',system-ui,Helvetica,Arial,sans-serif;
}

/* ---------- layout ---------- */
.dashboard-container{
  display:flex;
  min-height:100vh;
  background:linear-gradient(135deg,var(--c-grad-start) 0%,var(--c-grad-end) 100%);
}
.main-content{flex:1;padding:20px;background:transparent}
.content     {max-width:1100px;margin:0 auto}

/* ---------- headline ---------- */
.page-title{
  color:#18BDD7;text-align:center;margin:0 0 24px;
  font-size:clamp(20px,4vw,32px);font-weight:600;
  text-shadow:0 1px 3px rgba(0,0,0,.2);
}

/* ---------- filter & dropdown block ---------- */
.create-dropdown,
.user-filter{display:flex;align-items:center;gap:10px;margin-bottom:18px}
.dropdown-select,.filter-select,.search-box{
  height:40px;padding:0 12px;border-radius:var(--radius);
  border:none;background:#fff;font-size:14px;flex-shrink:0;
}
.search-box{flex:1;min-width:160px}

/* ---------- table / card ---------- */
.table-container{
  background:var(--c-card);border-radius:var(--radius);
  box-shadow:var(--shadow);backdrop-filter:blur(var(--blur));
  overflow-x:auto;margin-bottom:32px;
}
.users-table{width:100%;border-collapse:collapse;min-width:680px}
.users-table thead{background:rgba(255,255,255,.25)}
.users-table th,.users-table td{
  padding:12px 10px;text-align:center;font-size:14px;
  border-bottom:1px solid rgba(0,0,0,.05);
  color:#00394e;
}

/* ---------- buttons ---------- */
button{cursor:pointer;transition:.25s ease}
.edit-btn,
.access-btn,
.delete-btn{
  border:none;padding:6px 10px;border-radius:var(--radius);
  display:inline-flex;align-items:center;gap:4px;font-size:16px;
}
.edit-btn   {background:transparent;color:#333}
.edit-btn:hover{color:var(--c-primary)}
.access-btn {background:var(--c-primary);color:#fff}
.access-btn:hover{filter:brightness(1.1)}
.delete-btn {background:var(--c-danger);color:#fff}
.delete-btn:hover{filter:brightness(1.1)}

/* ---------- modal ---------- */
.modal-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.4);
  display:flex;align-items:center;justify-content:center;z-index:1000;
  padding:16px;
}
.modal-container{
  background:var(--c-card);border-radius:var(--radius);
  box-shadow:var(--shadow);backdrop-filter:blur(var(--blur));
  width:100%;max-width:720px;position:relative;padding:24px
}
.close-btn{
  margin-top:14px;width:100%;border:none;padding:12px;
  border-radius:var(--radius);background:var(--c-danger);color:#fff;
  font-size:15px
}
.close-btn:hover{filter:brightness(1.1)}

/* ---------- mobile (<600 px): превращаем строки таблицы в «карточки» ---------- */
@media(max-width:600px){
  .users-table{display:block}
  .users-table thead{display:none}
  .users-table tbody{display:grid;gap:12px}
  .users-table tr{
    display:grid;padding:12px;background:rgba(255,255,255,.65);
    border-radius:var(--radius);box-shadow:var(--shadow);
    grid-template-columns:repeat(2,1fr);grid-row-gap:6px
  }
  .users-table td{border:none;text-align:left;padding:4px 6px}
  .users-table td:nth-child(5){grid-column:1/3}
  .users-table td:nth-child(6){grid-column:1/3;text-align:right}
}
</style>
