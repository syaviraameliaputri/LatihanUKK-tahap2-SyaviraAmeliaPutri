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

$pesan = '';
$jenisPesan = 'success';
$editData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';
    $id_alat = (int) ($_POST['id_alat'] ?? 0);

    if ($aksi === 'tambah' || $aksi === 'edit') {
        $nama = trim($_POST['nama_alat'] ?? '');
        $id_kategori = (int) ($_POST['id_kategori'] ?? 0);
        $stok = filter_var($_POST['stok'] ?? null, FILTER_VALIDATE_INT);
        $kondisi = $_POST['kondisi'] ?? '';

        if (
            $nama === '' ||
            $id_kategori < 1 ||
            $stok === false ||
            $stok === null ||
            $stok < 0 ||
            !in_array($kondisi, ['baik', 'rusak'], true) ||
            ($aksi === 'edit' && $id_alat < 1)
        ) {
            $pesan = 'Data alat belum lengkap atau tidak valid.';
            $jenisPesan = 'danger';
        } else {
            $cek = mysqli_prepare(
                $koneksi,
                "SELECT id_kategori FROM kategori WHERE id_kategori = ?"
            );

            mysqli_stmt_bind_param($cek, "i", $id_kategori);
            mysqli_stmt_execute($cek);
            $kategoriAda = mysqli_num_rows(mysqli_stmt_get_result($cek)) > 0;
            mysqli_stmt_close($cek);

            if (!$kategoriAda) {
                $pesan = 'Kategori tidak ditemukan.';
                $jenisPesan = 'danger';
            } else {
                if ($aksi === 'tambah') {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "INSERT INTO alat (nama_alat, id_kategori, stok, kondisi)
                         VALUES (?, ?, ?, ?)"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "siis",
                        $nama,
                        $id_kategori,
                        $stok,
                        $kondisi
                    );
                } else {
                    $stmt = mysqli_prepare(
                        $koneksi,
                        "UPDATE alat
                         SET nama_alat = ?, id_kategori = ?, stok = ?, kondisi = ?
                         WHERE id_alat = ?"
                    );

                    mysqli_stmt_bind_param(
                        $stmt,
                        "siisi",
                        $nama,
                        $id_kategori,
                        $stok,
                        $kondisi,
                        $id_alat
                    );
                }

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header("Location: alat.php?pesan=" . $aksi);
                    exit;
                }

                mysqli_stmt_close($stmt);
                $pesan = 'Gagal menyimpan data alat.';
                $jenisPesan = 'danger';
            }
        }
    } elseif ($aksi === 'hapus') {
        if ($id_alat < 1) {
            $pesan = 'ID alat tidak valid.';
            $jenisPesan = 'danger';
        } else {
            $cek = mysqli_prepare(
                $koneksi,
                "SELECT
                    (SELECT COUNT(*) FROM peminjaman WHERE id_alat = ?) +
                    (SELECT COUNT(*) FROM detail_peminjaman WHERE id_alat = ?)
                    AS total"
            );

            mysqli_stmt_bind_param($cek, "ii", $id_alat, $id_alat);
            mysqli_stmt_execute($cek);
            $dataCek = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
            mysqli_stmt_close($cek);

            if ((int) $dataCek['total'] > 0) {
                $pesan = 'Alat tidak bisa dihapus karena sudah tercatat dalam riwayat peminjaman.';
                $jenisPesan = 'warning';
            } else {
                $stmt = mysqli_prepare(
                    $koneksi,
                    "DELETE FROM alat WHERE id_alat = ?"
                );

                mysqli_stmt_bind_param($stmt, "i", $id_alat);

                if (mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    header("Location: alat.php?pesan=hapus");
                    exit;
                }

                mysqli_stmt_close($stmt);
                $pesan = 'Gagal menghapus alat. Periksa relasi data pada database.';
                $jenisPesan = 'danger';
            }
        }
    }
}

if (isset($_GET['pesan'])) {
    $daftarPesan = [
        'tambah' => 'Data alat berhasil ditambahkan.',
        'edit' => 'Data alat berhasil diperbarui.',
        'hapus' => 'Data alat berhasil dihapus.'
    ];

    if (isset($daftarPesan[$_GET['pesan']])) {
        $pesan = $daftarPesan[$_GET['pesan']];
        $jenisPesan = 'success';
    }
}

$kategori = mysqli_query(
    $koneksi,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);

if (!$kategori) {
    die("Gagal mengambil data kategori.");
}

$dataAlat = mysqli_query(
    $koneksi,
    "SELECT a.id_alat, a.nama_alat, a.id_kategori, a.stok,
            a.kondisi, k.nama_kategori
     FROM alat a
     LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
     ORDER BY a.id_alat DESC"
);

if (!$dataAlat) {
    die("Gagal mengambil data alat.");
}

$jumlahAlat = mysqli_num_rows($dataAlat);

if (isset($_GET['edit'])) {
    $id_edit = (int) $_GET['edit'];

    if ($id_edit > 0) {
        $stmtEdit = mysqli_prepare(
            $koneksi,
            "SELECT * FROM alat WHERE id_alat = ?"
        );

        mysqli_stmt_bind_param($stmtEdit, "i", $id_edit);
        mysqli_stmt_execute($stmtEdit);
        $editData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtEdit));
        mysqli_stmt_close($stmtEdit);
    }

    if (!$editData) {
        header("Location: alat.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Alat - Pinjamin</title>

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

        .panel,
        .stat-card {
            background: var(--sheet);
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 25px;
            box-shadow: 0 4px 15px rgba(35, 56, 214, .04);
        }

        .panel-title {
            font: 600 1.7rem "Cormorant Garamond", serif;
        }

        .stat-number {
            font: 700 2.5rem "Cormorant Garamond", serif;
            color: var(--blue);
            margin-top: 8px;
        }

        .form-label {
            font-size: .9rem;
            font-weight: 600;
        }

        .form-control,
        .form-select {
            padding: 11px 13px;
            border-color: #D9DFF5;
            border-radius: 8px;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 .2rem rgba(35, 56, 214, .12);
        }

        .btn-blue {
            background: var(--blue);
            color: white;
            border: 1px solid var(--blue);
            padding: 10px 18px;
            border-radius: 8px;
        }

        .btn-blue:hover {
            background: #1929A8;
            color: white;
        }

        .btn-yellow {
            display: inline-block;
            background: var(--yellow);
            color: var(--ink);
            border: 1px solid var(--yellow);
            padding: 10px 18px;
            border-radius: 8px;
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
            background: #E2F5E8;
            color: #246B3C;
            font-size: .75rem;
            font-weight: 700;
        }

        .status.rusak {
            background: #FCE5E5;
            color: #A52626;
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

        .action {
            display: flex;
            gap: 7px;
            align-items: center;
        }

        .action form {
            margin: 0;
        }

        .btn-action {
            display: inline-block;
            padding: 7px 11px;
            border: 1px solid #D6DCF5;
            border-radius: 6px;
            color: var(--blue);
            background: white;
            text-decoration: none;
            font-size: .8rem;
            white-space: nowrap;
        }

        .btn-action:hover {
            background: var(--blue);
            color: white;
        }

        .btn-delete {
            border-color: #F0C4C4;
            color: #B42318;
            cursor: pointer;
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
                <a href="alat.php" class="active">Data Alat</a>
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
                    <h1 class="page-title mb-0">Data Alat</h1>
                    <p class="muted small mb-0 mt-2">
                        Kelola daftar alat, kategori, stok, dan kondisi alat.
                    </p>
                </div>

                <div class="text-md-end">
                    <div class="fw-semibold">
                        <?= e($_SESSION['username'] ?? 'Admin') ?>
                    </div>
                    <small class="muted">Administrator</small>
                    <div class="mt-2">
                        <span class="count"><?= $jumlahAlat ?> Data Alat</span>
                    </div>
                </div>
            </div>

            <?php if ($pesan !== ''): ?>
                <div class="alert alert-<?= e($jenisPesan) ?> alert-dismissible fade show">
                    <?= e($pesan) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <section class="panel mb-4">
                <h2 class="panel-title mb-3">
                    <?= $editData ? 'Edit Data Alat' : 'Tambah Alat Baru' ?>
                </h2>

                <p class="muted small mb-4">
                    Isi informasi alat yang akan disimpan ke inventaris.
                </p>

                <?php if (mysqli_num_rows($kategori) === 0): ?>
                    <div class="alert alert-warning">
                        Belum ada kategori. Tambahkan kategori terlebih dahulu di
                        <a href="kategori.php">Data Kategori</a>.
                    </div>
                <?php else: ?>
                    <form method="POST" action="alat.php">
                        <input type="hidden" name="aksi" value="<?= $editData ? 'edit' : 'tambah' ?>">

                        <?php if ($editData): ?>
                            <input type="hidden" name="id_alat" value="<?= (int) $editData['id_alat'] ?>">
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nama Alat</label>
                                <input
                                    type="text"
                                    name="nama_alat"
                                    class="form-control"
                                    placeholder="Contoh: Proyektor"
                                    maxlength="100"
                                    value="<?= e($editData['nama_alat'] ?? '') ?>"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Kategori</label>
                                <select name="id_kategori" class="form-select" required>
                                    <option value="">Pilih kategori</option>
                                    <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
                                        <option
                                            value="<?= (int) $k['id_kategori'] ?>"
                                            <?= isset($editData['id_kategori']) && (int) $editData['id_kategori'] === (int) $k['id_kategori'] ? 'selected' : '' ?>>
                                            <?= e($k['nama_kategori']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Stok</label>
                                <input
                                    type="number"
                                    name="stok"
                                    class="form-control"
                                    min="0"
                                    value="<?= e($editData['stok'] ?? '0') ?>"
                                    required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Kondisi</label>
                                <select name="kondisi" class="form-select" required>
                                    <option value="baik" <?= !isset($editData['kondisi']) || $editData['kondisi'] === 'baik' ? 'selected' : '' ?>>
                                        Baik
                                    </option>
                                    <option value="rusak" <?= isset($editData['kondisi']) && $editData['kondisi'] === 'rusak' ? 'selected' : '' ?>>
                                        Rusak
                                    </option>
                                </select>
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn-blue">
                                    <?= $editData ? 'Simpan Perubahan' : 'Tambah Alat' ?>
                                </button>

                                <?php if ($editData): ?>
                                    <a href="alat.php" class="btn-yellow">Batal</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </section>

            <section class="panel">
                <h2 class="panel-title mb-1">Daftar Alat</h2>
                <p class="muted small mb-4">
                    Semua alat yang terdaftar di inventaris.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Nama Alat</th>
                                <th>Kategori</th>
                                <th>Stok</th>
                                <th>Kondisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($jumlahAlat > 0): ?>
                                <?php $no = 1; ?>
                                <?php while ($a = mysqli_fetch_assoc($dataAlat)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>
                                        <td class="fw-semibold"><?= e($a['nama_alat']) ?></td>
                                        <td><?= e($a['nama_kategori'] ?? 'Tidak berkategori') ?></td>
                                        <td><?= (int) $a['stok'] ?></td>
                                        <td>
                                            <span class="status <?= $a['kondisi'] === 'rusak' ? 'rusak' : '' ?>">
                                                <?= e(ucfirst($a['kondisi'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action">
                                                <a
    href="edit_alat.php?id=<?= (int) $a['id_alat'] ?>"
    class="btn btn-sm btn-warning">
    Edit
</a>

                                                <form
                                                    method="POST"
                                                    action="alat.php"
                                                    onsubmit="return confirm('Yakin ingin menghapus alat ini?')">

                                                    <input type="hidden" name="aksi" value="hapus">
                                                    <input
                                                        type="hidden"
                                                        name="id_alat"
                                                        value="<?= (int) $a['id_alat'] ?>">

                                                    <button type="submit" class="btn-action btn-delete">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 muted">
                                        Belum ada data alat.
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>