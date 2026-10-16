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
    header("Location: alat.php");
    exit;
}

$id_peminjam = (int) $_SESSION['id_peminjam'];
$tanggal_kembali = $_POST['tgl_rencana_kembali'] ?? '';
$pilihan = $_POST['alat'] ?? [];

if (!is_array($pilihan) || empty($pilihan)) {
    echo "<script>
        alert('Pilih minimal satu alat.');
        window.location='alat.php';
    </script>";
    exit;
}

$tanggal = DateTime::createFromFormat('!Y-m-d', $tanggal_kembali);

if (
    !$tanggal ||
    $tanggal->format('Y-m-d') !== $tanggal_kembali ||
    $tanggal_kembali <= date('Y-m-d')
) {
    echo "<script>
        alert('Tanggal rencana pengembalian tidak valid.');
        window.location='alat.php';
    </script>";
    exit;
}

$alat_dipilih = [];

foreach ($pilihan as $id => $jumlah) {
    $id = filter_var($id, FILTER_VALIDATE_INT);
    $jumlah = filter_var($jumlah, FILTER_VALIDATE_INT);

    if (!$id || !$jumlah || $jumlah < 1) {
        continue;
    }

    $alat_dipilih[$id] = $jumlah;
}

if (empty($alat_dipilih)) {
    echo "<script>
        alert('Pilihan alat tidak valid.');
        window.location='alat.php';
    </script>";
    exit;
}

mysqli_begin_transaction($koneksi);

try {
    // Pastikan akun peminjam masih terdaftar.
    $cek_peminjam = mysqli_prepare(
        $koneksi,
        "SELECT id_peminjam FROM peminjam WHERE id_peminjam = ?"
    );
    mysqli_stmt_bind_param($cek_peminjam, "i", $id_peminjam);
    mysqli_stmt_execute($cek_peminjam);
    $hasil_peminjam = mysqli_stmt_get_result($cek_peminjam);

    if (!mysqli_fetch_assoc($hasil_peminjam)) {
        throw new Exception("Akun peminjam tidak ditemukan.");
    }

    // Kunci data alat dan periksa stok terbaru.
    $cek_alat = mysqli_prepare(
        $koneksi,
        "SELECT id_alat, stok, kondisi
         FROM alat
         WHERE id_alat = ?
         FOR UPDATE"
    );

    foreach ($alat_dipilih as $id_alat => $jumlah) {
        mysqli_stmt_bind_param($cek_alat, "i", $id_alat);
        mysqli_stmt_execute($cek_alat);
        $hasil_alat = mysqli_stmt_get_result($cek_alat);
        $data_alat = mysqli_fetch_assoc($hasil_alat);

        if (
            !$data_alat ||
            $data_alat['kondisi'] !== 'baik' ||
            (int) $data_alat['stok'] < $jumlah
        ) {
            throw new Exception(
                "Stok salah satu alat tidak mencukupi atau alat tidak tersedia."
            );
        }
    }

    // Simpan satu pengajuan utama.
    $alat_pertama = array_key_first($alat_dipilih);
    $jumlah_pertama = $alat_dipilih[$alat_pertama];
    $id_petugas = null;
    $status = 'menunggu';

    $simpan = mysqli_prepare(
        $koneksi,
        "INSERT INTO peminjaman
        (id_peminjam, id_alat, id_petugas, jumlah,
         tgl_pinjam, tgl_rencana_kembali, status)
         VALUES (?, ?, ?, ?, CURDATE(), ?, ?)"
    );

    mysqli_stmt_bind_param(
        $simpan,
        "iiiiss",
        $id_peminjam,
        $alat_pertama,
        $id_petugas,
        $jumlah_pertama,
        $tanggal_kembali,
        $status
    );

    if (!mysqli_stmt_execute($simpan)) {
        throw new Exception("Gagal menyimpan pengajuan.");
    }

    $id_peminjaman = mysqli_insert_id($koneksi);

    // Simpan semua alat dalam detail pengajuan.
    $simpan_detail = mysqli_prepare(
        $koneksi,
        "INSERT INTO detail_peminjaman
        (id_peminjaman, id_alat, jumlah)
        VALUES (?, ?, ?)"
    );

    foreach ($alat_dipilih as $id_alat => $jumlah) {
        mysqli_stmt_bind_param(
            $simpan_detail,
            "iii",
            $id_peminjaman,
            $id_alat,
            $jumlah
        );

        if (!mysqli_stmt_execute($simpan_detail)) {
            throw new Exception("Gagal menyimpan detail alat.");
        }
    }

    // Catat aktivitas peminjam jika tabel log tersedia.
    $aktivitas = "Mengajukan peminjaman #" . $id_peminjaman;

    $log = mysqli_prepare(
        $koneksi,
        "INSERT INTO log_aktivitas (id_peminjam, aktivitas, waktu)
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
        alert('Pengajuan berhasil dikirim. Silakan tunggu persetujuan Petugas.');
        window.location='riwayat.php';
    </script>";
    exit;

} catch (Throwable $e) {
    mysqli_rollback($koneksi);

    echo "<script>
        alert(" . json_encode(
            "Pengajuan gagal: " . $e->getMessage()
        ) . ");
        window.location='alat.php';
    </script>";
    exit;
}
?>