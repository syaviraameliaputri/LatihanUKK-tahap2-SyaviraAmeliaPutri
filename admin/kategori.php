<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$pesan = '';
$jenisPesan = '';

if (isset($_POST['tambah']) || isset($_POST['edit'])) {
    $nama = trim($_POST['nama_kategori'] ?? '');

    if ($nama === '') {
        $pesan = 'Nama kategori wajib diisi.';
        $jenisPesan = 'danger';
    } elseif (isset($_POST['tambah'])) {
        $stmt = mysqli_prepare(
            $koneksi,
            "INSERT INTO kategori (nama_kategori) VALUES (?)"
        );

        mysqli_stmt_bind_param($stmt, "s", $nama);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: kategori.php?pesan=tambah");
            exit;
        }

        $pesan = 'Kategori gagal ditambahkan.';
        $jenisPesan = 'danger';
        mysqli_stmt_close($stmt);
    } else {
        $id = (int) ($_POST['id_kategori'] ?? 0);

        if ($id < 1) {
            $pesan = 'ID kategori tidak valid.';
            $jenisPesan = 'danger';
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?"
            );

            mysqli_stmt_bind_param($stmt, "si", $nama, $id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: kategori.php?pesan=edit");
                exit;
            }

            $pesan = 'Kategori gagal diperbarui.';
            $jenisPesan = 'danger';
            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_POST['hapus'])) {
    $id = (int) ($_POST['id_kategori'] ?? 0);

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT COUNT(*) AS total FROM alat WHERE id_kategori = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);
        $cek = mysqli_fetch_assoc($hasil);
        mysqli_stmt_close($stmt);

        if ((int) $cek['total'] > 0) {
            $pesan = 'Kategori masih digunakan oleh data alat.';
            $jenisPesan = 'warning';
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "DELETE FROM kategori WHERE id_kategori = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: kategori.php?pesan=hapus");
                exit;
            }

            $pesan = 'Kategori gagal dihapus.';
            $jenisPesan = 'danger';
            mysqli_stmt_close($stmt);
        }
    }
}

if (isset($_GET['pesan'])) {
    $daftarPesan = [
        'tambah' => 'Kategori berhasil ditambahkan.',
        'edit' => 'Kategori berhasil diperbarui.',
        'hapus' => 'Kategori berhasil dihapus.'
    ];

    if (isset($daftarPesan[$_GET['pesan']])) {
        $pesan = $daftarPesan[$_GET['pesan']];
        $jenisPesan = 'success';
    }
}

$dataEdit = null;

if (isset($_GET['edit'])) {
    $idEdit = (int) $_GET['edit'];

    if ($idEdit > 0) {
        $stmt = mysqli_prepare(
            $koneksi,
            "SELECT id_kategori, nama_kategori
             FROM kategori WHERE id_kategori = ?"
        );

        mysqli_stmt_bind_param($stmt, "i", $idEdit);
        mysqli_stmt_execute($stmt);
        $hasil = mysqli_stmt_get_result($stmt);
        $dataEdit = mysqli_fetch_assoc($hasil);
        mysqli_stmt_close($stmt);
    }

    if (!$dataEdit) {
        $pesan = 'Kategori tidak ditemukan.';
        $jenisPesan = 'danger';
    }
}

$query = mysqli_query($koneksi, "
    SELECT k.id_kategori, k.nama_kategori,
           COUNT(a.id_alat) AS jumlah_alat
    FROM kategori k
    LEFT JOIN alat a ON k.id_kategori = a.id_kategori
    GROUP BY k.id_kategori, k.nama_kategori
    ORDER BY k.id_kategori DESC
");

if (!$query) {
    die('Gagal mengambil data kategori: ' . mysqli_error($koneksi));
}

$totalKategori = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kategori | Pinjamin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --ink: #17204A;
            --paper: #F4F6FF;
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
            min-height: 100vh;
            padding: 28px 20px;
            background: var(--blue);
        }

        .brand {
            color: white;
            text-decoration: none;
            font: 700 2rem "Cormorant Garamond", serif;
        }

        .brand span {
            color: var(--yellow);
        }

        .menu-label {
            margin: 35px 0 12px;
            color: #C9D0FF;
            font-size: .72rem;
            letter-spacing: .14em;
        }

        .menu a {
            display: block;
            padding: 11px 13px;
            margin-bottom: 5px;
            border-radius: 8px;
            color: #E5E9FF;
            text-decoration: none;
            font-size: .92rem;
        }

        .menu a:hover,
        .menu a.active {
            background: var(--yellow);
            color: var(--ink);
        }

        .main {
            padding: 35px;
        }

        .page-title,
        .panel-title,
        .stat-number {
            font-family: "Cormorant Garamond", serif;
            font-weight: 700;
        }

        .page-title {
            font-size: 2.5rem;
        }

        .panel-title {
            font-size: 1.7rem;
        }

        .muted {
            color: var(--muted);
        }

        .topbar {
            padding-bottom: 22px;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--line);
        }

        .panel,
        .stat-card {
            padding: 25px;
            border: 1px solid var(--line);
            border-radius: 14px;
            background: white;
            box-shadow: 0 4px 15px rgba(35, 56, 214, .04);
        }

        .stat-number {
            margin-top: 8px;
            color: var(--blue);
            font-size: 2.5rem;
        }

        .form-label {
            font-size: .9rem;
            font-weight: 600;
        }

        .form-control {
            padding: 11px 13px;
            border-color: #D9DFF5;
            border-radius: 8px;
        }

        .form-control:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 .2rem rgba(35, 56, 214, .12);
        }

        .btn-blue {
            padding: 10px 16px;
            border: 1px solid var(--blue);
            border-radius: 8px;
            background: var(--blue);
            color: white;
        }

        .btn-blue:hover {
            background: #1929A8;
            color: white;
        }

        .btn-yellow {
            padding: 10px 16px;
            border: 1px solid var(--yellow);
            border-radius: 8px;
            background: var(--yellow);
            color: var(--ink);
            text-decoration: none;
        }

        .btn-yellow:hover {
            background: #F0B900;
            color: var(--ink);
        }

        .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--ink);
            margin-bottom: 0;
            vertical-align: middle;
        }

        .table thead th {
            padding: 14px;
            background: #EDF0FF;
            color: #59628C;
            font-size: .8rem;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 15px 14px;
            border-color: var(--line);
        }

        .btn-action {
            display: inline-block;
            padding: 6px 11px;
            border: 1px solid #D6DCF5;
            border-radius: 6px;
            background: white;
            color: var(--blue);
            text-decoration: none;
            font-size: .85rem;
        }

        .btn-action:hover {
            background: var(--blue);
            color: white;
        }

        .btn-delete {
            border-color: #F0C4C4;
            color: #B42318;
        }

        .btn-delete:hover {
            background: #B42318;
            color: white;
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
                padding: 18px;
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
                <a href="kategori.php" class="active">Data Kategori</a>
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
                    <h1 class="page-title mb-0">Data Kategori</h1>
                </div>

                <div class="text-md-end">
                    <div class="fw-semibold">
                        <?= e($_SESSION['username'] ?? 'Admin') ?>
                    </div>
                    <small class="muted">Administrator</small>
                </div>
            </div>

            <?php if ($pesan !== ''): ?>
                <div class="alert alert-<?= e($jenisPesan) ?> alert-dismissible fade show">
                    <?= e($pesan) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-sm-6 col-lg-4">
                    <div class="stat-card">
                        <div class="muted small">Total Kategori</div>
                        <div class="stat-number"><?= $totalKategori ?></div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-4">
                    <div class="panel">
                        <h2 class="panel-title mb-3">
                            <?= $dataEdit ? 'Edit Kategori' : 'Tambah Kategori' ?>
                        </h2>

                        <p class="muted small mb-4">
                            Masukkan nama kategori alat sekolah.
                        </p>

                        <form method="POST" action="kategori.php">
                            <?php if ($dataEdit): ?>
                                <input type="hidden" name="id_kategori"
                                       value="<?= (int) $dataEdit['id_kategori'] ?>">
                            <?php endif; ?>

                            <div class="mb-3">
                                <label for="nama_kategori" class="form-label">
                                    Nama Kategori
                                </label>

                                <input
                                    type="text"
                                    id="nama_kategori"
                                    name="nama_kategori"
                                    class="form-control"
                                    placeholder="Contoh: Elektronik"
                                    maxlength="100"
                                    required
                                    value="<?= e($dataEdit['nama_kategori'] ?? '') ?>">
                            </div>

                            <?php if ($dataEdit): ?>
                                <button type="submit" name="edit" class="btn-blue">
                                    Simpan Perubahan
                                </button>
                                <a href="kategori.php" class="btn-yellow">Batal</a>
                            <?php else: ?>
                                <button type="submit" name="tambah" class="btn-blue">
                                    Tambah Kategori
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="panel">
                        <h2 class="panel-title mb-1">Daftar Kategori</h2>
                        <p class="muted small mb-4">
                            Semua kategori inventaris yang terdaftar.
                        </p>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Nama Kategori</th>
                                        <th>Jumlah Alat</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <?php if ($totalKategori > 0): ?>
                                        <?php $no = 1; ?>

                                        <?php while ($row = mysqli_fetch_assoc($query)): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td class="fw-semibold">
                                                    <?= e($row['nama_kategori']) ?>
                                                </td>
                                                <td><?= (int) $row['jumlah_alat'] ?> alat</td>
                                                <td>
                                                    <div class="d-flex gap-2">
                                                        <a href="edit_kategori.php?id=<?= $row['id_kategori'] ?>"
                                                           class="btn btn-sm btn-warning">
                                                            Edit
                                                        </a>

                                                        <form method="POST" action="kategori.php"
                                                              onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">
                                                            <input type="hidden" name="id_kategori"
                                                                   value="<?= (int) $row['id_kategori'] ?>">

                                                            <button type="submit" name="hapus"
                                                                    class="btn-action btn-delete">
                                                                Hapus
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 muted">
                                                Belum ada kategori. Silakan tambahkan kategori baru.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>