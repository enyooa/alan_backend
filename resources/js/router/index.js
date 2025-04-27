// router/index.js   (Vue-2)
import Vue       from "vue";
import VueRouter from "vue-router";
import MainLayout from "../layouts/MainLayout.vue";
import rawRoutes  from "./routes";    // your list without /login

Vue.use(VueRouter);

export default new VueRouter({
  mode:"history",
  routes:[
    { path:"/login", component:() => import("../views/Auth/Login.vue") },
    { path:"/", component:MainLayout, children:rawRoutes.filter(r => r.path!=="/login") },
    { path:"*", redirect:"/dashboard" },
  ],
});
