<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

include 'condb.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Method not allowed"
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);
$employeeId = filter_var($data['emp_id'] ?? null, FILTER_VALIDATE_INT);

if ($employeeId === false || $employeeId === null || $employeeId < 1) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "รหัสพนักงานไม่ถูกต้อง"
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM employee WHERE emp_id = :emp_id");
    $stmt->execute([':emp_id' => $employeeId]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "ไม่พบข้อมูลพนักงานที่ต้องการลบ"
        ]);
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "ลบข้อมูลพนักงานเรียบร้อย"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "ไม่สามารถลบข้อมูลพนักงานได้"
    ]);
}
?>
