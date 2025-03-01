require('./bootstrap');

import Vue from 'vue';
import App from './components/App.vue'; // This is your main component
import router from './router';
import store from './store'; // If using Vuex
import axios from 'axios';
import "primeicons/primeicons.css";

// Before making any requests, check and set the token globally
const token = localStorage.getItem("token");
if (token) {
  axios.defaults.headers.common["Authorization"] = `Bearer ${token}`;
} else {
  console.error("No token found in localStorage");
}

// 🔹 Initialize Vue
new Vue({
    router,
    store, // Optional
    render: (h) => h(App),
}).$mount('#app');
