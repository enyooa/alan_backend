<template>
  <div class="dashboard-container">
    <!-- Sidebar -->
    <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />

    <!-- Main Content -->
    <div class="main-content">
      <!-- Header -->
      <Header />

      <main class="content">
        <!-- Client List Section -->
        <div class="client-list">
          <button class="add-btn" @click="toggleModal('addClient')">Добавить контрагента</button>

          <table class="styled-table">
            <thead>
              <tr>
                <th>Наименование контрагента</th>
                <th>Действие</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="client in clients" :key="client.id">
                <td>{{ client.name }}</td>
                <td><button class="delete-btn" @click="deleteClient(client.id)">Удалить</button></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Add Client Modal -->
        <div v-if="showModal.addClient" class="add-client-modal stylish-form">
          <h2>Добавить контрагента</h2>
          <form @submit.prevent="addClient">
            <label>Наименование контрагента</label>
            <input type="text" v-model="newClient.name" placeholder="Введите наименование" required />
            <button type="submit" class="submit-btn">Добавить контрагента</button>
          </form>
          <button class="close-btn" @click="toggleModal('addClient')">Закрыть</button>
        </div>

        <!-- Instructions/Info -->
        <div class="info">
          <p>Контрагенты загружаются из базы данных</p>
        </div>
      </main>
    </div>
  </div>
</template>

<script>
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue";
import axios from "axios";

export default {
  components: { Sidebar, Header },

  data() {
    return {
      isSidebarOpen: true,
      clients: [], // ❌ Removed hardcoded data
      newClient: { name: "" },
      showModal: { addClient: false },
    };
  },

  async created() {
    await this.fetchClients(); // ✅ Fetch providers from API when page loads
  },

  methods: {
    toggleSidebar() {
      this.isSidebarOpen = !this.isSidebarOpen;
    },
    toggleModal(modalName) {
      this.showModal[modalName] = !this.showModal[modalName];
    },

    // ✅ Fetch providers from Laravel API
    async fetchClients() {
      try {
        const token = localStorage.getItem("token");
        const response = await axios.get("/api/providers", {
          headers: { Authorization: `Bearer ${token}` },
        });

        this.clients = response.data; // ✅ Replace Vue data with API data
      } catch (error) {
        console.error("Ошибка загрузки контрагентов:", error);
      }
    },

    // ✅ Add a new provider via API
    async addClient() {
      if (!this.newClient.name) {
        alert("❌ Пожалуйста, введите наименование!");
        return;
      }

      try {
        const token = localStorage.getItem("token");
        const response = await axios.post(
          "/api/create_providers",
          { name: this.newClient.name },
          { headers: { Authorization: `Bearer ${token}` } }
        );

        this.clients.push(response.data); // ✅ Add new provider to list
        this.newClient = { name: "" }; // ✅ Reset form
        this.toggleModal("addClient");
      } catch (error) {
        console.error("Ошибка при добавлении контрагента:", error);
      }
    },

    // ✅ Delete provider from API
    async deleteClient(id) {
      if (!confirm("Вы уверены, что хотите удалить контрагента?")) return;

      try {
        const token = localStorage.getItem("token");
        await axios.delete(`/api/providers/${id}`, {
          headers: { Authorization: `Bearer ${token}` },
        });

        this.clients = this.clients.filter(client => client.id !== id); // ✅ Remove from Vue list
      } catch (error) {
        console.error("Ошибка при удалении контрагента:", error);
      }
    },
  },
};
</script>

<style scoped>
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
  flex: 1;
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.client-list {
  margin-bottom: 20px;
  width: 100%;
  max-width: 800px;
}

.add-btn {
  background-color: #0288d1;
  color: white;
  padding: 10px 15px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
  margin-bottom: 10px;
  width: 100%;
  max-width: 200px;
}

.styled-table {
  width: 100%;
  border-collapse: collapse;
  background-color: #ffffff;
  box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
  border-radius: 8px;
  overflow: hidden;
}

.styled-table th,
.styled-table td {
  padding: 12px;
  text-align: left;
  border-bottom: 1px solid #ddd;
}

.styled-table tbody tr:nth-child(even) {
  background-color: #f2f2f2;
}

.delete-btn {
  background-color: #ff4d4d;
  color: white;
  border: none;
  border-radius: 5px;
  padding: 8px 12px;
  cursor: pointer;
}

.delete-btn:hover {
  background-color: #d32f2f;
}

.add-client-modal {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  background: white;
  padding: 20px;
  border-radius: 12px;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
  z-index: 1000;
  width: 100%;
  max-width: 600px;
}

.add-client-modal h2 {
  color: #0288d1;
}

.submit-btn {
  background-color: #0288d1;
  color: white;
  padding: 10px 15px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.close-btn {
  margin-top: 15px;
  background-color: #ff4d4d;
  color: white;
  padding: 8px 12px;
  border: none;
  border-radius: 5px;
  cursor: pointer;
}

.info {
  margin-top: 20px;
  font-size: 14px;
  color: #0288d1;
  text-align: center;
}
</style>
