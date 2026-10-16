<?php
session_start();
include "../koneksi.php";

if (
    !isset($_SESSION['id_petugas']) ||
    ($_SESSION['role'] ?? '') !== 'petugas'
) {
    header("Location: ../login.php");
    exit;
}

function hitungData($koneksi, $sql) {
    $query = mysqli_query($koneksi, $sql);

    if ($query) {
        $data = mysqli_fetch_assoc($query);
        return (int) ($data['total'] ?? 0);
    }

    return 0;
}

$menunggu = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM peminjaman
     WHERE status = 'menunggu'"
);

$total_alat = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total FROM alat"
);

$dipinjam = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM peminjaman
     WHERE status = 'dipinjam'"
);

$total_kembali = hitungData(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM peminjaman
     WHERE status = 'dikembalikan'"
);

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Petugas - Pinjamin</title>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --ink: #17204A;
            --paper: #F4F6FF;
            --sheet: #FFFFFF;
            --blue: #2338D6;
            --yellow: #FFC933;
            --muted: #737B98;
            --line: #E2E6F5;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--paper);
            color: var(--ink);
            font-family: "Figtree", sans-serif;
        }

        .sidebar {
            background: var(--blue);
            color: white;
            min-height: 100vh;
            padding: 28px 20px;
        }

        .brand {
            font: 700 2rem "Cormorant Garamond", serif;
            color: white;
            text-decoration: none;
        }

        .brand span {
            color: var(--yellow);
        }

        .menu-label {
            color: #C9D0FF;
            font-size: .72rem;
            letter-spacing: .14em;
            margin: 35px 0 12px;
        }

        .menu a {
            display: block;
            color: #E5E9FF;
            text-decoration: none;
            padding: 11px 13px;
            margin-bottom: 5px;
            border-radius: 8px;
            font-size: .92rem;
            transition: .2s;
        }

        .menu a:hover,
        .menu a.active {
            background: var(--yellow);
            color: var(--ink);
        }

        .content {
            padding: 35px;
        }

        .topbar {
            border-bottom: 1px solid var(--line);
            padding-bottom: 22px;
            margin-bottom: 30px;
        }

        .page-title {
            font: 600 2.5rem "Cormorant Garamond", serif;
        }

        .muted {
            color: var(--muted);
        }

        .welcome {
            background: var(--blue);
            color: white;
            border-radius: 14px;
            padding: 28px;
            margin-bottom: 28px;
        }

        .welcome p {
            color: #E0E5FF;
            margin-bottom: 0;
        }

        .stat-card {
            background: var(--sheet);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 24px;
            height: 100%;
            box-shadow: 0 4px 15px rgba(35, 56, 214, .04);
        }

        .stat-label {
            color: var(--muted);
            font-size: .85rem;
            margin-bottom: 12px;
        }

        .stat-number {
            color: var(--blue);
            font: 700 2.5rem "Cormorant Garamond", serif;
        }

        .section-card {
            background: var(--sheet);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 25px;
            margin-top: 25px;
            box-shadow: 0 4px 15px rgba(35, 56, 214, .04);
        }

        .section-title {
            font: 600 1.7rem "Cormorant Garamond", serif;
        }

        .btn-blue {
            display: inline-block;
            background: var(--blue);
            color: white;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid var(--blue);
            font-size: .9rem;
        }

        .btn-blue:hover {
            background: #1929A8;
            color: white;
        }

        .btn-yellow {
            display: inline-block;
            background: var(--yellow);
            color: var(--ink);
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 8px;
            border: 1px solid var(--yellow);
            font-size: .9rem;
        }

        .btn-yellow:hover {
            background: #F0B900;
            color: var(--ink);
        }

        @media (max-width: 767px) {
            .sidebar {
                min-height: auto;
                padding: 20px;
            }

            .menu-label {
                margin-top: 20px;
            }

            .content {
                padding: 22px 16px;
            }

            .page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
<div class="container-fluid">
    <div class="row">

        <aside class="col-md-3 col-xl-2 sidebar">
            <a href="index.php" class="brand">Pinjamin<span>.</span></a>

            <p class="small mt-1" style="color:#C9D0FF">
                PETUGAS
            </p>

            <p class="menu-label">MENU UTAMA</p>

            <nav class="menu">
                <a href="index.php" class="active">Dashboard</a>
<a href="peminjaman.php">Data Peminjaman</a>
<a href="pengembalian.php">Data Pengembalian</a>
<a href="laporan.php">Laporan</a>
            </nav>

            <p class="menu-label">AKUN</p>

            <nav class="menu">
                <a href="../logout.php"
                   onclick="return confirm('Yakin ingin keluar?')">
                    Logout
                </a>
            </nav>
        </aside>

        <main class="col-md-9 col-xl-10 content">

            <div class="topbar d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <p class="muted small mb-1">PINJAMIN / PETUGAS</p>
                    <h1 class="page-title mb-0">Dashboard</h1>
                    <p class="muted small mb-0 mt-2">
                        Ringkasan peminjaman dan pengembalian alat.
                    </p>
                </div>

                <div class="text-md-end">
                    <div class="fw-semibold">
                        <?= e($_SESSION['username'] ?? 'Petugas') ?>
                    </div>
                    <small class="muted">Petugas</small>
                </div>
            </div>

            <section class="welcome">
                <h3 class="fw-bold mb-2">
                    Selamat Datang, <?= e($_SESSION['username'] ?? 'Petugas') ?>!
                </h3>
                <p>
                    Kelola pengajuan peminjaman dan pantau pengembalian alat.
                </p>
            </section>

            <h5 class="fw-bold mb-3">Ringkasan Data</h5>

            <div class="row g-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-label">Menunggu Persetujuan</div>
                        <div class="stat-number"><?= $menunggu ?></div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-label">Total Jenis Alat</div>
                        <div class="stat-number"><?= $total_alat ?></div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-label">Sedang Dipinjam</div>
                        <div class="stat-number"><?= $dipinjam ?></div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-3">
                    <div class="stat-card">
                        <div class="stat-label">Sudah Dikembalikan</div>
                        <div class="stat-number"><?= $total_kembali ?></div>
                    </div>
                </div>
            </div>

            <section class="section-card">
                <h2 class="section-title mb-2">Kelola Peminjaman</h2>

                <p class="muted mb-4">
                    Periksa pengajuan peminjaman dan pantau pengembalian alat.
                </p>

                <a href="peminjaman.php" class="btn-blue">
                    Lihat Peminjaman
                </a>

                <a href="pengembalian.php" class="btn-yellow ms-2">
                    Lihat Pengembalian
                </a>

                <a href="laporan.php" class="btn-blue ms-2">
    Lihat Laporan
</a>
            </section>

        </main>
    </div>
</div>
</body>
</html>