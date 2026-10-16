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

/* PROSES PENGEMBALIAN */
if (isset($_POST['kembalikan'])) {
    $id_peminjaman = (int) ($_POST['id_peminjaman'] ?? 0);

    mysqli_begin_transaction($koneksi);

    try {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_peminjaman, tgl_rencana_kembali, status
             FROM peminjaman
             WHERE id_peminjaman = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
        mysqli_stmt_execute($stmt);
        $pinjam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if (!$pinjam || $pinjam['status'] !== 'dipinjam') {
            throw new Exception("Peminjaman tidak valid.");
        }

        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_alat, jumlah
             FROM detail_peminjaman
             WHERE id_peminjaman = ?
             FOR UPDATE"
        );

        mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
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

            mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
            mysqli_stmt_execute($stmt);
            $itemLama = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);

            if ($itemLama) {
                $daftarAlat[] = $itemLama;
            }
        }

        if (count($daftarAlat) === 0) {
            throw new Exception("Daftar alat tidak ditemukan.");
        }

        $tgl_kembali = date('Y-m-d');
        $tgl_rencana = $pinjam['tgl_rencana_kembali'];

        $selisih = (
            strtotime($tgl_kembali) - strtotime($tgl_rencana)
        ) / 86400;

        $hari_telat = max(0, (int) $selisih);
        $denda = $hari_telat * 1000;

        // Simpan data pengembalian.
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO pengembalian
                (id_peminjaman, tgl_kembali, hari_telat, denda)
             VALUES (?, ?, ?, ?)"
        );

        mysqli_stmt_bind_param(
            $stmt,
            "isii",
            $id_peminjaman,
            $tgl_kembali,
            $hari_telat,
            $denda
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Gagal menyimpan pengembalian.");
        }

        mysqli_stmt_close($stmt);

        // Kembalikan stok alat.
        foreach ($daftarAlat as $item) {
            $id_alat = (int) $item['id_alat'];
            $jumlah = (int) $item['jumlah'];

            if ($id_alat < 1 || $jumlah < 1) {
                throw new Exception("Data alat tidak valid.");
            }

            $stmt = mysqli_prepare(
                $koneksi,
                "UPDATE alat
                 SET stok = stok + ?
                 WHERE id_alat = ?"
            );

            mysqli_stmt_bind_param($stmt, "ii", $jumlah, $id_alat);
            mysqli_stmt_execute($stmt);

            if (mysqli_stmt_affected_rows($stmt) !== 1) {
                mysqli_stmt_close($stmt);
                throw new Exception("Gagal memperbarui stok.");
            }

            mysqli_stmt_close($stmt);
        }

        // Ubah status peminjaman.
        $stmt = mysqli_prepare(
            $koneksi,
            "UPDATE peminjaman
             SET status = 'dikembalikan'
             WHERE id_peminjaman = ?
               AND status = 'dipinjam'"
        );

        mysqli_stmt_bind_param($stmt, "i", $id_peminjaman);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_affected_rows($stmt) !== 1) {
            mysqli_stmt_close($stmt);
            throw new Exception("Gagal memperbarui status.");
        }

        mysqli_stmt_close($stmt);

        // Catat aktivitas jika tabel tersedia.
        $cekLog = mysqli_query(
            $koneksi,
            "SHOW TABLES LIKE 'log_aktivitas'"
        );

        if ($cekLog && mysqli_num_rows($cekLog) > 0) {
            $aktivitas = "Memproses pengembalian #" . $id_peminjaman;

            $stmtLog = mysqli_prepare(
                $koneksi,
                "INSERT INTO log_aktivitas
                    (id_petugas, aktivitas, waktu)
                 VALUES (?, ?, NOW())"
            );

            if ($stmtLog) {
                mysqli_stmt_bind_param(
                    $stmtLog,
                    "is",
                    $id_petugas,
                    $aktivitas
                );

                mysqli_stmt_execute($stmtLog);
                mysqli_stmt_close($stmtLog);
            }
        }

        mysqli_commit($koneksi);

        header("Location: pengembalian.php?pesan=berhasil");
        exit;

    } catch (Throwable $e) {
        mysqli_rollback($koneksi);
        error_log("Pengembalian: " . $e->getMessage());

        header("Location: pengembalian.php?pesan=gagal");
        exit;
    }
}

/* DATA PEMINJAMAN AKTIF */
$query = mysqli_query(
    $koneksi,
    "SELECT
        p.id_peminjaman,
        pm.username,
        p.tgl_pinjam,
        p.tgl_rencana_kembali,
        GROUP_CONCAT(
            CONCAT(a.nama_alat, ' (', d.jumlah, ')')
            ORDER BY a.nama_alat SEPARATOR ', '
        ) AS daftar_alat
     FROM peminjaman p
     JOIN peminjam pm ON p.id_peminjam = pm.id_peminjam
     LEFT JOIN detail_peminjaman d
        ON p.id_peminjaman = d.id_peminjaman
     LEFT JOIN alat a ON d.id_alat = a.id_alat
     WHERE p.status = 'dipinjam'
     GROUP BY
        p.id_peminjaman,
        pm.username,
        p.tgl_pinjam,
        p.tgl_rencana_kembali
     ORDER BY p.tgl_rencana_kembali ASC"
);

if (!$query) {
    die("Query peminjaman gagal: " . mysqli_error($koneksi));
}

$jumlahAktif = mysqli_num_rows($query);

/* RIWAYAT PENGEMBALIAN */
$riwayat = mysqli_query(
    $koneksi,
    "SELECT
        pg.id_pengembalian,
        pm.username,
        p.id_peminjaman,
        pg.tgl_kembali,
        pg.hari_telat,
        pg.denda,
        GROUP_CONCAT(
            CONCAT(a.nama_alat, ' (', d.jumlah, ')')
            ORDER BY a.nama_alat SEPARATOR ', '
        ) AS daftar_alat
     FROM pengembalian pg
     JOIN peminjaman p ON pg.id_peminjaman = p.id_peminjaman
     JOIN peminjam pm ON p.id_peminjam = pm.id_peminjam
     LEFT JOIN detail_peminjaman d
        ON p.id_peminjaman = d.id_peminjaman
     LEFT JOIN alat a ON d.id_alat = a.id_alat
     GROUP BY
        pg.id_pengembalian,
        pm.username,
        p.id_peminjaman,
        pg.tgl_kembali,
        pg.hari_telat,
        pg.denda
     ORDER BY pg.id_pengembalian DESC"
);

if (!$riwayat) {
    die("Query riwayat gagal: " . mysqli_error($koneksi));
}

$jumlahRiwayat = mysqli_num_rows($riwayat);

/* TAMPILKAN DAFTAR ALAT */
function tampilkanAlat($koneksi, $row) {
    if (!empty($row['daftar_alat'])) {
        return e($row['daftar_alat']);
    }

    $id = (int) $row['id_peminjaman'];

    $stmt = mysqli_prepare(
        $koneksi,
        "SELECT a.nama_alat, p.jumlah
         FROM peminjaman p
         JOIN alat a ON p.id_alat = a.id_alat
         WHERE p.id_peminjaman = ?"
    );

    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);

    $alat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$alat) {
        return '-';
    }

    return e($alat['nama_alat']) . ' (' . (int) $alat['jumlah'] . ')';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Data Pengembalian - Pinjamin</title>

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
            min-width: 0;
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
            margin-bottom: 28px;
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

        .status.dikembalikan {
            background: #E2F5E8;
            color: #246B3C;
        }

        .btn-kembali {
            display: inline-block;
            padding: 8px 11px;
            border-radius: 7px;
            text-decoration: none;
            font-size: .8rem;
            font-weight: 600;
            white-space: nowrap;
            border: 0;
            background: var(--blue);
            color: white;
        }

        .btn-kembali:hover {
            background: #1929A8;
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

            .panel {
                padding: 16px;
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
                <a href="peminjaman.php">Data Peminjaman</a>
                <a href="pengembalian.php" class="active">Data Pengembalian</a>
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
                    <h1 class="page-title mb-0">Data Pengembalian</h1>
                    <p class="muted small mb-0 mt-2">
                        Kelola pengembalian alat dan lihat riwayat pengembalian.
                    </p>
                </div>

                <div class="text-md-end">
                    <div class="fw-semibold">
                        <?= e($_SESSION['username'] ?? 'Petugas') ?>
                    </div>
                    <small class="muted">Petugas</small>
                    <div class="mt-2">
                        <span class="count">
                            <?= $jumlahAktif ?> Belum Dikembalikan
                        </span>
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['pesan'])): ?>
                <?php if ($_GET['pesan'] === 'berhasil'): ?>
                    <div class="alert alert-success pesan">
                        Pengembalian berhasil dicatat dan stok alat diperbarui.
                    </div>
                <?php elseif ($_GET['pesan'] === 'gagal'): ?>
                    <div class="alert alert-danger pesan">
                        Pengembalian gagal diproses. Periksa data peminjaman.
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <section class="panel">
                <h2 class="panel-title mb-1">Alat yang Belum Dikembalikan</h2>
                <p class="muted small mb-4">
                    Proses pengembalian untuk peminjaman yang masih aktif.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Peminjam</th>
                                <th>Daftar Alat</th>
                                <th>Tgl. Pinjam</th>
                                <th>Rencana Kembali</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($jumlahAktif > 0): ?>
                                <?php $no = 1; ?>

                                <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>

                                        <td class="fw-semibold">
                                            <?= e($row['username']) ?>
                                        </td>

                                        <td>
                                            <?= tampilkanAlat($koneksi, $row) ?>
                                        </td>

                                        <td><?= e($row['tgl_pinjam']) ?></td>

                                        <td><?= e($row['tgl_rencana_kembali']) ?></td>

                                        <td>
                                            <span class="status dipinjam">
                                                Dipinjam
                                            </span>
                                        </td>

                                        <td>
                                            <form method="POST"
                                                  onsubmit="return confirm('Proses pengembalian semua alat ini?')">
                                                <input
                                                    type="hidden"
                                                    name="id_peminjaman"
                                                    value="<?= (int) $row['id_peminjaman'] ?>">

                                                <button
                                                    type="submit"
                                                    name="kembalikan"
                                                    class="btn-kembali">
                                                    Proses Kembali
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 muted">
                                        Tidak ada alat yang sedang dipinjam.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="panel">
                <h2 class="panel-title mb-1">Riwayat Pengembalian</h2>
                <p class="muted small mb-4">
                    Data pengembalian yang sudah selesai beserta denda keterlambatan.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Peminjam</th>
                                <th>Daftar Alat</th>
                                <th>Tgl. Kembali</th>
                                <th>Terlambat</th>
                                <th>Denda</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($jumlahRiwayat > 0): ?>
                                <?php $no = 1; ?>

                                <?php while ($row = mysqli_fetch_assoc($riwayat)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>

                                        <td class="fw-semibold">
                                            <?= e($row['username']) ?>
                                        </td>

                                        <td>
                                            <?= tampilkanAlat($koneksi, $row) ?>
                                        </td>

                                        <td><?= e($row['tgl_kembali']) ?></td>

                                        <td>
                                            <?= (int) $row['hari_telat'] ?> hari
                                        </td>

                                        <td class="fw-semibold">
                                            Rp<?= number_format((int) $row['denda'], 0, ',', '.') ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 muted">
                                        Belum ada riwayat pengembalian.
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