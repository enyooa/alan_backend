<template>
   <div class="dashboard-container">
      <Sidebar :isSidebarOpen="isSidebarOpen" @toggleSidebar="toggleSidebar" />
      <div class="main-content">
         <Header />
         <main class="content">
            <div class="employee-list">
               <button class="add-btn" @click="toggleModal('addEmployee')">Добавить сотрудника</button>
               <button class="add-btn" @click="toggleModal('addRole')">Добавить должность</button>
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
                        <td>{{ employee.name }}</td>
                        <td>{{ employee.role }}</td>
                        <td>{{ employee.phone }}</td>
                        <td><button class="delete-btn" @click="deleteEmployee(employee.id)">Удалить</button></td>
                     </tr>
                  </tbody>
               </table>
            </div>

            <!-- Add Employee Modal -->
            <div v-if="showModal.addEmployee" class="add-employee stylish-form">
               <h2>Добавить сотрудника</h2>
               <form @submit.prevent="addEmployee">
                  <label>ФИО</label>
                  <input type="text" v-model="newEmployee.name" placeholder="Введите ФИО" />
                  <label>Должность</label>
                  <select v-model="newEmployee.role">
                     <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                  </select>
                  <label>Номер телефона</label>
                  <input type="text" v-model="newEmployee.phone" placeholder="Введите номер телефона" />
                  <label>Пароль</label>
                  <input type="password" v-model="newEmployee.password" placeholder="Введите пароль" />
                  <button type="submit" class="submit-btn">Добавить сотрудника</button>
               </form>
            </div>

            <!-- Add Role Modal -->
            <div v-if="showModal.addRole" class="add-employee stylish-form">
               <h2>Добавить должность</h2>
               <form @submit.prevent="addRole">
                  <label>Должность</label>
                  <input type="text" v-model="newRole.name" placeholder="Введите должность" />
                  <label>Кабинет</label>
                  <select v-model="newRole.department">
                     <option value="Склад">Склад</option>
                     <option value="Фасовка">Фасовка</option>
                     <option value="Курьер">Курьер</option>
                     <option value="Кассир">Кассир</option>
                  </select>
                  <button type="submit" class="submit-btn">Добавить должность</button>
               </form>
            </div>
         </main>
      </div>
   </div>
</template>

<script>
import Sidebar from "../components/Sidebar.vue";
import Header from "../components/Header.vue";

export default {
   components: { Sidebar, Header },
   data() {
      return {
         isSidebarOpen: true,
         employees: [
            { id: 1, name: "Мартынова Вера Капитоновна", role: "кассир", phone: "8700 999 11 11" },
            { id: 2, name: "Иванов Иван Иванович", role: "фасовщик", phone: "8700 999 11 11" },
            { id: 3, name: "Касеке Масеке Масеке", role: "кладовщик", phone: "8700 999 11 11" },
         ],
         roles: ["Склад", "Фасовка", "Кассир", "Курьер"],
         newEmployee: { name: "", role: "", phone: "", password: "" },
         newRole: { name: "", department: "" },
         showModal: { addEmployee: false, addRole: false },
      };
   },
   methods: {
      toggleSidebar() {
         this.isSidebarOpen = !this.isSidebarOpen;
      },
      toggleModal(modalName) {
         this.showModal[modalName] = !this.showModal[modalName];
      },
      addEmployee() {
         if (this.newEmployee.name && this.newEmployee.role && this.newEmployee.phone && this.newEmployee.password) {
            this.employees.push({ ...this.newEmployee, id: this.employees.length + 1 });
            this.newEmployee = { name: "", role: "", phone: "", password: "" };
            this.toggleModal("addEmployee");
         } else {
            alert("Пожалуйста, заполните все поля");
         }
      },
      addRole() {
         if (this.newRole.name && this.newRole.department) {
            this.roles.push(this.newRole.name);
            this.newRole = { name: "", department: "" };
            this.toggleModal("addRole");
         } else {
            alert("Пожалуйста, заполните все поля");
         }
      },
      deleteEmployee(id) {
         this.employees = this.employees.filter(employee => employee.id !== id);
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

.employee-list {
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

.add-employee.stylish-form {
   background: white;
   padding: 20px;
   border-radius: 12px;
   box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
   width: 100%;
   max-width: 600px;
   text-align: center;
}

.add-employee h2 {
   color: #0288d1;
}

.styled-table {
   width: 100%;
   border-collapse: collapse;
   background-color: #ffffff;
   box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
   border-radius: 8px;
   overflow: hidden;
}

.styled-table thead {
   background-color: #0288d1;
   color: white;
}

.styled-table th, .styled-table td {
   padding: 12px;
   text-align: left;
   border-bottom: 1px solid #ddd;
}

.styled-table tbody tr:nth-child(even) {
   background-color: #f2f2f2;
}

.styled-table tbody tr:hover {
   background-color: #e3f2fd;
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
</style>
