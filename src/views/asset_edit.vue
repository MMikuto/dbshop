<template>
  <div class="container mt-4">
    <h2 class="mb-3">รายการครุภัณฑ์</h2>

    <div class="mb-3">
      <button class="btn btn-primary" type="button" @click="openAddModal">
        เพิ่มครุภัณฑ์
      </button>
    </div>

    <div v-if="loading" class="text-center"><p>กำลังโหลดข้อมูล...</p></div>
    <div v-if="error" class="alert alert-danger">{{ error }}</div>
    <div v-if="message" class="alert alert-success">{{ message }}</div>

    <div class="table-responsive">
      <table class="table table-bordered table-striped align-middle">
        <thead class="table-primary">
          <tr>
            <th>ID</th>
            <th>รหัสสินค้า</th>
            <th>ชื่อสินค้า</th>
            <th>วันที่</th>
            <th>ราคา</th>
            <th>รูปภาพ</th>
            <th>การจัดการ</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="asset in assets" :key="asset.asset_id">
            <td>{{ asset.asset_id }}</td>
            <td>{{ asset.asset_code }}</td>
            <td>{{ asset.asset_name }}</td>
            <td>{{ asset.purchase_date || "-" }}</td>
            <td>{{ asset.price ?? "-" }}</td>
            <td>
              <img
                v-if="asset.image"
                :src="imageUrl(asset.image)"
                :alt="asset.asset_name"
                width="80"
                height="80"
                class="object-fit-contain"
              />
              <span v-else>-</span>
            </td>
            <td class="text-nowrap">
              <button class="btn btn-warning btn-sm me-2" type="button" @click="openEditModal(asset)">
                แก้ไข
              </button>
              <button
                class="btn btn-danger btn-sm"
                type="button"
                :disabled="deletingId === asset.asset_id"
                @click="deleteAsset(asset)"
              >
                {{ deletingId === asset.asset_id ? "กำลังลบ..." : "ลบ" }}
              </button>
            </td>
          </tr>
          <tr v-if="!loading && assets.length === 0">
            <td colspan="7" class="text-center">ยังไม่มีข้อมูลครุภัณฑ์</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      id="assetModal"
      class="modal fade"
      tabindex="-1"
      aria-labelledby="assetModalTitle"
      aria-hidden="true"
    >
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 id="assetModalTitle" class="modal-title">
              {{ isEditMode ? "แก้ไขครุภัณฑ์" : "เพิ่มครุภัณฑ์" }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="saveAsset">
              <div class="mb-3">
                <label for="asset-code" class="form-label">รหัสครุภัณฑ์</label>
                <input
                  id="asset-code"
                  v-model.trim="editForm.asset_code"
                  type="text"
                  maxlength="50"
                  class="form-control"
                  required
                />
              </div>
              <div class="mb-3">
                <label for="asset-name" class="form-label">ชื่อครุภัณฑ์</label>
                <input
                  id="asset-name"
                  v-model.trim="editForm.asset_name"
                  type="text"
                  maxlength="255"
                  class="form-control"
                  required
                />
              </div>
              <div class="mb-3">
                <label for="purchase-date" class="form-label">วันที่ซื้อ</label>
                <input id="purchase-date" v-model="editForm.purchase_date" type="date" class="form-control" />
              </div>
              <div class="mb-3">
                <label for="asset-price" class="form-label">ราคา</label>
                <input
                  id="asset-price"
                  v-model="editForm.price"
                  type="number"
                  min="0"
                  max="99999999.99"
                  step="0.01"
                  class="form-control"
                />
              </div>
              <div class="mb-3">
                <label for="asset-image" class="form-label">รูปภาพ</label>
                <input
                  id="asset-image"
                  type="file"
                  accept="image/jpeg,image/png,image/gif,image/webp"
                  class="form-control"
                  @change="handleImageUpload"
                />
                <img
                  v-if="isEditMode && editForm.image"
                  :src="imageUrl(editForm.image)"
                  :alt="editForm.asset_name"
                  width="100"
                  height="100"
                  class="object-fit-contain mt-2"
                />
              </div>
              <button type="submit" class="btn btn-success" :disabled="saving">
                {{ saving ? "กำลังบันทึก..." : (isEditMode ? "บันทึกการแก้ไข" : "เพิ่มครุภัณฑ์") }}
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

const apiUrl = "http://localhost/dbshop/php_api/api_asset.php";
const apiBaseUrl = "http://localhost/dbshop/php_api/";

export default {
  name: "AssetList",
  setup() {
    const assets = ref([]);
    const loading = ref(true);
    const saving = ref(false);
    const deletingId = ref(null);
    const error = ref("");
    const message = ref("");
    const isEditMode = ref(false);
    const selectedImage = ref(null);
    const editForm = ref({});
    let assetModal = null;

    const emptyForm = () => ({
      asset_id: null,
      asset_code: "",
      asset_name: "",
      purchase_date: "",
      price: "",
      image: ""
    });

    const imageUrl = (image) => `${apiBaseUrl}${image}`;

    const fetchAssets = async () => {
      loading.value = true;
      error.value = "";
      try {
        const response = await fetch(apiUrl);
        const result = await response.json();
        if (!response.ok || !result.success || !Array.isArray(result.data)) {
          throw new Error(result.message || "โหลดข้อมูลครุภัณฑ์ไม่สำเร็จ");
        }
        assets.value = result.data;
      } catch (err) {
        error.value = err.message || "เกิดข้อผิดพลาดขณะโหลดข้อมูลครุภัณฑ์";
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => {
      fetchAssets();
      const modalElement = document.getElementById("assetModal");
      if (modalElement) {
        assetModal = new Modal(modalElement);
      }
    });

    const resetFileInput = () => {
      const fileInput = document.getElementById("asset-image");
      if (fileInput) fileInput.value = "";
    };

    const openAddModal = () => {
      isEditMode.value = false;
      editForm.value = emptyForm();
      selectedImage.value = null;
      resetFileInput();
      assetModal?.show();
    };

    const openEditModal = (asset) => {
      isEditMode.value = true;
      editForm.value = { ...asset, purchase_date: asset.purchase_date || "", price: asset.price ?? "" };
      selectedImage.value = null;
      resetFileInput();
      assetModal?.show();
    };

    const handleImageUpload = (event) => {
      selectedImage.value = event.target.files?.[0] || null;
    };

    const saveAsset = async () => {
      saving.value = true;
      error.value = "";
      message.value = "";
      const formData = new FormData();
      formData.append("action", isEditMode.value ? "update" : "add");
      if (isEditMode.value) formData.append("asset_id", editForm.value.asset_id);
      formData.append("asset_code", editForm.value.asset_code);
      formData.append("asset_name", editForm.value.asset_name);
      formData.append("purchase_date", editForm.value.purchase_date || "");
      formData.append("price", editForm.value.price ?? "");
      if (selectedImage.value) formData.append("image", selectedImage.value);

      try {
        const response = await fetch(apiUrl, { method: "POST", body: formData });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || "บันทึกข้อมูลครุภัณฑ์ไม่สำเร็จ");
        }
        message.value = result.message;
        assetModal?.hide();
        await fetchAssets();
      } catch (err) {
        error.value = err.message || "เกิดข้อผิดพลาดขณะบันทึกข้อมูลครุภัณฑ์";
      } finally {
        saving.value = false;
      }
    };

    const deleteAsset = async (asset) => {
      if (!window.confirm(`ต้องการลบครุภัณฑ์ "${asset.asset_name}" หรือไม่?`)) return;

      deletingId.value = asset.asset_id;
      error.value = "";
      message.value = "";
      const formData = new FormData();
      formData.append("action", "delete");
      formData.append("asset_id", asset.asset_id);

      try {
        const response = await fetch(apiUrl, { method: "POST", body: formData });
        const result = await response.json();
        if (!response.ok || !result.success) {
          throw new Error(result.message || "ลบข้อมูลครุภัณฑ์ไม่สำเร็จ");
        }
        assets.value = assets.value.filter((item) => Number(item.asset_id) !== Number(asset.asset_id));
        message.value = result.message;
      } catch (err) {
        error.value = err.message || "เกิดข้อผิดพลาดขณะลบข้อมูลครุภัณฑ์";
      } finally {
        deletingId.value = null;
      }
    };

    return {
      assets,
      loading,
      saving,
      deletingId,
      error,
      message,
      isEditMode,
      editForm,
      imageUrl,
      openAddModal,
      openEditModal,
      handleImageUpload,
      saveAsset,
      deleteAsset
    };
  }
};
</script>