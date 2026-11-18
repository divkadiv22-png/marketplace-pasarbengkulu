
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "../config.php";

// API hanya menerima metode GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Metode request tidak diizinkan"
    ]);
    exit;
}

// Mengambil data kategori dari database
$sql = "SELECT id, nama_kategori FROM kategori ORDER BY id ASC";
$result = $conn->query($sql);

if ($result) {
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Data kategori berhasil diambil",
        "data" => $data
    ], JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Gagal mengambil data kategori"
    ]);
}

$conn->close();
?>
