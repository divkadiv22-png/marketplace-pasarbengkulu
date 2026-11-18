<?php
include 'config.php';
mysqli_report(MYSQLI_REPORT_OFF);

function cariGambar($nama_file){
  $nama_file = trim($nama_file ?? '');
  if($nama_file != '' && file_exists('uploads/'.$nama_file)) return 'uploads/'.$nama_file;
  $dasar = str_replace(['.jpg.png','.png.png','.jpeg.png'], '', $nama_file);
  $dasar = pathinfo($dasar, PATHINFO_FILENAME);
  foreach(['jpg','jpeg','png','webp'] as $ext){
    if(file_exists('uploads/'.$dasar.'.'.$ext)) return 'uploads/'.$dasar.'.'.$ext;
  }
  if(is_dir('uploads')){
    foreach(scandir('uploads') as $f){
      if($f=='.'||$f=='..') continue;
      if(!preg_match('/\.(jpg|jpeg|png|webp)$/i', $f)) continue;
      $fl = strtolower($f); $dl = strtolower($dasar);
      if(strlen($dl)>=3 && strpos($fl, $dl)!==false) return 'uploads/'.$f;
      if($dl=='baytat' && (strpos($fl,'bay')!==false || strpos($fl,'tat')!==false)) return 'uploads/'.$f;
      if($dl=='lepek' && strpos($fl,'lepek')!==false) return 'uploads/'.$f;
      if($dl=='bolukoja' && (strpos($fl,'bolu')!==false || strpos($fl,'koja')!==false)) return 'uploads/'.$f;
      if($dl=='cookies' && strpos($fl,'cook')!==false) return 'uploads/'.$f;
      if($dl=='tas' && strpos($fl,'tas')!==false) return 'uploads/'.$f;
      if($dl=='batik' && strpos($fl,'batik')!==false) return 'uploads/'.$f;
      if($dl=='lemea' && strpos($fl,'lemea')!==false) return 'uploads/'.$f;
      if($dl=='tempoyak' && strpos($fl,'tempoyak')!==false) return 'uploads/'.$f;
    }
  }
  return '';
}
function emoji_produk($nama){
  $n = strtolower($nama);
  if(str_contains($n,'bolu')||str_contains($n,'koja')) return '🍰';
  if(str_contains($n,'gulai')||str_contains($n,'tempoyak')) return '🍲';
  if(str_contains($n,'bay')||str_contains($n,'tat')) return '🍪';
  if(str_contains($n,'lepek')||str_contains($n,'binti')) return '🍙';
  if(str_contains($n,'lemea')) return '🌶️';
  if(str_contains($n,'batik')) return '👘';
  if(str_contains($n,'tas')||str_contains($n,'anyaman')) return '👜';
  if(str_contains($n,'cookies')||str_contains($n,'cookie')) return '🍪';
  return '🎁';
}
function kategori_fix($row){
  $k = trim($row['kategori'] ?? '');
  if($k != '' && $k != 'Bengkulu') return $k;
  $n = strtolower($row['nama'] ?? '');
  if(str_contains($n,'tas')||str_contains($n,'batik')) return 'Kerajinan / Fashion';
  if(str_contains($n,'bolu')||str_contains($n,'cookies')||str_contains($n,'bay tat')||str_contains($n,'baytat')) return 'Kue';
  return 'Makanan';
}

// Bikin tabel kalau belum ada
$conn->query("CREATE TABLE IF NOT EXISTS produk (id INT AUTO_INCREMENT PRIMARY KEY, nama VARCHAR(100), deskripsi TEXT, harga INT, stok INT, kategori VARCHAR(50), gambar VARCHAR(255), foto VARCHAR(255))");

$pesan = '';
if(isset($_GET['hapus'])){
  $hid = (int)$_GET['hapus'];
  $qh = $conn->query("SELECT gambar, foto FROM produk WHERE id=$hid");
  if($rh = $qh->fetch_assoc()){
    $f = $rh['gambar'] ?: $rh['foto'];
    if($f && file_exists('uploads/'.$f)) unlink('uploads/'.$f);
  }
  $conn->query("DELETE FROM produk WHERE id=$hid");
  header("Location: admin_produk.php?hapus_ok=1"); exit;
}

if(isset($_POST['tambah_produk'])){
  $nama = trim($_POST['nama'] ?? '');
  $harga = (int)($_POST['harga'] ?? 0);
  $stok = (int)($_POST['stok'] ?? 0);
  $deskripsi = trim($_POST['deskripsi'] ?? '');
  $kategori = trim($_POST['kategori'] ?? 'Makanan');
  $nama_foto = '';
  if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0){
    if(!is_dir('uploads')) mkdir('uploads', 0777, true);
    $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    if(in_array($ext, ['jpg','jpeg','png','webp'])){
      $nama_foto = time().'_'.preg_replace('/[^a-zA-Z0-9._-]/','', $_FILES['foto']['name']);
      move_uploaded_file($_FILES['foto']['tmp_name'], 'uploads/'.$nama_foto);
    }
  }
  if($nama_foto == '') $nama_foto = strtolower(str_replace(' ','', $nama)).'.jpg';
  $stmt = $conn->prepare("INSERT INTO produk (nama, deskripsi, harga, stok, kategori, gambar, foto) VALUES (?,?,?,?,?,?,?)");
  if($stmt){
    $stmt->bind_param("ssiisss", $nama, $deskripsi, $harga, $stok, $kategori, $nama_foto, $nama_foto);
    if($stmt->execute()) $pesan = '<div class="alert alert-success" style="border-radius:16px;font-weight:700">✅ Produk <b>'.htmlspecialchars($nama).'</b> berhasil ditambah ke toko!</div>';
    else $pesan = '<div class="alert alert-danger">❌ Gagal: '.$conn->error.'</div>';
  }
}

$list = [];
$q = $conn->query("SELECT * FROM produk ORDER BY id DESC");
if($q){ while($r=$q->fetch_assoc()) $list[] = $r; }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola Toko - PasarBengkulu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
body{font-family:'Plus Jakarta Sans',sans-serif;background:#FFF7ED}
.navbar-brand{font-weight:800;font-size:22px}.navbar-brand span{color:#EA580C}
.card-x{background:white;border-radius:20px;border:0;box-shadow:0 8px 25px rgba(0,0,0,.06)}
.btn-orange{background:#EA580C;color:white;border-radius:999px;font-weight:700;padding:12px;border:0;width:100%}
.btn-orange:hover{background:#C2410C;color:white}
.input-x{border-radius:14px;padding:12px}
.img-mini{width:60px;height:60px;object-fit:cover;border-radius:12px;background:#FEF3C7;display:flex;align-items:center;justify-content:center;font-size:28px}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">Pasar<span>Bengkulu</span> 🧺</a>
    <div class="ms-auto d-flex gap-2">
      <a href="index.php" class="btn btn-outline-dark rounded-pill">🏠 Lihat Toko</a>
      <a href="pesanan.php" class="btn btn-dark rounded-pill">📦 Pesanan</a>
    </div>
  </div>
</nav>

<div class="container py-4">
  <h2 class="fw-bold">🏪 Kelola Toko - Tambah Produk</h2>
  <p class="text-muted">Tambah produk UMKM Bengkulu di sini, langsung muncul di Beranda.</p>

  <?= $pesan ?>
  <?php if(isset($_GET['hapus_ok'])){ ?><div class="alert alert-warning" style="border-radius:16px;font-weight:700">🗑️ Produk berhasil dihapus.</div><?php } ?>

  <div class="row g-4">
    <div class="col-lg-4">
      <form method="POST" enctype="multipart/form-data" class="card card-x p-4">
        <h5 class="fw-bold">➕ Tambah Produk Baru</h5>
        <label class="mt-2 fw-semibold small">Nama Produk *</label>
        <input type="text" name="nama" class="form-control input-x" placeholder="Contoh: Lepek Binti" required>
        <label class="mt-3 fw-semibold small">Kategori</label>
        <select name="kategori" class="form-control input-x">
          <option>Makanan</option><option>Minuman</option><option>Kue</option><option>Kerajinan</option><option>Fashion</option>
        </select>
        <div class="row">
          <div class="col-6"><label class="mt-3 fw-semibold small">Harga (Rp) *</label><input type="number" name="harga" class="form-control input-x" placeholder="10000" required min="0"></div>
          <div class="col-6"><label class="mt-3 fw-semibold small">Stok *</label><input type="number" name="stok" class="form-control input-x" placeholder="35" required min="0"></div>
        </div>
        <label class="mt-3 fw-semibold small">Deskripsi</label>
        <textarea name="deskripsi" class="form-control input-x" rows="3" placeholder="Ketan kelapa bungkus daun pisang..."></textarea>
        <label class="mt-3 fw-semibold small">Foto Produk</label>
        <input type="file" name="foto" class="form-control input-x" accept="image/*">
        <small class="text-muted">JPG / PNG / WEBP. Kalau tidak diisi, pakai gambar default.</small>
        <button type="submit" name="tambah_produk" class="btn-orange mt-3">✅ Tambah ke Toko</button>
      </form>
    </div>

    <div class="col-lg-8">
      <div class="card card-x p-4">
        <h5 class="fw-bold">📋 Daftar Produk (<?= count($list) ?> produk)</h5>
        <?php if(empty($list)){ ?>
          <div class="text-center p-4 text-muted">Belum ada produk. Tambah di sebelah kiri ya.</div>
        <?php } else { ?>
        <div class="table-responsive">
        <table class="table align-middle">
          <tr><th>Foto</th><th>Produk</th><th>Harga</th><th>Stok</th><th>Aksi</th></tr>
          <?php foreach($list as $it){
            $foto_file = $it['gambar'] ?: ($it['foto'] ?? '');
            $foto_path = cariGambar($foto_file);
          ?>
          <tr>
            <td><?php if($foto_path){ ?><img src="<?= $foto_path ?>" class="img-mini"><?php } else { ?><div class="img-mini"><?= emoji_produk($it['nama']) ?></div><?php } ?></td>
            <td><b><?= htmlspecialchars($it['nama']) ?></b><br><small class="text-muted"><?= htmlspecialchars(kategori_fix($it)) ?> • ID: <?= $it['id'] ?></small></td>
            <td><b class="text-success">Rp <?= number_format($it['harga']) ?></b></td>
            <td><span class="badge bg-success"><?= $it['stok'] ?></span></td>
            <td>
              <a href="detail.php?id=<?= $it['id'] ?>" class="btn btn-sm btn-primary rounded-pill">Lihat</a>
              <a href="?hapus=<?= $it['id'] ?>" onclick="return confirm('Hapus <?= htmlspecialchars($it['nama']) ?>?')" class="btn btn-sm btn-danger rounded-pill">Hapus</a>
            </td>
          </tr>
          <?php } ?>
        </table>
        </div>
        <?php } ?>
      </div>
    </div>
  </div>
  <p class="text-center text-muted small mt-4">PasarBengkulu © 2026 • Universitas Muhammadiyah Bengkulu</p>
</div>
</body>
</html>