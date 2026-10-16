<?php
session_start();
include "../koneksi.php";

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

$query = mysqli_query($koneksi, "
    SELECT
        l.id_log,
        l.aktivitas,
        l.waktu,
        a.username AS admin_username,
        p.username AS petugas_username,
        pm.username AS peminjam_username
    FROM log_aktivitas l
    LEFT JOIN admin a ON l.id_admin = a.id_admin
    LEFT JOIN petugas p ON l.id_petugas = p.id_petugas
    LEFT JOIN peminjam pm ON l.id_peminjam = pm.id_peminjam
    ORDER BY l.id_log DESC
");

if (!$query) {
    die("Query error: " . mysqli_error($koneksi));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas | Pinjamin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

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
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .page-description {
            color: var(--muted);
            margin-bottom: 28px;
        }

        .panel {
            background: var(--sheet);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 24px;
            box-shadow: 0 5px 20px rgba(35, 56, 214, .04);
        }

        .table {
            margin-bottom: 0;
            color: var(--ink);
        }

        .table thead th {
            background: #EEF1FF;
            color: var(--ink);
            border-color: var(--line);
            font-size: .88rem;
            font-weight: 700;
            white-space: nowrap;
            padding: 14px;
        }

        .table td {
            border-color: var(--line);
            padding: 13px 14px;
            vertical-align: middle;
            font-size: .9rem;
        }

        .table tbody tr:hover td {
            background: #FAFBFF;
        }

        .user-label {
            display: inline-block;
            background: #EEF1FF;
            color: var(--blue);
            padding: 5px 10px;
            border-radius: 6px;
            font-size: .82rem;
            font-weight: 600;
        }

        .empty {
            color: var(--muted);
            padding: 30px !important;
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

            .panel {
                padding: 14px;
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
                <a href="peminjaman.php">Data Peminjaman</a>
                <a href="pengembalian.php">Data Pengembalian</a>
                <a href="petugas.php">Data Petugas</a>
                <a href="log_aktivitas.php" class="active">Log Aktivitas</a>
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

            <h3 class="page-title">Log Aktivitas</h3>
            <p class="page-description">
                Riwayat aktivitas admin, petugas, dan peminjam.
            </p>

            <div class="panel">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Riwayat Aktivitas</h5>
                    <span class="user-label">
                        <?= mysqli_num_rows($query); ?> aktivitas
                    </span>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Pengguna</th>
                                <th>Aktivitas</th>
                                <th>Waktu</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (mysqli_num_rows($query) > 0): ?>
                                <?php $no = 1; ?>

                                <?php while ($data = mysqli_fetch_assoc($query)): ?>
                                    <?php
                                    $pengguna = "-";

                                    if ($data['admin_username'] !== null) {
                                        $pengguna = "Admin: " . $data['admin_username'];
                                    } elseif ($data['petugas_username'] !== null) {
                                        $pengguna = "Petugas: " . $data['petugas_username'];
                                    } elseif ($data['peminjam_username'] !== null) {
                                        $pengguna = "Peminjam: " . $data['peminjam_username'];
                                    }
                                    ?>

                                    <tr>
                                        <td><?= $no++; ?></td>
                                        <td>
                                            <span class="user-label">
                                                <?= htmlspecialchars($pengguna); ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($data['aktivitas']); ?></td>
                                        <td><?= htmlspecialchars($data['waktu']); ?></td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center empty">
                                        Belum ada aktivitas tercatat.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
</div>

</body>
</html>