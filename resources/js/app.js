require("./bootstrap");

import Vue   from "vue";
import App   from "./components/App.vue";
import router from "./router";
import store  from "./store";
import axios  from "axios";
import "primeicons/primeicons.css";

const token = localStorage.getItem("token");
if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`;

new Vue({ router, store, render: h => h(App) }).$mount("#app");
