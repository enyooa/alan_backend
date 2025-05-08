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
import TariffPlan from "../views/TariffPlan.vue";
import RegisterOrganization from "../views/Auth/RegisterOrganization.vue";
import WriteOffPage from "../views/WriteOffPage.vue";
import PriceOfferPage from "../views/PriceOfferPage.vue";
import SalePage from "../views/SalePage.vue";
import ProviderPage from "../views/forms/references/ProviderPage.vue";
import ExpensePage from "../views/forms/references/ExpensePage.vue";
import ProductCardPage from "../views/forms/references/ProductCardPage.vue";
import AddressPage from "../views/forms/references/AddressPage.vue";
import CashFlowReportPage from "../views/forms/reports/CashFlowReportPage.vue";
import DebtsReportPage from "../views/forms/reports/DebtsReportPage.vue";
import SalesReportPage from "../views/forms/reports/SalesReportPage.vue";
import WarehouseReportPage from "../views/forms/reports/WarehouseReportPage.vue";
import EmployeeFormPage from "../views/forms/EmployeeFormPage.vue";
import EmployeesOld from "../views/EmployeesOld.vue";
import CashPage from "../views/forms/references/CashPage.vue";



export default [
  { path: "/", redirect: "/dashboard" }, // Default to the dashboard
  { path: "/login", component: Login, name: "login" },
  { path: "/dashboard", component: Dashboard, name: "dashboard" },
  { path: "/account", component: AccountView, name: "account" },
  { path: '/access', component: Access, name: 'access' },
  { path: "/employees",              component: Employees, name: "employees" },
  { path: "/employees-old",              component: EmployeesOld, name: "employees-old" },

  { path: "/access/:userId",         component: Access,    name: "access",   props: true },
    { path: "/clients", component: CounterAgent, name: "clients" },
  { path: "/product-cards", component: ProductCards, name: "product-cards" },
  { path: "/accounts", component: Accounts, name: "accounts" },
  { path: "/reports", component: Reports, name: "reports" },
  { path: "/client-debts", component: CustomerDebtReport, name: "client-debts" },
  { path: "/receive", component: ReceiveProducts, name: "receive" },
  { path: "/operation-history", component: OperationHistory, name: "operation-history" },
  { path: "/test", component: TestProducts, name: "test" },
  { path: "/tariff-plan", component: TariffPlan, name: "tariff-plan" },
  { path: "/register-organization", component: RegisterOrganization, name: "register-organization" },
  { path: "/write-off", component: WriteOffPage, name: "write-off" },
  { path: "/price-offers", component: PriceOfferPage, name: "price-offers" },
  { path: "/sales", component: SalePage, name: "sales" },
  { path: "/provider", component: ProviderPage, name: "provider" },
  { path: "/expense", component: ExpensePage, name: "expense" },

  { path: "/product-card", component: ProductCardPage, name: "product-card" },

  { path: "/address", component: AddressPage, name: "address" },


  { path: "/cash-report", component: CashFlowReportPage, name: "cash-report" },

  { path: "/debt-report", component: DebtsReportPage, name: "debt-report" },

  { path: "/sales-report", component: SalesReportPage, name: "sales-report" },
  { path: "/warehouse-report", component: WarehouseReportPage, name: "warehouse-report" },
  { path: "/roles", component: EmployeeFormPage, name: "roles" },

  { path: "/cash", component: CashPage, name: "cash" },





];
