<?php
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

/* =========================
   INIT SESSION
========================= */
$_SESSION['riwayat'] = $_SESSION['riwayat'] ?? [];
$_SESSION['saldo'] = $_SESSION['saldo'] ?? 0;
$_SESSION['lifetime'] = $_SESSION['lifetime'] ?? ['topup' => 0, 'sewa' => 0];

$saldo_sebelum = $_SESSION['saldo'];

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
    array_unshift($_SESSION['riwayat'], [
      'tanggal' => date("d-m-Y H:i:s"),
      'nama' => $_SESSION['nama'] ?? '-',
      'kendaraan' => 'Top-up Saldo',
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
}

// SEWA
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['aksi'] ?? '') === 'sewa' && isset($_SESSION['nama'])) {
  $idx = (int) ($_POST['kendaraan'] ?? 0);
  $lama = max(1, (int) ($_POST['lama'] ?? 1));
  $topup = max(0, (int) ($_POST['topup'] ?? 0));
  $topup_failed = false;

  // Topup from sewa form
  if ($topup > 0) {
    $nom = $topup;
    $status = $_SESSION['status'] ?? 'NonMember';
    $member_requires_initial = ($status === 'Member' && ($_SESSION['lifetime']['topup'] ?? 0) == 0 && ($_SESSION['saldo'] ?? 0) < 5000000);
    if ($member_requires_initial && $nom < 5000000) {
      $popupMsg = "Top-up gagal — Sebagai Member, top-up awal minimal Rp5.000.000.";
      $popupType = 'error';
      $topup_failed = true;
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
    }
  }

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
  <link href="rentalStyle.css" rel="stylesheet">
</head>

<body>
  <div class="container">
    <header class="header header-sticky"
      style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;">
      <div class="brand">🚗 Rent Garage</div>
      <?php if (isset($_SESSION['nama'])): ?>
        <div style="display:flex;align-items:center;gap:12px;">
          <span class="saldo" id="saldoHeader" style="
          background:linear-gradient(90deg,#00ffbf,#00eaff);
          color:#001;font-weight:700;padding:10px 16px;
          border-radius:14px;box-shadow:0 0 15px rgba(0,255,191,.25)">
            <?= rupiah($_SESSION['saldo']); ?>
          </span>
          <a href="?logout=1" class="btn" style="text-decoration:none;display:inline-block">Keluar</a>
        </div>
      <?php endif; ?>
    </header>

    <?php if (!isset($_SESSION['nama'])): ?>
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
                <input type="text" value="<?= ($_SESSION['status'] === 'Member' ? '👑 Member' : '🙂 Non-Member'); ?>" readonly>
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
                    <dd><?= $lo['cashback'] > 0 ? rupiah(round($lo['cashback'])) : '-'; ?></dd>
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
              <div><span class="chip"><?= ($_SESSION['status'] === 'Member' ? '👑 Member' : '🙂 Non-Member'); ?></span></div>
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
                        if (($r['tipe'] ?? '') === 'TOPUP') {
                          echo '+' . rupiah(abs($val));
                        } else {
                          echo rupiah($val);
                        }
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
    const DATA = <?= json_encode($daftar, JSON_UNESCAPED_UNICODE); ?>;
    const isMember = <?= json_encode((($_SESSION['status'] ?? '') === 'Member')); ?>;
    let saldo = <?= (int) $_SESSION['saldo']; ?>;

    // ====== ELEMS ======
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
    const rup = n => 'Rp ' + Math.round(n).toLocaleString('id-ID');

    // ====== PREVIEW ======
    function updatePreview() {
      const idx = parseInt(sel?.value || 0);
      const item = DATA[idx] || DATA[0];
      const jam = Math.max(1, parseInt(lamaEl?.value || 1));
      const top = Math.max(0, parseInt(topupEl?.value || 0));

      const hargaAwal = (item.harga || 0) * jam;
      const diskon = isMember ? 0.10 * hargaAwal : 0;
      const cashback = isMember ? 0.05 * hargaAwal : 0.02 * hargaAwal;
      const total = hargaAwal - diskon;

      const saldoPreview = saldo + (top > 0 ? top : 0);
      const sisa = saldoPreview - total;

      pvKend.textContent = item.nama || '-';
      pvKat.textContent = item.kategori || '-';
      pvHarga.textContent = item.harga > 0 ? rup(item.harga) : '-';
      pvDur.textContent = jam + ' jam';
      pvAwal.textContent = hargaAwal > 0 ? rup(hargaAwal) : '-';

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

    ['input', 'change'].forEach(ev => {
      sel?.addEventListener(ev, updatePreview);
      lamaEl?.addEventListener(ev, updatePreview);
      topupEl?.addEventListener(ev, updatePreview);
    });
    updatePreview();

    // ====== POPUP ======
    const popup = document.getElementById('popup');
    const popupBox = document.getElementById('popupBox');
    function showPopup(type, msg) {
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

    // Server-triggered popup
    <?php if ($popupMsg): ?>
      showPopup('<?= $popupType; ?>', '<?= esc($popupMsg); ?>');
    <?php endif; ?>

    // ====== MODAL TOPUP ======
    const modal = document.getElementById('modal');
    document.getElementById('openTopup')?.addEventListener('click', () => modal.style.display = 'flex');
    function closeModal() { modal.style.display = 'none'; }
    window.closeModal = closeModal;

    // ====== Saldo count-up ======
    (function () {
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
