// router/index.js   (Vue-2)
import Vue       from "vue";
import VueRouter from "vue-router";
import MainLayout from "../layouts/MainLayout.vue";
import rawRoutes  from "./routes";    // your list without /login
import LandingPage from "../views/LandingPage.vue";
import RegisterOrganization from "../views/Auth/RegisterOrganization.vue";
import RegisterIndividual from "../views/Auth/Register.vue";
import RegisterChoice from "../views/Auth/RegisterChoice.vue";

Vue.use(VueRouter);

export default new VueRouter({
  mode:"history",
  routes:[
     { path: "/", component: LandingPage, name: "home" },

    { path:"/login", component:() => import("../views/Auth/Login.vue") },
    { path: "/agreement", component: () => import("../views/UserAgreement.vue"), name:"agreement" },
    { path:'/register',            component:RegisterChoice },
    { path:'/register-individual', component:RegisterIndividual },
    { path:'/register-organization', component:RegisterOrganization },
    {                 // mount layout at /app to avoid path clash with "/"
        path: "/app",
        component: MainLayout,
        children: rawRoutes,        // rawRoutes no longer contains "/"
    },    { path:"*", redirect:"/dashboard" },
  ],
});
