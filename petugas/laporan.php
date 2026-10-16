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

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$tanggal_mulai = $_GET['mulai'] ?? '';
$tanggal_selesai = $_GET['selesai'] ?? '';

$where = "";

if (
    $tanggal_mulai !== '' &&
    $tanggal_selesai !== '' &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_mulai) &&
    preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_selesai) &&
    $tanggal_mulai <= $tanggal_selesai
) {
    $mulai = mysqli_real_escape_string($koneksi, $tanggal_mulai);
    $selesai = mysqli_real_escape_string($koneksi, $tanggal_selesai);

    $where = " WHERE DATE(p.tgl_pinjam) BETWEEN '$mulai' AND '$selesai' ";
} else {
    $tanggal_mulai = '';
    $tanggal_selesai = '';
}

$sql = "
    SELECT
        p.id_peminjaman,
        p.tgl_pinjam,
        p.status,
        pm.username AS nama_peminjam,
        a.nama_alat,
        dp.jumlah,
        pg.tgl_kembali,
        pg.hari_telat,
        pg.denda
    FROM peminjaman p
    LEFT JOIN peminjam pm
        ON p.id_peminjam = pm.id_peminjam
    LEFT JOIN detail_peminjaman dp
        ON p.id_peminjaman = dp.id_peminjaman
    LEFT JOIN alat a
        ON dp.id_alat = a.id_alat
    LEFT JOIN pengembalian pg
        ON p.id_peminjaman = pg.id_peminjaman
    $where
    ORDER BY p.tgl_pinjam DESC
";

$query = mysqli_query($koneksi, $sql);

if (!$query) {
    die("Gagal mengambil laporan: " . e(mysqli_error($koneksi)));
}

$data_laporan = [];

while ($row = mysqli_fetch_assoc($query)) {
    $data_laporan[] = $row;
}

$total_data = count($data_laporan);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laporan - Pinjamin</title>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --ink: #17204A;
            --paper: #F4F6FF;
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
            margin-bottom: 28px;
        }

        .page-title {
            font: 600 2.5rem "Cormorant Garamond", serif;
        }

        .muted {
            color: var(--muted);
        }

        .panel {
            background: white;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 24px;
            margin-bottom: 22px;
            box-shadow: 0 4px 15px rgba(35, 56, 214, .04);
        }

        .btn-blue {
            background: var(--blue);
            color: white;
            border: 1px solid var(--blue);
        }

        .btn-blue:hover {
            background: #1929A8;
            color: white;
        }

        .btn-yellow {
            background: var(--yellow);
            color: var(--ink);
            border: 1px solid var(--yellow);
        }

        .btn-yellow:hover {
            background: #F0B900;
            color: var(--ink);
        }

        .table thead th {
            background: #ECEFFF;
            color: var(--ink);
            white-space: nowrap;
            font-size: .85rem;
        }

        .table tbody td {
            font-size: .88rem;
            vertical-align: middle;
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
                padding: 22px 14px;
            }

            .page-title {
                font-size: 2rem;
            }
        }

        @media print {
            .sidebar,
            .filter-panel,
            .no-print {
                display: none !important;
            }

            body {
                background: white;
            }

            .content {
                padding: 0;
                width: 100%;
            }

            .panel {
                border: none;
                box-shadow: none;
                padding: 0;
            }

            .table thead th {
                background: #eee !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
<div class="container-fluid">
    <div class="row">

        <aside class="col-md-3 col-xl-2 sidebar no-print">
            <a href="index.php" class="brand">Pinjamin<span>.</span></a>

            <p class="small mt-1" style="color:#C9D0FF">
                PETUGAS
            </p>

            <p class="menu-label">MENU UTAMA</p>

            <nav class="menu">
                <a href="index.php">Dashboard</a>
                <a href="peminjaman.php">Data Peminjaman</a>
                <a href="pengembalian.php">Data Pengembalian</a>
                <a href="laporan.php" class="active">Laporan</a>
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
                    <h1 class="page-title mb-0">Laporan</h1>
                    <p class="muted small mt-2 mb-0">
                        Laporan peminjaman dan pengembalian alat.
                    </p>
                </div>

                <button onclick="window.print()" class="btn btn-blue no-print">
                    Cetak Laporan
                </button>
            </div>

            <section class="panel filter-panel">
                <h5 class="fw-bold mb-3">Filter Tanggal Peminjaman</h5>

                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label">Tanggal Mulai</label>
                        <input
                            type="date"
                            name="mulai"
                            class="form-control"
                            value="<?= e($tanggal_mulai) ?>"
                        >
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Tanggal Selesai</label>
                        <input
                            type="date"
                            name="selesai"
                            class="form-control"
                            value="<?= e($tanggal_selesai) ?>"
                        >
                    </div>

                    <div class="col-md-4 d-flex gap-2">
                        <button type="submit" class="btn btn-blue">
                            Filter
                        </button>

                        <a href="laporan.php" class="btn btn-yellow">
                            Reset
                        </a>
                    </div>
                </form>
            </section>

            <section class="panel">
                <div class="mb-3">
                    <h5 class="fw-bold mb-1">Data Laporan</h5>
                    <p class="muted small mb-0">
                        Total baris laporan: <?= $total_data ?>
                    </p>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Peminjam</th>
                                <th>Nama Alat</th>
                                <th>Jumlah</th>
                                <th>Tanggal Pinjam</th>
                                <th>Tanggal Kembali</th>
                                <th>Status</th>
                                <th>Hari Telat</th>
                                <th>Denda</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if ($total_data > 0): ?>
                            <?php foreach ($data_laporan as $index => $data): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= e($data['nama_peminjam'] ?? '-') ?></td>
                                    <td><?= e($data['nama_alat'] ?? '-') ?></td>
                                    <td><?= (int) ($data['jumlah'] ?? 0) ?></td>
                                    <td><?= e($data['tgl_pinjam'] ?? '-') ?></td>
                                    <td><?= e($data['tgl_kembali'] ?? '-') ?></td>
                                    <td><?= e(ucfirst($data['status'] ?? '-')) ?></td>
                                    <td><?= (int) ($data['hari_telat'] ?? 0) ?></td>
                                    <td>
                                        Rp <?= number_format((float) ($data['denda'] ?? 0), 0, ',', '.') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    Belum ada data laporan.
                                </td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

        </main>
    </div>
</div>
</body>
</html>