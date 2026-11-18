
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once "../config.php";

// API hanya menerima metode POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Gunakan metode POST"
    ]);
    exit;
}

// Membaca data JSON
$input = json_decode(file_get_contents("php://input"), true);

// Validasi data yang dikirim
if (
    !is_array($input) ||
    empty($input["pembeli_id"]) ||
    empty($input["alamat_kirim"]) ||
    empty($input["items"]) ||
    !is_array($input["items"])
) {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Data pesanan belum lengkap"
    ]);
    exit;
}

$pembeli_id = filter_var(
    $input["pembeli_id"],
    FILTER_VALIDATE_INT
);

$alamat = trim($input["alamat_kirim"]);
$items = $input["items"];

if (!$pembeli_id || $pembeli_id <= 0 || $alamat === "") {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "Data pembeli atau alamat tidak valid"
    ]);
    exit;
}

try {
    $conn->begin_transaction();

    // Memastikan pembeli terdaftar
    $stmt = $conn->prepare(
        "SELECT id FROM users WHERE id = ? AND role = 'pembeli'"
    );
    $stmt->bind_param("i", $pembeli_id);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Pembeli tidak ditemukan");
    }
    $stmt->close();

    $total = 0;
    $detail = [];

    // Memeriksa produk dan menghitung total
    foreach ($items as $item) {
        $produk_id = filter_var(
            $item["produk_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $jumlah = filter_var(
            $item["jumlah"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$produk_id || $produk_id <= 0 || !$jumlah || $jumlah <= 0) {
            throw new Exception("ID produk atau jumlah tidak valid");
        }

        $stmt = $conn->prepare(
            "SELECT harga, stok FROM produk WHERE id = ? FOR UPDATE"
        );
        $stmt->bind_param("i", $produk_id);
        $stmt->execute();

        $produk = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$produk) {
            throw new Exception("Produk tidak ditemukan");
        }

        if ($produk["stok"] < $jumlah) {
            throw new Exception("Stok produk tidak mencukupi");
        }

        $harga = (float) $produk["harga"];
        $subtotal = $harga * $jumlah;
        $total += $subtotal;

        $detail[] = [
            "produk_id" => $produk_id,
            "jumlah" => $jumlah,
            "harga" => $harga,
            "subtotal" => $subtotal
        ];
    }

    // Menyimpan data pesanan
    $stmt = $conn->prepare(
        "INSERT INTO pesanan (pembeli_id, total, alamat_kirim)
         VALUES (?, ?, ?)"
    );
    $stmt->bind_param("ids", $pembeli_id, $total, $alamat);
    $stmt->execute();

    $pesanan_id = $conn->insert_id;
    $stmt->close();

    // Menyimpan detail dan mengurangi stok
    foreach ($detail as $d) {
        $stmt = $conn->prepare(
            "INSERT INTO detail_pesanan
            (pesanan_id, produk_id, jumlah, harga_saat_beli, subtotal)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->bind_param(
            "iiidd",
            $pesanan_id,
            $d["produk_id"],
            $d["jumlah"],
            $d["harga"],
            $d["subtotal"]
        );
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare(
            "UPDATE produk SET stok = stok - ? WHERE id = ?"
        );
        $stmt->bind_param(
            "ii",
            $d["jumlah"],
            $d["produk_id"]
        );
        $stmt->execute();
        $stmt->close();
    }

    $conn->commit();

    http_response_code(201);
    echo json_encode([
        "status" => "success",
        "message" => "Pesanan berhasil dibuat",
        "data" => [
            "pesanan_id" => $pesanan_id,
            "total" => $total,
            "status" => "menunggu"
        ]
    ], JSON_PRETTY_PRINT);

} catch (Throwable $e) {
    $conn->rollback();

    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>
