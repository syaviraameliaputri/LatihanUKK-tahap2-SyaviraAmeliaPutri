<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

function e($teks) {
    return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

$sql = "
    SELECT
        p.id_peminjaman,
        pm.username,
        pm.jenis_peminjam,
        p.tgl_pinjam,
        p.tgl_rencana_kembali,
        p.status,
        GROUP_CONCAT(
            CONCAT(a.nama_alat, ' (', d.jumlah, ')')
            ORDER BY a.nama_alat
            SEPARATOR ', '
        ) AS daftar_alat
    FROM peminjaman p
    JOIN peminjam pm ON p.id_peminjam = pm.id_peminjam
    LEFT JOIN detail_peminjaman d
        ON p.id_peminjaman = d.id_peminjaman
    LEFT JOIN alat a ON d.id_alat = a.id_alat
    GROUP BY
        p.id_peminjaman,
        pm.username,
        pm.jenis_peminjam,
        p.tgl_pinjam,
        p.tgl_rencana_kembali,
        p.status
    ORDER BY p.id_peminjaman DESC
";

$dataPeminjaman = mysqli_query($koneksi, $sql);

if (!$dataPeminjaman) {
    die("Data gagal ditampilkan: " . mysqli_error($koneksi));
}

$totalPeminjaman = mysqli_num_rows($dataPeminjaman);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Peminjaman - Pinjamin</title>

    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        .count {
            display: inline-block;
            background: var(--yellow);
            color: var(--ink);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: .85rem;
            font-weight: 700;
        }

        .panel {
            background: var(--sheet);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(35, 56, 214, .04);
        }

        .panel-title {
            font: 600 1.7rem "Cormorant Garamond", serif;
        }

        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--ink);
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table thead th {
            background: #EDF0FF;
            color: #59628C;
            font-size: .8rem;
            font-weight: 600;
            padding: 14px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 14px;
            border-color: var(--line);
            font-size: .9rem;
        }

        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            background: #FFF1C2;
            color: #775700;
            font-size: .75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .status.dipinjam {
            background: #E2F5E8;
            color: #246B3C;
        }

        .status.ditolak {
            background: #FCE5E5;
            color: #A52626;
        }

        .status.dikembalikan {
            background: #E4E8E8;
            color: #3E5A5D;
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
                <a href="index.php">Dashboard</a>
                <a href="kategori.php">Data Kategori</a>
                <a href="alat.php">Data Alat</a>
                <a href="peminjaman.php" class="active">Data Peminjaman</a>
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
                    <h1 class="page-title mb-0">Data Peminjaman</h1>
                    <p class="muted small mb-0 mt-2">
                        Pantau pengajuan dan status peminjaman alat.
                    </p>
                </div>

                <div class="text-md-end">
                    <div class="fw-semibold">
                        <?= e($_SESSION['username'] ?? 'Admin') ?>
                    </div>
                    <small class="muted">Administrator</small>
                    <div class="mt-2">
                        <span class="count">
                            <?= $totalPeminjaman ?> Data Peminjaman
                        </span>
                    </div>
                </div>
            </div>

            <section class="panel">
                <h2 class="panel-title mb-1">Daftar Peminjaman</h2>
                <p class="muted small mb-4">
                    Daftar pengajuan dari siswa dan guru beserta statusnya.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Peminjam</th>
                                <th>Jenis</th>
                                <th>Daftar Alat (jumlah)</th>
                                <th>Tanggal Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php if ($totalPeminjaman > 0): ?>
                            <?php $no = 1; ?>

                            <?php while ($p = mysqli_fetch_assoc($dataPeminjaman)): ?>
                                <?php
                                $daftarAlat = $p['daftar_alat'] ?? '';

                                if ($daftarAlat === '') {
                                    $stmtLama = mysqli_prepare(
                                        $koneksi,
                                        "SELECT a.nama_alat, p.jumlah
                                         FROM peminjaman p
                                         JOIN alat a ON p.id_alat = a.id_alat
                                         WHERE p.id_peminjaman = ?"
                                    );

                                    mysqli_stmt_bind_param(
                                        $stmtLama,
                                        "i",
                                        $p['id_peminjaman']
                                    );

                                    mysqli_stmt_execute($stmtLama);

                                    $lama = mysqli_fetch_assoc(
                                        mysqli_stmt_get_result($stmtLama)
                                    );

                                    if ($lama) {
                                        $daftarAlat = $lama['nama_alat']
                                            . ' (' . $lama['jumlah'] . ')';
                                    }

                                    mysqli_stmt_close($stmtLama);
                                }

                                $statusTampil = strtolower((string) $p['status']);
                                $statusClass = preg_replace(
                                    '/[^a-z]/',
                                    '',
                                    $statusTampil
                                );
                                ?>

                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td class="fw-semibold">
                                        <?= e($p['username']) ?>
                                    </td>
                                    <td>
                                        <?= e(ucfirst($p['jenis_peminjam'])) ?>
                                    </td>
                                    <td>
                                        <?= e($daftarAlat !== '' ? $daftarAlat : '-') ?>
                                    </td>
                                    <td><?= e($p['tgl_pinjam']) ?></td>
                                    <td><?= e($p['tgl_rencana_kembali']) ?></td>
                                    <td>
                                        <span class="status <?= e($statusClass) ?>">
                                            <?= e(ucfirst($statusTampil)) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>

                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 muted">
                                    Belum ada data peminjaman.
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