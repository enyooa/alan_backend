<!-- resources/js/views/Access.vue -->
<template>
    <div class="access-page">
      <h2 class="title">Роли и доступы</h2>

      <!-- ╔══ РОЛИ ══╗ -->
      <section class="card">
        <h3 class="card-title">Настройка роли</h3>

        <div v-for="r in allRoles" :key="r" class="row">
          <span class="row-label">{{ beautify(r) }}</span>

          <!-- кастом-switch -->
          <label class="switch">
            <input
              type="checkbox"
              :checked="userRoles.includes(r)"
              @change="toggleRole(r)"
            />
            <span class="slider"></span>
          </label>
        </div>
      </section>

      <!-- ╔══ ПРАВА ══╗ -->
      <section class="card">
        <h3 class="card-title">Настройки доступа</h3>

        <div v-for="p in allPerms" :key="p.code" class="row">
          <span class="row-label">{{ p.name }}</span>

          <label class="switch">
            <input
              type="checkbox"
              :checked="userPerms.includes(p.code)"
              @change="togglePerm(p.code)"
            />
            <span class="slider"></span>
          </label>
        </div>
      </section>

      <button class="save-btn" @click="save">Сохранить</button>
    </div>
  </template>

  <script>
  import axios from "axios";

  export default {
    name: "Access",
    props: ["userId"],

    data: () => ({
      /* из /api/stuff */
      allRoles : [],                   // ["admin", "packer", …]
      allPerms : [],                   // [{ code:1102, name:"Накладные" }, …]

      /* выбранные чекбоксы */
      userRoles: [],
      userPerms: [],
    }),

    created() {
      this.fetchInitial();
    },

    methods: {
      /* ───────── загрузка данных ───────── */
      async fetchInitial() {
        try {
          const { data: groups } = await axios.get("/api/stuff");

          /* 1) все возможные роли */
          const roleSet = new Set();
          groups.forEach(g => {
            if (g.role) roleSet.add(g.role);
            g.users.forEach(u => u.roles.forEach(r => roleSet.add(r)));
          });
          this.allRoles = Array.from(roleSet).sort();

          /* 2) все возможные права (code → name) */
          const map = {};
          groups.forEach(g =>
            g.users.forEach(u =>
              u.permissions.forEach(([name, code]) => {
                if (!map[code]) map[code] = name;
              })
            )
          );
          this.allPerms = Object.entries(map)
            .map(([code, name]) => ({ code: Number(code), name }))
            .sort((a, b) => a.code - b.code);

          /* 3) данные текущего пользователя */
          const uid = Number(this.userId);
          for (const g of groups) {
            const user = g.users.find(u => u.id === uid);
            if (user) {
              this.userRoles = [...user.roles];
              this.userPerms = user.permissions.map(p => Number(p[1])); // только код
              break;
            }
          }
        } catch (e) {
          console.error("Access.vue fetchInitial error:", e);
        }
      },

      beautify(r) {
        return r === "Без ролей" ? r : r[0].toUpperCase() + r.slice(1);
      },

      /* ───────── чекбоксы ───────── */
      toggleRole(r) {
        const i = this.userRoles.indexOf(r);
        i === -1 ? this.userRoles.push(r) : this.userRoles.splice(i, 1);
      },

      togglePerm(code) {
        const i = this.userPerms.indexOf(code);
        i === -1 ? this.userPerms.push(code) : this.userPerms.splice(i, 1);
      },

      /* ───────── сохранение на сервер ───────── */
      async save() {
        try {
          await axios.put(`/api/users/${this.userId}/roles-permissions`, {
            roles:       this.userRoles,
            permissions: this.userPerms,
          });
          this.$toast?.success("Сохранено");
          this.$router.back();
        } catch (e) {
          console.error("Access.vue save error:", e);
          this.$toast?.error("Ошибка сохранения");
        }
      },
    },
  };
  </script>

  <style scoped>
  /* базовый фон и центровка */
  .access-page { max-width:640px;margin:0 auto;padding:1rem; }
  .title       { margin:.3rem 0 1.2rem; font-size:1.4rem; font-weight:600; color:#0096b4; }

  /* карточка блоков */
  .card       { background:#f7f7f7;border-radius:18px;padding:1rem 1.2rem;margin-bottom:1.5rem;
                box-shadow:0 2px 6px rgba(0,0,0,.05); }
  .card-title { margin:0 0 .8rem; font-weight:600; font-size:1.05rem; }

  /* строка внутри карточки */
  .row        { display:flex;align-items:center;justify-content:space-between;padding:.5rem 0; }
  .row-label  { max-width:70%; }

  /* кастомный switch */
  .switch              { position:relative; display:inline-block; width:46px; height:24px; }
  .switch input        { opacity:0; width:0; height:0; }
  .slider              { position:absolute; inset:0; border-radius:24px;
                          background:#bfc0c1; transition:.3s; cursor:pointer; }
  .slider::before      { content:""; position:absolute; height:18px; width:18px;
                          left:3px; top:3px; border-radius:50%;
                          background:#ffffff; transition:.3s; }
  input:checked + .slider      { background:#02c0e0; }
  input:checked + .slider::before { transform:translateX(22px); }

  /* кнопка */
  .save-btn   { width:100%; padding:1rem; border:none; border-radius:14px; font-size:1.1rem;
                color:#fff; background:linear-gradient(90deg,#02c0e0,#1fa4d5);
                box-shadow:0 3px 6px rgba(0,0,0,.15); cursor:pointer; }
  .save-btn:active { transform:scale(.98); }
  </style>
