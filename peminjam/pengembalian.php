<?php
session_start();
include '../koneksi.php';

if (
    !isset($_SESSION['login']) ||
    ($_SESSION['role'] ?? '') !== 'peminjam' ||
    !isset($_SESSION['id_peminjam'])
) {
    header("Location: ../login.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$id_peminjam = (int) $_SESSION['id_peminjam'];

$stmtUser = mysqli_prepare(
    $koneksi,
    "SELECT username FROM peminjam WHERE id_peminjam = ? LIMIT 1"
);
mysqli_stmt_bind_param($stmtUser, "i", $id_peminjam);
mysqli_stmt_execute($stmtUser);
$peminjam = mysqli_fetch_assoc(mysqli_stmt_get_result($stmtUser));

if (!$peminjam) {
    session_destroy();
    header("Location: ../login.php");
    exit;
}

$sql = "SELECT
            p.id_peminjaman,
            p.tgl_pinjam,
            p.tgl_rencana_kembali,
            COALESCE(
                GROUP_CONCAT(
                    CONCAT(a.nama_alat, ' (', d.jumlah, ' unit)')
                    ORDER BY a.nama_alat
                    SEPARATOR ', '
                ),
                CONCAT(a1.nama_alat, ' (', p.jumlah, ' unit)')
            ) AS daftar_alat
        FROM peminjaman p
        LEFT JOIN detail_peminjaman d
            ON d.id_peminjaman = p.id_peminjaman
        LEFT JOIN alat a
            ON a.id_alat = d.id_alat
        LEFT JOIN alat a1
            ON a1.id_alat = p.id_alat
        WHERE p.id_peminjam = ?
          AND p.status = 'dipinjam'
        GROUP BY
            p.id_peminjaman,
            p.tgl_pinjam,
            p.tgl_rencana_kembali,
            a1.nama_alat,
            p.jumlah
        ORDER BY p.tgl_rencana_kembali ASC";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_peminjam);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$data = [];

while ($row = mysqli_fetch_assoc($hasil)) {
    $data[] = $row;
}

$cek_pengembalian = mysqli_prepare(
    $koneksi,
    "SELECT id_peminjaman
     FROM pengembalian
     WHERE id_peminjaman = ?
     LIMIT 1"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pengembalian Alat - Pinjamin</title>

    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700;800&family=Figtree:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --biru: #2338d6;
            --kuning: #ffc933;
            --teks: #131a33;
            --latar: #f7f7fb;
            --putih: #ffffff;
            --abu: #777b91;
            --garis: #e7e8f0;
            --hijau: #12805c;
            --merah: #c0392b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Figtree', Arial, sans-serif;
            background: var(--latar);
            color: var(--teks);
        }

        /* NAVBAR */

        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;
            min-height: 76px;
            padding: 15px 7%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
            background: var(--biru);
            border-bottom: 3px solid var(--kuning);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 11px;
            color: white;
            text-decoration: none;
        }

        .logo-mark {
            width: 43px;
            height: 43px;
            display: grid;
            place-items: center;
            border-radius: 13px;
            background: var(--kuning);
            color: var(--teks);
            font-size: 23px;
            font-weight: 800;
        }

        .logo-name {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: -1px;
        }

        .logo-name span {
            color: var(--kuning);
        }

        .logo small {
            display: block;
            margin-top: 3px;
            color: #dce2ff;
            font-size: 9px;
            font-weight: 800;
            letter-spacing: 2px;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .nav-links a {
            color: #e5e9ff;
            text-decoration: none;
            font-size: 13px;
            font-weight: 700;
            padding: 10px 12px;
            border-radius: 8px;
            transition: .2s;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: var(--kuning);
            color: var(--teks);
        }

        .nav-links .logout {
            color: #ffe0e0;
        }

        .nav-links .user-name {
            background: rgba(255, 255, 255, .14);
            color: white;
            border: 1px solid rgba(255, 255, 255, .2);
            border-radius: 25px;
        }

        /* KONTEN */

        .container {
            width: 86%;
            max-width: 1100px;
            margin: 35px auto;
        }

        .hero {
            background: var(--biru);
            color: white;
            padding: 35px;
            border-radius: 20px;
            margin-bottom: 25px;
        }

        .hero small {
            color: var(--kuning);
            font-weight: 800;
            letter-spacing: 2px;
        }

        .hero h1 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 32px;
            margin: 12px 0;
        }

        .hero p {
            color: #e0e4ff;
            line-height: 1.6;
        }

        .card {
            background: white;
            border: 1px solid var(--garis);
            border-radius: 18px;
            padding: 24px;
            margin-bottom: 18px;
        }

        .card h2 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 21px;
            margin-bottom: 15px;
        }

        .detail {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 15px;
            padding: 13px 0;
            border-bottom: 1px solid #eeeef6;
            line-height: 1.6;
        }

        .detail:last-of-type {
            border-bottom: none;
        }

        .label {
            color: var(--abu);
            flex-shrink: 0;
        }

        .alat {
            max-width: 65%;
            text-align: right;
            font-weight: 700;
        }

        .btn {
            display: inline-block;
            border: none;
            background: var(--kuning);
            color: var(--teks);
            padding: 13px 18px;
            border-radius: 10px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            margin-top: 18px;
        }

        .btn:hover {
            background: #f0b900;
        }

        .status-menunggu {
            margin-top: 18px;
            color: #795900;
            font-weight: 700;
            line-height: 1.6;
        }

        .kosong {
            background: white;
            border: 1px dashed #cdd0e8;
            border-radius: 18px;
            padding: 45px 20px;
            text-align: center;
            color: var(--abu);
            line-height: 1.8;
        }

        .kosong h2 {
            color: var(--teks);
            margin-bottom: 10px;
        }

        @media (max-width: 900px) {
            .container {
                width: 90%;
            }
        }

        @media (max-width: 700px) {
            .navbar {
                padding: 18px 6%;
                align-items: flex-start;
                flex-direction: column;
            }

            .nav-links {
                gap: 5px;
            }

            .nav-links a {
                padding: 9px;
                font-size: 12px;
            }

            .container {
                width: 90%;
                margin-top: 22px;
            }

            .hero {
                padding: 24px;
            }

            .hero h1 {
                font-size: 27px;
            }

            .card {
                padding: 18px;
            }

            .detail {
                flex-direction: column;
                gap: 5px;
            }

            .alat {
                max-width: 100%;
                text-align: left;
            }
        }
    </style>
</head>

<body>

<header class="navbar">
    <a href="index.php" class="logo">
        <div class="logo-mark">P</div>
        <div>
            <div class="logo-name">Pinja<span>min</span></div>
            <small>SARANA &amp; PRASARANA</small>
        </div>
    </a>

    <nav class="nav-links">
        <a href="index.php">Dashboard</a>
        <a href="alat.php">Daftar Alat</a>
        <a href="riwayat.php">Riwayat</a>
        <a href="pengembalian.php" class="active">Pengembalian</a>
        <a href="../logout.php" class="logout"
           onclick="return confirm('Yakin ingin keluar?')">Logout</a>
        <a href="#" class="user-name"><?= e($peminjam['username']) ?></a>
    </nav>
</header>

<main class="container">

    <section class="hero">
        <small>SELESAI MENGGUNAKAN ALAT?</small>
        <h1>Pengembalian Alat</h1>
        <p>Pilih peminjaman yang ingin kamu kembalikan. Petugas akan memeriksa pengembalianmu.</p>
    </section>

    <?php if (count($data) > 0): ?>

        <?php foreach ($data as $row): ?>
            <?php
            mysqli_stmt_bind_param(
                $cek_pengembalian,
                "i",
                $row['id_peminjaman']
            );
            mysqli_stmt_execute($cek_pengembalian);
            $sudah_ada = mysqli_stmt_get_result($cek_pengembalian);
            $pengembalian_diajukan = mysqli_num_rows($sudah_ada) > 0;
            ?>

            <section class="card">
                <h2>
                    Peminjaman #<?= (int) $row['id_peminjaman'] ?>
                </h2>

                <div class="detail">
                    <span class="label">Alat dipinjam</span>
                    <span class="alat"><?= e($row['daftar_alat']) ?></span>
                </div>

                <div class="detail">
                    <span class="label">Tanggal pinjam</span>
                    <strong><?= e($row['tgl_pinjam']) ?></strong>
                </div>

                <div class="detail">
                    <span class="label">Rencana pengembalian</span>
                    <strong><?= e($row['tgl_rencana_kembali']) ?></strong>
                </div>

                <?php if ($pengembalian_diajukan): ?>
                    <p class="status-menunggu">
                        Pengembalian sudah diajukan dan menunggu pemeriksaan Petugas.
                    </p>
                <?php else: ?>
                    <form
                        action="proses_pengembalian.php"
                        method="POST"
                        onsubmit="return confirm('Ajukan pengembalian alat ini?')"
                    >
                        <input
                            type="hidden"
                            name="id_peminjaman"
                            value="<?= (int) $row['id_peminjaman'] ?>"
                        >

                        <button type="submit" class="btn">
                            Ajukan Pengembalian →
                        </button>
                    </form>
                <?php endif; ?>
            </section>

        <?php endforeach; ?>

    <?php else: ?>

        <section class="kosong">
            <h2>Tidak Ada Alat yang Sedang Dipinjam</h2>
            Pengajuan yang sudah disetujui akan muncul di halaman ini.
            <br>
            <a href="riwayat.php" class="btn">Lihat Riwayat</a>
        </section>

    <?php endif; ?>

</main>

</body>
</html>