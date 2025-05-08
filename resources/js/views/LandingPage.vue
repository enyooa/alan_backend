<template>
    <div class="landing-page">
      <!-- ═════════════════ HEADER (sticky pill) ══════════════════ -->
      <header class="lp-header">
        <img class="lp-logo" src="/assets/img/logo.png" alt="Alan logo"/>

        <nav class="header-nav">
          <ul>
            <li><a href="#hero">Главная</a></li>
            <li><a href="#pricing">Тарифы и цены</a></li>
            <li><a href="#solutions">Партнёрам</a></li>
            <li><a href="#contacts">Контакты</a></li>
          </ul>
        </nav>

        <div class="header-right">
          <router-link class="btn btn-light"   to="/login">Вход</router-link>
          <router-link class="btn btn-primary" to="/register">Регистрация</router-link>
        </div>
      </header>

      <!-- ═════════════════ HERO ══════════════════ -->
      <section id="hero" class="hero-section">
        <div class="hero-content">
          <div class="hero-background"></div>

          <div class="hero-text">
            <h1>Автоматизация торговли для розничных магазинов</h1>
            <p class="hero-subtitle">Инновационная система, которая помогает увеличить продажи и снизить затраты</p>

            <ul class="hero-bullets">
              <li>Складской учёт: контроль остатков и все операции</li>
              <li>Полная автоматизация рабочего места</li>
              <li>Готовая касса для продажи маркированного товара</li>
              <li>Мобильные приложения на Android и iOS</li>
              <li>Учёт покупателей</li>
              <li>Отчёты: прибыль, продажи и себестоимость</li>
              <li>Печать ценников, этикеток и штрих-кодов</li>
            </ul>

            <router-link class="btn cta-btn" to="/register-organization">
              Попробовать бесплатно
            </router-link>
          </div>
        </div>
      </section>

      <!-- ═════════════════ SOLUTIONS / PARTNERS ══════════════════ -->
<!-- ----------  📄  LandingPage.vue  ---------- -->
<section id="solutions" class="solutions-section">
    <h2>Готовые решения для автоматизации бизнеса</h2>

    <div class="solutions-grid">
      <!-- 1 -->
      <div class="solution-card">
        <fa icon="shopping-cart" class="sc-icon"/>
        <h3>Управление продажами</h3>
        <p>Запускайте кассу, анализируйте продажи и обслуживайте клиентов оперативно.</p>
      </div>

      <!-- 2 -->
      <div class="solution-card">
        <fa icon="user" class="sc-icon"/>
        <h3>Анализ клиентов</h3>
        <p>Получайте подробную информацию о покупателях и повышайте их лояльность.</p>
      </div>

      <!-- 3 -->
      <div class="solution-card">
        <fa icon="boxes" class="sc-icon"/>
        <h3>Складской учёт</h3>
        <p>Добавляйте, импортируйте и редактируйте товары. Контролируйте инвентаризацию.</p>
      </div>

      <!-- 4 -->
      <div class="solution-card">
        <fa icon="folder" class="sc-icon"/>
        <h3>Финансовый учёт</h3>
        <p>Планируйте расходы, контролируйте задолженности и формируйте отчёты.</p>
      </div>

      <!-- 5 -->
      <div class="solution-card">
        <fa icon="clipboard-list" class="sc-icon"/>
        <h3>Удобные отчёты</h3>
        <p>Ключевые показатели — выручка, прибыль, объём продаж — доступны в ре-тайме.</p>
      </div>

      <!-- 6 -->
      <div class="solution-card">
        <fa icon="check-circle" class="sc-icon"/>
        <h3>Удалённый доступ</h3>
        <p>Контролируйте магазин 24/7 через мобильное приложение или браузер.</p>
      </div>
    </div>
  </section>

      <!-- ═════════════════ PRICING ══════════════════ -->
      <!-- ══════════════ PRICING ══════════════ -->
<section id="pricing" class="pricing-section">
    <h2 class="pricing-title">Тарифные планы</h2>

    <div class="pricing-cards">

      <!-- ① Клиент -->
      <div class="pricing-card">
        <h3>Клиент</h3>
        <span class="price">0 ₸/м</span>

        <p class="benefits-caption">Список услуг</p>
        <ul class="price-benefits">
          <li>Оборот безлимитный</li>
          <li>Подтверждение поступления товара</li>
          <li>Подача заявки на товар</li>
          <li>РКО – оплата за товар</li>
          <li>Отчёты по поставщикам</li>
        </ul>

        <router-link class="btn reg-btn" to="/register-organization">
          Зарегистрироваться
        </router-link>
      </div>

      <!-- ② Посредник -->
      <div class="pricing-card">
        <h3>Посредник</h3>
        <span class="price">100 000 ₸/м</span>

        <p class="benefits-caption">Список услуг</p>
        <ul class="price-benefits">
          <li>Оборот до 1 млрд</li>
          <li>ИИ-голосовой набор</li>
          <li>Приход товара</li>
          <li>Учёт склада (списание)</li>
          <li>Кассовые операции</li>
          <li>Отчёты (по умолчанию)</li>
          <li>До 5 пользователей</li>
        </ul>

        <router-link class="btn reg-btn" to="/register-organization">
          Зарегистрироваться
        </router-link>
      </div>

      <!-- ③ Розница – популярный -->
      <div class="pricing-card popular">
        <span class="ribbon">☆ Популярный тариф</span>

        <h3>Розница</h3>
        <span class="price">200 000 ₸/м</span>

        <p class="benefits-caption">Список услуг</p>
        <ul class="price-benefits">
          <li>Оборот до 5 млрд</li>
          <li>1С-интеграция</li>
          <li>ИИ-голосовой набор</li>
          <li>Чат с клиентами</li>
          <li>Учёт доп. расходов</li>
          <li>Учёт склада</li>
          <li>Формирование заявок</li>
          <li>Отслеживание доставки</li>
          <li>Коммерческое предложение</li>
          <li>До 10 пользователей</li>
        </ul>

        <router-link class="btn reg-btn" to="/register-organization">
          Зарегистрироваться
        </router-link>
      </div>

      <!-- ④ Оптовик -->
      <div class="pricing-card">
        <h3>Оптовик</h3>
        <span class="price">500 000 ₸/м</span>

        <p class="benefits-caption">Список услуг</p>
        <ul class="price-benefits">
          <li>Оборот до 10 млрд</li>
          <li>1С-интеграция</li>
          <li>ИИ-голосовой набор</li>
          <li>Чат с клиентами</li>
          <li>Учёт доп. расходов</li>
          <li>Учёт склада</li>
          <li>Формирование заявок</li>
          <li>Отслеживание доставки</li>
          <li>Коммерческое предложение</li>
          <li>До 50 пользователей</li>
        </ul>

        <router-link class="btn reg-btn" to="/register-organization">
          Зарегистрироваться
        </router-link>
      </div>

      <!-- ⑤ Гранды -->
      <div class="pricing-card">
        <h3>Гранды</h3>
        <span class="price">1 000 000 ₸/м</span>

        <p class="benefits-caption">Список услуг</p>
        <ul class="price-benefits">
          <li>Оборот безлимитный</li>
          <li>Учёт доп. расходов</li>
          <li>Формирование заявок</li>
          <li>Отслеживание доставки</li>
          <li>Все новые фичи при релизах</li>
          <li>Безлимитные пользователи</li>
          <li>1С-интеграция</li>
          <li>ИИ-голосовой набор</li>
          <li>Чат с клиентами</li>
          <li>Реклама свежих товаров</li>
        </ul>

        <router-link class="btn reg-btn" to="/register-organization">
          Зарегистрироваться
        </router-link>
      </div>

    </div>
  </section>

      <!-- ═════════════════ MOBILE APP STRIP ══════════════════ -->
      <!-- ═════════════════ MOBILE APP STRIP ══════════════════ -->
<section id="mobile" class="mobile-app-section">
    <div class="mobile-inner">
      <!-- левая колонка — текст -->
      <div class="mobile-text">
        <h2>Мобильное приложение</h2>
        <p>
          Всегда имейте доступ к своему магазину благодаря мобильному
          приложению — закупка, продажа, отчёты и остатки прямо со смартфона.
        </p>

        <div class="mobile-app-stores">
          <img src="/assets/img/appstore.png"   alt="App Store" />
          <img src="/assets/img/googleplay.png" alt="Google Play" />
        </div>
      </div>

      <!-- правая колонка — увеличенный рендер телефона -->
      <div class="mobile-phone">
        <img src="/assets/img/mobile.png" alt="Phone UI" />
      </div>
    </div>
  </section>


      <!-- ═════════════════ FOOTER ══════════════════ -->
<footer id="contacts" class="lp-footer">
    <div class="footer-grid">
      <!-- 1. ЛОГО + слоган -->
      <div class="footer-col">
        <img src="/assets/img/logo.png" alt="ALAN" class="footer-logo" />
        <p class="footer-tagline">
          Автоматизация розничной торговли <br />и складского учёта
        </p>
      </div>

      <!-- 2. Блок «Ресурс» -->
      <div class="footer-col">
        <h3>Ресурс</h3>
        <ul>
          <li><a href="#pricing">Тарифы и цены</a></li>
          <li><router-link to="/agreement">Пользовательское соглашение</router-link></li>
          <li><a href="#">База знаний</a></li>
          <li><a href="#">Партнёрская программа</a></li>
        </ul>
      </div>

      <!-- 3. Блок «Контакты» -->
      <div class="footer-col">
        <h3>Контакты</h3>
        <ul class="footer-contacts">
          <li>📞&nbsp;+7&nbsp;777&nbsp;77&nbsp;77&nbsp;77</li>
          <li>✉️&nbsp;<a href="mailto:alan@gmail.com">alan@gmail.com</a></li>
          <li>🏢&nbsp;Ул.&nbsp;Пушкина,&nbsp;123</li>
        </ul>
      </div>
    </div>
  </footer>

    </div>
  </template>

  <script>
  export default {
    name: 'LandingPage',
  };
  </script>

  <style scoped>
  /* сразу после глобального reset (чтобы перекрыть default-color) */

  /* =============== GLOBAL RESET =============== */
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }
  a {
    text-decoration: none;
    color: inherit;
  }
  ul {
    list-style: none;
  }
  .btn {
    cursor: pointer;
    border: none;
    border-radius: 4px;
    padding: 0.6rem 1.2rem;
    font-weight: 500;
  }
  .btn-light {
    background-color: transparent;
    color: #fff;
    border: 1px solid #fff; /* White border for visibility on gradient */
  }
  .btn-primary {
    background-color: #00b3f3;
    color: #fff;
  }
  .cta-btn {
    background-color: #00b3f3;
    border-radius: 30px;
    font-size: 1.1rem;
    padding: 0.8rem 1.4rem;
  }

  /* =============== PAGE WRAPPER =============== */
  .landing-page{
    font-family:'Arial',sans-serif;
    color:#333;

    /* сам фон */
    background:
    url('/assets/img/linear2.jpg') left center / 200% auto no-repeat,
    linear-gradient(135deg,rgba(255,255,255,.5)0%,rgba(255,255,255,.9)100%);
  background-attachment:fixed;

  /*  ⬇️  минимум 4000 px – выберите любую цифру больше текущей */
  }


  /* ---------- STICKY, CENTERED PILL HEADER ---------- */
  .lp-header {
    position: sticky;        /* Stays at the top of viewport */
    top: 0;
    z-index: 999;

    background: linear-gradient(to right, #9ca7b8, #adc2d9);
    border-radius: 25px;     /* Pill shape */
    max-width: 1400px;
    margin: 0 auto 1rem;   /*  ← вместо 1rem auto  */
    padding: 0.6rem 1.5rem;

    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .lp-logo {
    max-height: 45px;
    margin-right: 1rem;
  }
  .header-nav ul {
    display: flex;
    gap: 1.5rem;
  }
  .header-nav a {
    color: #fff;
    font-weight: 500;
    transition: color 0.2s;
  }
  .header-nav a:hover {
    color: #c0fb63; /* subtle green hover color */
  }
  .header-right {
    display: flex;
    gap: 0.75rem;
  }

  /* ---------- HERO SECTION ---------- */
  .hero-section {
    position: relative;
    padding: 3rem 5%;
    display: flex;
    justify-content: center;
  }
  .hero-content {
    position: relative;
    width: 100%;
    max-width: 1200px;
    min-height: 450px;
  }
  .hero-background {
    position: absolute;
    top: 0;
    right: 0;
    width: 65%;
    height: 100%;
    background: url('/assets/img/banner.jpeg') center center no-repeat;
    background-size: cover;
    border-radius: 35% 65% 70% 30% / 30% 30% 70% 70%;
    z-index: 1;
  }
  .hero-text {
    position: relative;
    z-index: 2;
    max-width: 45%;
    display: flex;
    flex-direction: column;
    justify-content: center;
    height: 100%;
  }
  .hero-text h1 {
    font-size: 50px; /* bigger heading text */
    background: linear-gradient(to right, #333 40%, #C0FB63 60%);
    -webkit-background-clip: text;
    color: transparent;
    margin-bottom: 1rem;
  }
  .hero-subtitle {
    font-size: 1.2rem;
    margin-bottom: 1rem;
    color: #333;
  }
  .hero-bullets li {
    margin-bottom: 0.4rem;
    position: relative;
    padding-left: 1.4rem;
    color: #333;
  }
  .hero-bullets li::before {
    content: '✔';
    position: absolute;
    left: 0;
    color: #6bcc3c;
  }

  /* ---------- SOLUTIONS SECTION ---------- */
  .solutions-section{
    padding:4rem 5%;
    /* полупрозрачный «туман» + лёгкий blur */
    background:rgba(255,255,255,.5);
    backdrop-filter:blur(6px);
    border-radius:20px;
    /* отступы сверху/снизу от остальных блоков */
    margin:3rem 0;
  }
  .solutions-section h2{
    font-size:2.4rem;
    margin-bottom:2.5rem;
  }

  /* GRID 3 × 2; на мобильных превращается в одну колонку */
  .solutions-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(260px,1fr));
    gap:2rem;
  }

  .solution-card{
    background:rgba(255,255,255,.75);
    border-radius:28px;
    padding:2.2rem 1.8rem 2.5rem;
    text-align:center;

    /* тень как на макете */
    box-shadow:0 8px 22px rgba(0,0,0,.08);
    transition:transform .25s cubic-bezier(.2,.8,.4,1);
  }
  .solution-card:hover{
    transform:translateY(-4px);
  }

  /* иконки */
  .solution-card i{
    display:inline-block;
    font-size:46px;            /* замените на svg — удобней */
    color:#8EF255;             /* салатовый из макета */
    margin-bottom:1.2rem;
  }
  /*  Vue-scoped стиль: прокапываемся к path’у внутри <fa>  */
::v-deep .sc-icon svg path{
    fill: none;                 /* убираем заливку  */
    stroke: currentColor;       /* берём цвет из .sc-icon */
    stroke-width: 32;           /* толщина «обводки»      */
    stroke-linejoin: round;     /* ровные углы            */
  }

  /* ваш базовый класс — цвет / размер */
  .sc-icon{
    font-size:52px;
    color:#8EF255;
    margin-bottom:1.2rem;
  }

  /* заголовок + текст */
  .solution-card h3{
    font-size:1.15rem;
    margin-bottom:.9rem;
    font-weight:700;
  }
  .solution-card p{
    font-size:.92rem;
    line-height:1.45;
    color:#111;
  }

  /* мобильная адаптация */
  @media(max-width:768px){
    .solutions-section{padding:3rem 8%;}
    .solutions-grid{gap:1.5rem;}
  }

  /* ---------- PRICING SECTION ---------- */
  .pricing-section {
    padding: 3rem 5%;
  }
  .pricing-section h2 {
    text-align: center;
    margin-bottom: 2rem;
    font-size: 2rem;
  }
  .pricing-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
  }
  .pricing-card {
    border-radius: 8px;
    width: 220px;
    padding: 1.5rem;
    text-align: center;
    position: relative;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  }
  .pricing-card h3 {
    margin-bottom: 0.5rem;
  }
  .price {
    font-size: 1.5rem;
    color: #00b3f3;
    margin-bottom: 1rem;
    display: inline-block;
  }
  .price-benefits li {
    margin-bottom: 0.3rem;
  }
  .popular {
    border: 2px solid #00b3f3;
  }
  .popular-tag {
    position: absolute;
    top: -10px;
    right: -10px;
    background: #00b3f3;
    color: #fff;
    font-size: 0.8rem;
    padding: 0.3rem 0.6rem;
    border-radius: 4px;
  }

  /* ---------- MOBILE APP SECTION ---------- */
  /* ——— MOBILE APP SECTION ——— */
.mobile-app-section {
    position: relative;
    overflow: hidden;                       /* обрезаем лишнее после clip-path */
    background: linear-gradient(135deg,#18BDD7 0%,#6BC6DA 100%);
    /* диагональные верх/низ как на макете */
    clip-path: polygon(0 8%,100% 0,100% 92%,0 100%);
    color: #fff;
    margin: 4rem 0;
  }

  /* контейнер-выравнивание */
  .mobile-inner {
    max-width: 1200px;
    margin: 0 auto;
    padding: 5rem 5% 6rem;                  /* снизу чуть больше, чтобы не «резало» */
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 2rem;
  }

  /* ——— текстовая часть слева ——— */
  .mobile-text {
    flex: 1 1 380px;                        /* не ужимается меньше 380 px */
  }
  .mobile-text h2 {
    font-size: 2.4rem;
    margin-bottom: 1.5rem;
  }
  .mobile-text p {
    max-width: 540px;
    line-height: 1.45;
  }

  /* кнопки сто́ров */
  .mobile-app-stores {
    margin-top: 2.2rem;
    display: flex;
    gap: 1.5rem;
  }
  .mobile-app-stores img {
    max-height: 100px;
  }

  /* ——— телефон справа ——— */
  .mobile-phone {
    flex: 1 1 340px;
    text-align: right;
  }
  .mobile-phone img {
    width: 600px;               /* ↑ увеличен телефон */
    max-width: 100%;
    transform: translateY(-12%);/* лёгкий «выезд» вверх, как в макете */
    filter: drop-shadow(0 14px 28px rgba(0,0,0,.25));
  }

  /* ——— заголовок секции ——— */
.pricing-title{
    font-size:3rem;
    margin-bottom:2.5rem;
  }

  /* ——— контейнер карт ——— */
  .pricing-cards{
    display:flex;
    flex-wrap:wrap;
    gap:2.5rem;
    justify-content:center;
  }

  /* ——— карта тарифа ——— */
  .pricing-card{
    background: rgba(255,255,255,.5); /* ← было #fff */
    backdrop-filter: blur(4px);       /* лёгкое матовое стекло (опционально) */
      border-radius:28px;
    width:260px;
    padding:2.8rem 2rem 3.5rem;
    box-shadow:0 12px 24px rgba(0,0,0,.12);
    position:relative;
    transition:transform .25s ease;
  }
  .pricing-card:hover{ transform:translateY(-6px); }

  .pricing-card h3{
    font-size:1.35rem;
    text-align:center;
    margin-bottom:.75rem;
  }

  .price{
    display:block;
    text-align:center;
    font-size:2.6rem;
    font-weight:700;
    margin-bottom:2rem;
    color:#565656;
    letter-spacing:.5px;
  }

  .benefits-caption{
    font-size:.9rem;
    font-weight:600;
    color:#18BDD7;
    margin-bottom:.6rem;
  }

  .price-benefits{
    font-size:.93rem;
    line-height:1.35;
    margin-bottom:2.3rem;
  }

  /* ——— кнопка регистрации ——— */
  .reg-btn{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:.5rem;
    width:100%;
    height:48px;
    border-radius:9999px;
    background:linear-gradient(90deg,#18BDD7 0%,#37D5F4 100%);
    color:#fff;
    font-size:1rem;
    font-weight:600;
  }
  .reg-btn::before{
    content:'↗';
    font-size:1.15rem;
  }

  /* ——— «Популярный тариф» ——— */
  .ribbon{
    position:absolute;
    top:-1.8rem;
    left:50%;
    transform:translateX(-50%);
    background:#fff;
    color:#18BDD7;
    font-size:.9rem;
    font-weight:700;
    padding:.25rem .9rem;
    border-radius:999px;
    box-shadow:0 4px 12px rgba(0,0,0,.12);
  }
  .popular{
    outline:4px solid rgba(55,213,244,.35);
  }

  /* ——— адаптив ——— */
  @media(max-width:768px){
    .pricing-cards{ flex-direction:column; align-items:center; }
    .ribbon{ top:-1.4rem; }
  }

  /* ——— адаптив ——— */
  @media (max-width: 768px) {
    .mobile-app-section { clip-path: none; } /* диагонали убираем на совсем узких */
    .mobile-inner       { flex-direction: column; text-align: center; }
    .mobile-phone img   { width: 240px; transform: none; margin-top: 2rem; }
  }


  /* ---------- FOOTER ---------- */
  /* ——— ОБЁРТКА ФУТЕРА ——— */
.lp-footer {
    margin-top: -8rem;   /* подберите величину (5-8 rem обычно хватает) */

    position: relative;
    overflow: hidden;
    padding: 10rem 5% 6rem;
    color: #fff;

    /* фон: текстура + градиент поверх (как договорились ранее) */
    background:
      url('/assets/img/linear.jpg') center/cover no-repeat,
      linear-gradient(89.74deg, rgba(2, 89, 102, 0.9) 0%, rgba(2, 89, 102, 0.9) 0%,rgba(2, 89, 102, 0.9)  99.57%);
    background-blend-mode: overlay;
    clip-path: polygon(0 8%, 100% 0, 100% 100%, 0 100%);
}

  /* ——— GRID из трёх колонок ——— */
  .footer-grid {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 3rem;
  }

  /* ——— КОЛОНКИ ——— */
  .footer-col h3 {
    font-size: 1.3rem;
    margin-bottom: 1.2rem;
    font-weight: 600;
  }
  .footer-col ul li {
    margin-bottom: 0.6rem;
    line-height: 1.4;
  }
  .footer-col a {
    color: #fff;
    transition: opacity 0.2s;
  }
  .footer-col a:hover { opacity: 0.8; }

  /* ——— ЛОГО & СЛОГАН ——— */
  .footer-logo   { height: 60px; margin-bottom: 1rem; }
  .footer-tagline{ font-size: 1rem; line-height: 1.4; max-width: 260px; }

  /* ——— Контакты ——— */
  .footer-contacts li { list-style: none; }
  .footer-contacts a  { text-decoration: underline; }

  /* ——— АДАПТИВ ——— */
  @media (max-width: 768px) {
    .lp-footer       { padding: 3rem 8% 4rem; clip-path: none; }
    .footer-grid     { gap: 2rem; }
    .footer-logo     { height: 48px; }
  }


  /* ---------- RESPONSIVE BREAKPOINTS ---------- */
  @media (max-width: 992px) {
    .hero-background {
      width: 70%;
    }
    .hero-text {
      max-width: 50%;
    }
  }

  @media (max-width: 768px) {
    .lp-header {
      border-radius: 20px;
      margin: 0.5rem auto;
      padding: 0.5rem 1rem;
      width: 95%; /* slightly narrower on mobile */
    }
    .lp-logo {
      max-height: 35px;
    }
    .hero-section {
      justify-content: flex-start;
      padding: 2rem;
    }
    .hero-content {
      min-height: auto;
    }
    .hero-background {
      width: 100%;
      border-radius: 0;
    }
    .hero-text {
      max-width: 100%;
      padding: 1rem 0;
    }
    .solutions-grid {
      grid-template-columns: 1fr;
    }
    .pricing-cards {
      flex-direction: column;
      align-items: center;
    }
    .mobile-app-content {
      flex-direction: column;
    }
    .footer-top {
      flex-direction: column;
      align-items: flex-start;
    }
  }
  </style>
