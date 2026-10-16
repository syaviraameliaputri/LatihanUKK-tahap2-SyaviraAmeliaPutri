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

$id_peminjam = (int) $_SESSION['id_peminjam'];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* DATA PEMINJAM */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT username, jenis_peminjam, kelas
     FROM peminjam
     WHERE id_peminjam = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $id_peminjam);
mysqli_stmt_execute($stmt);
$peminjam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$peminjam) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

/* STATISTIK PEMINJAMAN */
$stmt = mysqli_prepare(
    $koneksi,
    "SELECT
        COUNT(*) AS total,
        COALESCE(SUM(status = 'menunggu'), 0) AS menunggu,
        COALESCE(SUM(status = 'dipinjam'), 0) AS dipinjam
     FROM peminjaman
     WHERE id_peminjam = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id_peminjam);
mysqli_stmt_execute($stmt);
$statistik = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$total = (int) ($statistik['total'] ?? 0);
$menunggu = (int) ($statistik['menunggu'] ?? 0);
$dipinjam = (int) ($statistik['dipinjam'] ?? 0);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Dashboard Peminjam | Pinjamin</title>

    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700;800&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            box-sizing: border-box;
        }

        :root {
            --cobalt: #2338d6;
            --tape: #ffc933;
            --ink: #131a33;
            --paper: #f7f7fb;
            --white: #ffffff;
            --muted: #777b91;
            --line: #e7e8f0;
            --green: #12805c;
            --red: #c0392b;
        }

        body {
            margin: 0;
            background: var(--paper);
            color: var(--ink);
            font-family: 'Figtree', Arial, sans-serif;
        }

        /* NAVBAR BIRU */
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
            background: var(--cobalt);
            border-bottom: 3px solid var(--tape);
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
            background: var(--tape);
            color: var(--ink);
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
            color: var(--tape);
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
            background: var(--tape);
            color: var(--ink);
        }

        .nav-links .logout {
            color: #ffe0e0;
        }

        .nav-links .user-name {
            background: rgba(255,255,255,.14);
            color: white;
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 25px;
        }

        .container {
            width: 86%;
            max-width: 1150px;
            margin: 35px auto 60px;
        }

        /* HERO */
        .hero {
            position: relative;
            overflow: hidden;
            padding: 38px;
            border-radius: 23px;
            background: var(--cobalt);
            color: white;
        }

        .hero::after {
            content: "";
            position: absolute;
            top: -95px;
            right: -55px;
            width: 250px;
            height: 250px;
            border: 35px solid rgba(255,255,255,.12);
            border-radius: 50%;
            pointer-events: none;
        }

        .eyebrow {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 30px;
            background: rgba(255,255,255,.15);
            color: var(--tape);
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        h1 {
            margin: 20px 0 12px;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: clamp(32px, 4vw, 48px);
            font-weight: 800;
            letter-spacing: -1.5px;
        }

        h1 span {
            color: var(--tape);
        }

        .hero p {
            max-width: 650px;
            margin: 0;
            color: #e5e8ff;
            font-size: 14px;
            line-height: 1.8;
        }

        .account-info {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 22px;
            padding: 10px 14px;
            border: 1px solid rgba(255,255,255,.25);
            border-radius: 10px;
            color: white;
            font-size: 12px;
        }

        .account-info strong {
            color: var(--tape);
        }

        /* STATISTIK */
        .cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 17px;
            margin-top: 22px;
        }

        .card {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 22px;
            border: 1px solid var(--line);
            border-radius: 17px;
            background: white;
        }

        .card-icon {
            width: 47px;
            height: 47px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: #eef0ff;
            color: var(--cobalt);
            font-size: 21px;
            font-weight: 800;
        }

        .card:nth-child(2) .card-icon {
            background: #fff5d1;
            color: #a77900;
        }

        .card:nth-child(3) .card-icon {
            background: #e0f5eb;
            color: var(--green);
        }

        .card-label {
            color: var(--muted);
            font-size: 12px;
            line-height: 1.5;
        }

        .card-number {
            margin-top: 5px;
            color: var(--ink);
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 31px;
            font-weight: 800;
        }

        /* MENU UTAMA */
        .section-title {
            margin: 38px 0 18px;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -.5px;
        }

        .actions {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 17px;
        }

        .action {
            display: flex;
            align-items: flex-start;
            gap: 17px;
            padding: 25px;
            border: 1px solid var(--line);
            border-radius: 17px;
            background: white;
            color: var(--ink);
            text-decoration: none;
            transition: .2s;
        }

        .action:hover {
            transform: translateY(-3px);
            border-color: var(--cobalt);
            box-shadow: 0 10px 25px rgba(35,56,214,.08);
        }

        .action-icon {
            width: 50px;
            height: 50px;
            flex-shrink: 0;
            display: grid;
            place-items: center;
            border-radius: 14px;
            background: var(--cobalt);
            color: white;
            font-size: 22px;
        }

        .action:nth-child(even) .action-icon {
            background: var(--tape);
            color: var(--ink);
        }

        .action strong {
            display: block;
            margin-bottom: 8px;
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 19px;
            font-weight: 800;
        }

        .action span {
            display: block;
            color: var(--muted);
            font-size: 12px;
            line-height: 1.7;
        }

        .footer {
            padding: 22px 15px;
            color: var(--muted);
            font-size: 11px;
            text-align: center;
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

            .container {
                width: 90%;
                margin-top: 23px;
            }

            .hero {
                padding: 26px 22px;
            }

            h1 {
                font-size: 32px;
            }

            .cards,
            .actions {
                grid-template-columns: 1fr;
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
        <a href="index.php" class="active">Dashboard</a>
        <a href="alat.php">Daftar Alat</a>
        <a href="riwayat.php">Riwayat</a>
        <a href="pengembalian.php">Pengembalian</a>
        <a href="../logout.php" class="logout"
           onclick="return confirm('Yakin ingin keluar?')">Logout</a>
        <a href="#" class="user-name"><?= e($peminjam['username']) ?></a>
    </nav>
</header>

<main class="container">

    <section class="hero">
        <span class="eyebrow">DASHBOARD PEMINJAM</span>

        <h1>
            Halo, <span><?= e($peminjam['username']) ?>!</span>
        </h1>

        <p>
            Selamat datang di Pinjamin. Cari alat yang kamu butuhkan,
            ajukan peminjaman, dan pantau status pengajuanmu melalui akun ini.
        </p>

        <div class="account-info">
            Jenis akun:
            <strong><?= e(ucfirst($peminjam['jenis_peminjam'])) ?></strong>

            <?php if ($peminjam['jenis_peminjam'] === 'siswa'): ?>
                <span>|</span>
                Kelas:
                <strong><?= e($peminjam['kelas']) ?></strong>
            <?php endif; ?>
        </div>
    </section>

    <section class="cards">

        <div class="card">
            <div class="card-icon">▤</div>
            <div>
                <div class="card-label">Total Pengajuan</div>
                <div class="card-number"><?= $total ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">◷</div>
            <div>
                <div class="card-label">Menunggu Persetujuan</div>
                <div class="card-number"><?= $menunggu ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-icon">✓</div>
            <div>
                <div class="card-label">Sedang Dipinjam</div>
                <div class="card-number"><?= $dipinjam ?></div>
            </div>
        </div>

    </section>

    <h2 class="section-title">Mau melakukan apa?</h2>

    <section class="actions">

        <a href="alat.php" class="action">
            <div class="action-icon">▦</div>
            <div>
                <strong>Daftar Alat</strong>
                <span>
                    Lihat alat yang tersedia, pilih jumlahnya,
                    lalu ajukan peminjaman.
                </span>
            </div>
        </a>

        <a href="riwayat.php" class="action">
            <div class="action-icon">↻</div>
            <div>
                <strong>Riwayat Peminjaman</strong>
                <span>
                    Pantau status pengajuan dan alat yang sedang dipinjam.
                </span>
            </div>
        </a>

        <a href="pengembalian.php" class="action">
            <div class="action-icon">✓</div>
            <div>
                <strong>Pengembalian</strong>
                <span>
                    Lihat informasi pengembalian alat dan status
                    peminjaman yang perlu dikembalikan.
                </span>
            </div>
        </a>

    </section>

</main>

<footer class="footer">
    PINJAMIN — Sarana &amp; Prasarana Sekolah
</footer>

</body>
</html>