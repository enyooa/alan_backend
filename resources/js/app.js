require("./bootstrap");

import Vue   from "vue";
import App   from "./components/App.vue";
import router from "./router";
import store  from "./store";
import axios  from "axios";
import "primeicons/primeicons.css";
import { library } from '@fortawesome/fontawesome-svg-core'
import { faShoppingCart, faUser, faBoxes, faFolder,
         faClipboardList, faCheckCircle } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'
library.add(faShoppingCart, faUser, faBoxes, faFolder, faClipboardList, faCheckCircle)
Vue.component('fa', FontAwesomeIcon)

require('./plugins/axios');   // ⬅️ ДОБАВИЛИ одну строку

const token = localStorage.getItem("token");
if (token) axios.defaults.headers.common.Authorization = `Bearer ${token}`;

new Vue({ router, store, render: h => h(App) }).$mount("#app");
