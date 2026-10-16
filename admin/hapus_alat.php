<?php
session_start();
require_once '../koneksi.php';

if (!isset($_SESSION['id_admin'])) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: alat.php");
    exit;
}

$id = (int) ($_POST['id_alat'] ?? 0);

if ($id <= 0) {
    header("Location: alat.php");
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "SELECT COUNT(*) AS total
     FROM peminjaman
     WHERE id_alat = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ((int) $data['total'] > 0) {
    header("Location: alat.php?pesan=gagal_hapus");
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM alat WHERE id_alat = ?"
);
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: alat.php?pesan=hapus");
    exit;
}

header("Location: alat.php?pesan=gagal_hapus");
exit;