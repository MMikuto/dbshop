<template>
  <div class="container mt-4">
    <h2 class="mb-3">รายการข้อความติดต่อ</h2>

    <div class="mb-3">
      <button class="btn btn-primary" type="button" @click="openAddModal">
        Add+ <i class="bi bi-plus-circle"></i>
      </button>
    </div>

    <div v-if="loading" class="text-center"><p>กำลังโหลดข้อมูล...</p></div>
    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="message" class="alert alert-success">{{ message }}</div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped">
        <thead class="table-primary">
          <tr>
            <th>ID</th>
            <th>หัวข้อ</th>
            <th>รายละเอียด</th>
            <th>อีเมล</th>
            <th>วันที่สร้าง</th>
            <th>แก้ไข/ลบ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="contact in contacts" :key="contact.contact_id">
            <td>{{ contact.contact_id }}</td>
            <td>{{ contact.subject }}</td>
            <td class="text-break">{{ contact.detail }}</td>
            <td>{{ contact.email }}</td>
            <td>{{ contact.created_at }}</td>
            <td class="text-nowrap">
              <button class="btn btn-warning btn-sm" type="button" @click="openEditModal(contact)">
                แก้ไข
              </button>
              |
              <button
                class="btn btn-danger btn-sm"
                type="button"
                :disabled="deletingId === contact.contact_id"
                @click="deleteContact(contact)"
              >
                {{ deletingId === contact.contact_id ? "กำลังลบ..." : "ลบ" }}
              </button>
            </td>
          </tr>
          <tr v-if="!loading && contacts.length === 0">
            <td colspan="6" class="text-center">ยังไม่มีข้อความติดต่อ</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="modal fade" id="contactModal" tabindex="-1" aria-labelledby="contactModalTitle" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 id="contactModalTitle" class="modal-title">
              {{ isEditMode ? "แก้ไขข้อความติดต่อ" : "เพิ่มข้อความติดต่อ" }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveContact">
              <div class="mb-3">
                <label for="contact-subject" class="form-label">หัวข้อ</label>
                <input id="contact-subject" v-model.trim="editContact.subject" type="text" class="form-control" required>
              </div>
              <div class="mb-3">
                <label for="contact-detail" class="form-label">รายละเอียด</label>
                <textarea id="contact-detail" v-model.trim="editContact.detail" class="form-control" rows="4" required></textarea>
              </div>
              <div class="mb-3">
                <label for="contact-email" class="form-label">อีเมล</label>
                <input id="contact-email" v-model.trim="editContact.email" type="email" class="form-control" required>
              </div>
              <button type="submit" class="btn btn-success" :disabled="saving">
                {{ saving ? "กำลังบันทึก..." : (isEditMode ? "บันทึกการแก้ไข" : "เพิ่มข้อความติดต่อ") }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { onMounted, ref } from "vue";
import { Modal } from "bootstrap";

const apiUrl = "http://localhost/dbshop/php_api/show_contacts.php";

export default {
  name: "ContactList",
  setup() {
    const contacts = ref([]);
    const loading = ref(true);
    const saving = ref(false);
    const deletingId = ref(null);
    const error = ref("");
    const message = ref("");
    const editContact = ref({});
    const isEditMode = ref(false);
    let contactModal = null;

    const fetchContacts = async () => {
      loading.value = true;
      error.value = "";
      try {
        const response = await fetch(apiUrl);
        if (!response.ok) {
          throw new Error(`โหลดข้อความติดต่อไม่สำเร็จ (HTTP ${response.status})`);
        }
        const data = await response.json();
        if (!Array.isArray(data)) {
          throw new Error(data.error || "รูปแบบข้อมูลข้อความติดต่อไม่ถูกต้อง");
        }
        contacts.value = data;
      } catch (err) {
        error.value = err.message || "เกิดข้อผิดพลาดขณะโหลดข้อความติดต่อ";
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => {
      fetchContacts();
      const modalElement = document.getElementById("contactModal");
      if (modalElement) {
        contactModal = new Modal(modalElement);
      }
    });

    const openAddModal = () => {
      isEditMode.value = false;
      editContact.value = { subject: "", detail: "", email: "" };
      contactModal?.show();
    };

    const openEditModal = (contact) => {
      isEditMode.value = true;
      editContact.value = { ...contact };
      contactModal?.show();
    };

    const saveContact = async () => {
      saving.value = true;
      error.value = "";
      message.value = "";
      try {
        const response = await fetch(apiUrl, {
          method: isEditMode.value ? "PUT" : "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(editContact.value)
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || "ไม่สามารถบันทึกข้อความติดต่อได้");
        }
        message.value = result.message;
        contactModal?.hide();
        await fetchContacts();
      } catch (err) {
        error.value = err.message || "เกิดข้อผิดพลาดขณะบันทึกข้อความติดต่อ";
      } finally {
        saving.value = false;
      }
    };

    const deleteContact = async (contact) => {
      if (!window.confirm(`ต้องการลบข้อความ "${contact.subject}" หรือไม่?`)) {
        return;
      }
      deletingId.value = contact.contact_id;
      error.value = "";
      message.value = "";
      try {
        const response = await fetch(apiUrl, {
          method: "DELETE",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ contact_id: contact.contact_id })
        });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || "ไม่สามารถลบข้อความติดต่อได้");
        }
        contacts.value = contacts.value.filter(
          (item) => Number(item.contact_id) !== Number(contact.contact_id)
        );
        message.value = result.message;
      } catch (err) {
        error.value = err.message || "เกิดข้อผิดพลาดขณะลบข้อความติดต่อ";
      } finally {
        deletingId.value = null;
      }
    };

    return {
      contacts,
      loading,
      saving,
      deletingId,
      error,
      message,
      editContact,
      isEditMode,
      openAddModal,
      openEditModal,
      saveContact,
      deleteContact
    };
  }
};
</script>
