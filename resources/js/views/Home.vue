
<style scoped>
.employees-container {
   min-height: 100vh;
   display: flex;
   flex-direction: column;
}

.header {
   background-color: #0288d1;
   color: white;
   padding: 15px;
   display: flex;
   align-items: center;
   gap: 15px;
}

.content {
   flex: 1;
   padding: 20px;
   background-color: #f5f5f5;
}

.employee-list {
   margin-bottom: 20px;
}

.add-btn {
   background-color: #0288d1;
   color: white;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   margin-bottom: 10px;
}

.add-btn:hover {
   background-color: #026ca0;
}

table {
   width: 100%;
   border-collapse: collapse;
   margin-bottom: 20px;
}

thead th {
   background-color: #0288d1;
   color: white;
   padding: 10px;
   text-align: left;
}

tbody td {
   padding: 10px;
   border: 1px solid #ddd;
}

.delete-btn {
   background-color: #ff4d4d;
   color: white;
   border: none;
   border-radius: 5px;
   cursor: pointer;
   padding: 5px 10px;
}

.delete-btn:hover {
   background-color: #cc0000;
}

.add-employee {
   background: white;
   padding: 20px;
   border-radius: 8px;
   box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

.add-employee h2 {
   color: #0288d1;
   margin-bottom: 15px;
}

.add-employee label {
   display: block;
   margin: 10px 0 5px;
   font-weight: bold;
}

.add-employee input,
.add-employee select {
   width: 100%;
   padding: 10px;
   margin-bottom: 15px;
   border: 1px solid #ddd;
   border-radius: 5px;
}

.add-employee button {
   background-color: #0288d1;
   color: white;
   padding: 10px 15px;
   border: none;
   border-radius: 5px;
   cursor: pointer;
}

.add-employee button:hover {
   background-color: #026ca0;
}
</style>
