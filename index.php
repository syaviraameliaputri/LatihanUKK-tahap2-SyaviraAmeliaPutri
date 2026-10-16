<?php
session_start();
include 'koneksi.php';

$alatSekolah = [];
$kategoriList = [];

$query = mysqli_query($koneksi, "
    SELECT a.id_alat, a.nama_alat, a.stok, a.kondisi,
           k.id_kategori, k.nama_kategori
    FROM alat a
    LEFT JOIN kategori k ON a.id_kategori = k.id_kategori
    ORDER BY k.nama_kategori ASC, a.nama_alat ASC
");

if ($query) {
    while ($row = mysqli_fetch_assoc($query)) {
        $alatSekolah[] = $row;

        if (!empty($row['nama_kategori'])) {
            $kategoriList[$row['id_kategori']] = $row['nama_kategori'];
        }
    }
}

function e($value) {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

$paletKategori = ['#2338d6', '#d9480f', '#12805c', '#7c3aed', '#b45309', '#0b7285'];

function warnaKategori($id, $palet) {
    if (empty($id)) return '#5b6482';
    return $palet[(int) $id % count($palet)];
}

function hurufAwal($teks) {
    $teks = trim((string) $teks);

    if ($teks === '') return '?';

    return function_exists('mb_substr')
        ? mb_strtoupper(mb_substr($teks, 0, 1, 'UTF-8'), 'UTF-8')
        : strtoupper(substr($teks, 0, 1));
}

/* CEK LOGIN PEMINJAM */
$sudahLogin = isset($_SESSION['login'])
    && ($_SESSION['role'] ?? '') === 'peminjam'
    && isset($_SESSION['id_peminjam']);

$urlLogin = 'login.php?redirect=peminjam%2Findex.php';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pinjamin - Peminjaman Sarana dan Prasarana</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        :root {
            --ink: #131a33;
            --paper: #f3f5f9;
            --white: #ffffff;
            --cobalt: #2338d6;
            --cobalt-dark: #1a2aa8;
            --tape: #ffc933;
            --muted: #5b6482;
            --line: #dde2ee;
            --ok: #12805c;
            --bad: #b42318;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            padding-bottom: 5.5rem;
            background: var(--paper);
            color: var(--ink);
            font-family: "Figtree", system-ui, sans-serif;
            line-height: 1.6;
        }

        h1, h2, h3, .brand {
            font-family: "Bricolage Grotesque", "Figtree", sans-serif;
        }

        :focus-visible {
            outline: 3px solid var(--tape);
            outline-offset: 2px;
        }

        .btn-main, .btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .5rem;
            padding: .75rem 1.3rem;
            border: 2px solid var(--ink);
            border-radius: 10px;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: transform .15s ease, background .15s ease;
        }

        .btn-main {
            background: var(--tape);
            color: var(--ink);
        }

        .btn-main:hover {
            background: #ffd54f;
            color: var(--ink);
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: transparent;
            color: var(--ink);
        }

        .btn-ghost:hover {
            background: var(--ink);
            color: var(--white);
        }

        /* NAVBAR */

        .site-nav {
            background: var(--cobalt);
            color: var(--white);
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: .6rem;
            color: var(--white);
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: -.03em;
            text-decoration: none;
        }

        .brand:hover {
            color: var(--white);
        }

        .brand-mark {
            position: relative;
            width: 30px;
            height: 22px;
            background: var(--tape);
            border-radius: 4px 8px 8px 4px;
        }

        .brand-mark::after {
            content: "";
            position: absolute;
            left: 6px;
            top: 50%;
            width: 6px;
            height: 6px;
            margin-top: -3px;
            background: var(--cobalt);
            border-radius: 50%;
        }

        .site-nav .btn-ghost {
            border-color: rgba(255, 255, 255, .7);
            color: var(--white);
            padding: .55rem 1rem;
            font-size: .92rem;
        }

        .site-nav .btn-ghost:hover {
            background: var(--white);
            color: var(--cobalt);
        }

        .nav-user {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-user-name {
            color: white;
            font-size: .9rem;
            font-weight: 600;
        }

        /* HERO */

        .hero {
            padding: 3.5rem 0 5rem;
            background: var(--cobalt);
            color: var(--white);
        }

        .hero h1 {
            max-width: 15ch;
            margin: 0 0 1.1rem;
            font-size: clamp(2.8rem, 6.5vw, 5.4rem);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -.045em;
        }

        .hero-copy {
            max-width: 46ch;
            margin-bottom: 1.75rem;
            color: #d6dcff;
            font-size: 1.1rem;
        }

        .search-box {
            display: flex;
            max-width: 520px;
            padding: .4rem;
            background: var(--white);
            border-radius: 14px;
            box-shadow: 0 18px 40px -22px rgba(0, 0, 0, .6);
        }

        .search-box input {
            flex: 1;
            min-width: 0;
            padding: .7rem .9rem;
            border: 0;
            background: transparent;
            color: var(--ink);
            font: inherit;
            font-size: 1rem;
        }

        .search-box input:focus {
            outline: none;
        }

        .search-box:focus-within {
            outline: 3px solid var(--tape);
            outline-offset: 2px;
        }

        .search-box .btn-main {
            padding: .6rem 1.2rem;
            border-radius: 10px;
        }

        .how-card {
            padding: 1.6rem;
            background: var(--white);
            color: var(--ink);
            border-radius: 18px;
            box-shadow: 0 30px 60px -35px rgba(0, 0, 0, .7);
        }

        .how-card h2 {
            margin: 0 0 1rem;
            font-size: 1.25rem;
            font-weight: 700;
        }

        .how-card ol {
            margin: 0;
            padding: 0;
            list-style: none;
            counter-reset: langkah;
        }

        .how-card li {
            display: flex;
            align-items: center;
            gap: .9rem;
            padding: .8rem 0;
            border-top: 1px solid var(--line);
            counter-increment: langkah;
        }

        .how-card li::before {
            content: counter(langkah);
            flex: none;
            display: grid;
            place-items: center;
            width: 30px;
            height: 30px;
            background: var(--tape);
            border-radius: 50%;
            font-family: "Bricolage Grotesque", sans-serif;
            font-weight: 800;
        }

        /* INVENTARIS */

        .inventory-section {
            padding: 3.5rem 0 3rem;
            margin-top: -2rem;
        }

        .inventory-head {
            display: flex;
            flex-wrap: wrap;
            align-items: end;
            justify-content: space-between;
            gap: .75rem;
            margin-bottom: 1.25rem;
        }

        .section-heading {
            margin: 0;
            font-size: clamp(1.9rem, 4vw, 2.8rem);
            font-weight: 800;
            line-height: 1.1;
            letter-spacing: -.035em;
        }

        .hasil-info {
            margin: 0;
            color: var(--muted);
            font-size: .95rem;
        }

        .category-list {
            display: flex;
            gap: .55rem;
            margin-bottom: 1.75rem;
            padding-bottom: .3rem;
            overflow-x: auto;
        }

        .category-button {
            flex: none;
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .5rem 1rem;
            border: 2px solid var(--line);
            border-radius: 999px;
            background: var(--white);
            color: var(--ink);
            font: inherit;
            font-size: .92rem;
            font-weight: 600;
            cursor: pointer;
            transition: border-color .15s ease, background .15s ease;
        }

        .category-button .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: var(--c, var(--ink));
        }

        .category-button:hover {
            border-color: var(--ink);
        }

        .category-button.active {
            background: var(--ink);
            border-color: var(--ink);
            color: var(--white);
        }

        .item-card {
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
            border: 2px solid var(--line);
            border-radius: 16px;
            background: var(--white);
            transition: border-color .15s ease, box-shadow .15s ease;
        }

        .item-card::before {
            content: "";
            height: 8px;
            background: var(--c);
        }

        .item-card:hover {
            border-color: #b8c0d9;
        }

        .item-card.selected {
            border-color: var(--cobalt);
            box-shadow: 0 0 0 3px rgba(35, 56, 214, .15);
        }

        .item-card.unavailable {
            background: #f8f9fc;
        }

        .item-card.unavailable .item-name,
        .item-card.unavailable .item-tile {
            opacity: .55;
        }

        .item-body {
            display: flex;
            flex-direction: column;
            flex: 1;
            padding: 1.1rem 1.2rem 1.2rem;
        }

        .item-top {
            display: flex;
            align-items: center;
            gap: .85rem;
            margin-bottom: .9rem;
        }

        .item-tile {
            flex: none;
            display: grid;
            place-items: center;
            width: 52px;
            height: 52px;
            border-radius: 12px;
            background: color-mix(in srgb, var(--c) 14%, white);
            color: var(--c);
            font-family: "Bricolage Grotesque", sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
        }

        .item-category {
            margin: 0;
            color: var(--c);
            font-size: .85rem;
            font-weight: 700;
        }

        .item-name {
            margin: 0 0 .75rem;
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: -.02em;
        }

        .item-meta {
            display: flex;
            flex-wrap: wrap;
            gap: .4rem;
            margin-bottom: 1rem;
        }

        .chip {
            padding: .2rem .65rem;
            border-radius: 999px;
            background: var(--paper);
            color: var(--muted);
            font-size: .82rem;
            font-weight: 600;
        }

        .chip.ok {
            background: #e3f4ec;
            color: var(--ok);
        }

        .chip.low {
            background: #fff3d1;
            color: #8a5a00;
        }

        .chip.bad {
            background: #fde8e6;
            color: var(--bad);
        }

        .item-action {
            margin-top: auto;
        }

        .pilih-alat {
            width: 100%;
            padding: .65rem;
            border: 2px solid var(--ink);
            border-radius: 10px;
            background: var(--white);
            color: var(--ink);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s ease, color .15s ease;
        }

        .pilih-alat:hover {
            background: var(--ink);
            color: var(--white);
        }

        .item-card.selected .pilih-alat {
            border-color: var(--cobalt);
            background: var(--cobalt);
            color: var(--white);
        }

        .pilih-alat:disabled {
            border-color: var(--line);
            background: transparent;
            color: var(--muted);
            cursor: not-allowed;
        }

        .jumlah-alat {
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            margin-top: .8rem;
            font-weight: 600;
            font-size: .92rem;
        }

        .item-card.selected .jumlah-alat {
            display: flex;
        }

        .stepper {
            display: inline-flex;
            align-items: center;
            border: 2px solid var(--line);
            border-radius: 10px;
            overflow: hidden;
        }

        .stepper button {
            width: 38px;
            height: 38px;
            border: 0;
            background: var(--paper);
            color: var(--ink);
            font-size: 1.2rem;
            font-weight: 700;
            cursor: pointer;
        }

        .stepper button:hover {
            background: var(--line);
        }

        .stepper input {
            width: 52px;
            height: 38px;
            border: 0;
            text-align: center;
            font: inherit;
            font-weight: 700;
            -moz-appearance: textfield;
        }

        .stepper input::-webkit-outer-spin-button,
        .stepper input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .empty-state {
            padding: 3rem 1.5rem;
            border: 2px dashed #bcc4dc;
            border-radius: 16px;
            color: var(--muted);
            text-align: center;
        }

        .empty-state h3 {
            color: var(--ink);
            font-size: 1.4rem;
            font-weight: 700;
        }

        /* TRAY PENGAJUAN */

        .tray {
            position: fixed;
            left: 50%;
            bottom: 14px;
            z-index: 30;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            width: min(720px, calc(100% - 24px));
            padding: .8rem .8rem .8rem 1.25rem;
            background: var(--ink);
            color: var(--white);
            border-radius: 16px;
            box-shadow: 0 20px 40px -15px rgba(19, 26, 51, .6);
            transform: translate(-50%, 140%);
            visibility: hidden;
            transition: transform .3s ease, visibility .3s ease;
        }

        .tray.show {
            transform: translate(-50%, 0);
            visibility: visible;
        }

        .tray strong {
            display: block;
            font-size: 1.05rem;
        }

        .tray span {
            color: #b9c1e0;
            font-size: .88rem;
        }

        /* TENTANG */

        .about-section {
            padding: 3.5rem 0 4rem;
            background: var(--white);
            border-top: 1px solid var(--line);
        }

        .about-item {
            height: 100%;
            padding-top: 1rem;
            border-top: 4px solid var(--c);
        }

        .about-item h3 {
            margin-bottom: .4rem;
            font-size: 1.3rem;
            font-weight: 700;
        }

        .about-item p {
            margin: 0;
            color: var(--muted);
        }

        footer {
            padding: 1.5rem 0;
            background: var(--ink);
            color: var(--white);
        }

        footer small {
            color: #aab3d4;
        }

        @media (max-width: 767px) {
            .hero {
                padding: 2.5rem 0 4rem;
            }

            .nav-note {
                display: none;
            }

            .search-box {
                flex-wrap: wrap;
            }

            .search-box .btn-main {
                width: 100%;
                margin-top: .4rem;
            }
        }

        @media (max-width: 480px) {
            .tray {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
                padding: 1rem;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                transition: none !important;
                scroll-behavior: auto !important;
            }
        }
    </style>
</head>

<body>

<nav class="site-nav py-3">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <a class="brand" href="index.php">
            <span class="brand-mark" aria-hidden="true"></span>Pinjamin
        </a>

        <div class="nav-user">
            <?php if ($sudahLogin): ?>
                <span class="nav-user-name">
                    Halo, <?= e($_SESSION['username'] ?? 'Peminjam') ?>
                </span>
                <a href="peminjam/index.php" class="btn-ghost">Dashboard Peminjam</a>
                <a href="logout.php" class="btn-ghost"
                   onclick="return confirm('Yakin ingin keluar?')">Logout</a>
            <?php else: ?>
                <a href="login.php" class="btn-ghost">Login</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<header class="hero">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <h1>Pinjam alat sekolah tanpa antre.</h1>

                <p class="hero-copy">
                    Cari alat yang kamu butuhkan, pilih beberapa sekaligus,
                    lalu ajukan peminjaman setelah login.
                </p>

                <form class="search-box" id="form-cari" role="search">
                    <label for="cari" class="visually-hidden">Cari alat</label>
                    <input
                        type="search"
                        id="cari"
                        placeholder="Cari alat, misalnya proyektor"
                        autocomplete="off">
                    <button type="submit" class="btn-main">Cari alat</button>
                </form>
            </div>

            <div class="col-lg-5">
                <div class="how-card">
                    <h2>Cara meminjam</h2>
                    <ol>
                        <li>Login sebagai peminjam</li>
                        <li>Pilih alat dan jumlahnya</li>
                        <li>Tentukan tanggal pengembalian</li>
                        <li>Tunggu persetujuan petugas</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</header>

<section class="inventory-section" id="daftar-sarpras">
    <div class="container">
        <div class="inventory-head">
            <h2 class="section-heading">Sarana dan prasarana</h2>
            <p class="hasil-info" id="hasil-info" aria-live="polite"></p>
        </div>

        <div class="category-list" aria-label="Filter kategori">
            <button type="button" class="category-button active" data-category="all">
                Semua kategori
            </button>

            <?php foreach ($kategoriList as $idKategori => $namaKategori): ?>
                <button
                    type="button"
                    class="category-button"
                    style="--c: <?= e(warnaKategori($idKategori, $paletKategori)) ?>"
                    data-category="<?= (int) $idKategori ?>">
                    <span class="dot" aria-hidden="true"></span>
                    <?= e($namaKategori) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="row g-4" id="daftar-item">

            <?php if (count($alatSekolah) > 0): ?>

                <?php foreach ($alatSekolah as $item): ?>
                    <?php
                    $stok = (int) $item['stok'];
                    $kondisiBaik = $item['kondisi'] === 'baik';
                    $tersedia = $stok > 0 && $kondisiBaik;
                    $warna = warnaKategori($item['id_kategori'] ?? null, $paletKategori);
                    $idAlat = (int) $item['id_alat'];
                    ?>

                    <div
                        class="col-sm-6 col-lg-4 item-column"
                        data-category="<?= (int) ($item['id_kategori'] ?? 0) ?>"
                        data-nama="<?= e(strtolower($item['nama_alat'] . ' ' . ($item['nama_kategori'] ?? ''))) ?>">

                        <article class="item-card<?= $tersedia ? '' : ' unavailable' ?>"
                                 style="--c: <?= e($warna) ?>">
                            <div class="item-body">
                                <div class="item-top">
                                    <div class="item-tile" aria-hidden="true">
                                        <?= e(hurufAwal($item['nama_alat'])) ?>
                                    </div>
                                    <p class="item-category">
                                        <?= e($item['nama_kategori'] ?? 'Belum berkategori') ?>
                                    </p>
                                </div>

                                <h3 class="item-name"><?= e($item['nama_alat']) ?></h3>

                                <div class="item-meta">
                                    <span class="chip">
                                        Kondisi <?= e(ucfirst($item['kondisi'])) ?>
                                    </span>

                                    <?php if ($tersedia && $stok <= 3): ?>
                                        <span class="chip low">Sisa <?= $stok ?></span>
                                    <?php elseif ($tersedia): ?>
                                        <span class="chip ok">Stok <?= $stok ?></span>
                                    <?php elseif ($stok <= 0): ?>
                                        <span class="chip bad">Stok habis</span>
                                    <?php else: ?>
                                        <span class="chip bad">Tidak layak pinjam</span>
                                    <?php endif; ?>
                                </div>

                                <div class="item-action">
                                    <?php if ($tersedia): ?>
                                        <button
                                            type="button"
                                            class="pilih-alat"
                                            data-id="<?= $idAlat ?>"
                                            data-stok="<?= $stok ?>"
                                            aria-pressed="false"
                                            onclick="pilihAlat(this)">
                                            Pilih alat
                                        </button>

                                        <div class="jumlah-alat">
                                            <label for="qty-<?= $idAlat ?>">Jumlah</label>
                                            <div class="stepper">
                                                <button type="button"
                                                    aria-label="Kurangi jumlah <?= e($item['nama_alat']) ?>"
                                                    onclick="geserJumlah(this, -1)">&minus;</button>

                                                <input
                                                    type="number"
                                                    id="qty-<?= $idAlat ?>"
                                                    class="input-jumlah"
                                                    min="1"
                                                    max="<?= $stok ?>"
                                                    value="1"
                                                    inputmode="numeric"
                                                    onchange="ubahJumlah(this)"
                                                    oninput="perbaruiPilihan()">

                                                <button type="button"
                                                    aria-label="Tambah jumlah <?= e($item['nama_alat']) ?>"
                                                    onclick="geserJumlah(this, 1)">+</button>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <button type="button" class="pilih-alat" disabled>
                                            Tidak bisa dipinjam
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; ?>

                <div class="col-12 d-none" id="kosong-filter">
                    <div class="empty-state">
                        <h3 class="mb-2">Alat tidak ditemukan</h3>
                        <p class="mb-0">Coba kata kunci lain atau pilih kategori Semua.</p>
                    </div>
                </div>

            <?php else: ?>

                <div class="col-12">
                    <div class="empty-state">
                        <h3 class="mb-2">Belum ada data sarpras</h3>
                        <p class="mb-0">
                            Data akan muncul setelah Admin menambahkannya.
                        </p>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
</section>

<section class="about-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="about-item" style="--c: #2338d6">
                    <h3>Lihat stok dan kondisi</h3>
                    <p>Cek kategori, kondisi, dan jumlah perlengkapan sekolah sebelum meminjam.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="about-item" style="--c: #ffc933">
                    <h3>Pengajuan lebih mudah</h3>
                    <p>Login sebagai peminjam untuk mengajukan perlengkapan sekolah.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="about-item" style="--c: #12805c">
                    <h3>Pengembalian tercatat</h3>
                    <p>Petugas memantau status peminjaman dan pengembalian lewat sistem.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<footer>
    <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
        <a class="brand" href="index.php" style="font-size:1.3rem">
            <span class="brand-mark" aria-hidden="true"></span>Pinjamin
        </a>

        <small>Sistem Informasi Peminjaman Sarana dan Prasarana Sekolah &copy; 2026</small>
    </div>
</footer>

<div class="tray" id="tray" role="region" aria-label="Ringkasan pilihan">
    <div>
        <strong id="jumlah-pilihan">0 alat dipilih</strong>
        <span id="total-unit"></span>
    </div>

    <button type="button" class="btn-main" onclick="lanjutPeminjaman()">
        Ajukan peminjaman
    </button>
</div>

<script>
const tombolKategori = document.querySelectorAll('.category-button');
const kolomAlat = document.querySelectorAll('.item-column');
const kolomKosong = document.getElementById('kosong-filter');
const inputCari = document.getElementById('cari');

let kategoriAktif = 'all';

function terapkanFilter() {
    const kata = inputCari.value.trim().toLowerCase();
    let terlihat = 0;

    kolomAlat.forEach(kolom => {
        const cocokKategori = kategoriAktif === 'all' ||
            kolom.dataset.category === kategoriAktif;
        const cocokKata = kata === '' || kolom.dataset.nama.includes(kata);
        const tampil = cocokKategori && cocokKata;

        kolom.classList.toggle('d-none', !tampil);

        if (tampil) terlihat++;
    });

    if (kolomKosong) {
        kolomKosong.classList.toggle('d-none', terlihat !== 0);
    }

    const info = document.getElementById('hasil-info');

    if (info && kolomAlat.length > 0) {
        info.textContent = 'Menampilkan ' + terlihat +
            ' dari ' + kolomAlat.length + ' alat';
    }
}

tombolKategori.forEach(tombol => {
    tombol.addEventListener('click', () => {
        kategoriAktif = tombol.dataset.category;

        tombolKategori.forEach(item => item.classList.remove('active'));
        tombol.classList.add('active');

        terapkanFilter();
    });
});

inputCari.addEventListener('input', terapkanFilter);

document.getElementById('form-cari').addEventListener('submit', event => {
    event.preventDefault();
    terapkanFilter();

    document.getElementById('daftar-sarpras').scrollIntoView({
        behavior: 'smooth'
    });
});

terapkanFilter();

function jumlahValid(input) {
    const max = parseInt(input.max, 10) || 1;
    let nilai = parseInt(input.value, 10);

    if (!Number.isInteger(nilai) || nilai < 1) nilai = 1;
    if (nilai > max) nilai = max;

    return nilai;
}

function pilihAlat(tombol) {
    const kartu = tombol.closest('.item-card');
    const terpilih = kartu.classList.toggle('selected');

    tombol.setAttribute('aria-pressed', terpilih ? 'true' : 'false');
    tombol.textContent = terpilih ? '✓ Dipilih' : 'Pilih alat';

    if (terpilih) {
        kartu.querySelector('.input-jumlah').value = 1;
    }

    perbaruiPilihan();
}

function ubahJumlah(input) {
    input.value = jumlahValid(input);
    perbaruiPilihan();
}

function geserJumlah(tombol, arah) {
    const input = tombol.closest('.stepper').querySelector('.input-jumlah');
    input.value = jumlahValid(input) + arah;
    ubahJumlah(input);
}

function ambilPilihan() {
    const hasil = {};

    document.querySelectorAll('.item-card.selected').forEach(kartu => {
        const tombol = kartu.querySelector('.pilih-alat');
        const input = kartu.querySelector('.input-jumlah');
        const id = tombol.dataset.id;

        hasil[id] = jumlahValid(input);
    });

    return hasil;
}

function perbaruiPilihan() {
    const pilihan = ambilPilihan();
    const jumlahJenis = Object.keys(pilihan).length;
    const totalUnit = Object.values(pilihan).reduce((a, b) => a + b, 0);

    document.getElementById('jumlah-pilihan').textContent =
        jumlahJenis + ' alat dipilih';

    document.getElementById('total-unit').textContent =
        'Total ' + totalUnit + ' unit';

    document.getElementById('tray').classList.toggle('show', jumlahJenis > 0);
}

function lanjutPeminjaman() {
    const pilihan = ambilPilihan();

    if (Object.keys(pilihan).length === 0) {
        alert('Pilih minimal satu alat terlebih dahulu.');
        return;
    }

    <?php if (!$sudahLogin): ?>
        if (confirm('Kamu harus login sebagai peminjam terlebih dahulu. Login sekarang?')) {
            window.location.href = <?= json_encode($urlLogin) ?>;
        }
        return;
    <?php else: ?>
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'peminjam/form_peminjaman.php';

        Object.entries(pilihan).forEach(([id, jumlah]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'alat[' + id + ']';
            input.value = jumlah;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    <?php endif; ?>
}
</script>

</body>
</html>