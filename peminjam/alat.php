<?php
session_start();
include '../koneksi.php';

if (
    !isset($_SESSION['login']) ||
    ($_SESSION['role'] ?? '') !== 'peminjam' ||
    !isset($_SESSION['id_peminjam'])
) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id_peminjam = (int) $_SESSION['id_peminjam'];

$stmtUser = mysqli_prepare(
    $koneksi,
    "SELECT username FROM peminjam WHERE id_peminjam = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmtUser, "i", $id_peminjam);
mysqli_stmt_execute($stmtUser);
$peminjam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUser));

if (!$peminjam) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$nama_peminjam = $peminjam['username'];
$keyword = trim($_GET['cari'] ?? '');
$kategori = $_GET['kategori'] ?? '';

$sql = "SELECT a.id_alat, a.nama_alat, a.stok, a.kondisi,
               k.nama_kategori
        FROM alat a
        LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
        WHERE a.kondisi = 'baik'";

$types = '';
$params = [];

if ($keyword !== '') {
    $sql .= " AND a.nama_alat LIKE ?";
    $types .= 's';
    $params[] = "%$keyword%";
}

if ($kategori !== '') {
    $sql .= " AND a.id_kategori = ?";
    $types .= 'i';
    $params[] = (int) $kategori;
}

$sql .= " ORDER BY a.nama_alat ASC";

$stmt = mysqli_prepare($koneksi, $sql);

if ($types !== '') {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$jumlah_alat = mysqli_num_rows($hasil);

$daftar_kategori = mysqli_query(
    $koneksi,
    "SELECT * FROM kategori ORDER BY nama_kategori ASC"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Alat - Pinjamin</title>

    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700;800&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --biru: #2338d6;
            --kuning: #ffc933;
            --teks: #131a33;
            --latar: #f7f7fb;
            --putih: #ffffff;
            --abu: #777b91;
            --garis: #e7e8f0;
            --hijau: #12805c;
            --merah: #c0392b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', Arial, sans-serif;
            background: var(--latar);
            color: var(--teks);
        }

        /* NAVBAR */

        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            min-height: 76px;
            padding: 15px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            background: var(--biru);
            border-bottom: 3px solid var(--kuning);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 11px;
            color: white;
            text-decoration: none;
        }

        .logo-mark {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: var(--kuning);
            color: var(--teks);
            font-size: 23px;
            font-weight: 800;
        }

        .logo-name {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .logo-name span {
            color: var(--kuning);
        }

        .logo small {
            display: block;
            margin-top: 3px;
            color: #dce2ff;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #e5e9ff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            padding: 10px 12px;
            border-radius: 8px;
            transition: .2s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: var(--kuning);
            color: var(--teks);
        }

        .nav-links .logout {
            color: #ffe0e0;
        }

        .nav-links .user-name {
            background: rgba(255, 255, 255, .14);
            color: white;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 25px;
        }

        /* KONTEN */

        .container {
            width: 86%;
            max-width: 1200px;
            margin: 35px auto 110px;
        }

        .hero {
            padding: 35px;
            background: var(--biru);
            color: white;
            border-radius: 20px;
            margin-bottom: 25px;
        }

        .hero h1 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .hero p {
            color: #e5e9ff;
            line-height: 1.6;
        }

        .filter-box {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 25px;
        }

        .filter-box input,
        .filter-box select {
            min-width: 180px;
            flex: 1;
            padding: 13px 15px;
            border: 1px solid var(--garis);
            border-radius: 10px;
            background: white;
            font: inherit;
            outline: none;
        }

        .filter-box input:focus,
        .filter-box select:focus {
            border-color: var(--biru);
        }

        .btn {
            border: none;
            border-radius: 10px;
            padding: 13px 20px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            background: var(--biru);
            color: white;
        }

        .btn-primary:hover {
            background: #182bb8;
        }

        .section-title {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
        }

        .section-title h2 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 23px;
        }

        .count {
            color: var(--abu);
            font-size: 13px;
        }

        .alat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 20px;
        }

        .alat-card {
            background: white;
            border: 1px solid var(--garis);
            border-radius: 16px;
            padding: 22px;
            transition: .2s;
        }

        .alat-card:hover {
            border-color: var(--biru);
            transform: translateY(-3px);
        }

        .alat-icon {
            width: 52px;
            height: 52px;
            display: grid;
            place-items: center;
            background: #eef0ff;
            border-radius: 14px;
            font-size: 25px;
            margin-bottom: 17px;
        }

        .kategori-label {
            display: inline-block;
            background: #fff5d6;
            color: #7b5a00;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .alat-card h3 {
            font-size: 18px;
            margin-bottom: 9px;
        }

        .stok-info {
            color: var(--abu);
            font-size: 13px;
            margin-bottom: 20px;
        }

        .jumlah-control {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding-top: 15px;
            border-top: 1px solid var(--garis);
        }

        .jumlah-control span {
            font-size: 13px;
            font-weight: 700;
        }

        .jumlah-input {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .jumlah-input button {
            width: 32px;
            height: 32px;
            border: 1px solid var(--garis);
            border-radius: 8px;
            background: white;
            font-size: 18px;
            cursor: pointer;
        }

        .jumlah-input button:hover {
            background: #eef0ff;
        }

        .jumlah-input input {
            width: 30px;
            border: none;
            outline: none;
            background: transparent;
            text-align: center;
            font: inherit;
            font-weight: 800;
        }

        .empty {
            grid-column: 1 / -1;
            padding: 50px 20px;
            text-align: center;
            background: white;
            border: 1px solid var(--garis);
            border-radius: 16px;
            color: var(--abu);
        }

        /* BAR PILIHAN */

        .bottom-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 90;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 16px 7%;
            background: white;
            border-top: 1px solid var(--garis);
            box-shadow: 0 -5px 20px rgba(19, 26, 51, .06);
        }

        .bottom-info strong {
            display: block;
            font-size: 16px;
        }

        .bottom-info span {
            color: var(--abu);
            font-size: 12px;
        }

        .bottom-bar .btn:disabled {
            background: #c8c9d4;
            cursor: not-allowed;
        }

        @media (max-width: 900px) {
            .alat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .container {
                width: 90%;
            }
        }

        @media (max-width: 700px) {
            .navbar {
                padding: 18px 6%;
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-links {
                gap: 5px;
            }

            .nav-links a {
                padding: 9px;
                font-size: 12px;
            }

            .hero {
                padding: 25px;
            }

            .hero h1 {
                font-size: 26px;
            }

            .alat-grid {
                grid-template-columns: 1fr;
            }

            .filter-box {
                flex-direction: column;
            }

            .filter-box input,
            .filter-box select {
                width: 100%;
            }

            .bottom-bar {
                padding: 14px 5%;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="logo">
        <div class="logo-mark">P</div>
        <div>
            <div class="logo-name">Pinja<span>min</span></div>
            <small>SARANA &amp; PRASARANA</small>
        </div>
    </a>

    <nav class="nav-links">
        <a href="index.php">Dashboard</a>
        <a href="alat.php" class="active">Daftar Alat</a>
        <a href="riwayat.php">Riwayat</a>
        <a href="pengembalian.php">Pengembalian</a>
        <a href="../logout.php" class="logout"
           onclick="return confirm('Yakin ingin keluar?')">Logout</a>
        <a href="#" class="user-name"><?= e($peminjam['username']) ?></a>
    </nav>
</header>

<main class="container">

    <section class="hero">
        <h1>Temukan alat yang kamu butuhkan.</h1>
        <p>Pilih alat yang tersedia, tentukan jumlahnya, lalu lanjutkan pengajuan peminjaman.</p>
    </section>

    <form action="alat.php" method="GET" class="filter-box">
        <input
            type="text"
            name="cari"
            placeholder="Cari nama alat..."
            value="<?= e($keyword) ?>"
        >

        <select name="kategori">
            <option value="">Semua Kategori</option>

            <?php while ($kat = mysqli_fetch_assoc($daftar_kategori)): ?>
                <option
                    value="<?= (int) $kat['id_kategori'] ?>"
                    <?= ((string) $kategori === (string) $kat['id_kategori']) ? 'selected' : '' ?>
                >
                    <?= e($kat['nama_kategori']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button type="submit" class="btn btn-primary">Cari Alat</button>
        <a href="alat.php" class="btn" style="background:#e7e8f0;color:#131a33;">Reset</a>
    </form>

    <div class="section-title">
        <h2>Daftar Alat</h2>
        <span class="count"><?= $jumlah_alat ?> alat ditemukan</span>
    </div>

    <form action="form_peminjaman.php" method="GET" id="formAlat">
        <div class="alat-grid">

            <?php if ($jumlah_alat > 0): ?>
                <?php while ($alat = mysqli_fetch_assoc($hasil)): ?>
                    <article class="alat-card">
                        <div class="alat-icon">📦</div>

                        <span class="kategori-label">
                            <?= e($alat['nama_kategori'] ?? 'Tanpa Kategori') ?>
                        </span>

                        <h3><?= e($alat['nama_alat']) ?></h3>

                        <p class="stok-info">
                            Stok tersedia: <strong><?= (int) $alat['stok'] ?></strong>
                        </p>

                        <div class="jumlah-control">
                            <span>Jumlah pinjam</span>

                            <div class="jumlah-input">
                                <button
                                    type="button"
                                    onclick="ubahJumlah(<?= (int) $alat['id_alat'] ?>, -1)"
                                >−</button>

                                <input
                                    type="number"
                                    id="jumlah_<?= (int) $alat['id_alat'] ?>"
                                    name="alat[<?= (int) $alat['id_alat'] ?>]"
                                    value="0"
                                    min="0"
                                    max="<?= (int) $alat['stok'] ?>"
                                    data-stok="<?= (int) $alat['stok'] ?>"
                                    data-nama="<?= e($alat['nama_alat']) ?>"
                                    onchange="validasiJumlah(this); hitungTotal()"
                                >

                                <button
                                    type="button"
                                    onclick="ubahJumlah(<?= (int) $alat['id_alat'] ?>, 1)"
                                >+</button>
                            </div>
                        </div>
                    </article>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty">
                    <h3>Alat tidak ditemukan</h3>
                    <p>Coba gunakan kata kunci atau kategori lainnya.</p>
                </div>
            <?php endif; ?>

        </div>
    </form>
</main>

<div class="bottom-bar">
    <div class="bottom-info">
        <strong><span id="totalItem">0</span> alat dipilih</strong>
        <span>Pilih alat sebelum melanjutkan.</span>
    </div>

    <button
        type="button"
        class="btn btn-primary"
        id="btnLanjut"
        onclick="lanjutPeminjaman()"
        disabled
    >
        Lanjutkan →
    </button>
</div>

<script>
function ubahJumlah(id, perubahan) {
    const input = document.getElementById('jumlah_' + id);
    const stok = parseInt(input.dataset.stok) || 0;
    let jumlah = parseInt(input.value) || 0;

    jumlah += perubahan;

    if (jumlah < 0) jumlah = 0;
    if (jumlah > stok) jumlah = stok;

    input.value = jumlah;
    hitungTotal();
}

function validasiJumlah(input) {
    const stok = parseInt(input.dataset.stok) || 0;
    let jumlah = parseInt(input.value) || 0;

    if (jumlah < 0) jumlah = 0;
    if (jumlah > stok) jumlah = stok;

    input.value = jumlah;
}

function hitungTotal() {
    let total = 0;

    document.querySelectorAll('.jumlah-input input').forEach(function(input) {
        total += parseInt(input.value) || 0;
    });

    document.getElementById('totalItem').textContent = total;
    document.getElementById('btnLanjut').disabled = total === 0;
}

function lanjutPeminjaman() {
    let total = 0;

    document.querySelectorAll('.jumlah-input input').forEach(function(input) {
        total += parseInt(input.value) || 0;
    });

    if (total === 0) {
        alert('Pilih minimal satu alat terlebih dahulu.');
        return;
    }

    document.getElementById('formAlat').submit();
}

hitungTotal();
</script>

</body>
</html>