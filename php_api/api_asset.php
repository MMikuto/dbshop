<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

function respond($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

include "condb.php";

function saveAssetImage()
{
    if (!isset($_FILES["image"]) || $_FILES["image"]["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
        respond(400, ["success" => false, "message" => "อัปโหลดรูปภาพไม่สำเร็จ"]);
    }

    $mimeTypes = [
        "image/jpeg" => "jpg",
        "image/png" => "png",
        "image/gif" => "gif",
        "image/webp" => "webp"
    ];
    $mimeType = (new finfo(FILEINFO_MIME_TYPE))->file($_FILES["image"]["tmp_name"]);
    if (!isset($mimeTypes[$mimeType])) {
        respond(400, ["success" => false, "message" => "รองรับเฉพาะไฟล์ภาพ JPG, PNG, GIF และ WebP"]);
    }

    $directory = __DIR__ . "/uploads/assets";
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        respond(500, ["success" => false, "message" => "ไม่สามารถเตรียมโฟลเดอร์รูปภาพได้"]);
    }

    $filename = bin2hex(random_bytes(16)) . "." . $mimeTypes[$mimeType];
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $directory . "/" . $filename)) {
        respond(500, ["success" => false, "message" => "บันทึกรูปภาพไม่สำเร็จ"]);
    }

    return "uploads/assets/" . $filename;
}

try {
    $method = $_SERVER["REQUEST_METHOD"];

    if ($method === "GET") {
        $stmt = $conn->query("SELECT asset_id, asset_code, asset_name, purchase_date, price, image, created_at FROM assets ORDER BY asset_id DESC");
        respond(200, ["success" => true, "data" => $stmt->fetchAll()]);
    }

    if ($method !== "POST") {
        header("Allow: GET, POST, OPTIONS");
        respond(405, ["success" => false, "message" => "Method ไม่ถูกต้อง"]);
    }

    $action = $_POST["action"] ?? "";
    if ($action === "add" || $action === "update") {
        $assetCode = trim((string) ($_POST["asset_code"] ?? ""));
        $assetName = trim((string) ($_POST["asset_name"] ?? ""));
        $purchaseDate = trim((string) ($_POST["purchase_date"] ?? ""));
        $price = trim((string) ($_POST["price"] ?? ""));

        if ($assetCode === "" || $assetName === "") {
            respond(400, ["success" => false, "message" => "กรุณากรอกรหัสและชื่อครุภัณฑ์"]);
        }
        if (strlen($assetCode) > 50 || strlen($assetName) > 255) {
            respond(400, ["success" => false, "message" => "รหัสหรือชื่อครุภัณฑ์ยาวเกินกำหนด"]);
        }

        if ($purchaseDate !== "") {
            $date = DateTimeImmutable::createFromFormat("!Y-m-d", $purchaseDate);
            if (!$date || $date->format("Y-m-d") !== $purchaseDate) {
                respond(400, ["success" => false, "message" => "วันที่ซื้อต้องอยู่ในรูปแบบ YYYY-MM-DD"]);
            }
        } else {
            $purchaseDate = null;
        }

        if ($price !== "" && !preg_match('/^\d{1,8}(?:\.\d{1,2})?$/D', $price)) {
            respond(400, ["success" => false, "message" => "ราคาต้องเป็นตัวเลขไม่เกิน 10 หลักและทศนิยมไม่เกิน 2 ตำแหน่ง"]);
        }
        if ($price === "") {
            $price = null;
        }

        if ($action === "add") {
            $image = saveAssetImage();
            $stmt = $conn->prepare(
                "INSERT INTO assets (asset_code, asset_name, purchase_date, price, image)
                 VALUES (:asset_code, :asset_name, :purchase_date, :price, :image)"
            );
            $stmt->execute([
                ":asset_code" => $assetCode,
                ":asset_name" => $assetName,
                ":purchase_date" => $purchaseDate,
                ":price" => $price,
                ":image" => $image
            ]);
            respond(201, ["success" => true, "message" => "เพิ่มข้อมูลครุภัณฑ์เรียบร้อย", "asset_id" => $conn->lastInsertId()]);
        }

        $assetId = filter_var($_POST["asset_id"] ?? null, FILTER_VALIDATE_INT);
        if ($assetId === false || $assetId === null || $assetId < 1) {
            respond(400, ["success" => false, "message" => "รหัสลำดับครุภัณฑ์ไม่ถูกต้อง"]);
        }

        $image = saveAssetImage();
        $sql = "UPDATE assets
                SET asset_code = :asset_code, asset_name = :asset_name,
                    purchase_date = :purchase_date, price = :price";
        if ($image !== null) {
            $sql .= ", image = :image";
        }
        $sql .= " WHERE asset_id = :asset_id";

        $parameters = [
            ":asset_code" => $assetCode,
            ":asset_name" => $assetName,
            ":purchase_date" => $purchaseDate,
            ":price" => $price,
            ":asset_id" => $assetId
        ];
        if ($image !== null) {
            $parameters[":image"] = $image;
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($parameters);
        if ($stmt->rowCount() === 0) {
            $check = $conn->prepare("SELECT 1 FROM assets WHERE asset_id = :asset_id");
            $check->execute([":asset_id" => $assetId]);
            if (!$check->fetchColumn()) {
                respond(404, ["success" => false, "message" => "ไม่พบข้อมูลครุภัณฑ์"]);
            }
        }
        respond(200, ["success" => true, "message" => "แก้ไขข้อมูลครุภัณฑ์เรียบร้อย"]);
    }

    if ($action === "delete") {
        $assetId = filter_var($_POST["asset_id"] ?? null, FILTER_VALIDATE_INT);
        if ($assetId === false || $assetId === null || $assetId < 1) {
            respond(400, ["success" => false, "message" => "รหัสลำดับครุภัณฑ์ไม่ถูกต้อง"]);
        }

        $stmt = $conn->prepare("DELETE FROM assets WHERE asset_id = :asset_id");
        $stmt->execute([":asset_id" => $assetId]);
        if ($stmt->rowCount() === 0) {
            respond(404, ["success" => false, "message" => "ไม่พบข้อมูลครุภัณฑ์"]);
        }
        respond(200, ["success" => true, "message" => "ลบข้อมูลครุภัณฑ์เรียบร้อย"]);
    }

    respond(400, ["success" => false, "message" => "Action ไม่ถูกต้อง"]);
} catch (PDOException $e) {
    respond(500, ["success" => false, "message" => "เกิดข้อผิดพลาดในการจัดการข้อมูลครุภัณฑ์"]);
}
?>