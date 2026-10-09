<template>
  <div class="container mt-4">
    <h2 class="mb-3">รายชื่อพนักงาน</h2>
    
    <div class="text-end mb-3">
    <a href="/AE" class="btn btn-primary">Add+</a>
    </div>
    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>ลำดับที่</th>
          <th>รหัสพนักงาน</th>
          <th>ชื่อ</th>
          <th>นามสกุล</th>
          <th>เบอร์โทร</th>
          <th>ชื่อผู้ใช้</th>
          <th>จัดการ</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="(item,index) in employees" :key="item.emp_id">
          <td>{{ index + 1 }}</td>
          <td>{{ item.emp_id }}</td>
          <td>{{ item.firstName }}</td>
          <td>{{ item.lastName }}</td>
          <td>{{ item.phone }}</td>
          <td>{{ item.username }}</td>
          <td>
            <button
              type="button"
              class="btn btn-danger btn-sm"
              :disabled="deletingId === item.emp_id"
              @click="deleteEmployee(item)"
            >
              {{ deletingId === item.emp_id ? "กำลังลบ..." : "ลบ" }}
            </button>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="loading" class="text-center">
      <p>กำลังโหลดข้อมูล...</p>
    </div>

    <div v-if="error" class="alert alert-danger">
      {{ error }}
    </div>
    <div v-if="message" class="alert alert-success">
      {{ message }}
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from "vue";

export default {
  name: "EmployeeList",

  setup() {
    const employees = ref([]);
    const loading = ref(true);
    const deletingId = ref(null);
    const error = ref(null);
    const message = ref("");

    const fetchEmployees = async () => {
      try {
        const response = await fetch("http://localhost/dbshop/php_api/show_employee.php");
        if (!response.ok) {
          throw new Error("ไม่สามารถดึงข้อมูลได้");
        }

        const data = await response.json();
        if (!Array.isArray(data)) {
          throw new Error(data.error || "รูปแบบข้อมูลไม่ถูกต้อง");
        }
        employees.value = data;
      } catch (err) {
        error.value = err.message;
      } finally {
        loading.value = false;
      }
    };

    const deleteEmployee = async (employee) => {
      const name = `${employee.firstName} ${employee.lastName}`;
      if (!window.confirm(`ต้องการลบข้อมูลพนักงาน ${name} หรือไม่?`)) {
        return;
      }

      deletingId.value = employee.emp_id;
      error.value = null;
      message.value = "";

      try {
        const response = await fetch("http://localhost/dbshop/php_api/delete_employee.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ emp_id: employee.emp_id })
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || "ไม่สามารถลบข้อมูลพนักงานได้");
        }

        employees.value = employees.value.filter((item) => item.emp_id !== employee.emp_id);
        message.value = data.message;
      } catch (err) {
        error.value = err.message;
      } finally {
        deletingId.value = null;
      }
    };

    onMounted(fetchEmployees);

    return {
      employees,
      loading,
      deletingId,
      error,
      message,
      deleteEmployee
    };
  }
};
</script>