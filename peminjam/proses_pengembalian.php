<?php
session_start();
include '../koneksi.php';

if (
    !isset($_SESSION['login']) ||
    $_SESSION['role'] != 'peminjam' ||
    !isset($_SESSION['id_peminjam'])
) {
    header("Location: ../login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: pengembalian.php");
    exit;
}

$id_peminjam = (int) $_SESSION['id_peminjam'];
$id_peminjaman = filter_input(
    INPUT_POST,
    'id_peminjaman',
    FILTER_VALIDATE_INT
);

if (!$id_peminjaman || $id_peminjaman < 1) {
    echo "<script>
        alert('Data peminjaman tidak valid.');
        window.location='pengembalian.php';
    </script>";
    exit;
}

mysqli_begin_transaction($koneksi);

try {
    // Pastikan peminjaman milik akun yang sedang login.
    $cek = mysqli_prepare(
        $koneksi,
        "SELECT id_peminjaman
         FROM peminjaman
         WHERE id_peminjaman = ?
           AND id_peminjam = ?
           AND status = 'dipinjam'
         FOR UPDATE"
    );

    mysqli_stmt_bind_param(
        $cek,
        "ii",
        $id_peminjaman,
        $id_peminjam
    );

    mysqli_stmt_execute($cek);
    $hasil = mysqli_stmt_get_result($cek);

    if (!mysqli_fetch_assoc($hasil)) {
        throw new Exception(
            "Peminjaman tidak ditemukan atau belum bisa dikembalikan."
        );
    }

    // Cegah pengajuan pengembalian ganda.
    $cek_pengembalian = mysqli_prepare(
        $koneksi,
        "SELECT id_pengembalian
         FROM pengembalian
         WHERE id_peminjaman = ?"
    );

    mysqli_stmt_bind_param(
        $cek_pengembalian,
        "i",
        $id_peminjaman
    );

    mysqli_stmt_execute($cek_pengembalian);
    $hasil_pengembalian = mysqli_stmt_get_result($cek_pengembalian);

    if (mysqli_num_rows($hasil_pengembalian) > 0) {
        throw new Exception(
            "Pengembalian untuk peminjaman ini sudah diajukan."
        );
    }

    // Catat pengajuan pengembalian.
    // Tanggal kembali dan denda belum ditetapkan karena menunggu pemeriksaan Petugas.
    $simpan = mysqli_prepare(
        $koneksi,
        "INSERT INTO pengembalian
            (id_peminjaman, tgl_kembali, hari_telat, denda)
         VALUES (?, NULL, 0, 0)"
    );

    if (!$simpan) {
        throw new Exception(
            "Struktur tabel pengembalian belum sesuai: " .
            mysqli_error($koneksi)
        );
    }

    mysqli_stmt_bind_param(
        $simpan,
        "i",
        $id_peminjaman
    );

    if (!mysqli_stmt_execute($simpan)) {
        throw new Exception(
            "Gagal menyimpan pengajuan pengembalian."
        );
    }

    // Catat aktivitas jika berhasil.
    $aktivitas = "Mengajukan pengembalian peminjaman #" . $id_peminjaman;

    $log = mysqli_prepare(
        $koneksi,
        "INSERT INTO log_aktivitas
            (id_peminjam, aktivitas, waktu)
         VALUES (?, ?, NOW())"
    );

    if ($log) {
        mysqli_stmt_bind_param(
            $log,
            "is",
            $id_peminjam,
            $aktivitas
        );

        if (!mysqli_stmt_execute($log)) {
            throw new Exception("Gagal mencatat aktivitas.");
        }
    }

    mysqli_commit($koneksi);

    echo "<script>
        alert('Pengajuan pengembalian berhasil. Silakan tunggu pemeriksaan Petugas.');
        window.location='pengembalian.php';
    </script>";
    exit;

} catch (Throwable $e) {
    mysqli_rollback($koneksi);

    echo "<script>
        alert(" . json_encode(
            "Pengajuan gagal: " . $e->getMessage()
        ) . ");
        window.location='pengembalian.php';
    </script>";
    exit;
}
?>