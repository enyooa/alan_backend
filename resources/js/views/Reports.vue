<template>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />

    <!-- Main content area -->
    <div class="main-content">
      <Header />

      <main class="content">
        <!-- Dynamic Report Page Content -->
        <div class="dynamic-report-page">
          <!-- Dropdown to select report -->
          <div class="report-dropdown">
            <select v-model="selectedOption">
              <option disabled value="">Выберите отчет</option>
              <option
                v-for="option in reportOptions"
                :key="option.label"
                :value="option.label"
              >
                {{ option.label }}
              </option>
            </select>
          </div>

          <!-- Render the selected report component -->
          <div class="report-content">
            <component :is="currentPageComponent" />
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue";

// Import the four report components
import CashFlowReportPage from "./forms/reports/CashFlowReportPage.vue";
import WarehouseReportPage from "./forms/reports/WarehouseReportPage.vue";
import DebtsReportPage from "./forms/reports/DebtsReportPage.vue";
import SalesReportPage from "./forms/reports/SalesReportPage.vue";

export default {
  name: "DynamicReportPage",
  components: {
    Sidebar,
    Header,
    CashFlowReportPage,
    WarehouseReportPage,
    DebtsReportPage,
    SalesReportPage,
    // Default component shown when no report is selected
    DefaultReportMessage: {
      template: '<div><p>Выберите отчет из списка</p></div>',
    },
  },
  data() {
    return {
      isSidebarOpen: true,
      selectedOption: '',
      reportOptions: [
        {
          label: 'Отчет по кассе',
          component: 'CashFlowReportPage',
        },
        {
          label: 'Отчет по складу',
          component: 'WarehouseReportPage',
        },
        {
          label: 'Отчет долги',
          component: 'DebtsReportPage',
        },
        {
          label: 'Отчет по продажам',
          component: 'SalesReportPage',
        },
      ],
    };
  },
  computed: {
    // Determine which component to render based on the selected option
    currentPageComponent() {
      const found = this.reportOptions.find(
        (option) => option.label === this.selectedOption
      );
      return found ? found.component : 'DefaultReportMessage';
    },
  },
  methods: {
    toggleSidebar() {
      this.isSidebarOpen = !this.isSidebarOpen;
    },
  },
};
</script>

<style scoped>
/* example styles from your snippet */
.dashboard-container {
  display: flex;
  min-height: 100vh;
}
.main-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  background-color: #f5f5f5;
}
.content {
  flex-grow: 1;
  padding: 20px;
  background-color: #f5f5f5;
}
.dynamic-report-page {
  padding: 16px;
  font-family: sans-serif;
}
.report-dropdown {
  margin-bottom: 20px;
}
.report-dropdown select {
  width: 100%;
  padding: 8px;
  font-size: 16px;
}
.report-content {
  min-height: 200px;
  border: 1px solid #ddd;
  padding: 16px;
  background-color: #fff;
  border-radius: 4px;
}
</style>
