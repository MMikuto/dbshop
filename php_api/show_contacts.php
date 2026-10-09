<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

function respond($status, $payload)
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function readRequestData()
{
    $data = json_decode(file_get_contents("php://input"), true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
        respond(400, ["success" => false, "message" => "ข้อมูล JSON ไม่ถูกต้อง"]);
    }
    return $data;
}

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(204);
    exit;
}

include "condb.php";

try {
    $method = $_SERVER["REQUEST_METHOD"];

    if ($method === "GET") {
        $stmt = $conn->query("SELECT * FROM contacts ORDER BY contact_id DESC");
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === "POST") {
        $data = readRequestData();
        foreach (["subject", "detail", "email"] as $field) {
            if (!isset($data[$field]) || !is_scalar($data[$field]) || trim((string) $data[$field]) === "") {
                respond(400, ["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ"]);
            }
        }
        if (!filter_var(trim((string) $data["email"]), FILTER_VALIDATE_EMAIL)) {
            respond(400, ["success" => false, "message" => "รูปแบบอีเมลไม่ถูกต้อง"]);
        }

        $stmt = $conn->prepare(
            "INSERT INTO contacts (subject, detail, email, created_at)
             VALUES (:subject, :detail, :email, NOW())"
        );
        $stmt->execute([
            ":subject" => trim((string) $data["subject"]),
            ":detail" => trim((string) $data["detail"]),
            ":email" => trim((string) $data["email"])
        ]);
        respond(201, ["success" => true, "message" => "เพิ่มข้อความติดต่อเรียบร้อย"]);
    }

    if ($method === "PUT") {
        $data = readRequestData();
        $contactIdValue = $data["contact_id"] ?? null;
        if (!is_scalar($contactIdValue)) {
            respond(400, ["success" => false, "message" => "รหัสข้อความติดต่อไม่ถูกต้อง"]);
        }
        $contactId = filter_var($contactIdValue, FILTER_VALIDATE_INT);
        if ($contactId === false || $contactId === null || $contactId < 1) {
            respond(400, ["success" => false, "message" => "รหัสข้อความติดต่อไม่ถูกต้อง"]);
        }
        foreach (["subject", "detail", "email"] as $field) {
            if (!isset($data[$field]) || !is_scalar($data[$field]) || trim((string) $data[$field]) === "") {
                respond(400, ["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ"]);
            }
        }
        if (!filter_var(trim((string) $data["email"]), FILTER_VALIDATE_EMAIL)) {
            respond(400, ["success" => false, "message" => "รูปแบบอีเมลไม่ถูกต้อง"]);
        }

        $stmt = $conn->prepare(
            "UPDATE contacts
             SET subject = :subject, detail = :detail, email = :email
             WHERE contact_id = :id"
        );
        $stmt->execute([
            ":subject" => trim((string) $data["subject"]),
            ":detail" => trim((string) $data["detail"]),
            ":email" => trim((string) $data["email"]),
            ":id" => $contactId
        ]);
        if ($stmt->rowCount() === 0) {
            $check = $conn->prepare("SELECT 1 FROM contacts WHERE contact_id = :id");
            $check->execute([":id" => $contactId]);
            if (!$check->fetchColumn()) {
                respond(404, ["success" => false, "message" => "ไม่พบข้อความติดต่อ"]);
            }
        }
        respond(200, ["success" => true, "message" => "แก้ไขข้อความติดต่อเรียบร้อย"]);
    }

    if ($method === "DELETE") {
        $data = readRequestData();
        $contactIdValue = $data["contact_id"] ?? null;
        if (!is_scalar($contactIdValue)) {
            respond(400, ["success" => false, "message" => "รหัสข้อความติดต่อไม่ถูกต้อง"]);
        }
        $contactId = filter_var($contactIdValue, FILTER_VALIDATE_INT);
        if ($contactId === false || $contactId === null || $contactId < 1) {
            respond(400, ["success" => false, "message" => "รหัสข้อความติดต่อไม่ถูกต้อง"]);
        }

        $stmt = $conn->prepare("DELETE FROM contacts WHERE contact_id = :id");
        $stmt->execute([":id" => $contactId]);
        if ($stmt->rowCount() === 0) {
            respond(404, ["success" => false, "message" => "ไม่พบข้อความติดต่อ"]);
        }
        respond(200, ["success" => true, "message" => "ลบข้อความติดต่อเรียบร้อย"]);
    }

    header("Allow: GET, POST, PUT, DELETE, OPTIONS");
    respond(405, ["success" => false, "message" => "Method ไม่ถูกต้อง"]);
} catch (PDOException $e) {
    respond(500, ["success" => false, "message" => "เกิดข้อผิดพลาดในการจัดการข้อความติดต่อ"]);
}
?>
