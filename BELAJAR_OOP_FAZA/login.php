<?php
session_start();
require_once 'db.php';

function esc($s) {
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

$popupMsg = null;
$popupType = 'info';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'login') {
  $nama = trim($_POST['nama'] ?? '');
  $status = (($_POST['status'] ?? 'NonMember') === 'Member') ? 'adalah_member' : 'bukan_member';
  if ($nama === '') {
    $popupMsg = "Nama tidak boleh kosong.";
    $popupType = 'error';
  } else {
    $mysqli = db();
    $namaEsc = $mysqli->real_escape_string($nama);
    $statusEsc = $mysqli->real_escape_string($status);

    // Check if user exists with matching status
    $res = $mysqli->query("SELECT * FROM pelanggan WHERE nama='$namaEsc' AND status='$statusEsc' LIMIT 1");
    if ($row = $res->fetch_assoc()) {
      // User exists, set session
      $_SESSION['id_Pelanggan'] = $row['id_Pelanggan'];
      $_SESSION['nama'] = $row['nama'];
      $_SESSION['status'] = $row['status'] == 'adalah_member' ? 'Member' : 'NonMember';
      $_SESSION['saldo'] = $row['saldoDigital'];
      $_SESSION['riwayat'] = [];
      $_SESSION['lifetime'] = ['topup' => 0, 'sewa' => 0];
      $mysqli->close();
      header("Location: rental.php");
      exit;
    } else {
      $popupMsg = "Akun tidak ditemukan. Silakan daftar terlebih dahulu.";
      $popupType = 'error';
    }
    $mysqli->close();
  }
}

// Handle Sign Up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'signup') {
  $nama = trim($_POST['nama'] ?? '');
  $status = (($_POST['status'] ?? 'NonMember') === 'Member') ? 'adalah_member' : 'bukan_member';
  if ($nama === '') {
    $popupMsg = "Nama tidak boleh kosong.";
    $popupType = 'error';
  } else {
    $mysqli = db();
    $namaEsc = $mysqli->real_escape_string($nama);
    $statusEsc = $mysqli->real_escape_string($status);

    // Check if user already exists
    $res = $mysqli->query("SELECT * FROM pelanggan WHERE nama='$namaEsc' AND status='$statusEsc' LIMIT 1");
    if ($res->fetch_assoc()) {
      $popupMsg = "Akun sudah terdaftar. Silakan login.";
      $popupType = 'error';
    } else {
      // New user, insert
      $stmt = $mysqli->prepare("INSERT INTO pelanggan (nama, status, saldoDigital) VALUES (?, ?, ?)");
      $saldoAwal = 0;
      $stmt->bind_param("ssd", $nama, $status, $saldoAwal);
      $stmt->execute();
      $id = $stmt->insert_id;
      $_SESSION['id_Pelanggan'] = $id;
      $_SESSION['nama'] = $nama;
      $_SESSION['status'] = $status == 'adalah_member' ? 'Member' : 'NonMember';
      $_SESSION['saldo'] = $saldoAwal;
      $_SESSION['riwayat'] = [];
      $_SESSION['lifetime'] = ['topup' => 0, 'sewa' => 0];
      $stmt->close();
      $mysqli->close();
      header("Location: rental.php");
      exit;
    }
    $mysqli->close();
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title>Login / Daftar — Rent Garage</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <link href="rentalStyle.css" rel="stylesheet">
  <style>
    .tabs { display: flex; gap: 10px; margin-bottom: 18px; }
    .tab-btn {
      padding: 8px 24px;
      border-radius: 8px;
      border: none;
      background: #00f5ff;
      color: #222;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .tab-btn.active { background: #00c3ff; color: #fff; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
  </style>
</head>
<body>
  <div class="container">
    <section class="panel" style="max-width:420px;margin:10vh auto 0">
      <h2 style="margin:0 0 6px;color:var(--primary)">🚗 Rent Garage</h2>
      <div class="tabs">
        <button class="tab-btn active" id="loginTabBtn" type="button">Login</button>
        <button class="tab-btn" id="signupTabBtn" type="button">Daftar</button>
      </div>
      <div class="tab-content active" id="loginTab">
        <form method="post" class="grid" style="margin-top:12px">
          <input type="hidden" name="aksi" value="login">
          <div>
            <label>Nama</label>
            <input type="text" name="nama" placeholder="Nama Anda" required>
          </div>
          <div>
            <label>Status Pelanggan</label>
            <div class="select-wrap">
              <select name="status" required>
                <option value="Member">Member</option>
                <option value="NonMember">Non-Member</option>
              </select>
            </div>
          </div>
          <button class="btn" type="submit">Masuk</button>
        </form>
      </div>
      <div class="tab-content" id="signupTab">
        <form method="post" class="grid" style="margin-top:12px">
          <input type="hidden" name="aksi" value="signup">
          <div>
            <label>Nama</label>
            <input type="text" name="nama" placeholder="Nama Anda" required>
          </div>
          <div>
            <label>Status Pelanggan</label>
            <div class="select-wrap">
              <select name="status" required>
                <option value="Member">Member</option>
                <option value="NonMember">Non-Member</option>
              </select>
            </div>
            <p class="small" style="margin-top:6px">Info: disarankan untuk Member isi saldo awal &gt; Rp5.000.000 agar nyaman transaksi.</p>
          </div>
          <button class="btn" type="submit">Daftar</button>
        </form>
      </div>
      <?php if ($popupMsg): ?>
        <div class="popup-box <?= $popupType ?>">
          <p><?= esc($popupMsg) ?></p>
        </div>
      <?php endif; ?>
    </section>
  </div>
  <script>
    // Simple tab switcher
    const loginTabBtn = document.getElementById('loginTabBtn');
    const signupTabBtn = document.getElementById('signupTabBtn');
    const loginTab = document.getElementById('loginTab');
    const signupTab = document.getElementById('signupTab');
    loginTabBtn.onclick = () => {
      loginTabBtn.classList.add('active');
      signupTabBtn.classList.remove('active');
      loginTab.classList.add('active');
      signupTab.classList.remove('active');
    };
    signupTabBtn.onclick = () => {
      signupTabBtn.classList.add('active');
      loginTabBtn.classList.remove('active');
      signupTab.classList.add('active');
      loginTab.classList.remove('active');
    };
  </script>
</body>
</html>