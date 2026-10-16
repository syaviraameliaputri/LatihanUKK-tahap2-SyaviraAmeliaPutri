```php
<?php
session_start();
include '../koneksi.php';

if (
    !isset($_SESSION['login']) ||
    $_SESSION['role'] != 'peminjam' ||
    !isset($_SESSION['id_peminjam'])
) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

$pilihan = $_GET['alat'] ?? [];

if (!is_array($pilihan) || empty($pilihan)) {
    header("Location: alat.php");
    exit;
}

$alat_dipilih = [];
$total_alat = 0;

foreach ($pilihan as $id => $jumlah) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $jumlah = filter_var($jumlah, FILTER_VALIDATE_INT);

    if (!$id || !$jumlah || $jumlah < 1) {
        continue;
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT id_alat, nama_alat, stok, kondisi
         FROM alat
         WHERE id_alat = ? AND kondisi = 'baik' AND stok >= ?"
    );

    mysqli_stmt_bind_param($stmt, "ii", $id, $jumlah);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);

    if (!$data) {
        continue;
    }

    $data['jumlah_pinjam'] = $jumlah;
    $alat_dipilih[] = $data;
    $total_alat += $jumlah;
}

if (empty($alat_dipilih)) {
    echo "<script>
        alert('Pilihan alat tidak valid atau stok tidak mencukupi.');
        window.location='alat.php';
    </script>";
    exit;
}

$nama_peminjam = $_SESSION['username'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ajukan Peminjaman | Pinjamin</title>

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --biru: #2338d6;
    --kuning: #ffc933;
    --latar: #f5f6ff;
    --teks: #202344;
    --abu: #777b94;
}

body {
    font-family: Arial, Helvetica, sans-serif;
    background: var(--latar);
    color: var(--teks);
}

header {
    background: var(--biru);
    color: white;
    padding: 18px 6%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
    flex-wrap: wrap;
}

.logo {
    font-size: 27px;
    font-weight: 900;
    letter-spacing: -1px;
}

.logo span {
    color: var(--kuning);
}

nav {
    display: flex;
    gap: 22px;
    flex-wrap: wrap;
    align-items: center;
}

nav a {
    color: white;
    text-decoration: none;
    font-size: 14px;
    font-weight: bold;
}

nav a:hover,
nav a.aktif {
    color: var(--kuning);
}

.logout {
    background: var(--kuning);
    color: var(--biru) !important;
    padding: 11px 17px;
    border-radius: 10px;
}

.container {
    max-width: 850px;
    margin: 35px auto;
    padding: 0 20px;
}

.judul {
    margin-bottom: 25px;
}

.judul small {
    color: var(--biru);
    font-weight: bold;
    letter-spacing: 2px;
    text-transform: uppercase;
}

.judul h1 {
    font-size: 32px;
    margin: 10px 0;
}

.judul p {
    color: var(--abu);
    line-height: 1.6;
}

.panel {
    background: white;
    border: 1px solid #e8e9f5;
    border-radius: 18px;
    padding: 25px;
    margin-bottom: 20px;
}

.panel h2 {
    font-size: 20px;
    margin-bottom: 18px;
}

.info {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 13px 0;
    border-bottom: 1px solid #eeeef6;
}

.info:last-child {
    border-bottom: none;
}

.label {
    color: var(--abu);
}

.daftar-alat {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.item {
    display: flex;
    justify-content: space-between;
    gap: 15px;
    padding: 15px;
    border-radius: 12px;
    background: #f5f6ff;
}

.item h3 {
    font-size: 16px;
    margin-bottom: 6px;
}

.item small {
    color: var(--abu);
}

.jumlah {
    color: var(--biru);
    font-weight: bold;
    white-space: nowrap;
}

label {
    display: block;
    font-weight: bold;
    font-size: 14px;
    margin-bottom: 9px;
}

input[type="date"] {
    width: 100%;
    padding: 13px;
    border: 1px solid #dedff0;
    border-radius: 10px;
    font-size: 15px;
    outline: none;
}

input[type="date"]:focus {
    border-color: var(--biru);
}

.catatan {
    background: #fff5d5;
    color: #6c5100;
    padding: 14px;
    border-radius: 11px;
    line-height: 1.6;
    font-size: 13px;
    margin-top: 17px;
}

.tombol {
    display: flex;
    gap: 12px;
    margin-top: 22px;
}

.btn {
    flex: 1;
    text-align: center;
    text-decoration: none;
    border: none;
    border-radius: 10px;
    padding: 14px 18px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
}

.btn-kembali {
    background: #eef0ff;
    color: var(--biru);
}

.btn-kirim {
    background: var(--kuning);
    color: var(--biru);
}

.btn:hover {
    filter: brightness(.97);
}

@media (max-width: 600px) {
    header {
        padding: 18px 20px;
    }

    nav {
        gap: 13px;
    }

    .container {
        margin-top: 25px;
        padding: 0 14px;
    }

    .panel {
        padding: 20px;
    }

    .judul h1 {
        font-size: 27px;
    }

    .tombol {
        flex-direction: column;
    }
}
</style>
</head>

<body>

<header>
    <div class="logo">Pinja<span>min.</span></div>

    <nav>
        <a href="index.php">Dashboard</a>
        <a href="alat.php" class="aktif">Daftar Alat</a>
        <a href="riwayat.php">Riwayat</a>
        <a href="pengembalian.php">Pengembalian</a>
        <a href="../logout.php" class="logout">Keluar</a>
    </nav>
</header>

<main class="container">

    <section class="judul">
        <small>Pengajuan Baru</small>
        <h1>Ajukan Peminjaman</h1>
        <p>Periksa alat yang kamu pilih dan tentukan tanggal rencana pengembalian.</p>
    </section>

    <section class="panel">
        <h2>Informasi Peminjam</h2>

        <div class="info">
            <span class="label">Nama peminjam</span>
            <strong><?= e($nama_peminjam) ?></strong>
        </div>

        <div class="info">
            <span class="label">Jenis peminjam</span>
            <strong><?= e(ucfirst($_SESSION['jenis_peminjam'] ?? '-')) ?></strong>
        </div>
    </section>

    <section class="panel">
        <h2>Alat yang Dipilih</h2>

        <div class="daftar-alat">
            <?php foreach ($alat_dipilih as $alat) : ?>
                <div class="item">
                    <div>
                        <h3><?= e($alat['nama_alat']) ?></h3>
                        <small>Stok tersedia: <?= (int) $alat['stok'] ?></small>
                    </div>

                    <div class="jumlah">
                        <?= (int) $alat['jumlah_pinjam'] ?> unit
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="info" style="margin-top:15px">
            <span class="label">Total unit dipilih</span>
            <strong><?= $total_alat ?> unit</strong>
        </div>
    </section>

    <form action="proses_peminjaman.php" method="POST">
        <?php foreach ($alat_dipilih as $alat) : ?>
            <input
                type="hidden"
                name="alat[<?= (int) $alat['id_alat'] ?>]"
                value="<?= (int) $alat['jumlah_pinjam'] ?>"
            >
        <?php endforeach; ?>

        <section class="panel">
            <h2>Jadwal Peminjaman</h2>

            <label for="tgl_rencana_kembali">
                Tanggal Rencana Pengembalian
            </label>

            <input
                type="date"
                id="tgl_rencana_kembali"
                name="tgl_rencana_kembali"
                min="<?= date('Y-m-d', strtotime('+1 day')) ?>"
                required
            >

            <div class="catatan">
                Pengajuan akan berstatus <strong>Menunggu</strong> sampai diperiksa
                oleh Petugas. Alat belum resmi dipinjam sebelum disetujui.
            </div>

            <div class="tombol">
                <a href="alat.php" class="btn btn-kembali">Kembali</a>
                <button type="submit" class="btn btn-kirim">
                    Kirim Pengajuan →
                </button>
            </div>
        </section>
    </form>

</main>

</body>
</html>