<template>
  <div class="container mt-4">
    <!-- หัวข้อหน้า -->
    <h2 class="mb-3">รายชื่อลูกค้า</h2>
    
    <div class="text-end mb-3">
    <a href="/add" class="btn btn-primary">Add+</a>
    </div>

    <table class="table table-bordered table-striped">
      <thead class="table-dark">
        <tr>
          <th>ลำดับที่</th>
          <th>รหัสลูกค้า</th>
          <th>ชื่อ</th>
          <th>นามสกุล</th>
          <th>เบอร์โทร</th>
          <th>ชื่อผู้ใช้</th>
          <th>จัดการ</th>
        </tr>
      </thead>

      <tbody>
        <tr v-for="(item,index) in customers" :key="item.customer_id">
          <td>{{ index + 1 }}</td>
          <td>{{ item.customer_id }}</td>
          <td>{{ item.firstName }}</td>
          <td>{{ item.lastName }}</td>
          <td>{{ item.phone }}</td>
          <td>{{ item.username }}</td>
          <td>
            <button
              type="button"
              class="btn btn-danger btn-sm"
              :disabled="deletingId === item.customer_id"
              @click="deleteCustomer(item)"
            >
              {{ deletingId === item.customer_id ? "กำลังลบ..." : "ลบ" }}
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
  name: "CustomerList",

  setup() {
    const customers = ref([]);
    const loading = ref(true);
    const deletingId = ref(null);
    const error = ref(null);
    const message = ref("");

    const fetchCustomers = async () => {
      try {
        const response = await fetch("http://localhost/dbshop/php_api/show_customers.php");
        if (!response.ok) {
          throw new Error("ไม่สามารถดึงข้อมูลได้");
        }

        const data = await response.json();
        if (!Array.isArray(data)) {
          throw new Error(data.error || "รูปแบบข้อมูลไม่ถูกต้อง");
        }
        customers.value = data;
      } catch (err) {
        error.value = err.message;
      } finally {
        loading.value = false;
      }
    };

    const deleteCustomer = async (customer) => {
      const name = `${customer.firstName} ${customer.lastName}`;
      if (!window.confirm(`ต้องการลบข้อมูลลูกค้า ${name} หรือไม่?`)) {
        return;
      }

      deletingId.value = customer.customer_id;
      error.value = null;
      message.value = "";

      try {
        const response = await fetch("http://localhost/dbshop/php_api/delete_customer.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ customer_id: customer.customer_id })
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.message || "ไม่สามารถลบข้อมูลลูกค้าได้");
        }

        customers.value = customers.value.filter((item) => item.customer_id !== customer.customer_id);
        message.value = data.message;
      } catch (err) {
        error.value = err.message;
      } finally {
        deletingId.value = null;
      }
    };

    onMounted(fetchCustomers);

    return {
      customers,
      loading,
      deletingId,
      error,
      message,
      deleteCustomer
    };
  }
};
</script>