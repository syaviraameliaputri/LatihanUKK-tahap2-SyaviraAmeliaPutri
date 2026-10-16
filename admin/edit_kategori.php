<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

$id = (int) ($_GET['id'] ?? $_POST['id_kategori'] ?? 0);

if ($id < 1) {
    header("Location: kategori.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_kategori'] ?? '');

    if ($nama === '') {
        header("Location: edit_kategori.php?id=$id&pesan=nama_kosong");
        exit;
    }

    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE kategori SET nama_kategori = ? WHERE id_kategori = ?"
    );

    mysqli_stmt_bind_param($stmt, "si", $nama, $id);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: kategori.php?pesan=edit");
        exit;
    }

    die("Gagal mengedit kategori: " . mysqli_error($koneksi));
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM kategori WHERE id_kategori = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$data) {
    header("Location: kategori.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Kategori | Pinjamin</title>

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

        .page-description {
            color: var(--muted);
            margin-bottom: 28px;
        }

        .panel {
            max-width: 650px;
            background: white;
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 5px 20px rgba(35, 56, 214, .04);
        }

        .form-label {
            color: var(--ink);
            font-weight: 600;
        }

        .form-control {
            border-color: var(--line);
            border-radius: 8px;
            padding: 11px 13px;
        }

        .form-control:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 .2rem rgba(35, 56, 214, .1);
        }

        .btn-save {
            background: var(--blue);
            color: white;
            border: 1px solid var(--blue);
            border-radius: 8px;
            padding: 10px 18px;
            font-weight: 600;
        }

        .btn-save:hover {
            background: #192bb5;
            color: white;
        }

        .btn-cancel {
            background: white;
            color: var(--ink);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 10px 18px;
        }

        .btn-cancel:hover {
            background: #EEF1FF;
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
                padding: 20px;
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
            <h3 class="fw-bold mb-2">Edit Kategori</h3>
            <p class="page-description">
                Ubah nama kategori alat sesuai kebutuhan.
            </p>

            <div class="panel">
                <h5 class="fw-bold mb-4">Informasi Kategori</h5>

                <form method="POST">
                    <input
                        type="hidden"
                        name="id_kategori"
                        value="<?= (int) $data['id_kategori'] ?>"
                    >

                    <div class="mb-4">
                        <label class="form-label">Nama Kategori</label>
                        <input
                            type="text"
                            name="nama_kategori"
                            class="form-control"
                            maxlength="100"
                            value="<?= e($data['nama_kategori']) ?>"
                            placeholder="Masukkan nama kategori"
                            required
                        >
                    </div>

                    <div class="d-flex gap-2 flex-wrap">
                        <button
                            type="submit"
                            name="simpan"
                            class="btn btn-save"
                        >
                            Simpan Perubahan
                        </button>

                        <a href="kategori.php" class="btn btn-cancel">
                            Batal
                        </a>
                    </div>
                </form>
            </div>
        </main>

    </div>
</div>

</body>
</html>