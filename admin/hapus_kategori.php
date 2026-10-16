<?php
session_start();
include '../koneksi.php';

if (
    !isset($_SESSION['login']) ||
    $_SESSION['role'] !== 'admin'
) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: kategori.php");
    exit;
}

$id = (int) ($_POST['id_kategori'] ?? 0);

if ($id < 1) {
    header("Location: kategori.php");
    exit;
}

$stmt = mysqli_prepare(
    $koneksi,
    "DELETE FROM kategori WHERE id_kategori = ?"
);

mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: kategori.php?pesan=hapus");
    exit;
}

header("Location: kategori.php?pesan=gagal_hapus");
exit;
?>