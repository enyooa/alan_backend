<template>
    <div class="landing-page">
      <!-- HEADER (Sticky + Centered Gradient) -->
      <header class="lp-header">
        <div class="header-left">
          <img class="lp-logo" src="/assets/img/logo.png" alt="Alan Logo" />
        </div>
        <nav class="header-nav">
          <ul>
            <li><a href="#">Главная</a></li>
            <li><a href="#">Тарифы и цены</a></li>
            <li><a href="#">Партнерам</a></li>
            <li><a href="#">Контакты</a></li>
          </ul>
        </nav>
        <div class="header-right">
          <button class="btn btn-light">Вход</button>
          <button class="btn btn-primary">Регистрация</button>
        </div>
      </header>

      <!-- HERO SECTION -->
      <section class="hero-section">
        <div class="hero-content">
          <div class="hero-background"></div>
          <div class="hero-text">
            <h1>Автоматизация торговли для розничных магазинов</h1>
            <p class="hero-subtitle">
              Инновационная система, которая помогает увеличить продажи и снизить затраты
            </p>
            <ul class="hero-bullets">
              <li>Складской учет: контроль остатков и все операции</li>
              <li>Полная автоматизация рабочего места</li>
              <li>Готовая касса для продажи марочного товара</li>
              <li>Мобильные приложения на Android и iOS</li>
              <li>Учет покупателей</li>
              <li>Отчеты: прибыль, продажи и себестоимость</li>
              <li>Печать ценников, этикеток и штрихкодов</li>
            </ul>
            <button class="btn cta-btn">Попробовать бесплатно</button>
          </div>
        </div>
      </section>

      <!-- SOLUTIONS SECTION -->
      <section class="solutions-section">
        <h2>Готовые решения для автоматизации бизнеса</h2>
        <div class="solutions-grid">
          <div class="solution-card">
            <h3>Управление продажами</h3>
            <p>
              Запускайте кассу, анализируйте продажи и обслуживайте клиентов оперативно.
            </p>
          </div>
          <div class="solution-card">
            <h3>Анализ клиентов</h3>
            <p>
              Получайте подробную информацию о ваших покупателях, повышайте лояльность.
            </p>
          </div>
          <div class="solution-card">
            <h3>Складской учет</h3>
            <p>
              Добавляйте, импортируйте и редактируйте товары. Перемещайте, списывайте и контролируйте инвентаризацию.
            </p>
          </div>
          <div class="solution-card">
            <h3>Финансовый учет</h3>
            <p>
              Планируйте расходы, контролируйте задолженности и формируйте сводные финансовые отчеты.
            </p>
          </div>
          <div class="solution-card">
            <h3>Удобные отчеты</h3>
            <p>
              Все ключевые показатели (выручка, прибыль, объем продаж) доступны в реальном времени.
            </p>
          </div>
          <div class="solution-card">
            <h3>Удобный доступ</h3>
            <p>
              Доступ из мобильного приложения или с компьютера — все под рукой.
            </p>
          </div>
        </div>
      </section>

      <!-- PRICING SECTION -->
      <section class="pricing-section">
        <h2>Тарифные планы</h2>
        <div class="pricing-cards">
          <div class="pricing-card">
            <h3>Клиент</h3>
            <span class="price">0₸/М</span>
            <ul class="price-benefits">
              <li>Список услуг:</li>
              <li>Обмен с 1C</li>
              <li>Поддержка</li>
              <li>Ограниченный функционал</li>
            </ul>
            <button class="btn btn-primary">Зарегистрироваться</button>
          </div>
          <div class="pricing-card">
            <h3>Посредник</h3>
            <span class="price">100 000₸/М</span>
            <ul class="price-benefits">
              <li>Список услуг</li>
              <li>Обмен с 1C</li>
              <li>ИМ-интеграции</li>
              <li>Учёт персонала</li>
            </ul>
            <button class="btn btn-primary">Зарегистрироваться</button>
          </div>
          <div class="pricing-card popular">
            <h3>Розница</h3>
            <span class="price">200 000₸/М</span>
            <div class="popular-tag">Популярный тариф</div>
            <ul class="price-benefits">
              <li>Список услуг</li>
              <li>Обмен с 1C</li>
              <li>ИМ-интеграции</li>
              <li>Учёт FFД</li>
              <li>Учёт покупателей</li>
            </ul>
            <button class="btn btn-primary">Зарегистрироваться</button>
          </div>
          <div class="pricing-card">
            <h3>Оптовик</h3>
            <span class="price">500 000₸/М</span>
            <ul class="price-benefits">
              <li>Список услуг</li>
              <li>Обмен с 1C</li>
              <li>Доп. модули</li>
              <li>Для 50 пользователей</li>
            </ul>
            <button class="btn btn-primary">Зарегистрироваться</button>
          </div>
          <div class="pricing-card">
            <h3>Гранды</h3>
            <span class="price">1000 000₸/М</span>
            <ul class="price-benefits">
              <li>Список услуг</li>
              <li>Обмен с 1C</li>
              <li>Все возможности системы</li>
              <li>Без лимитов</li>
            </ul>
            <button class="btn btn-primary">Зарегистрироваться</button>
          </div>
        </div>
      </section>

      <!-- MOBILE APP SECTION -->
      <section class="mobile-app-section">
        <h2>Мобильное приложение</h2>
        <p>
          Всегда имейте доступ к вашему магазину благодаря мобильному приложению.
          Операции (закупка, продажа, перемещение, инвентаризация), отчеты, остатки и
          денежные показатели — всё у вас под рукой.
        </p>
        <div class="mobile-app-content">
          <div class="mobile-app-stores">
            <img src="/assets/img/appstore.png" alt="App Store" />
            <img src="/assets/img/googleplay.png" alt="Google Play" />
          </div>
          <div class="mobile-phone">
            <img src="/assets/img/phone-ui.png" alt="Phone UI" />
          </div>
        </div>
      </section>

      <!-- FOOTER -->
      <footer class="lp-footer">
        <div class="footer-top">
          <img src="/assets/img/logo.png" alt="Alan Logo" class="footer-logo" />
          <div class="footer-links">
            <h3>Ресурс</h3>
            <ul>
              <li><a href="#">Тарифы и цены</a></li>
              <li><a href="#">Онлайн-касса 54-ФЗ</a></li>
              <li><a href="#">Партнер CloudShop</a></li>
              <li><a href="#">Партнерская программа</a></li>
              <li><a href="#">База знаний</a></li>
              <li><a href="#">Блог</a></li>
              <li><a href="#">Служба поддержки</a></li>
              <li><a href="#">Лицензионный договор (Оферта)</a></li>
              <li><a href="#">Вакансии</a></li>
              <li><a href="#">Реквизиты компании</a></li>
              <li><a href="#">Для бизнеса</a></li>
            </ul>
          </div>
          <div class="footer-contacts">
            <h3>Контакты</h3>
            <p>+7 777 77 77 77</p>
            <p>alan@gmail.com</p>
            <p>Улица Пушкина, дом 123</p>
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
  .landing-page {
    font-family: 'Arial', sans-serif;
    color: #333;
  }

  /* ---------- STICKY, CENTERED PILL HEADER ---------- */
  .lp-header {
    position: sticky;        /* Stays at the top of viewport */
    top: 0;
    z-index: 999;

    background: linear-gradient(to right, #9ca7b8, #adc2d9);
    border-radius: 25px;     /* Pill shape */
    max-width: 1100px;
    margin: 1rem auto;       /* Center horizontally + top margin */
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
    background: #ffffff;
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
  .solutions-section {
    padding: 3rem 5%;
    background: #f9fafc;
  }
  .solutions-section h2 {
    text-align: center;
    margin-bottom: 2rem;
    font-size: 2rem;
  }
  .solutions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
  }
  .solution-card {
    background: #fff;
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    text-align: center;
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
    background: #fff;
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
  .mobile-app-section {
    background: #00b3f3;
    color: #fff;
    padding: 3rem 5%;
    position: relative;
  }
  .mobile-app-section h2 {
    margin-bottom: 1rem;
    font-size: 2rem;
  }
  .mobile-app-content {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    margin-top: 2rem;
    align-items: center;
  }
  .mobile-app-stores img {
    max-height: 40px;
    margin-right: 1rem;
  }
  .mobile-phone img {
    max-width: 200px;
  }

  /* ---------- FOOTER ---------- */
  .lp-footer {
    background: #f7f8fa;
    padding: 2rem 5%;
  }
  .footer-top {
    display: flex;
    flex-wrap: wrap;
    gap: 2rem;
    align-items: flex-start;
  }
  .footer-logo {
    max-height: 40px;
  }
  .footer-links,
  .footer-contacts {
    min-width: 150px;
  }
  .footer-links ul {
    margin-top: 0.5rem;
  }
  .footer-links a {
    color: #333;
    font-size: 0.95rem;
  }
  .footer-links h3,
  .footer-contacts h3 {
    margin-bottom: 0.5rem;
    font-size: 1.1rem;
  }
  .footer-contacts p {
    margin-bottom: 0.3rem;
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
