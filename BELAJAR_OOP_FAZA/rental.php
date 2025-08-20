<?php
// rental.php
session_start();
date_default_timezone_set('Asia/Jakarta');

/* -----------------------------
   DATA KENDARAAN (SAMA DENGAN ASLI)
---------------------------------- */
$daftar = [
    ["nama" => "Pilih Kendaraan",        "harga" => 0,      "gambar" => "NOMADIC - CAFE - john zaki.jpg", "kategori" => ""],
    ["nama" => "Toyota Alphard",         "harga" => 250000,"gambar" => "alphard.jpg", "kategori" => "Mobil - Eksklusif"],
    ["nama" => "Mazda 3 Hatchback",      "harga" => 120000,"gambar" => "mazda.jpg",   "kategori" => "Mobil - City Car"],
    ["nama" => "BMW M3",                 "harga" => 300000,"gambar" => "bmw.jpg",     "kategori" => "Mobil - Sport Car"],
    ["nama" => "Honda CBR250RR",         "harga" => 90000, "gambar" => "cbr.jpg",     "kategori" => "Motor - Sport 250cc"],
    ["nama" => "Sepeda Gunung United",   "harga" => 35000, "gambar" => "sepeda.jpg",  "kategori" => "Sepeda - Gunung"],
];

/* -----------------------------
   HELPERS
---------------------------------- */
function esc($s){ return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function rupiah($n){ return 'Rp '.number_format((int)$n,0,',','.'); }

/* -----------------------------
   INIT SESSION STATE
---------------------------------- */
if (!isset($_SESSION['riwayat'])) $_SESSION['riwayat'] = [];
if (!isset($_SESSION['saldo']))   $_SESSION['saldo']   = 0;
$saldo_sebelum = $_SESSION['saldo'];

/* -----------------------------
   PROSES FORM: LOGIN / TOPUP / SEWA
---------------------------------- */
$popupMsg = null;
$popupType = 'info'; // success | error | info

// LOGIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi']==='login') {
    $nama = trim($_POST['nama'] ?? '');
    $status = ($_POST['status'] ?? 'NonMember') === 'Member' ? 'Member' : 'NonMember';
    if ($nama === '') {
        $popupMsg = "Nama tidak boleh kosong.";
        $popupType = 'error';
    } else {
        $_SESSION['nama'] = $nama;
        $_SESSION['status'] = $status;
        // keep saldo & riwayat if existing
        $popupMsg = "Login berhasil! Selamat datang, {$nama}.";
        $popupType = 'success';
    }
}

// TOP-UP (via modal atau form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi']==='topup') {
    $nom = (int)($_POST['nominal'] ?? 0);
    if ($nom < 50000) {
        $popupMsg = "Top-up gagal — minimal Rp50.000.";
        $popupType = 'error';
    } else {
        $_SESSION['saldo'] += $nom;
        $popupMsg = "Top-up berhasil: +".rupiah($nom)." (Saldo sekarang: ".rupiah($_SESSION['saldo']).")";
        $popupType = 'success';
    }
}

// SEWA (form utama)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi']) && $_POST['aksi']==='sewa' && isset($_SESSION['nama'])) {
    $idx = (int)($_POST['kendaraan'] ?? 0);
    $lama = max(1, (int)($_POST['lama'] ?? 1));
    $topup = max(0, (int)($_POST['topup'] ?? 0));

    // apply topup if provided (must be >= 50k)
    if ($topup > 0) {
        if ($topup < 50000) {
            $popupMsg = "Top-up gagal — minimal Rp50.000.";
            $popupType = 'error';
        } else {
            $_SESSION['saldo'] += $topup;
            $popupMsg = "Top-up berhasil +".rupiah($topup).".";
            $popupType = 'success';
        }
    }

    $kend = $daftar[$idx] ?? $daftar[0];
    if ($kend['harga'] <= 0) {
        $popupMsg = "Pilih kendaraan yang valid.";
        $popupType = 'error';
    } else {
        $hargaAwal = $kend['harga'] * $lama;
        // aturan: member diskon 10%, cashback 5% dari hargaAwal
        // non-member cashback 2%, no discount
        $status = $_SESSION['status'] ?? 'NonMember';
        $diskon = ($status === 'Member') ? 0.10 * $hargaAwal : 0;
        $cashback = ($status === 'Member') ? 0.05 * $hargaAwal : 0.02 * $hargaAwal;
        $total = $hargaAwal - $diskon;

        // coba bayar
        if ($_SESSION['saldo'] >= $total) {
            // potong pembayaran
            $_SESSION['saldo'] -= $total;
            // tambahkan cashback
            $_SESSION['saldo'] += round($cashback);
            // simpan riwayat
            $ent = [
                'tanggal'  => date("d-m-Y H:i:s"),
                'nama'     => $_SESSION['nama'],
                'kendaraan'=> $kend['nama'],
                'lama'     => $lama,
                'hargaJam' => $kend['harga'],
                'hargaAwal'=> $hargaAwal,
                'diskon'   => $diskon,
                'cashback' => $cashback,
                'total'    => $total,
                'gambar'   => $kend['gambar'],
                'status'   => $status
            ];
            array_unshift($_SESSION['riwayat'], $ent);
            $_SESSION['last_order'] = $ent;
            $popupMsg = "Sewa berhasil! Total ".rupiah($total).". Cashback ".rupiah(round($cashback))." telah diperoleh.";
            $popupType = 'success';
        } else {
            $popupMsg = "Saldo tidak cukup. Total ".rupiah($total).". Silakan top-up.";
            $popupType = 'error';
        }
    }
}

// LOGOUT
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
<style>
:root{
  --bg1:#0e0f12; --bg2:#0a0b0d;
  --panel:rgba(255,255,255,.04);
  --primary:#00eaff; --primary2:#00ffbf; --text:#eef6fa;
  --muted:#9fb0bf; --danger:#ff6b6b; --success:#2ee6a6;
  --select-bg: rgba(255,255,255,0.03);
}
*{box-sizing:border-box}
body{
  margin:0; font-family:'Poppins',sans-serif; color:var(--text);
  background:
    radial-gradient(1200px 600px at 15% -10%, rgba(0,234,255,.04), transparent 60%),
    radial-gradient(1000px 500px at 90% 10%, rgba(0,255,191,.03), transparent 60%),
    linear-gradient(135deg,var(--bg1),var(--bg2));
  min-height:100vh; padding:28px;
}
.container{max-width:1150px;margin:0 auto}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
.logo{color:var(--primary); font-weight:700; font-size:20px}
.badge{color:#000;background:linear-gradient(90deg,var(--primary2),var(--primary));padding:8px 12px;border-radius:10px;font-weight:700}

/* panels */
.panel{
  background:var(--panel); border-radius:14px; padding:18px;
  border:1px solid rgba(255,255,255,.06); box-shadow:0 12px 40px rgba(2,6,23,0.6);
}

/* login */
.login-wrap{display:flex;align-items:center;justify-content:center;min-height:78vh}
.login-card{width:380px;border-radius:14px;padding:22px;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));border:1px solid rgba(255,255,255,0.04)}
.login-card h2{margin-bottom:6px;color:var(--primary)}
.muted{color:var(--muted);font-size:14px}

/* form */
label{display:block;margin:10px 0 6px;font-weight:600;color:var(--muted)}
input[type="text"], input[type="number"], select{
  width:100%; padding:12px 14px; border-radius:12px; border:none; outline:none;
  background:var(--select-bg); color:var(--text); font-size:14px;
  transition: box-shadow .12s ease, transform .06s;
  -webkit-appearance:none; -moz-appearance:none; appearance:none;
}
input:focus, select:focus{box-shadow:0 10px 30px rgba(0,234,255,.04)}

/* layout */
.cols{display:grid;grid-template-columns:1.1fr .9fr;gap:18px}
@media(max-width:980px){ .cols{grid-template-columns:1fr} }

/* main controls */
.grid{display:grid;gap:14px}
.grid-2{grid-template-columns:1fr 1fr;gap:12px}

/* select appearance */
.select-wrap{position:relative}
.select-wrap:after{content:"▾";position:absolute;right:14px;top:50%;transform:translateY(-50%);color:var(--primary);pointer-events:none}

/* buttons */
.btn{
  border:none;padding:12px 14px;border-radius:12px;cursor:pointer;font-weight:700;background:linear-gradient(90deg,var(--primary2),var(--primary));color:#000;
}
.btn.ghost{background:transparent;border:1px solid rgba(255,255,255,.06);color:var(--text)}

/* saldo */
.saldo{display:inline-block;padding:10px 14px;border-radius:12px;background:linear-gradient(90deg, rgba(0,234,255,.06), rgba(0,255,191,.03));color:#001;font-weight:800}

/* preview card */
.order-card{display:grid;grid-template-columns:1fr .9fr;gap:16px;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01));border-radius:14px;padding:14px;border:1px solid rgba(255,255,255,.03)}
@media(max-width:920px){ .order-card{grid-template-columns:1fr} }

.order-left .chip{display:inline-block;padding:6px 10px;border-radius:999px;background:rgba(0,234,255,.09);color:var(--primary)}
.order-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}
.label{color:var(--muted);font-size:13px}
.value{font-weight:700}
.total{font-size:18px;color:#fff;text-shadow:0 0 10px rgba(0,234,255,.24)}

/* IMAGE area: make image big & visible */
.order-right{border-radius:12px;overflow:hidden;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(255,255,255,.02);padding:8px}
.order-right .imgbox{width:100%;height:280px;overflow:hidden;border-radius:10px}
.order-right img{width:100%;height:100%;object-fit:cover;display:block;}

/* detail order (last order) */
.order-detail{display:flex;gap:16px;align-items:flex-start;margin-top:12px;border-radius:12px;padding:12px;background:linear-gradient(180deg, rgba(255,255,255,0.02), transparent);border:1px solid rgba(255,255,255,0.03)}
.order-detail .imgbox-lg{width:45%;min-width:220px;height:220px;overflow:hidden;border-radius:12px}
.order-detail img{width:100%;height:100%;object-fit:cover}

/* table */
table{width:100%;border-collapse:collapse;margin-top:8px}
th,td{padding:10px;border-bottom:1px solid rgba(255,255,255,0.03);font-size:13px;text-align:left}
th{color:var(--primary)}

/* popup */
.popup{position:fixed;inset:0;display:none;align-items:center;justify-content:center;z-index:9999;backdrop-filter: blur(6px)}
.popup .box{min-width:320px;max-width:420px;background:#0c1116;border-radius:12px;padding:18px;border:1px solid rgba(255,255,255,.05)}
.box.success{border-color:rgba(46,230,166,.25)}
.box.error{border-color:rgba(255,107,107,.25)}

/* small */
.muted-small{color:var(--muted);font-size:13px}
</style>
</head>
<body>
<div class="container">
  <div class="header">
    <div class="logo">🚗 Rent Garage</div>
    <div>
      <?php if(isset($_SESSION['nama'])): ?>
        <span class="saldo" id="saldoHeader"><?php echo rupiah($_SESSION['saldo']); ?></span>
        <a href="?logout=1" class="btn" style="margin-left:12px">Keluar</a>
      <?php endif; ?>
    </div>
  </div>

  <?php if(!isset($_SESSION['nama'])): ?>
    <!-- LOGIN -->
    <div class="login-wrap">
      <div class="login-card panel">
        <h2>🔐 Masuk untuk Sewa</h2>
        <p class="muted">Isi nama & pilih status (Member / Non-Member).</p>
        <form method="post" style="margin-top:12px">
          <input type="hidden" name="aksi" value="login">
          <label>Nama</label>
          <input type="text" name="nama" placeholder="Nama Anda" required>

          <label>Status Pelanggan</label>
          <div class="select-wrap">
            <select name="status" required>
              <option value="Member">Member</option>
              <option value="NonMember">Non-Member</option>
            </select>
          </div>

          <div style="margin-top:12px"><button class="btn" type="submit">Masuk</button></div>
        </form>
      </div>
    </div>

  <?php else: ?>
    <!-- MAIN -->
    <div class="cols">
      <!-- LEFT: form -->
      <section class="panel">
        <h3 style="margin:0 0 6px">🔧 Formulir Sewa</h3>
        <p class="muted" style="margin-top:-6px">Nama diisi otomatis sesuai login (tidak dapat diubah).</p>

        <form method="post" id="form-sewa" class="grid" style="margin-top:12px">
          <input type="hidden" name="aksi" value="sewa">

          <div>
            <label>Nama Pemesan</label>
            <input type="text" name="nama_display" value="<?php echo esc($_SESSION['nama']); ?>" readonly>
          </div>

          <div>
            <label>Status</label>
            <input type="text" value="<?php echo ($_SESSION['status']==='Member'?'👑 Member':'🙂 Non-Member'); ?>" readonly>
          </div>

          <div>
            <label>Pilih Kendaraan</label>
            <div class="select-wrap">
              <select name="kendaraan" id="kendSelect" required>
                <?php foreach($daftar as $i=>$k): ?>
                  <option value="<?php echo $i; ?>"><?php echo esc($k['nama']); ?> — Rp <?php echo number_format($k['harga'],0,',','.'); ?>/jam</option>
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
              <small class="muted-small"><?php echo ($_SESSION['status']==='Member' ? 'Member dapat top-up bebas (min 50.000)' : 'Non-member top-up minimal 50.000'); ?></small>
            </div>
          </div>

          <div style="display:flex;gap:10px;">
            <button class="btn" type="submit">✅ Proses Sewa</button>
            <button type="button" id="openTopup" class="btn ghost">Top-up Manual</button>
          </div>
        </form>

        <!-- LAST ORDER -->
        <?php if(!empty($_SESSION['last_order'])):
            $lo = $_SESSION['last_order'];
        ?>
          <div style="margin-top:16px">
            <h4 style="margin:6px 0">📦 Pesanan Terakhir</h4>
            <div class="order-detail">
              <div class="imgbox-lg"><img src="<?php echo esc($lo['gambar']); ?>" alt=""></div>
              <div style="flex:1">
                <h3 style="margin:0 0 6px"><?php echo esc($lo['kendaraan']); ?></h3>
                <div class="muted-small">Pemesan: <?php echo esc($lo['nama']); ?> • <?php echo esc($lo['status']); ?></div>
                <div style="height:10px"></div>
                <div class="row" style="display:flex;flex-direction:column;gap:8px;padding:0">
                  <div style="display:flex;justify-content:space-between"><div class="label">Durasi</div><div class="value"><?php echo intval($lo['lama']); ?> jam</div></div>
                  <div style="display:flex;justify-content:space-between"><div class="label">Harga/Jam</div><div class="value"><?php echo rupiah($lo['hargaJam']); ?></div></div>
                  <div style="display:flex;justify-content:space-between"><div class="label">Harga Awal</div><div class="value"><?php echo rupiah($lo['hargaAwal']); ?></div></div>
                  <div style="display:flex;justify-content:space-between"><div class="label">Diskon</div><div class="value"><?php echo $lo['diskon']>0?rupiah($lo['diskon']):'-'; ?></div></div>
                  <div style="display:flex;justify-content:space-between"><div class="label">Cashback</div><div class="value"><?php echo $lo['cashback']>0?rupiah(round($lo['cashback'])):'-'; ?></div></div>
                  <div style="height:8px"></div>
                  <div class="total-row"><div>Total Bayar</div><div><?php echo rupiah($lo['total']); ?></div></div>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>

      </section>

      <!-- RIGHT: preview & riwayat -->
      <aside class="panel">
        <h3 style="margin:0 0 6px">🧾 Preview Realtime</h3>
        <p class="muted" style="margin-top:-6px">Periksa gambar & perhitungan sebelum submit.</p>

        <div class="order-card" style="margin-top:12px">
          <div class="order-left">
            <div class="order-title">
              <span class="chip"><?php echo ($_SESSION['status']==='Member'?'👑 Member':'🙂 Non-Member'); ?></span>
              <h3 id="pvNama" style="margin:8px 0 4px"><?php echo esc($_SESSION['nama']); ?></h3>
              <p class="muted">Preview perhitungan sebelum submit</p>
            </div>

            <div class="order-grid" style="margin-top:12px">
              <div><div class="label">Kendaraan</div><div class="value" id="pvKend">-</div></div>
              <div><div class="label">Kategori</div><div class="value" id="pvKat">-</div></div>
              <div><div class="label">Harga/Jam</div><div class="value" id="pvHarga">-</div></div>
              <div><div class="label">Durasi</div><div class="value" id="pvDur">-</div></div>
              <div><div class="label">Harga Order</div><div class="value" id="pvAwal">-</div></div>
              <div id="pvDiskRow" style="display:none"><div class="label">Diskon</div><div class="value" id="pvDisk">-</div></div>
              <div><div class="label">Total Bayar</div><div class="value total" id="pvTotal">-</div></div>
              <div><div class="label">Cashback</div><div class="value" id="pvCash">-</div></div>
              <div><div class="label">Saldo Saat Ini</div><div class="value" id="pvSaldoNow"><?php echo rupiah($_SESSION['saldo']); ?></div></div>
              <div><div class="label">Estimasi Sisa Saldo</div><div class="value" id="pvSaldoAfter">-</div></div>
            </div>
          </div>

          <div class="order-right">
            <div class="imgbox"><img id="pvImg" src="<?php echo esc($daftar[0]['gambar']); ?>" alt="preview"></div>
            <div style="padding-top:10px"><div class="chip" id="pvTag">—</div></div>
          </div>
        </div>

        <!-- RIWAYAT -->
        <div style="margin-top:14px">
          <h4 style="margin:0 0 8px">📜 Riwayat Transaksi</h4>
          <?php if(empty($_SESSION['riwayat'])): ?>
            <div class="muted-small">Belum ada transaksi.</div>
          <?php else: ?>
            <table>
              <thead><tr><th>Tanggal</th><th>Kendaraan</th><th>Lama</th><th>Total</th></tr></thead>
              <tbody>
                <?php foreach($_SESSION['riwayat'] as $r): ?>
                  <tr>
                    <td><?php echo esc($r['tanggal']); ?></td>
                    <td><?php echo esc($r['kendaraan']); ?></td>
                    <td><?php echo intval($r['lama']); ?> jam</td>
                    <td><?php echo rupiah($r['total']); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>

      </aside>
    </div>
  <?php endif; ?>
</div>

<!-- POPUP overlay -->
<div id="popup" class="popup">
  <div class="box" id="popupBox">
    <h4 id="popupTitle">Info</h4>
    <p id="popupMsg">Pesan</p>
    <div style="margin-top:12px"><button class="btn" onclick="closePopup()">Tutup</button></div>
  </div>
</div>

<!-- TOPUP modal -->
<div id="modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.5);align-items:center;justify-content:center;z-index:999">
  <div style="background:#0b1116;padding:16px;border-radius:12px;border:1px solid rgba(255,255,255,0.04);width:320px">
    <h4 style="margin:0 0 8px">Top-up Saldo</h4>
    <form method="post" id="modalForm">
      <input type="hidden" name="aksi" value="topup">
      <label style="color:var(--muted);font-weight:600">Nominal (min Rp50.000)</label>
      <input type="number" name="nominal" id="modalNom" min="50000" required style="margin-top:8px;padding:10px;border-radius:10px;border:none;background:var(--select-bg);color:var(--text);width:100%">
      <div style="display:flex;gap:8px;margin-top:12px">
        <button class="btn" type="submit">Top-up</button>
        <button type="button" class="btn ghost" onclick="closeModal()">Batal</button>
      </div>
    </form>
  </div>
</div>

<script>
// DATA kendaraan dari server
const DATA = <?php echo json_encode($daftar, JSON_UNESCAPED_UNICODE); ?>;
const isMember = <?php echo json_encode( (isset($_SESSION['status']) && $_SESSION['status']==='Member') ); ?>;
let saldo = <?php echo (int)$_SESSION['saldo']; ?>;
const saldoHeader = document.getElementById('saldoHeader');

// elemen
const sel = document.getElementById('kendSelect');
const lamaEl = document.getElementById('lama');
const topupEl = document.getElementById('topup');

const pvKend = document.getElementById('pvKend');
const pvKat = document.getElementById('pvKat');
const pvHarga = document.getElementById('pvHarga');
const pvDur = document.getElementById('pvDur');
const pvAwal = document.getElementById('pvAwal');
const pvDiskRow = document.getElementById('pvDiskRow');
const pvDisk = document.getElementById('pvDisk');
const pvTotal = document.getElementById('pvTotal');
const pvCash = document.getElementById('pvCash');
const pvSaldoNow = document.getElementById('pvSaldoNow');
const pvSaldoAfter = document.getElementById('pvSaldoAfter');
const pvImg = document.getElementById('pvImg');
const pvTag = document.getElementById('pvTag');

// format Rp
const rup = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

// update preview
function updatePreview(){
  const idx = parseInt(sel?.value || 0);
  const item = DATA[idx] || DATA[0];
  const jam = Math.max(1, parseInt(lamaEl?.value || 1));
  const top = Math.max(0, parseInt(topupEl?.value || 0));

  const hargaAwal = item.harga * jam;
  const diskon = isMember ? 0.10 * hargaAwal : 0;
  const cashback = isMember ? 0.05 * hargaAwal : 0.02 * hargaAwal;
  const total = hargaAwal - diskon;

  let saldoPreview = saldo + (top > 0 ? top : 0);
  let sisa = saldoPreview - total;

  pvKend.textContent = item.nama;
  pvKat.textContent = item.kategori || '-';
  pvHarga.textContent = item.harga > 0 ? rup(item.harga) : '-';
  pvDur.textContent = jam + ' jam';
  pvAwal.textContent = hargaAwal>0 ? rup(hargaAwal) : '-';
  if (diskon > 0){
    pvDiskRow.style.display = '';
    pvDisk.textContent = '- ' + rup(diskon);
  } else {
    pvDiskRow.style.display = 'none';
  }
  pvTotal.textContent = total>0 ? rup(total) : '-';
  pvCash.textContent = rup(Math.round(cashback));
  pvSaldoNow.textContent = rup(saldoPreview);
  pvSaldoAfter.textContent = (sisa >= 0) ? rup(sisa) : ('- ' + rup(Math.abs(sisa)) + ' (kurang)');
  pvImg.src = item.gambar || '';
  pvTag.textContent = item.nama || '—';
}

// event listeners
['change','input'].forEach(ev=>{
  sel?.addEventListener(ev, updatePreview);
  lamaEl?.addEventListener(ev, updatePreview);
  topupEl?.addEventListener(ev, updatePreview);
});
updatePreview();

// popup
const popup = document.getElementById('popup');
const popupBox = document.getElementById('popupBox');
function showPopup(type, msg){
  popup.style.display = 'flex';
  const title = document.getElementById('popupTitle');
  const p = document.getElementById('popupMsg');
  p.textContent = msg;
  title.textContent = (type==='success'?'Berhasil ✅': (type==='error'?'Gagal ❌':'Info ℹ️'));
  popupBox.classList.remove('success','error');
  if(type==='success') popupBox.classList.add('success');
  if(type==='error') popupBox.classList.add('error');
}
function closePopup(){ popup.style.display='none'; }

// show server popup if any
<?php if($popupMsg): ?>
  showPopup('<?php echo $popupType; ?>', '<?php echo esc($popupMsg); ?>');
<?php endif; ?>

// topup modal
const modal = document.getElementById('modal');
document.getElementById('openTopup')?.addEventListener('click', ()=> modal.style.display='flex');
function closeModal(){ modal.style.display='none'; }

// Saldo count-up animation
(function(){
  const el = document.getElementById('saldoHeader');
  if(!el) return;
  const target = <?php echo (int)$_SESSION['saldo']; ?>;
  const start = <?php echo (int)$saldo_sebelum; ?>;
  if (target === start) return;
  const dur=700, t0 = performance.now();
  function step(t){
    const p = Math.min(1, (t - t0)/dur);
    const v = Math.round(start + (target - start)*p);
    el.textContent = rup(v);
    if(p<1) requestAnimationFrame(step);
  }
  requestAnimationFrame(step);
})();
</script>
</body>
</html>
