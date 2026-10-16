<?php
session_start();
include 'koneksi.php';

$error = '';

if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/index.php');
        exit;
    }

    if ($_SESSION['role'] === 'petugas') {
        header('Location: petugas/index.php');
        exit;
    }

    if ($_SESSION['role'] === 'peminjam') {
        header('Location: peminjam/index.php');
        exit;
    }
}

if (isset($_GET['daftar']) && $_GET['daftar'] === 'berhasil') {
    $error = 'Pendaftaran berhasil. Silakan login.';
}

if (isset($_GET['pesan']) && $_GET['pesan'] === 'gagal') {
    $error = 'Username atau password salah.';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login - Pinjamin</title>

<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@400;500;600;700;800&family=Figtree:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
:root {
    --ink: #131A33;
    --blue: #2338D6;
    --blue-dark: #1929A8;
    --yellow: #FFC933;
    --paper: #F7F7FB;
    --white: #FFFFFF;
    --muted: #777B91;
    --line: #E7E8F0;
    --red: #C0392B;
}

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    font-family: 'Figtree', sans-serif;
    background: var(--paper);
    color: var(--ink);
}

.login-wrapper {
    min-height: 100vh;
    display: flex;
}

/* PANEL KIRI */

.brand-side {
    width: 52%;
    min-height: 100vh;
    background: var(--blue);
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px;
}

.circle-one,
.circle-two {
    position: absolute;
    border: 1px solid rgba(255, 255, 255, .15);
    border-radius: 50%;
}

.circle-one {
    width: 420px;
    height: 420px;
    top: -180px;
    left: -170px;
}

.circle-two {
    width: 560px;
    height: 560px;
    bottom: -300px;
    right: -250px;
}

.circle-three {
    position: absolute;
    width: 170px;
    height: 170px;
    background: rgba(255, 201, 51, .18);
    border-radius: 50%;
    top: 90px;
    right: 80px;
}

.brand-content {
    position: relative;
    z-index: 2;
    max-width: 520px;
    color: var(--white);
}

.brand-small {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 25px;
}

.brand-small-line {
    width: 45px;
    height: 2px;
    background: var(--yellow);
}

.brand-small-text {
    font-size: 10px;
    letter-spacing: 4px;
    font-weight: 700;
    color: var(--yellow);
}

.brand-title {
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 72px;
    font-weight: 800;
    letter-spacing: 2px;
    line-height: .95;
    margin: 0;
    color: var(--white);
}

.brand-subtitle {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 5px;
    margin-top: 18px;
    color: var(--yellow);
}

.brand-description {
    margin-top: 45px;
    max-width: 380px;
    color: #E0E5FF;
    font-size: 14px;
    line-height: 1.8;
}

.brand-bottom {
    margin-top: 60px;
    color: #C5CEFF;
    font-size: 11px;
    letter-spacing: 1px;
}

/* PANEL KANAN */

.form-side {
    width: 48%;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 50px;
    background: var(--paper);
}

.login-box {
    width: 100%;
    max-width: 390px;
}

.form-heading {
    margin-bottom: 38px;
}

.form-heading h2 {
    color: var(--ink);
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 34px;
    font-weight: 800;
    margin: 0 0 8px;
}

.form-heading p {
    color: var(--muted);
    font-size: 13px;
    margin: 0;
}

.heading-line {
    width: 45px;
    height: 4px;
    background: var(--yellow);
    margin-top: 18px;
    border-radius: 5px;
}

/* INPUT */

.form-group {
    margin-bottom: 22px;
}

.form-label {
    display: block;
    color: var(--ink);
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    margin-bottom: 9px;
}

.form-control {
    width: 100%;
    height: 49px;
    border: 1px solid var(--line);
    border-radius: 7px;
    background: var(--white);
    padding: 0 14px;
    font-family: 'Figtree', sans-serif;
    font-size: 13px;
    color: var(--ink);
    outline: none;
    transition: .2s;
}

.form-control::placeholder {
    color: #A1A4B5;
}

.form-control:focus {
    border-color: var(--blue);
    box-shadow: 0 0 0 3px rgba(35, 56, 214, .1);
}

/* TOMBOL LOGIN */

.btn-login {
    width: 100%;
    height: 49px;
    margin-top: 7px;
    border: 1px solid var(--blue);
    border-radius: 7px;
    background: var(--blue);
    color: var(--white);
    font-family: 'Figtree', sans-serif;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 1px;
    cursor: pointer;
    transition: .2s;
}

.btn-login:hover {
    background: var(--blue-dark);
    border-color: var(--blue-dark);
}

/* PESAN ERROR */

.alert-error {
    padding: 12px 14px;
    margin-bottom: 22px;
    background: #FCEBE9;
    border-left: 3px solid var(--red);
    border-radius: 4px;
    color: #922F25;
    font-size: 13px;
    line-height: 1.6;
}

/* LINK DAFTAR */

.register-link {
    margin-top: 22px;
    text-align: center;
    font-size: 12px;
    color: var(--muted);
}

.register-link a {
    color: var(--blue);
    font-weight: 700;
    text-decoration: none;
}

.register-link a:hover {
    color: var(--blue-dark);
    text-decoration: underline;
}

/* FOOTER */

.form-footer {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid var(--line);
    text-align: center;
    color: #999CAF;
    font-size: 10px;
    line-height: 1.8;
}

/* RESPONSIVE */

@media (max-width: 850px) {
    .login-wrapper {
        display: block;
    }

    .brand-side {
        width: 100%;
        min-height: 300px;
        padding: 40px 30px;
    }

    .brand-title {
        font-size: 52px;
    }

    .brand-description,
    .brand-bottom {
        display: none;
    }

    .circle-three {
        right: -30px;
        top: -50px;
    }

    .form-side {
        width: 100%;
        min-height: auto;
        padding: 45px 25px 60px;
    }
}

@media (max-width: 500px) {
    .brand-side {
        min-height: 250px;
    }

    .brand-title {
        font-size: 42px;
        letter-spacing: 1px;
    }

    .brand-subtitle {
        letter-spacing: 3px;
    }
}
</style>
</head>

<body>

<div class="login-wrapper">

    <section class="brand-side">
        <div class="circle-one"></div>
        <div class="circle-two"></div>
        <div class="circle-three"></div>

        <div class="brand-content">

            <div class="brand-small">
                <div class="brand-small-line"></div>
                <div class="brand-small-text">WELCOME TO</div>
            </div>

            <h1 class="brand-title">PINJAMIN</h1>

            <div class="brand-subtitle">
                SARANA &amp; PRASARANA
            </div>

            <p class="brand-description">
                Sistem peminjaman sarana dan prasarana sekolah untuk
                mengelola data alat, peminjaman, dan pengembalian.
            </p>

            <div class="brand-bottom">
                TERTIB · MUDAH · TERDATA
            </div>

        </div>
    </section>

    <section class="form-side">
        <div class="login-box">

            <div class="form-heading">
                <h2>Selamat Datang</h2>
                <p>Masuk untuk melanjutkan ke sistem.</p>
                <div class="heading-line"></div>
            </div>

            <?php if ($error !== ''): ?>
                <div class="alert-error">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="proses_login.php">

                <div class="form-group">
                    <label class="form-label" for="username">
                        USERNAME
                    </label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        class="form-control"
                        placeholder="Masukkan username"
                        autocomplete="username"
                        required
                    >
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">
                        PASSWORD
                    </label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control"
                        placeholder="Masukkan password"
                        autocomplete="current-password"
                        required
                    >
                </div>

                <button type="submit" class="btn-login">
                    MASUK
                </button>

            </form>

            <div class="register-link">
                Peminjam belum punya akun?
                <a href="register.php">Daftar di sini</a>
            </div>

            <div class="form-footer">
                Sistem Informasi Peminjaman Sarana dan Prasarana Sekolah
                &copy; 2026
            </div>

        </div>
    </section>

</div>

</body>
</html>