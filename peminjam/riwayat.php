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
            p.status,
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
        GROUP BY
            p.id_peminjaman,
            p.tgl_pinjam,
            p.tgl_rencana_kembali,
            p.status,
            a1.nama_alat,
            p.jumlah
        ORDER BY p.id_peminjaman DESC";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, "i", $id_peminjam);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);

$total = 0;
$menunggu = 0;
$dipinjam = 0;
$ditolak = 0;
$dikembalikan = 0;
$data = [];

while ($row = mysqli_fetch_assoc($hasil)) {
    $data[] = $row;
    $total++;

    if ($row['status'] === 'menunggu') {
        $menunggu++;
    }

    if ($row['status'] === 'dipinjam') {
        $dipinjam++;
    }

    if ($row['status'] === 'ditolak') {
        $ditolak++;
    }

    if ($row['status'] === 'dikembalikan') {
        $dikembalikan++;
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Peminjaman - Pinjamin</title>

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
            max-width: 1200px;
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

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat {
            background: white;
            border: 1px solid var(--garis);
            border-radius: 15px;
            padding: 20px;
        }

        .stat span {
            color: var(--abu);
            font-size: 13px;
        }

        .stat h2 {
            color: var(--biru);
            font-size: 28px;
            margin-top: 10px;
        }

        .panel {
            background: white;
            border: 1px solid var(--garis);
            border-radius: 18px;
            padding: 22px;
        }

        .panel h2 {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 21px;
            margin-bottom: 20px;
        }

        .table-wrap {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        th {
            background: #f0f1ff;
            color: var(--biru);
            text-align: left;
            padding: 14px;
            font-size: 12px;
            text-transform: uppercase;
        }

        td {
            padding: 16px 14px;
            border-bottom: 1px solid #eeeef6;
            font-size: 14px;
            line-height: 1.6;
        }

        tbody tr:hover {
            background: #fafaff;
        }

        .status {
            display: inline-block;
            padding: 7px 11px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            white-space: nowrap;
        }

        .menunggu {
            background: #fff0bd;
            color: #795900;
        }

        .dipinjam {
            background: #dce7ff;
            color: #2446b5;
        }

        .ditolak {
            background: #ffe1e1;
            color: #b42323;
        }

        .dikembalikan {
            background: #d9f8e7;
            color: #147342;
        }

        .kosong {
            padding: 40px 15px;
            text-align: center;
            color: var(--abu);
            line-height: 1.8;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            background: var(--kuning);
            color: var(--teks);
            padding: 12px 18px;
            border-radius: 10px;
            font-weight: 700;
            margin-top: 15px;
        }

        .btn:hover {
            background: #f0b900;
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
                margin-top: 22px;
            }

            .hero {
                padding: 24px;
            }

            .hero h1 {
                font-size: 27px;
            }

            .panel {
                padding: 16px;
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
        <a href="riwayat.php" class="active">Riwayat</a>
        <a href="pengembalian.php">Pengembalian</a>
        <a href="../logout.php" class="logout"
           onclick="return confirm('Yakin ingin keluar?')">Logout</a>
        <a href="#" class="user-name"><?= e($peminjam['username']) ?></a>
    </nav>
</header>

<main class="container">

    <section class="hero">
        <small>AKTIVITAS AKUN</small>
        <h1>Riwayat Peminjaman</h1>
        <p>Pantau semua pengajuan dan status peminjaman alatmu di sini.</p>
    </section>

    <section class="stats">
        <div class="stat">
            <span>Total Pengajuan</span>
            <h2><?= $total ?></h2>
        </div>

        <div class="stat">
            <span>Menunggu</span>
            <h2><?= $menunggu ?></h2>
        </div>

        <div class="stat">
            <span>Sedang Dipinjam</span>
            <h2><?= $dipinjam ?></h2>
        </div>

        <div class="stat">
            <span>Dikembalikan</span>
            <h2><?= $dikembalikan ?></h2>
        </div>
    </section>

    <section class="panel">
        <h2>Daftar Pengajuan</h2>

        <?php if (count($data) > 0): ?>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No. Pengajuan</th>
                            <th>Daftar Alat</th>
                            <th>Tanggal Pinjam</th>
                            <th>Rencana Kembali</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <td>#<?= (int) $row['id_peminjaman'] ?></td>
                                <td><?= e($row['daftar_alat']) ?></td>
                                <td><?= e($row['tgl_pinjam']) ?></td>
                                <td><?= e($row['tgl_rencana_kembali']) ?></td>
                                <td>
                                    <span class="status <?= e($row['status']) ?>">
                                        <?= e(ucfirst($row['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="kosong">
                <h3>Belum ada riwayat peminjaman</h3>
                Pengajuan yang kamu kirim akan muncul di halaman ini.
                <br>
                <a href="alat.php" class="btn">Lihat Daftar Alat</a>
            </div>
        <?php endif; ?>
    </section>

</main>

</body>
</html>