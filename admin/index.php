<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_admin'])) {
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

$totalAlat = hitungData($koneksi, "SELECT COUNT(*) AS total FROM alat");
$totalKategori = hitungData($koneksi, "SELECT COUNT(*) AS total FROM kategori");
$totalPeminjaman = hitungData($koneksi, "SELECT COUNT(*) AS total FROM peminjaman");
$totalPengembalian = hitungData($koneksi, "SELECT COUNT(*) AS total FROM peminjaman WHERE status = 'dikembalikan'");
$totalPetugas = hitungData($koneksi, "SELECT COUNT(*) AS total FROM petugas");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - Pinjamin</title>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --ink: #17204A;
            --paper: #F4F6FF;
            --sheet: #FFFFFF;
            --blue: #2338d6;
            --yellow: #ffc933;
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

        .main {
            padding: 35px;
        }

        .page-title {
            font: 600 2.5rem "Cormorant Garamond", serif;
        }

        .muted {
            color: var(--muted);
        }

        .stat-card {
            background: var(--sheet);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 25px;
            height: 100%;
            box-shadow: 0 4px 15px rgba(35, 56, 214, .04);
        }

        .stat-title {
            color: var(--muted);
            font-size: .9rem;
        }

        .stat-number {
            font: 700 2.7rem "Cormorant Garamond", serif;
            color: var(--blue);
            margin: 12px 0 0;
        }

        .stat-mark {
            width: 35px;
            height: 4px;
            background: var(--yellow);
            border-radius: 5px;
            margin-top: 15px;
        }

        .panel {
            background: var(--sheet);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(35, 56, 214, .04);
        }

        .panel h2 {
            font: 600 1.7rem "Cormorant Garamond", serif;
        }

        .quick-link {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--ink);
            text-decoration: none;
            padding: 15px 0;
            border-bottom: 1px solid var(--line);
        }

        .quick-link:last-child {
            border-bottom: 0;
        }

        .quick-link:hover {
            color: var(--blue);
        }

        .quick-link span:last-child {
            color: var(--blue);
            font-size: 1.2rem;
        }

        .topbar {
            border-bottom: 1px solid var(--line);
            padding-bottom: 22px;
            margin-bottom: 30px;
        }

        @media (max-width: 767px) {
            .sidebar {
                min-height: auto;
                padding: 20px;
            }

            .menu-label {
                margin-top: 20px;
            }

            .main {
                padding: 22px 16px;
            }
        }
    </style>
</head>

<body>
<div class="container-fluid">
    <div class="row">

        <aside class="col-md-3 col-xl-2 sidebar">
            <a href="index.php" class="brand">Pinjamin<span>.</span></a>
            <p class="small mt-1" style="color:#C9D0FF">ADMINISTRATION</p>

            <p class="menu-label">MENU UTAMA</p>

            <nav class="menu">
                <a href="index.php" class="active">Dashboard</a>
                <a href="kategori.php">Data Kategori</a>
                <a href="alat.php">Data Alat</a>
                <a href="peminjaman.php">Data Peminjaman</a>
                <a href="pengembalian.php">Data Pengembalian</a>
                <a href="petugas.php">Data Petugas</a>
                <a href="log_aktivitas.php">Log Aktivitas</a>
            </nav>

            <p class="menu-label">AKUN</p>

            <nav class="menu">
                <a href="../logout.php"
                   onclick="return confirm('Yakin ingin keluar?')">
                    Logout
                </a>
            </nav>
        </aside>

        <main class="col-md-9 col-xl-10 main">

            <div class="topbar d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <p class="muted small mb-1">PINJAMIN / ADMIN</p>
                    <h1 class="page-title mb-0">Dashboard</h1>
                </div>

                <div class="text-md-end">
                    <div class="fw-semibold">
                        <?= htmlspecialchars($_SESSION['username'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <small class="muted">Administrator</small>
                </div>
            </div>

            <div class="mb-4">
                <h2 class="h5 fw-semibold">Selamat datang di Pinjamin.</h2>
                <p class="muted mb-0">
                    Pantau inventaris alat dan aktivitas peminjaman sekolah
                    melalui satu halaman.
                </p>
            </div>

            <div class="row g-3 mb-4">

                <div class="col-sm-6 col-xl-4">
                    <div class="stat-card">
                        <div class="stat-title">Total Alat</div>
                        <div class="stat-number"><?= $totalAlat ?></div>
                        <div class="stat-mark"></div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="stat-card">
                        <div class="stat-title">Total Kategori</div>
                        <div class="stat-number"><?= $totalKategori ?></div>
                        <div class="stat-mark"></div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-4">
                    <div class="stat-card">
                        <div class="stat-title">Total Peminjaman</div>
                        <div class="stat-number"><?= $totalPeminjaman ?></div>
                        <div class="stat-mark"></div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-6">
                    <div class="stat-card">
                        <div class="stat-title">Total Pengembalian</div>
                        <div class="stat-number"><?= $totalPengembalian ?></div>
                        <div class="stat-mark"></div>
                    </div>
                </div>

                <div class="col-sm-6 col-xl-6">
                    <div class="stat-card">
                        <div class="stat-title">Total Petugas</div>
                        <div class="stat-number"><?= $totalPetugas ?></div>
                        <div class="stat-mark"></div>
                    </div>
                </div>

            </div>

            <div class="row g-3">

                <div class="col-lg-6">
                    <div class="panel">
                        <h2>Pengelolaan Data</h2>
                        <p class="muted small">
                            Kelola inventaris dan data pendukung sistem.
                        </p>

                        <a href="alat.php" class="quick-link">
                            <span>Data Alat</span>
                            <span>&rarr;</span>
                        </a>

                        <a href="kategori.php" class="quick-link">
                            <span>Data Kategori</span>
                            <span>&rarr;</span>
                        </a>

                        <a href="petugas.php" class="quick-link">
                            <span>Data Petugas</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="panel">
                        <h2>Aktivitas Peminjaman</h2>
                        <p class="muted small">
                            Pantau pengajuan dan proses pengembalian alat.
                        </p>

                        <a href="peminjaman.php" class="quick-link">
                            <span>Data Peminjaman</span>
                            <span>&rarr;</span>
                        </a>

                        <a href="pengembalian.php" class="quick-link">
                            <span>Data Pengembalian</span>
                            <span>&rarr;</span>
                        </a>

                        <a href="log_aktivitas.php" class="quick-link">
                            <span>Log Aktivitas</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>

            </div>

        </main>
    </div>
</div>
</body>
</html>