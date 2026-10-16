
<?php
session_start();
include '../koneksi.php';

if (!isset($_SESSION['id_petugas']) || $_SESSION['role'] !== 'petugas') {
    header("Location: ../login.php");
    exit;
}

if (isset($_POST['id_pengembalian'])) {
    $id_pengembalian = (int) $_POST['id_pengembalian'];

    mysqli_begin_transaction($koneksi);

    try {
        $query = mysqli_query($koneksi, "
            SELECT id_peminjaman
            FROM pengembalian
            WHERE id_pengembalian = $id_pengembalian
            FOR UPDATE
        ");

        if (!$query || mysqli_num_rows($query) == 0) {
            throw new Exception("Data pengembalian tidak ditemukan.");
        }

        $data = mysqli_fetch_assoc($query);
        $id_peminjaman = (int) $data['id_peminjaman'];

        $detail = mysqli_query($koneksi, "
            SELECT id_alat, jumlah
            FROM detail_peminjaman
            WHERE id_peminjaman = $id_peminjaman
        ");

        if (!$detail) {
            throw new Exception("Gagal mengambil detail peminjaman.");
        }

        while ($alat = mysqli_fetch_assoc($detail)) {
            $id_alat = (int) $alat['id_alat'];
            $jumlah = (int) $alat['jumlah'];

            $updateStok = mysqli_query($koneksi, "
                UPDATE alat
                SET stok = stok - $jumlah
                WHERE id_alat = $id_alat
                  AND stok >= $jumlah
            ");

            if (!$updateStok || mysqli_affected_rows($koneksi) < 1) {
                throw new Exception("Stok alat tidak mencukupi.");
            }
        }

        $hapus = mysqli_query($koneksi, "
            DELETE FROM pengembalian
            WHERE id_pengembalian = $id_pengembalian
        ");

        if (!$hapus) {
            throw new Exception("Gagal menghapus pengembalian.");
        }

        $updateStatus = mysqli_query($koneksi, "
            UPDATE peminjaman
            SET status = 'dipinjam'
            WHERE id_peminjaman = $id_peminjaman
        ");

        if (!$updateStatus) {
            throw new Exception("Gagal mengubah status peminjaman.");
        }

        mysqli_commit($koneksi);

        header("Location: pengembalian.php?pesan=hapus");
        exit;

    } catch (Exception $e) {
        mysqli_rollback($koneksi);
        die("Gagal menghapus: " . $e->getMessage());
    }
}

header("Location: pengembalian.php");
exit;
?>
