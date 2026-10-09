<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include 'condb.php';

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === "GET") {
        $stmt = $conn->prepare(
            "SELECT customer_id, firstName, lastName, phone, username
             FROM customers ORDER BY customer_id DESC"
        );
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "data" => $result]);
    }

    // ✅ เพิ่มข้อมูลลูกค้า
    elseif ($method === "POST") {
        // ตรวจสอบว่าข้อมูลมาจาก JSON หรือ form-data
        $contentType = $_SERVER["CONTENT_TYPE"] ?? '';

        if (stripos($contentType, "application/json") !== false) {
            $data = json_decode(file_get_contents("php://input"), true);
        } else {
            $data = $_POST;
        }

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ข้อมูลไม่ถูกต้อง"]);
            exit;
        }

        // ตรวจสอบค่าว่าง
        if (empty($data["firstName"]) || empty($data["lastName"]) || empty($data["phone"]) || empty($data["username"]) || empty($data["password"])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ"]);
            exit;
        }

        // เข้ารหัสรหัสผ่าน
        $password_hash = password_hash($data["password"], PASSWORD_BCRYPT);

        // เพิ่มข้อมูลลูกค้า
        $stmt = $conn->prepare("INSERT INTO customers (firstName, lastName, phone, username, password)
                                VALUES (:firstName, :lastName, :phone, :username, :password)");

        $stmt->bindParam(":firstName", $data["firstName"]);
        $stmt->bindParam(":lastName", $data["lastName"]);
        $stmt->bindParam(":phone", $data["phone"]);
        $stmt->bindParam(":username", $data["username"]);
        $stmt->bindParam(":password", $password_hash);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "เพิ่มข้อมูลลูกค้าเรียบร้อย"]);
        } else {
            echo json_encode(["success" => false, "message" => "ไม่สามารถเพิ่มข้อมูลลูกค้าได้"]);
        }
    }

    // ✅ แก้ไขข้อมูล
    elseif ($method === "PUT") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data) || !isset($data["customer_id"])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ไม่พบค่า customer_id"]);
            exit;
        }

        $customer_id = filter_var($data["customer_id"], FILTER_VALIDATE_INT);
        if ($customer_id === false || $customer_id < 1) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "รหัสลูกค้าไม่ถูกต้อง"]);
            exit;
        }

        foreach (["firstName", "lastName", "phone", "username"] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === "") {
                http_response_code(400);
                echo json_encode(["success" => false, "message" => "กรุณากรอกข้อมูลให้ครบ"]);
                exit;
            }
        }

        if (!empty($data["password"])) {
            $password_hash = password_hash($data["password"], PASSWORD_BCRYPT);
            $sql = "UPDATE customers 
                    SET firstName = :firstName, 
                        lastName = :lastName, 
                        phone = :phone, 
                        username = :username,
                        password = :password
                    WHERE customer_id = :id";
        } else {
            $sql = "UPDATE customers 
                    SET firstName = :firstName, 
                        lastName = :lastName, 
                        phone = :phone, 
                        username = :username
                    WHERE customer_id = :id";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":firstName", $data["firstName"]);
        $stmt->bindParam(":lastName", $data["lastName"]);
        $stmt->bindParam(":phone", $data["phone"]);
        $stmt->bindParam(":username", $data["username"]);
        if (!empty($data["password"])) {
            $stmt->bindParam(":password", $password_hash);
        }
        $stmt->bindParam(":id", $customer_id, PDO::PARAM_INT);

        $stmt->execute();
        if ($stmt->rowCount() === 0) {
            $check = $conn->prepare("SELECT 1 FROM customers WHERE customer_id = :id");
            $check->execute([":id" => $customer_id]);
            if (!$check->fetchColumn()) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลลูกค้า"]);
                exit;
            }
        }
        echo json_encode(["success" => true, "message" => "แก้ไขข้อมูลเรียบร้อย"]);
    }

    // ✅ ลบข้อมูล
    elseif ($method === "DELETE") {
        $data = json_decode(file_get_contents("php://input"), true);

        if (!is_array($data) || !isset($data["customer_id"])) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "ไม่พบค่า customer_id"]);
            exit;
        }

        $customer_id = filter_var($data["customer_id"], FILTER_VALIDATE_INT);
        if ($customer_id === false || $customer_id < 1) {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "รหัสลูกค้าไม่ถูกต้อง"]);
            exit;
        }

        $stmt = $conn->prepare("DELETE FROM customers WHERE customer_id = :id");
        $stmt->bindParam(":id", $customer_id, PDO::PARAM_INT);

        if ($stmt->execute()) {
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(["success" => false, "message" => "ไม่พบข้อมูลลูกค้า"]);
                exit;
            }
            echo json_encode(["success" => true, "message" => "ลบข้อมูลเรียบร้อย"]);
        } else {
            http_response_code(500);
            echo json_encode(["success" => false, "message" => "ไม่สามารถลบข้อมูลได้"]);
        }
    }

    else {
        header("Allow: GET, POST, PUT, DELETE, OPTIONS");
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Method ไม่ถูกต้อง"]);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "เกิดข้อผิดพลาดในการจัดการข้อมูลลูกค้า"]);
}
?>
