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

if (isset($_POST['tambah'])) {
    $username = trim($_POST['username'] ?? '');
    $passwordInput = $_POST['password'] ?? '';
    $no_hp = trim($_POST['no_hp'] ?? '');

    if (
        $username === '' ||
        strlen($username) > 50 ||
        strlen($passwordInput) < 6 ||
        strlen($no_hp) > 15
    ) {
        $pesan = 'Periksa kembali username, password minimal 6 karakter, dan nomor HP.';
        $jenisPesan = 'danger';
    } else {
        $password = password_hash($passwordInput, PASSWORD_DEFAULT);

        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_petugas FROM petugas WHERE username = ?"
        );

        mysqli_stmt_bind_param($cek, "s", $username);
        mysqli_stmt_execute($cek);
        $hasilCek = mysqli_stmt_get_result($cek);
        $usernameAda = mysqli_num_rows($hasilCek) > 0;
        mysqli_stmt_close($cek);

        if ($usernameAda) {
            $pesan = 'Username sudah digunakan.';
            $jenisPesan = 'danger';
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO petugas (username, password, no_hp)
                 VALUES (?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "sss",
                $username,
                $password,
                $no_hp
            );

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: petugas.php?pesan=tambah");
                exit;
            }

            mysqli_stmt_close($stmt);
            $pesan = 'Gagal menambahkan petugas.';
            $jenisPesan = 'danger';
        }
    }
}

if (isset($_POST['hapus'])) {
    $id = (int) ($_POST['id_petugas'] ?? 0);

    if ($id < 1) {
        $pesan = 'ID petugas tidak valid.';
        $jenisPesan = 'danger';
    } else {
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT COUNT(*) AS total
             FROM peminjaman
             WHERE id_petugas = ?"
        );

        mysqli_stmt_bind_param($cek, "i", $id);
        mysqli_stmt_execute($cek);
        $hasilCek = mysqli_fetch_assoc(mysqli_stmt_get_result($cek));
        mysqli_stmt_close($cek);

        if ((int) $hasilCek['total'] > 0) {
            $pesan = 'Petugas tidak bisa dihapus karena sudah memiliki riwayat peminjaman.';
            $jenisPesan = 'warning';
        } else {
            $stmt = mysqli_prepare(
                $koneksi,
                "DELETE FROM petugas WHERE id_petugas = ?"
            );

            mysqli_stmt_bind_param($stmt, "i", $id);

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                header("Location: petugas.php?pesan=hapus");
                exit;
            }

            mysqli_stmt_close($stmt);
            $pesan = 'Gagal menghapus petugas.';
            $jenisPesan = 'danger';
        }
    }
}

if (isset($_GET['pesan'])) {
    if ($_GET['pesan'] === 'tambah') {
        $pesan = 'Petugas berhasil ditambahkan.';
        $jenisPesan = 'success';
    } elseif ($_GET['pesan'] === 'hapus') {
        $pesan = 'Petugas berhasil dihapus.';
        $jenisPesan = 'success';
    }
}

$query = mysqli_query(
    $koneksi,
    "SELECT id_petugas, username, no_hp
     FROM petugas
     ORDER BY id_petugas DESC"
);

if (!$query) {
    die("Query error: " . mysqli_error($koneksi));
}

$totalPetugas = mysqli_num_rows($query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Data Petugas - Pinjamin</title>

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

        .count {
            display: inline-block;
            background: var(--yellow);
            color: var(--ink);
            padding: 8px 14px;
            border-radius: 20px;
            font-size: .85rem;
            font-weight: 700;
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

        .btn-delete {
            background: white;
            color: #B42318;
            border: 1px solid #F0C4C4;
            border-radius: 7px;
            padding: 7px 11px;
            font-size: .8rem;
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
                <a href="alat.php">Data Alat</a>
                <a href="peminjaman.php">Data Peminjaman</a>
                <a href="pengembalian.php">Data Pengembalian</a>
                <a href="petugas.php" class="active">Data Petugas</a>
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
                    <h1 class="page-title mb-0">Data Petugas</h1>
                    <p class="muted small mb-0 mt-2">
                        Kelola akun petugas peminjaman alat.
                    </p>
                </div>

                <div class="text-md-end">
                    <div class="fw-semibold">
                        <?= e($_SESSION['username'] ?? 'Admin') ?>
                    </div>
                    <small class="muted">Administrator</small>
                    <div class="mt-2">
                        <span class="count">
                            <?= $totalPetugas ?> Data Petugas
                        </span>
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
                <h2 class="panel-title mb-1">Tambah Petugas</h2>
                <p class="muted small mb-4">
                    Buat akun baru untuk petugas.
                </p>

                <form method="POST" action="petugas.php">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Username</label>
                            <input
                                type="text"
                                name="username"
                                class="form-control"
                                maxlength="50"
                                autocomplete="username"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                minlength="6"
                                autocomplete="new-password"
                                required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Nomor HP</label>
                            <input
                                type="text"
                                name="no_hp"
                                class="form-control"
                                maxlength="15"
                                inputmode="tel">
                        </div>
                    </div>

                    <button type="submit" name="tambah" class="btn-blue mt-3">
                        Simpan Petugas
                    </button>
                </form>
            </section>

            <section class="panel">
                <h2 class="panel-title mb-1">Daftar Petugas</h2>
                <p class="muted small mb-4">
                    Semua akun petugas yang terdaftar.
                </p>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Username</th>
                                <th>Nomor HP</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if ($totalPetugas > 0): ?>
                                <?php $no = 1; ?>

                                <?php while ($data = mysqli_fetch_assoc($query)): ?>
                                    <tr>
                                        <td><?= $no++ ?></td>

                                        <td class="fw-semibold">
                                            <?= e($data['username']) ?>
                                        </td>

                                        <td>
                                            <?= e($data['no_hp'] ?? '-') ?>
                                        </td>

                                        <td>
                                            <form
                                                method="POST"
                                                action="petugas.php"
                                                onsubmit="return confirm('Yakin ingin menghapus petugas ini?')">

                                                <input type="hidden" name="id_petugas"
                                                       value="<?= (int) $data['id_petugas'] ?>">

                                                <button
                                                    type="submit"
                                                    name="hapus"
                                                    class="btn-delete">
                                                    Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>

                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 muted">
                                        Belum ada data petugas.
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