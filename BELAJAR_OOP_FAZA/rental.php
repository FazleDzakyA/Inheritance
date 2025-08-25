<?php
<<<<<<< HEAD
session_start();
require_once 'db.php';
if (!isset($_SESSION['nama'])) {
  header("Location: login.php");
  exit;
}
date_default_timezone_set('Asia/Jakarta');

$mysqli = db();

/* =========================
   DATA KENDARAAN (from DB)
========================= */
$daftar = [];
$res = $mysqli->query("SELECT * FROM kendaraan");
while ($row = $res->fetch_assoc()) {
  $daftar[] = [
    "nama" => $row['nama_kendaraan'],
    "harga" => $row['harga_kenderaan'],
    "gambar" => $row['gambar_kendaraan'],
    "kategori" => $row['jenis_kendaraan']
  ];
}
array_unshift($daftar, ["nama" => "Pilih Kendaraan", "harga" => 0, "gambar" => "", "kategori" => ""]);

/* =========================
   HELPERS
========================= */
function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function rupiah($n) { return 'Rp ' . number_format((int) $n, 0, ',', '.'); }
=======
/**
 * rental.php — Single file, OOP + extends, UI & fitur sama, daftar kendaraan persis milikmu
 * Command cepat:
 * - Simpan file ini sebagai rental.php
 * - Jalankan PHP server lokal: php -S localhost:8000
 * - Buka: http://localhost:8000/rental.php
 */

// Mulai session untuk menyimpan data sementara antar-request
session_start();
// Pastikan timezone sesuai (penting untuk timestamp riwayat)
date_default_timezone_set('Asia/Jakarta');

/* =========================================================
   1) HELPERS (fungsi util kecil)
   - esc() : escape output HTML agar aman dari XSS
   - rupiah(): format angka ke format rupiah "Rp 1.000.000"
========================================================= */
function esc($s)
{
  return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
function rupiah($n)
{
  return 'Rp ' . number_format((int) $n, 0, ',', '.');
}
>>>>>>> 567f31317ccab134bbc010b790e08ac5eba563ca

/* =========================================================
   2) DATA KENDARAAN (PERSIS PUNYAMU — JANGAN DIUBAH)
   Catatan: Nilai & urutan TIDAK diubah.
   - $daftar: array asosiatif; index 0 adalah placeholder "Pilih Kendaraan"
   - setiap item: nama, harga (per jam), gambar, kategori
========================================================= */
$daftar = [
  ["nama" => "Pilih Kendaraan", "harga" => 0, "gambar" => "NOMADIC - CAFE - john zaki.jpg", "kategori" => ""],
  ["nama" => "Toyota Alphard", "harga" => 250000, "gambar" => "alphard.jpg", "kategori" => "Mobil - Eksklusif"],
  ["nama" => "Mazda 3 Hatchback", "harga" => 120000, "gambar" => "mazda.jpg", "kategori" => "Mobil - City Car"],
  ["nama" => "BMW M3", "harga" => 300000, "gambar" => "bmw.jpg", "kategori" => "Mobil - Sport Car"],
  ["nama" => "Honda CBR250RR", "harga" => 90000, "gambar" => "cbr.jpg", "kategori" => "Motor - Sport 250cc"],
  ["nama" => "Sepeda Gunung United", "harga" => 35000, "gambar" => "sepeda.jpg", "kategori" => "Sepeda - Gunung"],
];

/* =========================================================
   3) DOMAIN MODELS (POPO + Service) — OOP + extends
   Penjelasan singkat:
   - Kendaraan: representasi kendaraan (value object)
   - KendaraanRepo: wrapper untuk mengambil kendaraan dari $daftar
   - PricingPolicy: aturan harga umum (diskon/cashback)
   - MemberPricingPolicy: override untuk member
   - Wallet: interface session untuk saldo
   - History, Lifetime: helper untuk riwayat & lifetime totals (session)
   - RentalService: business logic utama untuk topup & sewa
   - Auth: login/logout helper
========================================================= */

<<<<<<< HEAD
/* =========================
   POPUP STATE
========================= */
$popupMsg = null;
$popupType = 'info';

/* =========================
   ACTIONS
========================= */
// TOP-UP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'topup') {
  $nom = (int) ($_POST['nominal'] ?? 0);
  $status = $_SESSION['status'] ?? 'NonMember';
  $member_requires_initial = ($status === 'Member' && ($_SESSION['lifetime']['topup'] ?? 0) == 0 && ($_SESSION['saldo'] ?? 0) < 5000000);

  if ($member_requires_initial && $nom < 5000000) {
    $popupMsg = "Top-up gagal — Sebagai Member, top-up awal minimal Rp5.000.000.";
    $popupType = 'error';
  } else if ($nom < 50000) {
    $popupMsg = "Top-up gagal — minimal Rp50.000.";
    $popupType = 'error';
  } else {
    $_SESSION['saldo'] += $nom;
    $_SESSION['lifetime']['topup'] += $nom;
    // Update saldo in DB
    $id = $_SESSION['id_Pelanggan'];
    $mysqli->query("UPDATE pelanggan SET saldoDigital = saldoDigital + $nom WHERE id_Pelanggan = $id");
    $popupMsg = "Top-up berhasil: +" . rupiah($nom) . " (Saldo sekarang: " . rupiah($_SESSION['saldo']) . ")";
    $popupType = 'success';
=======
/** Representasi Kendaraan (immutable-ish) */
class Kendaraan
{
  public string $nama;
  public int $harga;
  public string $gambar;
  public string $kategori;
  // Konstruktor menerima nilai awal kendaraan
  public function __construct(string $nama, int $harga, string $gambar, string $kategori)
  {
    $this->nama = $nama;
    $this->harga = $harga;
    $this->gambar = $gambar;
    $this->kategori = $kategori;
  }
  // Factory: buat Kendaraan dari array (menggunakan data $daftar)
  public static function fromArray(array $a): self
  {
    return new self($a['nama'] ?? '', (int) ($a['harga'] ?? 0), $a['gambar'] ?? '', $a['kategori'] ?? '');
  }
}

/** Repository ringan untuk akses list kendaraan
 * - Menyimpan array Kendaraan dan memberikan method get(int)
 * - get(int) aman: jika idx tidak ada, kembalikan indeks 0 (placeholder)
 */
class KendaraanRepo
{
  /** @var Kendaraan[] */
  private array $items = [];
  public function __construct(array $rawDaftar)
  {
    foreach ($rawDaftar as $row) {
      // Konversi setiap array ke objek Kendaraan
      $this->items[] = Kendaraan::fromArray($row);
    }
  }
  /** get by index aman: kembalikan Kendaraan atau placeholder */
  public function get(int $idx): Kendaraan
  {
    return $this->items[$idx] ?? $this->items[0];
  }
}

/** Aturan harga dasar
 * - discount(gross): potongan harga dari gross (default 0)
 * - cashback(gross): nilai cashback (default 2%)
 *
 * Catatan: semua angka integer (floor/round dibuat di implementasi)
 */
class PricingPolicy
{
  /** Diskon (default 0%) */
  public function discount(int $gross): int
  {
    return 0;
  }
  /** Cashback (default 2%) */
  public function cashback(int $gross): int
  {
    return (int) round(0.02 * $gross);
  }
}

/** Aturan harga untuk Member (EXTENDS PricingPolicy)
 * - Member dapat diskon 10% dan cashback 5%
 */
class MemberPricingPolicy extends PricingPolicy
{
  public function discount(int $gross): int
  {
    return (int) round(0.10 * $gross);
  }
  public function cashback(int $gross): int
  {
    return (int) round(0.05 * $gross);
  }
}

/** Wallet/saldo user dalam session
 * Helper statis untuk membaca & menulis saldo ke $_SESSION['saldo']
 */
class Wallet
{
  // Baca saldo (selalu integer)
  public static function balance(): int
  {
    return (int) ($_SESSION['saldo'] ?? 0);
  }
  // Tambah saldo
  public static function add(int $amount): void
  {
    $_SESSION['saldo'] = self::balance() + $amount;
  }
  // Kurangi saldo (jika cukup). Return true jika berhasil, false jika tidak cukup.
  public static function sub(int $amount): bool
  {
    if (self::balance() >= $amount) {
      $_SESSION['saldo'] -= $amount;
      return true;
    }
    return false;
  }
}

/** Riwayat transaksi (disimpan di session)
 * - pushTopup: catat topup biasa
 * - pushTopupFromSewa: catat topup yang dilakukan dari form sewa (diberi label berbeda)
 * - pushSewa: catat entri sewa
 *
 * Struktur riwayat sama seperti versi awalmu, supaya UI tidak berubah.
 */
class History
{
  public static function pushTopup(int $nom): void
  {
>>>>>>> 567f31317ccab134bbc010b790e08ac5eba563ca
    array_unshift($_SESSION['riwayat'], [
      'tanggal' => date("d-m-Y H:i:s"),
      'nama' => $_SESSION['nama'] ?? '-',
      'kendaraan' => 'Top-up Saldo',
      'lama' => 0,
      'hargaJam' => 0,
      'hargaAwal' => $nom,
      'diskon' => 0,
      'cashback' => 0,
<<<<<<< HEAD
      'total' => -$nom,
=======
      'total' => -$nom, // negatif -> di tabel akan ditampilkan '+' sesuai UI lama
>>>>>>> 567f31317ccab134bbc010b790e08ac5eba563ca
      'gambar' => '',
      'status' => $_SESSION['status'] ?? 'NonMember',
      'tipe' => 'TOPUP'
    ]);
  }
  public static function pushTopupFromSewa(int $nom): void
  {
    array_unshift($_SESSION['riwayat'], [
      'tanggal' => date("d-m-Y H:i:s"),
      'nama' => $_SESSION['nama'] ?? '-',
      'kendaraan' => 'Top-up Saldo (Form Sewa)',
      'lama' => 0,
      'hargaJam' => 0,
      'hargaAwal' => $nom,
      'diskon' => 0,
      'cashback' => 0,
      'total' => -$nom,
      'gambar' => '',
      'status' => $_SESSION['status'] ?? 'NonMember',
      'tipe' => 'TOPUP'
    ]);
  }
  public static function pushSewa(array $ent): void
  {
    // Ent harus sesuai struktur: tanggal,nama,kendaraan,lama,hargaJam,hargaAwal,diskon,cashback,total,gambar,status,tipe
    array_unshift($_SESSION['riwayat'], $ent);
  }
}

/** LTV agregat (lifetime totals)
 * - Menyimpan ringkasan total topup & total sewa (dibayar)
 */
class Lifetime
{
  public static function addTopup(int $n): void
  {
    $_SESSION['lifetime']['topup'] = (int) ($_SESSION['lifetime']['topup'] ?? 0) + $n;
  }
  public static function addSewa(int $n): void
  {
    $_SESSION['lifetime']['sewa'] = (int) ($_SESSION['lifetime']['sewa'] ?? 0) + $n;
  }
}

/** Service untuk validasi topup & sewa
 * - class ini mengenkapsulasi semua aturan bisnis:
 *   * aturan minimal topup untuk member (topup awal >= 5jt bila saldo < 5jt & lifetime topup == 0)
 *   * aturan minimal topup umum (>= 50.000)
 *   * aturan diskon & cashback (berdasarkan policy: MemberPricingPolicy atau PricingPolicy)
 *   * mengatur pemotongan saldo, pemberian cashback, penyimpanan riwayat
 */
class RentalService
{
  private PricingPolicy $policy;
  // Konstruktor menerima flag isMember untuk memilih policy yang sesuai
  public function __construct(bool $isMember)
  {
    $this->policy = $isMember ? new MemberPricingPolicy() : new PricingPolicy();
  }

  /**
   * topup(int $nom, bool $fromSewa = false): array
   * - $nom: jumlah topup
   * - $fromSewa: apakah topup ini berasal dari form sewa (untuk label riwayat)
   * Return array: ['ok'=>bool,'type'=>'success'|'error','msg'=>string]
   */
  public function topup(int $nom, bool $fromSewa = false): array
  {
    // Ambil status user dari session (Member/NonMember)
    $status = $_SESSION['status'] ?? 'NonMember';

    // Ketentuan: jika user Member dan belum pernah topup (lifetime topup == 0) dan saldo saat ini < 5 juta,
    // maka topup awal wajib >= 5.000.000
    $requiresInitial = ($status === 'Member'
      && (int) ($_SESSION['lifetime']['topup'] ?? 0) == 0
      && (int) ($_SESSION['saldo'] ?? 0) < 5000000);

    // Jika aturan awal terpenuhi namun nominal kurang -> gagal
    if ($requiresInitial && $nom < 5000000) {
      return ['ok' => false, 'type' => 'error', 'msg' => "Top-up gagal — Sebagai Member, top-up awal minimal Rp5.000.000."];
    }
    // Aturan umum: minimal topup 50.000
    if ($nom < 50000) {
      return ['ok' => false, 'type' => 'error', 'msg' => "Top-up gagal — minimal Rp50.000."];
    }

    // Jika lolos validasi -> tambahkan saldo, simpan lifetime & riwayat
    Wallet::add($nom);
    Lifetime::addTopup($nom);
    $fromSewa ? History::pushTopupFromSewa($nom) : History::pushTopup($nom);

    // Kembalikan pesan sukses beserta saldo sekarang
    return ['ok' => true, 'type' => 'success', 'msg' => "Top-up berhasil: +" . rupiah($nom) . " (Saldo sekarang: " . rupiah(Wallet::balance()) . ")"];
  }

  /**
   * sewa(Kendaraan $kend, int $lama): array
   * - Proses sewa utama:
   *   1. validasi kendaraan (harga > 0)
   *   2. hitung hargaAwal = harga * lama
   *   3. terapkan diskon & cashback sesuai policy
   *   4. cek saldo -> jika cukup maka potong saldo & tambahkan cashback
   *   5. simpan riwayat & last_order
   * - Return: array status & pesan untuk ditampilkan di popup
   */
  public function sewa(Kendaraan $kend, int $lama): array
  {
    // Jika kendaraan placeholder (harga 0), anggap tidak valid
    if ($kend->harga <= 0) {
      return ['ok' => false, 'type' => 'error', 'msg' => "Pilih kendaraan yang valid."];
    }

    // Hitung harga awal (harga per jam * jam)
    $hargaAwal = $kend->harga * $lama;

    // Hitung diskon & cashback via policy (policy bergantung pada member / non-member)
    $diskon = $this->policy->discount($hargaAwal);
    $cashback = $this->policy->cashback($hargaAwal);

    // Total yang harus dibayar = hargaAwal - diskon
    $total = $hargaAwal - $diskon;

    // Cek saldo user: jika saldo kurang, kembalikan error
    if (!Wallet::sub($total)) {
      return ['ok' => false, 'type' => 'error', 'msg' => "Saldo tidak cukup. Total " . rupiah($total) . ". Silakan top-up."];
    }

    // Jika pembayaran berhasil, tambahkan cashback ke saldo user
    Wallet::add((int) $cashback);
    // Tambah agregat lifetime sewa (pencatatan berapa total sewa dibayar)
    Lifetime::addSewa($total);

    // Buat entry riwayat sesuai struktur lama supaya UI tidak perlu diubah
    $ent = [
      'tanggal' => date("d-m-Y H:i:s"),
      'nama' => $_SESSION['nama'] ?? '-',
      'kendaraan' => $kend->nama,
      'lama' => $lama,
      'hargaJam' => $kend->harga,
      'hargaAwal' => $hargaAwal,
      'diskon' => $diskon,
      'cashback' => $cashback,
      'total' => $total,
      'gambar' => $kend->gambar,
      'status' => $_SESSION['status'] ?? 'NonMember',
      'tipe' => 'SEWA'
    ];
    // simpan riwayat dan last_order untuk ditampilkan di UI
    History::pushSewa($ent);
    $_SESSION['last_order'] = $ent;

    // kembalikan pesan sukses
    return ['ok' => true, 'type' => 'success', 'msg' => "Sewa berhasil! Total " . rupiah($total) . ". Cashback " . rupiah((int) $cashback) . " telah masuk."];
  }
}

/** Session facade (login/logout)
 * - Auth::login(name,status) -> menyimpan nama & status di session
 * - Auth::isLogged() -> cek apakah session nama ada
 * - Auth::isMember() -> cek apakah status di session Member
 */
class Auth
{
  public static function login(string $nama, string $status): array
  {
    if (trim($nama) === '') {
      return ['ok' => false, 'type' => 'error', 'msg' => "Nama tidak boleh kosong."];
    }
    // Simpan nama & status ke session
    $_SESSION['nama'] = $nama;
    $_SESSION['status'] = ($status === 'Member') ? 'Member' : 'NonMember';
    return ['ok' => true, 'type' => 'success', 'msg' => "Login berhasil! Selamat datang, {$nama}."];
  }
  // Cek login (apakah session nama ter-set)
  public static function isLogged(): bool
  {
    return isset($_SESSION['nama']);
  }
  // Cek apakah user di session berstatus member
  public static function isMember(): bool
  {
    return (($_SESSION['status'] ?? '') === 'Member');
  }
}

/* =========================================================
   4) INIT SESSION STATE (sama seperti versi lama)
   - Pastikan index-index session ada & memiliki default value
========================================================= */
$_SESSION['riwayat'] = $_SESSION['riwayat'] ?? [];
$_SESSION['saldo'] = $_SESSION['saldo'] ?? 0;
$_SESSION['lifetime'] = $_SESSION['lifetime'] ?? ['topup' => 0, 'sewa' => 0];
// Simpan saldo sebelum aksi (dipakai untuk animasi count-up)
$saldo_sebelum = (int) $_SESSION['saldo'];

/* =========================================================
   5) POPUP HOLDER
   - $popupMsg & $popupType diisi ketika ada aksi (login/topup/sewa)
========================================================= */
$popupMsg = null;  // pesan popup (string)
$popupType = 'info'; // success | error | info

/* =========================================================
   6) ACTION HANDLERS (gunakan service OOP)
   - Buat repository kendaraan lalu tangani aksi POST
========================================================= */
$repo = new KendaraanRepo($daftar);

// LOGIN: jika form login dikirimkan (aksi=login)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'login') {
  // Panggil Auth::login -> kembalikan array result {ok,type,msg}
  $res = Auth::login($_POST['nama'] ?? '', ($_POST['status'] ?? 'NonMember'));
  $popupMsg = $res['msg'];
  $popupType = $res['type'];
}

// TOP-UP (modal / form topup)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'topup') {
  // Buat RentalService dengan policy berdasarkan status member saat ini
  $service = new RentalService(Auth::isMember());
  // Ambil nominal dari form
  $nom = (int) ($_POST['nominal'] ?? 0);
  // Jalankan topup
  $res = $service->topup($nom, false);
  $popupMsg = $res['msg'];
  $popupType = $res['type'];
}

<<<<<<< HEAD
// SEWA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'sewa' && isset($_SESSION['nama'])) {
  $idx = (int) ($_POST['kendaraan'] ?? 0);
  $lama = max(1, (int) ($_POST['lama'] ?? 1));
  $topup = max(0, (int) ($_POST['topup'] ?? 0));
  $topup_failed = false;

  // Topup from sewa form
=======
// SEWA (form sewa) — termasuk opsi topup dari form sewa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'sewa' && Auth::isLogged()) {
  $service = new RentalService(Auth::isMember());
  $idx = (int) ($_POST['kendaraan'] ?? 0);            // indeks kendaraan di $daftar
  $lama = max(1, (int) ($_POST['lama'] ?? 1));        // lama sewa (min 1 jam)
  $topup = max(0, (int) ($_POST['topup'] ?? 0));      // opsional topup dari form sewa

  $topup_failed = false;

  // Jika user memilih topup dari form sewa, jalankan topup dulu
>>>>>>> 567f31317ccab134bbc010b790e08ac5eba563ca
  if ($topup > 0) {
    // validasi & topup dilakukan oleh service (menggunakan aturan member jika perlu)
    $resT = $service->topup($topup, true);
    if (!$resT['ok']) {
      // jika topup gagal, tandai gagal sehingga proses sewa tidak lanjut
      $topup_failed = true;
<<<<<<< HEAD
    } else if ($nom < 50000) {
      $popupMsg = "Top-up gagal — minimal Rp50.000.";
      $popupType = 'error';
      $topup_failed = true;
    } else {
      $_SESSION['saldo'] += $nom;
      $_SESSION['lifetime']['topup'] += $nom;
      $id = $_SESSION['id_Pelanggan'];
      $mysqli->query("UPDATE pelanggan SET saldoDigital = saldoDigital + $nom WHERE id_Pelanggan = $id");
      array_unshift($_SESSION['riwayat'], [
        'tanggal' => date("d-m-Y H:i:s"),
        'nama' => $_SESSION['nama'],
        'kendaraan' => 'Top-up Saldo (Form Sewa)',
        'lama' => 0,
        'hargaJam' => 0,
        'hargaAwal' => $nom,
        'diskon' => 0,
        'cashback' => 0,
        'total' => -$nom,
        'gambar' => '',
        'status' => $_SESSION['status'],
        'tipe' => 'TOPUP'
      ]);
      $popupMsg = "Top-up berhasil +" . rupiah($nom) . ".";
      $popupType = 'success';
=======
>>>>>>> 567f31317ccab134bbc010b790e08ac5eba563ca
    }
    // tampilkan pesan topup (baik sukses/gagal) melalui popup
    $popupMsg = $resT['msg'];
    $popupType = $resT['type'];
  }

<<<<<<< HEAD
  if ($topup_failed) {
    // nothing else
  } else {
    $kend = $daftar[$idx] ?? $daftar[0];
    if (($kend['harga'] ?? 0) <= 0) {
      $popupMsg = "Pilih kendaraan yang valid.";
      $popupType = 'error';
    } else {
      $hargaAwal = $kend['harga'] * $lama;
      $status = $_SESSION['status'] ?? 'NonMember';
      $diskon = ($status === 'Member') ? 0.10 * $hargaAwal : 0;
      $cashback = ($status === 'Member') ? 0.05 * $hargaAwal : 0.02 * $hargaAwal;
      $total = $hargaAwal - $diskon;

      if ($_SESSION['saldo'] >= $total) {
        $_SESSION['saldo'] -= $total;
        $_SESSION['saldo'] += round($cashback);
        $_SESSION['lifetime']['sewa'] += $total;
        $id = $_SESSION['id_Pelanggan'];
        // Update saldo in DB
        $mysqli->query("UPDATE pelanggan SET saldoDigital = saldoDigital - $total + " . round($cashback) . " WHERE id_Pelanggan = $id");

        $ent = [
          'tanggal' => date("d-m-Y H:i:s"),
          'nama' => $_SESSION['nama'],
          'kendaraan' => $kend['nama'],
          'lama' => $lama,
          'hargaJam' => $kend['harga'],
          'hargaAwal' => $hargaAwal,
          'diskon' => $diskon,
          'cashback' => $cashback,
          'total' => $total,
          'gambar' => $kend['gambar'],
          'status' => $status,
          'tipe' => 'SEWA'
        ];
        array_unshift($_SESSION['riwayat'], $ent);
        $_SESSION['last_order'] = $ent;

        $popupMsg = "Sewa berhasil! Total " . rupiah($total) . ". Cashback " . rupiah(round($cashback)) . " telah masuk.";
        $popupType = 'success';
      } else {
        $popupMsg = "Saldo tidak cukup. Total " . rupiah($total) . ". Silakan top-up.";
        $popupType = 'error';
      }
    }
=======
  // Jika topup berhasil / tidak ada topup, lanjut proses sewa
  if (!$topup_failed) {
    // Ambil kendaraan dari repo (memastikan struktur Kendaraan)
    $kend = $repo->get($idx);
    // Proses sewa (service menentukan diskon, cashback, pengecekan saldo)
    $resS = $service->sewa($kend, $lama);
    $popupMsg = $resS['msg'];
    $popupType = $resS['type'];
>>>>>>> 567f31317ccab134bbc010b790e08ac5eba563ca
  }
}

// LOGOUT: jika ada query param logout=1, hentikan session dan redirect
if (isset($_GET['logout'])) {
  session_destroy();
  header("Location: rental.php");
  exit;
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Rent Garage — Sewa Kendaraan</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
<<<<<<< HEAD
  <link href="rentalStyle.css" rel="stylesheet">
=======
  <style>
    /* --- CSS tetap persis seperti versi terakhirmu --- */
    :root {
      --bg1: #0e0f12;
      --bg2: #0a0b0d;
      --panel: rgba(255, 255, 255, .04);
      --primary: #00eaff;
      --primary2: #00ffbf;
      --text: #eef6fa;
      --muted: #9fb0bf;
      --danger: #ff6b6b;
      --success: #2ee6a6;
      --select: rgba(255, 255, 255, 0.03);
    }

    * {
      box-sizing: border-box
    }

    html,
    body {
      margin: 0
    }

    body {
      font-family: 'Poppins', sans-serif;
      color: var(--text);
      min-height: 100vh;
      padding: 28px;
      background:
        radial-gradient(1200px 600px at 15% -10%, rgba(0, 234, 255, .05), transparent 60%),
        radial-gradient(1000px 500px at 90% 10%, rgba(0, 255, 191, .04), transparent 60%),
        linear-gradient(135deg, var(--bg1), var(--bg2));
    }

    .container {
      max-width: 1150px;
      margin: 0 auto
    }

    header.header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 18px;
    }

    .brand {
      color: var(--primary);
      font-weight: 700;
      font-size: 20px;
      letter-spacing: .3px
    }

    button,
    a.button {
      border: none;
      border-radius: 12px;
      cursor: pointer;
      font-weight: 700
    }

    .panel {
      background: var(--panel);
      border-radius: 14px;
      padding: 18px;
      border: 1px solid rgba(255, 255, 255, .06);
      box-shadow: 0 12px 40px rgba(2, 6, 23, .6);
    }

    label {
      display: block;
      margin: 10px 0 6px;
      font-weight: 600;
      color: var(--muted)
    }

    input[type="text"],
    input[type="number"],
    select {
      width: 100%;
      padding: 12px 14px;
      border-radius: 12px;
      border: none;
      outline: none;
      background: var(--select);
      color: var(--text);
      font-size: 14px;
    }

    input:focus,
    select:focus {
      box-shadow: 0 10px 30px rgba(0, 234, 255, .05)
    }

    .select-wrap {
      position: relative
    }

    .select-wrap:after {
      content: '';
      position: absolute;
      right: 14px;
      top: 50%;
      transform: translateY(-60%) rotate(45deg);
      width: 10px;
      height: 10px;
      border-right: 2px solid rgba(255, 255, 255, .7);
      border-bottom: 2px solid rgba(255, 255, 255, .7);
      opacity: .9;
      pointer-events: none;
    }

    select option {
      color: #001;
      background: #fff;
    }

    .btn {
      padding: 12px 14px;
      background: linear-gradient(90deg, var(--primary2), var(--primary));
      color: #000;
      border-radius: 12px;
      border: none;
      cursor: pointer
    }

    .btn.ghost {
      background: transparent;
      border: 1px solid rgba(255, 255, 255, .08);
      color: var(--text)
    }

    .btn.small {
      padding: 8px 10px;
      border-radius: 10px
    }

    .saldo {
      display: inline-block;
      padding: 10px 14px;
      border-radius: 12px;
      background: linear-gradient(90deg, rgba(0, 234, 255, .06), rgba(0, 255, 191, .03));
      color: #001;
      font-weight: 800
    }

    .grid {
      display: grid;
      gap: 14px
    }

    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px
    }

    .cols {
      display: grid;
      grid-template-columns: 1.05fr .95fr;
      gap: 18px
    }

    @media(max-width:980px) {
      .cols {
        grid-template-columns: 1fr
      }
    }

    .preview {
      display: grid;
      grid-template-columns: 1fr .9fr;
      gap: 16px;
      background: linear-gradient(180deg, rgba(255, 255, 255, .02), rgba(255, 255, 255, .01));
      border-radius: 14px;
      padding: 14px;
      border: 1px solid rgba(255, 255, 255, .03)
    }

    @media(max-width:920px) {
      .preview {
        grid-template-columns: 1fr
      }
    }

    .chip {
      display: inline-block;
      padding: 6px 10px;
      border-radius: 999px;
      background: rgba(0, 234, 255, .09);
      color: var(--primary)
    }

    .dl {
      display: grid;
      grid-template-columns: auto 1fr;
      gap: 8px 12px;
      margin-top: 12px
    }

    .dl dt {
      color: var(--muted);
      font-size: 13px
    }

    .dl dd {
      margin: 0;
      font-weight: 700
    }

    .imgbox {
      width: 100%;
      height: 280px;
      overflow: hidden;
      border-radius: 10px;
      background: rgba(255, 255, 255, .02)
    }

    .imgbox img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block
    }

    .order-detail {
      display: flex;
      gap: 16px;
      align-items: flex-start;
      margin-top: 12px;
      border-radius: 12px;
      padding: 12px;
      background: linear-gradient(180deg, rgba(255, 255, 255, .02), transparent);
      border: 1px solid rgba(255, 255, 255, .03)
    }

    .order-detail .img-lg {
      width: 45%;
      min-width: 220px;
      height: 220px;
      overflow: hidden;
      border-radius: 12px
    }

    .order-detail .img-lg img {
      width: 100%;
      height: 100%;
      object-fit: cover
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 8px
    }

    th,
    td {
      padding: 10px;
      border-bottom: 1px solid rgba(255, 255, 255, .06);
      font-size: 13px;
      text-align: left
    }

    th {
      color: var(--primary)
    }

    .small {
      color: var(--muted);
      font-size: 12.6px
    }

    #popup {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      backdrop-filter: blur(6px)
    }

    .popup-box {
      min-width: 320px;
      max-width: 420px;
      background: #0c1116;
      border-radius: 12px;
      padding: 18px;
      border: 1px solid rgba(255, 255, 255, .07)
    }

    .popup-box.success {
      border-color: rgba(46, 230, 166, .28)
    }

    .popup-box.error {
      border-color: rgba(255, 107, 107, .28)
    }

    #modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .5);
      align-items: center;
      justify-content: center;
      z-index: 999
    }

    .modal-card {
      background: #0b1116;
      padding: 16px;
      border-radius: 12px;
      border: 1px solid rgba(255, 255, 255, .07);
      width: 320px
    }

    .header-sticky {
      position: sticky;
      top: 0;
      z-index: 1000;
      padding: 12px 0;
      background: linear-gradient(180deg, rgba(6, 10, 16, .6), rgba(6, 10, 16, .9));
      backdrop-filter: blur(6px);
      transition: box-shadow 0.28s ease, transform 0.18s ease;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
    }

    body.scrolled .header-sticky {
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.28);
    }

    @media (max-width:480px) {
      select {
        font-size: 16px;
        padding: 12px;
      }
    }
  </style>
>>>>>>> 567f31317ccab134bbc010b790e08ac5eba563ca
</head>

<body>
  <div class="container">
    <header class="header header-sticky">
      <div class="brand">🚗 Rent Garage</div>
      <?php if (Auth::isLogged()): ?>
        <div style="display:flex;align-items:center;gap:12px;">
          <span class="saldo" id="saldoHeader"
            style="background:linear-gradient(90deg,#00ffbf,#00eaff); color:#001;font-weight:700;padding:10px 16px;border-radius:14px;box-shadow:0 0 15px rgba(0,255,191,.25)">
            <?= rupiah($_SESSION['saldo']); ?>
          </span>
          <a href="?logout=1" class="btn" style="text-decoration:none;display:inline-block">Keluar</a>
        </div>
      <?php endif; ?>
    </header>

    <?php if (!Auth::isLogged()): ?>
      <!-- LOGIN -->
    <?php else: ?>
      <!-- MAIN -->
      <main class="cols">
        <!-- LEFT: FORM -->
        <section class="panel">
          <header style="margin-bottom:6px">
            <h3 style="margin:0">🔧 Formulir Sewa</h3>
            <p class="small" style="margin-top:6px">Nama otomatis dari akun.</p>
          </header>

          <form method="post" id="form-sewa" class="grid" style="margin-top:12px">
            <input type="hidden" name="aksi" value="sewa">

            <div class="grid-2">
              <div>
                <label>Nama Pemesan</label>
                <input type="text" value="<?= esc($_SESSION['nama']); ?>" readonly>
              </div>
              <div>
                <label>Status</label>
                <input type="text"
                  value="<?= (($_SESSION['status'] ?? '') === 'Member' ? '👑 Member' : '🙂 Non-Member'); ?>" readonly>
              </div>
            </div>

            <div>
              <label>Pilih Kendaraan</label>
              <div class="select-wrap">
                <select name="kendaraan" id="kendSelect" required>
                  <?php foreach ($daftar as $i => $k): ?>
                    <option value="<?= $i; ?>">
                      <?= esc($k['nama']); ?> — Rp <?= number_format($k['harga'], 0, ',', '.'); ?>/jam
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>

            <div class="grid-2">
              <div>
                <label>Lama Sewa (jam)</label>
                <input type="number" name="lama" id="lama" min="1" value="1" required>
              </div>
              <div>
                <label>Top-Up (opsional)</label>
                <input type="number" name="topup" id="topup" min="0" placeholder="Contoh: 500000">
                <p class="small" style="margin-top:6px">
                  Minimal top-up Rp50.000.<br>
                  !Member top-up awal wajib ≥ Rp5.000.000.
                </p>
              </div>
            </div>

            <div style="display:flex;gap:10px;flex-wrap:wrap">
              <button class="btn" type="submit">✅ Proses Sewa</button>
              <button type="button" id="openTopup" class="btn ghost">Top-up Manual</button>
            </div>
          </form>

          <!-- LAST ORDER -->
          <?php if (!empty($_SESSION['last_order'])):
            $lo = $_SESSION['last_order']; ?>
            <article style="margin-top:16px">
              <h4 style="margin:6px 0">📦 Pesanan Terakhir</h4>
              <div class="order-detail">
                <div class="img-lg"><img src="<?= esc($lo['gambar']); ?>" alt=""></div>
                <div style="flex:1">
                  <h3 style="margin:0 0 6px"><?= esc($lo['kendaraan']); ?></h3>
                  <p class="small">Pemesan: <?= esc($lo['nama']); ?> • <?= esc($lo['status']); ?></p>
                  <dl class="dl" style="margin-top:8px">
                    <dt>Durasi</dt>
                    <dd><?= (int) $lo['lama']; ?> jam</dd>
                    <dt>Harga/Jam</dt>
                    <dd><?= rupiah($lo['hargaJam']); ?></dd>
                    <dt>Harga Awal</dt>
                    <dd><?= rupiah($lo['hargaAwal']); ?></dd>
                    <dt>Diskon</dt>
                    <dd><?= $lo['diskon'] > 0 ? rupiah($lo['diskon']) : '-'; ?></dd>
                    <dt>Cashback</dt>
                    <dd><?= $lo['cashback'] > 0 ? rupiah((int) $lo['cashback']) : '-'; ?></dd>
                    <dt><strong>Total Bayar</strong></dt>
                    <dd><strong><?= rupiah($lo['total']); ?></strong></dd>
                  </dl>
                </div>
              </div>
            </article>
          <?php endif; ?>
        </section>

        <!-- RIGHT: PREVIEW, RIWAYAT & LIFETIME -->
        <aside class="panel">
          <header style="margin-bottom:6px">
            <h3 style="margin:0">🧾 Preview Realtime</h3>
            <p class="small" style="margin-top:6px">Cek gambar & hitungan sebelum submit.</p>
          </header>

          <section class="preview" style="margin-top:12px; display:flex;gap:18px;align-items:flex-start;">
            <!-- FOTO -->
            <figure style="flex:0 0 320px;margin:0">
              <div class="imgbox"
                style="height:220px;border-radius:12px;overflow:hidden;border:1px solid rgba(255,255,255,.03)">
                <img id="pvImg" src="<?= esc($daftar[0]['gambar']); ?>" alt="preview"
                  style="width:100%;height:100%;object-fit:cover">
              </div>
              <figcaption style="text-align:center;margin-top:10px">
                <span class="chip" id="pvTag">—</span>
              </figcaption>
            </figure>

            <!-- DETAIL -->
            <div style="flex:1">
              <div><span
                  class="chip"><?= (($_SESSION['status'] ?? '') === 'Member' ? '👑 Member' : '🙂 Non-Member'); ?></span>
              </div>
              <h3 id="pvNama" style="margin:8px 0 4px"><?= esc($_SESSION['nama']); ?></h3>
              <p class="small" style="margin-bottom:10px">Ringkasan order saat ini.</p>

              <dl class="dl" style="grid-template-columns:120px 1fr;">
                <dt>Kendaraan</dt>
                <dd id="pvKend">-</dd>
                <dt>Kategori</dt>
                <dd id="pvKat">-</dd>
                <dt>Harga/Jam</dt>
                <dd id="pvHarga">-</dd>
                <dt>Durasi</dt>
                <dd id="pvDur">-</dd>
                <dt>Harga Order</dt>
                <dd id="pvAwal">-</dd>
                <dt id="pvDiskLabel" style="display:none">Diskon</dt>
                <dd id="pvDiskVal" style="display:none">-</dd>
                <dt>Total Bayar</dt>
                <dd id="pvTotal" style="font-size:15px;color:var(--primary)"><strong>-</strong></dd>
                <dt>Cashback</dt>
                <dd id="pvCash">-</dd>
                <dt>Saldo Saat Ini</dt>
                <dd id="pvSaldoNow"><?= rupiah($_SESSION['saldo']); ?></dd>
                <dt>Estimasi Sisa Saldo</dt>
                <dd id="pvSaldoAfter">-</dd>
              </dl>
            </div>
          </section>

          <!-- FILTER RIWAYAT + RINGKASAN -->
          <section style="margin-top:14px">
            <div style="display:flex;gap:10px;align-items:center;justify-content:space-between;flex-wrap:wrap">
              <h4 style="margin:0">📜 Riwayat Transaksi</h4>
              <div style="display:flex;gap:8px;align-items:center">
                <label class="small" for="filter">Filter</label>
                <div class="select-wrap">
                  <select id="filter" style="min-width:140px">
                    <option value="ALL">Semua</option>
                    <option value="SEWA">Sewa</option>
                    <option value="TOPUP">Top-up</option>
                  </select>
                </div>
              </div>
            </div>

            <?php if (empty($_SESSION['riwayat'])): ?>
              <p class="small" style="margin-top:8px">Belum ada transaksi.</p>
            <?php else: ?>
              <table id="tblRiwayat">
                <thead>
                  <tr>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Item</th>
                    <th>Lama</th>
                    <th>Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($_SESSION['riwayat'] as $r): ?>
                    <tr data-tipe="<?= esc($r['tipe'] ?? 'SEWA'); ?>">
                      <td><?= esc($r['tanggal']); ?></td>
                      <td><?= esc($r['tipe'] ?? 'SEWA'); ?></td>
                      <td><?= esc($r['kendaraan']); ?></td>
                      <td><?= (int) $r['lama']; ?><?= $r['lama'] ? ' jam' : ''; ?></td>
                      <td>
                        <?php
                        $val = (int) $r['total'];
                        if (($r['tipe'] ?? '') === 'TOPUP')
                          echo '+' . rupiah(abs($val));
                        else
                          echo rupiah($val);
                        ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>

            <div class="panel" style="margin-top:12px">
              <h4 style="margin:0 0 8px">📈 Ringkasan Lifetime</h4>
              <div class="dl" style="margin-top:4px">
                <dt>Total Top-up</dt>
                <dd><?= rupiah($_SESSION['lifetime']['topup']); ?></dd>
                <dt>Total Sewa (dibayar)</dt>
                <dd><?= rupiah($_SESSION['lifetime']['sewa']); ?></dd>
              </div>
            </div>
          </section>
        </aside>
      </main>
    <?php endif; ?>
  </div>

  <!-- POPUP -->
  <div id="popup">
    <div id="popupBox" class="popup-box">
      <h4 id="popupTitle" style="margin:0 0 8px">Info</h4>
      <p id="popupMsg" style="margin:0">Pesan</p>
      <div style="margin-top:12px;display:flex;justify-content:flex-end">
        <button class="btn small" onclick="closePopup()">Tutup</button>
      </div>
    </div>
  </div>

  <!-- TOPUP MODAL -->
  <div id="modal">
    <div class="modal-card">
      <h4 style="margin:0 0 8px">Top-up Saldo</h4>
      <form method="post" id="modalForm">
        <input type="hidden" name="aksi" value="topup">
        <label>Nominal (min Rp50.000)</label>
        <input type="number" name="nominal" id="modalNom" min="50000" required>
        <div class="small" style="margin-top:6px">
          Untuk Member disarankan top-up awal &gt; Rp5.000.000.
        </div>
        <div style="display:flex;gap:8px;margin-top:12px;justify-content:flex-end">
          <button class="btn" type="submit">Top-up</button>
          <button type="button" class="btn ghost" onclick="closeModal()">Batal</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // ====== DATA & STATE ======
    // DATA: daftar kendaraan (dikirim dari PHP ke JS untuk preview realtime)
    const DATA = <?= json_encode($daftar, JSON_UNESCAPED_UNICODE); ?>;
    // isMember: boolean apakah session status === 'Member'
    const isMember = <?= json_encode((($_SESSION['status'] ?? '') === 'Member')); ?>;
    // saldo: nilai saldo saat ini (dipakai untuk preview)
    let saldo = <?= (int) $_SESSION['saldo']; ?>;

    // ====== ELEMS ====== (ambil elemen DOM yang akan dipakai)
    const saldoHeader = document.getElementById('saldoHeader');
    const sel = document.getElementById('kendSelect');
    const lamaEl = document.getElementById('lama');
    const topupEl = document.getElementById('topup');

    const pvKend = document.getElementById('pvKend');
    const pvKat = document.getElementById('pvKat');
    const pvHarga = document.getElementById('pvHarga');
    const pvDur = document.getElementById('pvDur');
    const pvAwal = document.getElementById('pvAwal');
    const pvDiskLabel = document.getElementById('pvDiskLabel');
    const pvDiskVal = document.getElementById('pvDiskVal');
    const pvTotal = document.getElementById('pvTotal');
    const pvCash = document.getElementById('pvCash');
    const pvSaldoNow = document.getElementById('pvSaldoNow');
    const pvSaldoAfter = document.getElementById('pvSaldoAfter');
    const pvImg = document.getElementById('pvImg');
    const pvTag = document.getElementById('pvTag');

    // ====== UTIL ======
    // fungsi format rupiah di client side (mirip dengan PHP rupiah)
    const rup = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    // ====== PREVIEW ======
    // fungsi updatePreview menghitung semua nilai preview berdasarkan pilihan user
    function updatePreview() {
      // ambil index kendaraan yang dipilih (default 0)
      const idx = parseInt(sel?.value || 0);
      // ambil objek kendaraan dari DATA
      const item = DATA[idx] || DATA[0];
      // ambil lama sewa dari input (min 1)
      const jam = Math.max(1, parseInt(lamaEl?.value || 1));
      // ambil nominal topup sekalian untuk preview (jika user isi)
      const top = Math.max(0, parseInt(topupEl?.value || 0));

      // harga awal (harga per jam * jam)
      const hargaAwal = (item.harga || 0) * jam;
      // diskon & cashback sesuai status member
      const diskon = isMember ? 0.10 * hargaAwal : 0;
      const cashback = isMember ? 0.05 * hargaAwal : 0.02 * hargaAwal;
      const total = hargaAwal - diskon;

      // saldo preview = saldo saat ini + topup sementara
      const saldoPreview = saldo + (top > 0 ? top : 0);
      const sisa = saldoPreview - total;

      // Update elemen preview
      pvKend.textContent = item.nama || '-';
      pvKat.textContent = item.kategori || '-';
      pvHarga.textContent = item.harga > 0 ? rup(item.harga) : '-';
      pvDur.textContent = jam + ' jam';
      pvAwal.textContent = hargaAwal > 0 ? rup(hargaAwal) : '-';

      // Tampilkan diskon jika > 0
      if (diskon > 0) {
        pvDiskLabel.style.display = '';
        pvDiskVal.style.display = '';
        pvDiskVal.textContent = '- ' + rup(diskon);
      } else {
        pvDiskLabel.style.display = 'none';
        pvDiskVal.style.display = 'none';
      }

      pvTotal.textContent = total > 0 ? rup(total) : '-';
      pvCash.textContent = rup(Math.round(cashback));
      pvSaldoNow.textContent = rup(saldoPreview);
      pvSaldoAfter.textContent = (sisa >= 0) ? rup(sisa) : ('- ' + rup(Math.abs(sisa)) + ' (kurang)');
      pvImg.src = item.gambar || '';
      pvTag.textContent = item.nama || '—';
    }

    // Pasang event listener untuk memperbarui preview saat input berubah
    ['input', 'change'].forEach(ev => {
      sel?.addEventListener(ev, updatePreview);
      lamaEl?.addEventListener(ev, updatePreview);
      topupEl?.addEventListener(ev, updatePreview);
    });
    // Jalankan sekali saat load page supaya preview terisi
    updatePreview();

    // ====== POPUP ======
    const popup = document.getElementById('popup');
    const popupBox = document.getElementById('popupBox');
    function showPopup(type, msg) {
      // Tampilkan popup overlay dan isi pesan + ubah warna border sesuai type
      popup.style.display = 'flex';
      document.getElementById('popupMsg').textContent = msg;
      const title = document.getElementById('popupTitle');
      title.textContent = (type === 'success' ? 'Berhasil ✅' : type === 'error' ? 'Gagal ❌' : 'Info ℹ️');
      popupBox.classList.remove('success', 'error');
    }

    function closePopup() {
      popup.style.display = 'none';
    }
    window.closePopup = closePopup;

    // Server-triggered popup (jika PHP menyetel $popupMsg)
    <?php if ($popupMsg): ?>
      showPopup('<?= $popupType; ?>', '<?= esc($popupMsg); ?>');
    <?php endif; ?>

    // ====== MODAL TOPUP ======
    const modal = document.getElementById('modal');
    // Tombol "Top-up Manual" membuka modal
    document.getElementById('openTopup')?.addEventListener('click', () => modal.style.display = 'flex');
    function closeModal() { modal.style.display = 'none'; }
    window.closeModal = closeModal;

    // ====== Saldo count-up ======
    (function () {
      // Animasi count-up saldo: dari saldo_sebelum -> saldo sekarang
      const el = document.getElementById('saldoHeader');
      if (!el) return;
      const target = <?= (int) $_SESSION['saldo']; ?>;
      const start = <?= (int) $saldo_sebelum; ?>;
      if (target === start) return;
      const dur = 700, t0 = performance.now();
      function step(t) {
        const p = Math.min(1, (t - t0) / dur);
        const v = Math.round(start + (target - start) * p);
        el.textContent = rup(v);
        if (p < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    })();

    // ====== Filter Riwayat ======
    const filter = document.getElementById('filter');
    const tbl = document.getElementById('tblRiwayat');
    // Saat user mengubah opsi filter, kita sembunyikan/ tampilkan baris berdasarkan atribut data-tipe
    filter?.addEventListener('change', () => {
      if (!tbl) return;
      const type = filter.value;
      const rows = tbl.querySelectorAll('tbody tr');
      rows.forEach(tr => {
        const t = tr.getAttribute('data-tipe') || 'SEWA';
        tr.style.display = (type === 'ALL' || t === type) ? '' : 'none';
      });
    });

    // ====== header shadow on scroll (toggle class) ======
    window.addEventListener('scroll', function () {
      if (window.scrollY > 10) document.body.classList.add('scrolled');
      else document.body.classList.remove('scrolled');
    });
  </script>
</body>

</html>
