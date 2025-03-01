// router/index.js
import Vue from "vue";
import VueRouter from "vue-router";
import routes from "./routes"; // Import your routes file
import "primeicons/primeicons.css";

Vue.use(VueRouter);

const router = new VueRouter({
  mode: "history",
  routes,
});

// Navigation Guard
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem("token"); // Check if the user is authenticated by checking the token

  if (!token && to.path !== "/login") {
    // If no token and trying to access a protected page, redirect to login
    next("/login");
  } else if (token && to.path === "/login") {
    // If authenticated and trying to access the login page, redirect to dashboard
    next("/dashboard");
  } else {
    // If authenticated or on the login page, allow navigation
    next();
  }
});

export default router;
