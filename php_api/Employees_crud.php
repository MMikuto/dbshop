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
    $contentType = $_SERVER["CONTENT_TYPE"] ?? "";
    if (stripos($contentType, "application/json") !== false) {
        $data = json_decode(file_get_contents("php://input"), true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            respond(400, ["success" => false, "message" => "ข้อมูล JSON ไม่ถูกต้อง"]);
        }
        return $data;
    }

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        return $_POST;
    }

    $data = [];
    parse_str(file_get_contents("php://input"), $data);
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
        $stmt = $conn->query("SELECT * FROM employee ORDER BY emp_id DESC");
        respond(200, [
            "success" => true,
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    if ($method === "POST") {
        $data = readRequestData();
        foreach (["firstName", "lastName", "phone", "username", "password"] as $field) {
            if (!isset($data[$field]) || !is_scalar($data[$field]) || trim((string) $data[$field]) === "") {
                respond(400, ["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ"]);
            }
        }

        $stmt = $conn->prepare(
            "INSERT INTO employee (firstName, lastName, phone, username, password)
             VALUES (:firstName, :lastName, :phone, :username, :password)"
        );
        $stmt->execute([
            ":firstName" => trim((string) $data["firstName"]),
            ":lastName" => trim((string) $data["lastName"]),
            ":phone" => trim((string) $data["phone"]),
            ":username" => trim((string) $data["username"]),
            ":password" => password_hash((string) $data["password"], PASSWORD_DEFAULT)
        ]);

        respond(201, ["success" => true, "message" => "เพิ่มข้อมูลพนักงานเรียบร้อย"]);
    }

    if ($method === "PUT") {
        $data = readRequestData();
        $employeeIdValue = $data["emp_id"] ?? null;
        if (!is_scalar($employeeIdValue)) {
            respond(400, ["success" => false, "message" => "รหัสพนักงานไม่ถูกต้อง"]);
        }
        $employeeId = filter_var($employeeIdValue, FILTER_VALIDATE_INT);
        if ($employeeId === false || $employeeId === null || $employeeId < 1) {
            respond(400, ["success" => false, "message" => "รหัสพนักงานไม่ถูกต้อง"]);
        }

        foreach (["firstName", "lastName", "phone", "username"] as $field) {
            if (!isset($data[$field]) || !is_scalar($data[$field]) || trim((string) $data[$field]) === "") {
                respond(400, ["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ"]);
            }
        }

        $values = [
            ":firstName" => trim((string) $data["firstName"]),
            ":lastName" => trim((string) $data["lastName"]),
            ":phone" => trim((string) $data["phone"]),
            ":username" => trim((string) $data["username"]),
            ":id" => $employeeId
        ];
        $passwordSql = "";
        if (isset($data["password"]) && is_scalar($data["password"]) && trim((string) $data["password"]) !== "") {
            $passwordSql = ", password = :password";
            $values[":password"] = password_hash((string) $data["password"], PASSWORD_DEFAULT);
        }

        $stmt = $conn->prepare(
            "UPDATE employee
             SET firstName = :firstName, lastName = :lastName,
                 phone = :phone, username = :username{$passwordSql}
             WHERE emp_id = :id"
        );
        $stmt->execute($values);

        if ($stmt->rowCount() === 0) {
            $check = $conn->prepare("SELECT 1 FROM employee WHERE emp_id = :id");
            $check->execute([":id" => $employeeId]);
            if (!$check->fetchColumn()) {
                respond(404, ["success" => false, "message" => "ไม่พบข้อมูลพนักงาน"]);
            }
        }

        respond(200, ["success" => true, "message" => "แก้ไขข้อมูลพนักงานเรียบร้อย"]);
    }

    if ($method === "DELETE") {
        $data = readRequestData();
        $employeeIdValue = $data["emp_id"] ?? null;
        if (!is_scalar($employeeIdValue)) {
            respond(400, ["success" => false, "message" => "รหัสพนักงานไม่ถูกต้อง"]);
        }
        $employeeId = filter_var($employeeIdValue, FILTER_VALIDATE_INT);
        if ($employeeId === false || $employeeId === null || $employeeId < 1) {
            respond(400, ["success" => false, "message" => "รหัสพนักงานไม่ถูกต้อง"]);
        }

        $stmt = $conn->prepare("DELETE FROM employee WHERE emp_id = :id");
        $stmt->execute([":id" => $employeeId]);
        if ($stmt->rowCount() === 0) {
            respond(404, ["success" => false, "message" => "ไม่พบข้อมูลพนักงาน"]);
        }

        respond(200, ["success" => true, "message" => "ลบข้อมูลพนักงานเรียบร้อย"]);
    }

    header("Allow: GET, POST, PUT, DELETE, OPTIONS");
    respond(405, ["success" => false, "message" => "Method ไม่ถูกต้อง"]);
} catch (PDOException $e) {
    respond(500, ["success" => false, "message" => "เกิดข้อผิดพลาดในการดำเนินการกับข้อมูลพนักงาน"]);
}
?>
