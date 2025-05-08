<!-- src/views/RegisterChoice.vue -->
<template>
    <div class="wrap">
      <div class="card">
        <h2>Выберите тип аккаунта</h2>

        <router-link to="/register-individual" class="tile">
          <span>Физическое лицо</span>
        </router-link>

        <router-link to="/register-organization" class="tile">
          <span>Юридическое лицо<br />(компания)</span>
        </router-link>

        <router-link class="link-back" to="/login">
          Уже есть аккаунт? Войти
        </router-link>
      </div>
    </div>
  </template>

  <style scoped>
  .wrap{
    min-height:100vh;
    display:flex;justify-content:center;align-items:center;
    background:var(--app-bg);
  }
  .card{
    width:320px;background:var(--glass-bg);backdrop-filter:var(--glass-blur);
    border-radius:20px;padding:32px 28px;box-shadow:0 12px 28px rgba(0,0,0,.12);
    text-align:center;
  }
  .card h2{margin-bottom:24px;color:#222}
  .tile{
    display:block;margin-bottom:18px;padding:18px 14px;border-radius:14px;
    background:var(--brand-from);color:#fff;font-size:16px;font-weight:600;
    box-shadow:0 4px 10px rgba(0,0,0,.14);transition:.15s;
  }
  .tile:hover{filter:brightness(.95)}
  .link-back{display:inline-block;margin-top:8px;color:var(--brand-from)}
  </style>
