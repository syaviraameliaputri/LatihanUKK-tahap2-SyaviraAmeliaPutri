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

$id = (int) ($_GET['id'] ?? $_POST['id_alat'] ?? 0);

if ($id <= 0) {
    header("Location: alat.php");
    exit;
}

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_alat'] ?? '');
    $id_kategori = (int) ($_POST['id_kategori'] ?? 0);
    $stok = filter_var($_POST['stok'] ?? null, FILTER_VALIDATE_INT);
    $kondisi = $_POST['kondisi'] ?? '';

    if (
        $nama === '' ||
        $id_kategori <= 0 ||
        $stok === false ||
        $stok === null ||
        $stok < 0 ||
        !in_array($kondisi, ['baik', 'rusak'], true)
    ) {
        die("Data alat tidak valid.");
    }

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
        $id
    );

    if (mysqli_stmt_execute($stmt)) {
        header("Location: alat.php?pesan=edit");
        exit;
    }

    die("Gagal mengedit data alat.");
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT * FROM alat WHERE id_alat = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);

$alat = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$alat) {
    header("Location: alat.php");
    exit;
}

$kategori = mysqli_query(
    $koneksi,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Alat - Pinjamin</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #F4F6FF;
            color: #17204A;
            font-family: Arial, sans-serif;
        }

        .card {
            border: 1px solid #E2E6F5;
            border-radius: 14px;
        }

        .btn-simpan {
            background: #2338D6;
            color: white;
            border: none;
        }

        .btn-simpan:hover {
            background: #192BA8;
            color: white;
        }
    </style>
</head>

<body>
<div class="container py-5" style="max-width: 650px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h3 class="mb-4">Edit Data Alat</h3>

            <form method="POST" action="edit_alat.php?id=<?= $id ?>">
                <input type="hidden" name="id_alat" value="<?= $id ?>">

                <div class="mb-3">
                    <label class="form-label">Nama Alat</label>
                    <input
                        type="text"
                        name="nama_alat"
                        class="form-control"
                        maxlength="100"
                        value="<?= e($alat['nama_alat']) ?>"
                        required
                    >
                </div>

                <div class="mb-3">
                    <label class="form-label">Kategori</label>
                    <select name="id_kategori" class="form-select" required>
                        <?php while ($k = mysqli_fetch_assoc($kategori)): ?>
                            <option
                                value="<?= (int) $k['id_kategori'] ?>"
                                <?= (int) $k['id_kategori'] === (int) $alat['id_kategori'] ? 'selected' : '' ?>>
                                <?= e($k['nama_kategori']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label">Stok</label>
                    <input
                        type="number"
                        name="stok"
                        class="form-control"
                        min="0"
                        value="<?= (int) $alat['stok'] ?>"
                        required
                    >
                </div>

                <div class="mb-4">
                    <label class="form-label">Kondisi</label>
                    <select name="kondisi" class="form-select" required>
                        <option value="baik" <?= $alat['kondisi'] === 'baik' ? 'selected' : '' ?>>Baik</option>
                        <option value="rusak" <?= $alat['kondisi'] === 'rusak' ? 'selected' : '' ?>>Rusak</option>
                    </select>
                </div>

                <button type="submit" name="simpan" class="btn btn-simpan">
                    Simpan Perubahan
                </button>

                <a href="alat.php" class="btn btn-outline-secondary">
                    Batal
                </a>
            </form>
        </div>
    </div>
</div>
</body>
</html>