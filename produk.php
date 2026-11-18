<?php
header("Content-Type: application/json; charset=UTF-8");

// Menghubungkan API dengan database marketplace
require_once "../config.php";

// Memastikan request menggunakan metode GET
if ($_SERVER["REQUEST_METHOD"] !== "GET") {
    http_response_code(405);

    echo json_encode([
        "status" => "error",
        "message" => "Metode request tidak diizinkan"
    ]);
    exit;
}

// Memeriksa apakah parameter ID dikirim
if (isset($_GET["id"])) {

    $id = filter_var($_GET["id"], FILTER_VALIDATE_INT);

    if ($id === false || $id <= 0) {
        http_response_code(400);

        echo json_encode([
            "status" => "error",
            "message" => "ID produk tidak valid"
        ]);
        exit;
    }

    // Mengambil detail produk berdasarkan ID
    $stmt = $conn->prepare(
        "SELECT id, kategori_id, nama, deskripsi,
                harga, stok, gambar
         FROM produk
         WHERE id = ?"
    );

    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $produk = $result->fetch_assoc();

        echo json_encode([
            "status" => "success",
            "message" => "Detail produk berhasil ditemukan",
            "data" => $produk
        ], JSON_PRETTY_PRINT);
    } else {
        http_response_code(404);

        echo json_encode([
            "status" => "error",
            "message" => "Produk tidak ditemukan"
        ]);
    }

    $stmt->close();

} else {

    // Mengambil seluruh data produk
    $sql = "SELECT id, kategori_id, nama, deskripsi,
                   harga, stok, gambar
            FROM produk
            ORDER BY id ASC";

    $result = $conn->query($sql);

    if ($result) {
        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "message" => "Data produk berhasil ditampilkan",
            "data" => $data
        ], JSON_PRETTY_PRINT);

    } else {
        http_response_code(500);

        echo json_encode([
            "status" => "error",
            "message" => "Gagal mengambil data produk"
        ]);
    }
}

$conn->close();
?>