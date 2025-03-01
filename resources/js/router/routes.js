import Login from "../views/Auth/Login.vue";
import Dashboard from "../views/Admin.vue";
import AccountView from "../views/AccountView.vue";
import Access from '../views/Access.vue';
import Employees from "../views/Employees.vue";
import CounterAgent from "../views/ConterAgent.vue";
import ProductCards from "../views/ProductCards.vue";
import Accounts from "../views/Accounts.vue";
import Reports from "../views/Reports.vue";
import CustomerDebtReport from "../views/CustomerDebtReport.vue";
import ReceiveProducts from "../views/ReceiveProducts.vue";
import OperationHistory from "../views/OperationHistory.vue";
import TestProducts from "../views/TestProducts.vue";

export default [
  { path: "/", redirect: "/dashboard" }, // Default to the dashboard
  { path: "/login", component: Login, name: "login" },
  { path: "/dashboard", component: Dashboard, name: "dashboard" },
  { path: "/account", component: AccountView, name: "account" },
  { path: '/access', component: Access, name: 'access' },
  { path: "/employees", component: Employees, name: 'employees' },
  { path: "/clients", component: CounterAgent, name: "clients" },
  { path: "/product-cards", component: ProductCards, name: "product-cards" },
  { path: "/accounts", component: Accounts, name: "accounts" },
  { path: "/reports", component: Reports, name: "reports" },
  { path: "/client-debts", component: CustomerDebtReport, name: "client-debts" },
  { path: "/receive", component: ReceiveProducts, name: "receive" },
  { path: "/operation-history", component: OperationHistory, name: "operation-history" },
  { path: "/test", component: TestProducts, name: "test" },

  
];
