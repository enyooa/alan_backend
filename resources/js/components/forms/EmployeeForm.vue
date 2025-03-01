<template>
            <div class="employee-list">
               <button class="add-btn" @click="toggleModal('addEmployee')">Добавить сотрудника</button>
               <table class="styled-table">
                  <thead>
                     <tr>
                        <th>Имя</th>
                        <th>Должность</th>
                        <th>Номер телефона</th>
                        <th>Действие</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr v-for="employee in employees" :key="employee.id">
                        <td>{{ employee.first_name }}</td>
                        <td>{{ employee.role }}</td>
                        <td>{{ employee.whatsapp_number }}</td>
                        <td>
                           <button class="delete-btn" @click="deleteEmployee(employee.id)">Удалить</button>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>

            <!-- Add Employee Modal -->
            <div v-if="showModal.addEmployee" class="modal-overlay">
               <div class="modal-container">
                  <h2>Добавить сотрудника</h2>
                  <form @submit.prevent="addEmployee">
                     <label>ФИО</label>
                     <input type="text" v-model="newEmployee.first_name" placeholder="Введите ФИО" required />

                     <label>Должность</label>
                     <select v-model="newEmployee.role" required>
                        <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                     </select>

                     <label>Номер телефона</label>
                     <input type="text" v-model="newEmployee.whatsapp_number" placeholder="Введите номер телефона" required />

                     <label>Пароль</label>
                     <input type="password" v-model="newEmployee.password" placeholder="Введите пароль" required />

                     <button type="submit" class="submit-btn">Добавить сотрудника</button>
                     <button type="button" class="close-btn" @click="toggleModal('addEmployee')">Закрыть</button>
                  </form>
               </div>
            </div>
         
         </template>
<script>
// import Sidebar from "../components/Sidebar.vue";
import axios from "axios";

export default {
   data() {
      return {
         employees: [],
         roles: ["Склад", "Фасовка", "Кассир", "Курьер"],
         newEmployee: { first_name: "", role: "", whatsapp_number: "", password: "" },
         showModal: { addEmployee: false },
      };
   },
   async created() {
      await this.fetchEmployees();
   },
   methods: {
      toggleSidebar() {
         this.isSidebarOpen = !this.isSidebarOpen;
      },
      toggleModal(modalName) {
         this.showModal[modalName] = !this.showModal[modalName];
      },
      async fetchEmployees() {
         try {
            const token = localStorage.getItem("token");
            const response = await axios.get("/api/users", {
               headers: { Authorization: `Bearer ${token}` },
            });
            this.employees = response.data;
         } catch (error) {
            console.error("Ошибка загрузки сотрудников:", error);
         }
      },
      async addEmployee() {
         if (!this.newEmployee.first_name || !this.newEmployee.role || !this.newEmployee.whatsapp_number || !this.newEmployee.password) {
            alert("❌ Заполните все поля!");
            return;
         }

         try {
            const token = localStorage.getItem("token");
            const response = await axios.post(
               "/api/users",
               {
                  first_name: this.newEmployee.first_name,
                  role: this.newEmployee.role,
                  whatsapp_number: this.newEmployee.whatsapp_number,
                  password: this.newEmployee.password,
               },
               { headers: { Authorization: `Bearer ${token}` } }
            );

            this.employees.push(response.data.user);
            this.newEmployee = { first_name: "", role: "", whatsapp_number: "", password: "" };
            this.toggleModal("addEmployee");
         } catch (error) {
            console.error("Ошибка при добавлении сотрудника:", error);
         }
      },
      async deleteEmployee(id) {
    console.log("Deleting user ID:", id); // Debugging log

    if (!confirm("Вы уверены, что хотите удалить этого сотрудника?")) return;

    try {
        const token = localStorage.getItem("token"); // Get token from storage

        if (!token) {
            console.error("❌ Token is missing!");
            alert("Ошибка: Токен не найден. Авторизуйтесь снова.");
            return;
        }

        const response = await axios.delete(`/api/users/${id}`, {
            headers: {
                Authorization: `Bearer ${token}`, // Attach token here
                Accept: "application/json",
            },
        });

        console.log("Response from server:", response.data);

        if (response.status === 200) {
            this.employees = this.employees.filter(employee => employee.id !== id);
            console.log("Updated employee list:", this.employees);
        }
    } catch (error) {
        console.error("Ошибка при удалении сотрудника:", error);
        if (error.response && error.response.status === 401) {
            alert("Ошибка: Сессия истекла. Пожалуйста, войдите снова.");
            localStorage.removeItem("authToken");
            this.$router.push("/login"); // Redirect to login page
        }
    }
}

,
   },
};
</script>
