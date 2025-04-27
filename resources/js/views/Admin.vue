<!-- resources/js/views/Admin.vue -->
<template>
    <section class="dashboard">
      <h2 class="page-title">Панель управления</h2>

      <!-- KPI cards --------------------------------------------------------- -->
      <div class="cards">
        <div class="card">
          <span class="kpi">124</span>
          <span class="label">Новых заявок</span>
        </div>
        <div class="card">
          <span class="kpi">38 000 ₸</span>
          <span class="label">Выручка сегодня</span>
        </div>
        <div class="card">
          <span class="kpi">7</span>
          <span class="label">Низкий остаток</span>
        </div>
      </div>

      <!-- Put charts / tables / recent activity here ------------------------ -->
      <div class="placeholder">
        📊 Здесь могут быть графики, таблицы или другая информация
      </div>
    </section>
  </template>

  <script>
  export default {
    name: "DashboardView",
  };
  </script>

  <style scoped>
  /* ---- basic layout ---------------------------------------------------- */
  .dashboard {
    padding: 24px;
    background: #f5f5f5;
    min-height: calc(100vh - 56px); /* 56 px — высота Header'а */
  }

  .page-title {
    font-size: 22px;
    font-weight: 600;
    margin: 0 0 24px;
  }

  /* ---- KPI cards ------------------------------------------------------- */
  .cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
  }

  .card {
    background: #fff;
    border-radius: 12px;
    padding: 18px;
    box-shadow: 0 2px 5px rgba(0,0,0,.06);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }

  .kpi   { font-size: 28px; font-weight: 700; line-height: 1; }
  .label { font-size: 14px; color: #555; margin-top: 6px; }

  /* ---- placeholder block ---------------------------------------------- */
  .placeholder {
    height: 260px;
    border: 2px dashed #c7d7e4;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #748aa3;
    font-size: 16px;
  }
  </style>
