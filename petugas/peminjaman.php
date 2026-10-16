<?php
session_start();
include '../koneksi.php';

if (
    !isset($_SESSION['id_petugas']) ||
    ($_SESSION['role'] ?? '') !== 'petugas'
) {
    header("Location: ../login.php");
    exit;
}

$id_petugas = (int) $_SESSION['id_petugas'];

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/* PROSES SETUJUI / TOLAK */
if (isset($_GET['aksi'], $_GET['id'])) {
    $id = (int) $_GET['id'];
    $aksi = $_GET['aksi'];

    if ($id > 0 && in_array($aksi, ['setujui', 'tolak'], true)) {
        mysqli_begin_transaction($koneksi);

        try {
            $stmt = mysqli_prepare(
                $koneksi,
                "SELECT id_peminjaman, status
                 FROM peminjaman
                 WHERE id_peminjaman = ?
                 FOR UPDATE"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);
            mysqli_stmt_execute($stmt);
            $pinjam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if (!$pinjam || $pinjam['status'] !== 'menunggu') {
                throw new Exception("Pengajuan sudah diproses.");
            }

            if ($aksi === 'setujui') {
                $stmt = mysqli_prepare(
                    $koneksi,
                    "SELECT id_alat, jumlah
                     FROM detail_peminjaman
                     WHERE id_peminjaman = ?
                     FOR UPDATE"
                );

                mysqli_stmt_bind_param($stmt, "i", $id);
                mysqli_stmt_execute($stmt);
                $hasil = mysqli_stmt_get_result($stmt);
                $daftarAlat = [];

                while ($item = mysqli_fetch_assoc($hasil)) {
                    $daftarAlat[] = $item;
                }

                mysqli_stmt_close($stmt);

                // Mendukung data peminjaman lama.
                if (count($daftarAlat) === 0) {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "SELECT id_alat, jumlah
                         FROM peminjaman
                         WHERE id_peminjaman = ?"
                    );

                    mysqli_stmt_bind_param($stmt, "i", $id);
                    mysqli_stmt_execute($stmt);
                    $itemLama = mysqli_fetch_assoc(
                        mysqli_stmt_get_result($stmt)
                    );
                    mysqli_stmt_close($stmt);

                    if ($itemLama) {
                        $daftarAlat[] = $itemLama;
                    }
                }

                if (count($daftarAlat) === 0) {
                    throw new Exception("Daftar alat kosong.");
                }

                // Periksa stok dan kondisi seluruh alat.
                foreach ($daftarAlat as $item) {
                    $idAlat = (int) $item['id_alat'];
                    $jumlah = (int) $item['jumlah'];

                    if ($idAlat < 1 || $jumlah < 1) {
                        throw new Exception("Data alat tidak valid.");
                    }

                    $stmt = mysqli_prepare(
                        $koneksi,
                        "SELECT stok, kondisi
                         FROM alat
                         WHERE id_alat = ?
                         FOR UPDATE"
                    );

                    mysqli_stmt_bind_param($stmt, "i", $idAlat);
                    mysqli_stmt_execute($stmt);
                    $alat = mysqli_fetch_assoc(
                        mysqli_stmt_get_result($stmt)
                    );
                    mysqli_stmt_close($stmt);

                    if (
                        !$alat ||
                        $alat['kondisi'] !== 'baik' ||
                        (int) $alat['stok'] < $jumlah
                    ) {
                        throw new Exception(
                            "Stok tidak cukup atau kondisi alat rusak."
                        );
                    }
                }

                // Kurangi stok setiap alat.
                foreach ($daftarAlat as $item) {
                    $idAlat = (int) $item['id_alat'];
                    $jumlah = (int) $item['jumlah'];

                    $stmt = mysqli_prepare(
                        $koneksi,
                        "UPDATE alat
                         SET stok = stok - ?
                         WHERE id_alat = ? AND stok >= ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "iii",
                        $jumlah,
                        $idAlat,
                        $jumlah
                    );

                    mysqli_stmt_execute($stmt);

                    if (mysqli_stmt_affected_rows($stmt) !== 1) {
                        mysqli_stmt_close($stmt);
                        throw new Exception("Gagal mengurangi stok.");
                    }

                    mysqli_stmt_close($stmt);
                }
            }

            $status = ($aksi === 'setujui') ? 'dipinjam' : 'ditolak';

            $stmt = mysqli_prepare(
                $koneksi,
                "UPDATE peminjaman
                 SET status = ?, id_petugas = ?
                 WHERE id_peminjaman = ?
                   AND status = 'menunggu'"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sii",
                $status,
                $id_petugas,
                $id
            );

            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                mysqli_stmt_close($stmt);
                throw new Exception("Gagal memperbarui status.");
            }

            mysqli_stmt_close($stmt);
            mysqli_commit($koneksi);

            header("Location: peminjaman.php?pesan=berhasil");
            exit;

        } catch (Throwable $e) {
            mysqli_rollback($koneksi);
            error_log("Proses peminjaman: " . $e->getMessage());

            header("Location: peminjaman.php?pesan=gagal");
            exit;
        }
    }
}

/* AMBIL DATA PEMINJAMAN */
$query = mysqli_query(
    $koneksi,
    "SELECT
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
     JOIN peminjam pm
        ON p.id_peminjam = pm.id_peminjam
     LEFT JOIN detail_peminjaman d
        ON p.id_peminjaman = d.id_peminjaman
     LEFT JOIN alat a
        ON d.id_alat = a.id_alat
     GROUP BY
        p.id_peminjaman,
        pm.username,
        pm.jenis_peminjam,
        p.tgl_pinjam,
        p.tgl_rencana_kembali,
        p.status
     ORDER BY p.id_peminjaman DESC"
);

if (!$query) {
    die("Query gagal: " . mysqli_error($koneksi));
}

$jumlahPengajuan = mysqli_num_rows($query);
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
            margin-bottom: 28px;
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
            padding: 6px 10px;
            border-radius: 20px;
            background: #FFF0C2;
            color: #785600;
            font-size: .75rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .status.dipinjam {
            background: #DCE5FF;
            color: #2338A0;
        }

        .status.ditolak {
            background: #FCE5E5;
            color: #A52626;
        }

        .status.dikembalikan {
            background: #E2F5E8;
            color: #246B3C;
        }

        .btn-setujui,
        .btn-tolak {
            display: inline-block;
            padding: 7px 10px;
            border-radius: 7px;
            text-decoration: none;
            font-size: .8rem;
            font-weight: 600;
            white-space: nowrap;
            border: 0;
        }

        .btn-setujui {
            background: var(--blue);
            color: white;
        }

        .btn-setujui:hover {
            background: #1929A8;
            color: white;
        }

        .btn-tolak {
            background: #FCE5E5;
            color: #A52626;
        }

        .btn-tolak:hover {
            background: #A52626;
            color: white;
        }

        .pesan {
            border-radius: 9px;
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
            <p class="small mt-1" style="color:#C9D0FF">PETUGAS</p>

            <p class="menu-label">MENU UTAMA</p>

            <nav class="menu">
                <a href="index.php">Dashboard</a>
                <a href="peminjaman.php" class="active">Data Peminjaman</a>
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
                    <h1 class="page-title mb-0">Data Peminjaman</h1>
                    <p class="muted small mb-0 mt-2">
                        Kelola pengajuan dan persetujuan peminjaman alat.
                    </p>
                </div>

                <div class="text-md-end">
                    <div class="fw-semibold">
                        <?= e($_SESSION['username'] ?? 'Petugas') ?>
                    </div>
                    <small class="muted">Petugas</small>
                    <div class="mt-2">
                        <span class="count">
                            <?= $jumlahPengajuan ?> Pengajuan
                        </span>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['pesan'])): ?>
                <?php if ($_GET['pesan'] === 'berhasil'): ?>
                    <div class="alert alert-success pesan">
                        Pengajuan berhasil diproses.
                    </div>
                <?php elseif ($_GET['pesan'] === 'gagal'): ?>
                    <div class="alert alert-danger pesan">
                        Pengajuan gagal diproses. Periksa stok, kondisi alat,
                        dan status pengajuan.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <section class="panel">
                <h2 class="panel-title mb-1">Daftar Pengajuan</h2>
                <p class="muted small mb-4">
                    Setujui pengajuan jika alat tersedia dan dalam kondisi baik.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Peminjam</th>
                                <th>Jenis</th>
                                <th>Daftar Alat</th>
                                <th>Tgl. Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($jumlahPengajuan > 0): ?>
                                <?php $no = 1; ?>

                                <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>

                                        <td class="fw-semibold">
                                            <?= e($row['username']) ?>
                                        </td>

                                        <td>
                                            <?= e(ucfirst($row['jenis_peminjam'])) ?>
                                        </td>

                                        <td>
                                            <?php
                                            if (!empty($row['daftar_alat'])) {
                                                echo e($row['daftar_alat']);
                                            } else {
                                                $stmtLama = mysqli_prepare(
                                                    $koneksi,
                                                    "SELECT a.nama_alat, p.jumlah
                                                     FROM peminjaman p
                                                     JOIN alat a
                                                       ON p.id_alat = a.id_alat
                                                     WHERE p.id_peminjaman = ?"
                                                );

                                                mysqli_stmt_bind_param(
                                                    $stmtLama,
                                                    "i",
                                                    $row['id_peminjaman']
                                                );

                                                mysqli_stmt_execute($stmtLama);

                                                $lama = mysqli_fetch_assoc(
                                                    mysqli_stmt_get_result($stmtLama)
                                                );

                                                if ($lama) {
                                                    echo e($lama['nama_alat'])
                                                        . ' (' . (int) $lama['jumlah'] . ')';
                                                } else {
                                                    echo '-';
                                                }

                                                mysqli_stmt_close($stmtLama);
                                            }
                                            ?>
                                        </td>

                                        <td><?= e($row['tgl_pinjam']) ?></td>

                                        <td><?= e($row['tgl_rencana_kembali']) ?></td>

                                        <td>
                                            <span class="status <?= e($row['status']) ?>">
                                                <?= e(ucfirst($row['status'])) ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if ($row['status'] === 'menunggu'): ?>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <a
                                                        href="peminjaman.php?aksi=setujui&id=<?= (int) $row['id_peminjaman'] ?>"
                                                        class="btn-setujui"
                                                        onclick="return confirm('Setujui pengajuan ini dan kurangi stok alat?')">
                                                        Setujui
                                                    </a>

                                                    <a
                                                        href="peminjaman.php?aksi=tolak&id=<?= (int) $row['id_peminjaman'] ?>"
                                                        class="btn-tolak"
                                                        onclick="return confirm('Tolak pengajuan ini?')">
                                                        Tolak
                                                    </a>

                                                    <a href="laporan.php" class="btn-blue ms-2">
    Lihat Laporan
</a>
                                                </div>
                                            <?php else: ?>
                                                <span class="muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 muted">
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