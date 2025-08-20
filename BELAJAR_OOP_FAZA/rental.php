<?php

class Kendaraan {
    private $namaKendaraan;
    private $hargaPerJam;
    private $gambarUrl;
    private $kategori;

    public function __construct($namaKendaraan, $hargaPerJam, $gambarUrl, $kategori) {
        $this->namaKendaraan = $namaKendaraan;
        $this->hargaPerJam = $hargaPerJam;
        $this->gambarUrl = $gambarUrl;
        $this->kategori = $kategori;
    }

    public function getNamaKendaraan() {
        return $this->namaKendaraan;
    }

    public function getHargaPerJam() {
        return $this->hargaPerJam;
    }

    public function getGambarUrl() {
        return $this->gambarUrl;
    }

    public function getKategori() {
        return $this->kategori;
    }
}

class Pelanggan {
    protected $nama;
    protected $status;
    protected $saldoDigital = 0;

    public function __construct($nama) {
        $this->nama = $nama;
    }

    public function getNama() {
        return $this->nama;
    }

    public function getStatus() {
        return $this->status;
    }

    public function getSaldoDigital() {
        return $this->saldoDigital;
    }

    public function tampilkanSaldo() {
        echo "<div class='saldo'>💰 Saldo Anda: <strong>Rp " . number_format($this->saldoDigital, 0, ',', '.') . "</strong></div>";
    }

    public function topUp($jumlah) {
        $this->saldoDigital += $jumlah;
        echo "<div class='info'>Top-up Rp " . number_format($jumlah, 0, ',', '.') . " berhasil!</div>";
        $this->tampilkanSaldo();
    }

    public function tambahCashback($jumlah) {
        $this->saldoDigital += $jumlah;
        echo "<div class='info'>🎁 Cashback Rp " . number_format($jumlah, 0, ',', '.') . " telah ditambahkan ke saldo digital.</div>";
    }

    public function bayar($jumlahTagihan) {
        if ($this->saldoDigital >= $jumlahTagihan) {
            $this->saldoDigital -= $jumlahTagihan;
            echo "<div class='success'>✅ Pembayaran berhasil dipotong dari saldo digital.</div>";
            return true;
        } else {
            echo "<div class='error'>❌ Mohon maaf, saldo Anda kurang.</div>";
            return false;
        }
    }
}

class NonMember extends Pelanggan {
    public function __construct($nama) {
        parent::__construct($nama);
        $this->status = "Non-Membership";
    }

    public function topUp($jumlah) {
        $biayaAdmin = 1500;
        $bersih = $jumlah - $biayaAdmin;
        if ($bersih <= 50000) {
            echo "<div class='error'>Top-up gagal. Jumlah terlalu kecil setelah dikurangi biaya admin.</div>";
            return;
        }
        $this->saldoDigital += $bersih;
        echo "<div class='info'>Top-up Rp " . number_format($jumlah, 0, ',', '.') . " berhasil! (Biaya admin: Rp1.500)</div>";
        $this->tampilkanSaldo();
    }

    public function sewa(Kendaraan $kendaraan, $jumlahJam) {
        $hargaAwal = $kendaraan->getHargaPerJam() * $jumlahJam;
        $cashback = 0.02 * $hargaAwal;
        $total = $hargaAwal;

        echo "<div class='container'>";
        echo "<div class='transaksi'>";
        echo "<div class='kiri'>";
        echo "<h3>🤩 Pelanggan: {$this->getNama()} ({$this->getStatus()})</h3>";
        echo "<div class='info'>";
        echo "📦 <strong>Kendaraan:</strong> {$kendaraan->getNamaKendaraan()}<br/>";
        echo "⏳ <strong>Waktu :</strong> $jumlahJam jam<br/>";
        echo "📁 <strong>Kategori:</strong> {$kendaraan->getKategori()}<br/>";
        echo "💵 <strong>Harga /Jam:</strong> Rp " . number_format($kendaraan->getHargaPerJam(), 0, ',', '.') . "<br/>";
        echo "🧾 <strong>Harga Order:</strong> Rp " . number_format($hargaAwal, 0, ',', '.') . "<br/>";
        echo "💳 <strong>Total Bayar:</strong> Rp " . number_format($total, 0, ',', '.') . "<br/>";
        echo "🎁 <strong>Cashback (2%):</strong> Rp " . number_format($cashback, 0, ',', '.') . "<br/>";
        echo "</div>";

        if ($this->bayar($total)) {
            $this->tambahCashback($cashback);
        }

        $this->tampilkanSaldo();
        echo "</div>";
        echo "<div class='kanan'>
                <img src='{$kendaraan->getGambarUrl()}' alt='kendaraan' />
                <div class='nama-kendaraan'>{$kendaraan->getNamaKendaraan()}</div>
              </div>";
        echo "</div>";
        echo "</div>";
    }
}

class Member extends Pelanggan {
    public function __construct($nama) {
        parent::__construct($nama);
        $this->status = "Membership";
    }

    public function sewa(Kendaraan $kendaraan, $jumlahJam) {
        $hargaAwal = $kendaraan->getHargaPerJam() * $jumlahJam;
        $diskon = 0.10 * $hargaAwal;
        $cashback = 0.05 * $hargaAwal;
        $total = $hargaAwal - $diskon;

        echo "<div class='container'>";
        echo "<div class='transaksi'>";
        echo "<div class='kiri'>";
        echo "<h3>👑 Pelanggan: {$this->getNama()} ({$this->getStatus()})</h3>";
        echo "<div class='info'>";
        echo "📦 <strong>Kendaraan:</strong> {$kendaraan->getNamaKendaraan()}<br/>";
        echo "⏳ <strong>Waktu :</strong> $jumlahJam jam<br/>";
        echo "📁 <strong>Kategori:</strong> {$kendaraan->getKategori()}<br/>";
        echo "💵 <strong>Harga /Jam:</strong> Rp " . number_format($kendaraan->getHargaPerJam(), 0, ',', '.') . "<br/>";
        echo "🧾 <strong>Harga Order:</strong> Rp " . number_format($hargaAwal, 0, ',', '.') . "<br/>";
        echo "🔖 <strong>Diskon (10%):</strong> Rp " . number_format($diskon, 0, ',', '.') . "<br/>";
        echo "💳 <strong>Total :</strong> Rp " . number_format($total, 0, ',', '.') . "<br/>";
        echo "🎁 <strong>Cashback (5%):</strong> Rp " . number_format($cashback, 0, ',', '.') . "<br/>";
        echo "</div>";

        if ($this->bayar($total)) {
            $this->tambahCashback($cashback);
        }

        $this->tampilkanSaldo();
        echo "</div>";
        echo "<div class='kanan'>
                <img src='{$kendaraan->getGambarUrl()}' alt='kendaraan' />
                <div class='nama-kendaraan'>{$kendaraan->getNamaKendaraan()}</div>
              </div>";
        echo "</div>";
        echo "</div>";
    }
}

// DATA KENDARAAN
$daftar = [
    new Kendaraan("Toyota Alphard", 250000, "alphard.jpg", "Mobil - Eksklusif"),
    new Kendaraan("Mazda 3 Hatchback", 120000, "mazda.jpg", "Mobil - City Car"),
    new Kendaraan("BMW M3", 300000, "bmw.jpg", "Mobil - Sport Car"),
    new Kendaraan("Honda CBR250RR", 90000, "cbr.jpg", "Motor - Sport 250cc"),
    new Kendaraan("Sepeda Gunung United", 35000, "sepeda.jpg", "Sepeda - Gunung"),
];
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rental Kendaraan</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 40px;
      background: linear-gradient(to right, #0f0c29, #302b63, #24243e);
      font-family: 'Poppins', sans-serif;
      color: #ffffff;
    }

    h2 {
      text-align: center;
      font-size: 34px;
      color: #00f5ff;
      margin-bottom: 40px;
      letter-spacing: 1px;
    }

    hr {
      border: none;
      height: 2px;
      background: linear-gradient(to right, #00f5ff, #00ffcc);
      margin: 40px 0;
    }

    h3 {
      font-size: 22px;
      margin-bottom: 12px;
      color: #ffffff;
    }

    .info {
      background: rgba(0, 245, 255, 0.2);
      border-left: 5px solid #00ffe1;
      padding: 14px;
      margin-bottom: 12px;
      border-radius: 10px;
      font-size: 16px;
      box-shadow: 0 0 10px rgba(0, 255, 255, 0.3);
    }

    .success {
      background: rgba(0, 255, 136, 0.12);
      color: #00ffa2;
      font-weight: bold;
      padding: 10px 14px;
      border-radius: 10px;
      margin-top: 10px;
      margin-bottom: 16px;
    }

    .error {
      background: rgba(255, 0, 60, 0.12);
      color: #ff4c4c;
      font-weight: bold;
      padding: 10px 14px;
      border-radius: 10px;
      margin-top: 10px;
      margin-bottom: 16px;
    }

    .saldo {
      background: linear-gradient(90deg, #00ffbf, #00f5ff);
      color: #000;
      border: 3px solid #00ffe1;
      padding: 16px 26px;
      border-radius: 16px;
      font-size: 20px;
      font-weight: 600;
      box-shadow: 0 0 14px rgba(0, 255, 191, 0.6);
      display: inline-block;
      margin-top: 10px;
      margin-bottom: 16px;
      transition: all 0.3s ease;
    }

    .container {
      max-width: 900px;
      margin: 0 auto;
    }

    .transaksi {
      display: flex;
      align-items: stretch;
      justify-content: space-between;
      background: rgba(0, 136, 247, 0.05);
      border-radius: 10px;
      padding: 30px;
      margin-bottom: 40px;
      gap: 25px;
      box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.18);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .transaksi:hover {
      transform: scale(1.02);
      box-shadow: 0 0 40px rgba(0, 245, 255, 0.4);
    }

    .transaksi .kanan {
      background: rgba(0, 245, 255, 0.07);
      border-radius: 14px;
      padding: 12px;
      border: 2px solid #00f5ff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }

    .transaksi .kanan img {
      width: 320px;
      height: 250px;
      object-fit: cover;
      border-radius: 10px;
      box-shadow: 0 0 16px rgba(0, 245, 255, 0.25);
      margin-bottom: 10px;
      transition: transform 0.3s ease;
    }

    .nama-kendaraan {
      font-size: 16px;
      font-weight: bold;
      color: #00eaff;
      text-align: center;
      text-shadow: 0 0 4px rgba(0, 245, 255, 0.5);
      margin-top: 56px;
      padding: 8px 12px;
      border-radius: 10px;
      border: 1px solid #00f5ff;
      background: rgba(0, 255, 255, 0.08);
      backdrop-filter: blur(3px);
    }

    .kiri {
      flex: 1;
      background: rgba(255, 255, 255, 0.04);
      padding: 24px;
      border-radius: 10px;
      border: 1px solid rgba(0, 255, 255, 0.2);
      box-shadow: inset 0 0 12px rgba(0, 245, 255, 0.1);
      backdrop-filter: blur(4px);
      transition: background 0.3s;
    }

    .kiri:hover {
      background: rgba(14, 255, 235, 0.07);
    }

    /* Popup error futuristik */
    .popup-error {
      position: fixed;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      background: rgba(0, 245, 255, 0.12);
      border: 2px solid #00f5ff;
      color: #00f5ff;
      font-size: 20px;
      font-weight: 600;
      padding: 25px 40px;
      border-radius: 16px;
      box-shadow: 0 0 30px rgba(0, 245, 255, 0.6);
      text-align: center;
      z-index: 9999;
      animation: fadeIn 0.4s ease, pulseGlow 2s infinite;
      backdrop-filter: blur(10px);
    }
    .popup-error button {
      margin-top: 15px;
      padding: 10px 20px;
      border: none;
      border-radius: 10px;
      background: linear-gradient(90deg, #00ffbf, #00f5ff);
      color: #000;
      font-weight: bold;
      cursor: pointer;
      transition: transform 0.2s ease;
    }
    .popup-error button:hover {
      transform: scale(1.05);
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translate(-50%, -55%); }
      to { opacity: 1; transform: translate(-50%, -50%); }
    }
    @keyframes pulseGlow {
      0% { box-shadow: 0 0 20px rgba(0, 245, 255, 0.3); }
      50% { box-shadow: 0 0 40px rgba(0, 245, 255, 0.7); }
      100% { box-shadow: 0 0 20px rgba(0, 245, 255, 0.3); }
    }
  </style>
</head>
<body>

<h2>🚗 Daftar Sewa Kendaraan</h2>
<hr/>

<!-- FORM INPUT -->
<form method="post" class="container">
  <h3>🔧 Formulir Sewa Kendaraan</h3>
  <div class="info">
    <label>Nama Pelanggan:<br>
      <input type="text" name="nama" required style="width:100%;padding:8px;border-radius:8px;border:none;">
    </label><br><br>

    <label>Status Pelanggan:<br>
      <select name="status" required style="width:100%;padding:8px;border-radius:8px;border:none;">
        <option value="Member">Membership</option>
        <option value="NonMember">Non-Membership</option>
      </select>
    </label><br><br>

    <label>Jumlah Top-Up (Rp):<br>
      <input type="number" name="topup" required style="width:100%;padding:8px;border-radius:8px;border:none;">
    </label><br><br>

    <label>Pilih Kendaraan:<br>
      <select name="kendaraan" required style="width:100%;padding:8px;border-radius:8px;border:none;">
        <?php foreach ($daftar as $index => $k) {
          echo "<option value='$index'>{$k->getNamaKendaraan()} - Rp " . number_format($k->getHargaPerJam(), 0, ',', '.') . "/jam</option>";
        } ?>
      </select>
    </label><br><br>

    <label>Lama Sewa (jam):<br>
      <input type="number" name="lama" min="1" required style="width:100%;padding:8px;border-radius:8px;border:none;">
    </label><br><br>

    <button type="submit" style="padding:10px 20px;border-radius:10px;border:none;background:#00ffe1;color:#000;font-weight:bold;cursor:pointer;">🚀 Proses Sewa</button>
  </div>
</form>

<hr/>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = $_POST['nama'];
    $status = $_POST['status'];
    $topup = (int) $_POST['topup'];
    $indexKendaraan = (int) $_POST['kendaraan'];
    $lama = (int) $_POST['lama'];

    if ($status === "Member" && $topup < 5000000) {
        echo "<div class='popup-error' id='popup'>
                ❌ Gagal Top-Up<br>Minimal Rp 5.000.000 untuk Membership
                <br><button onclick=\"document.getElementById('popup').style.display='none'\">Tutup</button>
              </div>
              <script>
                setTimeout(()=>{document.getElementById('popup').style.display='none'},4000);
              </script>";
    } else {
        $pelanggan = ($status === "Member") ? new Member($nama) : new NonMember($nama);
        $pelanggan->topUp($topup);
        $pelanggan->sewa($daftar[$indexKendaraan], $lama);
    }
}
?>

</body>
