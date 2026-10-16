<?php
session_start();
require_once 'koneksi.php';

$pesan = '';
$jenis = 'siswa';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $login_username = trim($_POST['login_username'] ?? '');
    $password = $_POST['password'] ?? '';
    $jenis = $_POST['jenis_peminjam'] ?? 'siswa';
    $nisn = trim($_POST['nisn'] ?? '');
    $kelas = trim($_POST['kelas'] ?? '');

    if (
        $nama === '' ||
        $login_username === '' ||
        $password === '' ||
        !in_array($jenis, ['siswa', 'guru'], true)
    ) {
        $pesan = 'Semua kolom wajib diisi.';
    } elseif (strlen($password) < 6) {
        $pesan = 'Password minimal 6 karakter.';
    } elseif ($jenis === 'siswa' && ($nisn === '' || $kelas === '')) {
        $pesan = 'NISN dan kelas wajib diisi untuk siswa.';
    } elseif ($jenis === 'siswa' && !preg_match('/^[0-9]{1,10}$/', $nisn)) {
        $pesan = 'NISN harus berupa angka, maksimal 10 digit.';
    } else {
        $cek = mysqli_prepare(
            $koneksi,
            "SELECT id_peminjam FROM peminjam WHERE login_username = ?"
        );
        mysqli_stmt_bind_param($cek, "s", $login_username);
        mysqli_stmt_execute($cek);
        mysqli_stmt_store_result($cek);

        if (mysqli_stmt_num_rows($cek) > 0) {
            $pesan = 'Username sudah digunakan. Gunakan username lain.';
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            if ($jenis === 'guru') {
                $nisn = '';
                $kelas = '';
            }

            $stmt = mysqli_prepare(
                $koneksi,
                "INSERT INTO peminjam
                (username, nisn, kelas, jenis_peminjam, login_username, password)
                VALUES (?, ?, ?, ?, ?, ?)"
            );

            mysqli_stmt_bind_param(
                $stmt,
                "ssssss",
                $nama,
                $nisn,
                $kelas,
                $jenis,
                $login_username,
                $password_hash
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: login.php?daftar=berhasil");
                exit;
            } else {
                $pesan = 'Pendaftaran gagal. Coba lagi.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun | Pinjamin</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 25px 15px;
            background: #211914;
            color: #f5eee5;
            font-family: Arial, sans-serif;
        }

        .card {
            width: 100%;
            max-width: 440px;
            padding: 32px;
            background: #30251f;
            border: 1px solid #554337;
            border-radius: 14px;
        }

        .logo {
            color: #d5b894;
            font-family: Georgia, serif;
            font-size: 29px;
            letter-spacing: 2px;
            text-align: center;
        }

        .subtitle {
            color: #b7a99d;
            text-align: center;
            font-size: 13px;
            margin: 8px 0 25px;
        }

        label {
            display: block;
            margin: 14px 0 7px;
            font-size: 13px;
            color: #e7d9ca;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #655044;
            border-radius: 7px;
            background: #211914;
            color: #f5eee5;
            outline: none;
        }

        input:focus, select:focus {
            border-color: #c6a57e;
        }

        button {
            width: 100%;
            margin-top: 23px;
            padding: 13px;
            border: none;
            border-radius: 7px;
            background: #c6a57e;
            color: #211914;
            font-weight: bold;
            cursor: pointer;
        }

        button:hover {
            background: #d8bc9a;
        }

        .pesan {
            padding: 11px;
            border-radius: 7px;
            background: #49302b;
            color: #ffd7ce;
            font-size: 13px;
            line-height: 1.5;
        }

        .link {
            text-align: center;
            color: #cbb9a8;
            font-size: 13px;
            margin-top: 20px;
        }

        a {
            color: #dfbd91;
            text-decoration: none;
        }

        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">PINJAMIN</div>
        <p class="subtitle">Daftar akun peminjam</p>

        <?php if ($pesan !== ''): ?>
            <div class="pesan">
                <?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <label>Nama lengkap</label>
            <input type="text" name="nama" required
                   value="<?= htmlspecialchars($_POST['nama'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label>Daftar sebagai</label>
            <select name="jenis_peminjam" id="jenis" onchange="aturForm()" required>
                <option value="siswa" <?= $jenis === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                <option value="guru" <?= $jenis === 'guru' ? 'selected' : '' ?>>Guru</option>
            </select>

            <div id="dataSiswa">
                <label>NISN</label>
                <input type="text" name="nisn" id="nisn" maxlength="10"
                       value="<?= htmlspecialchars($_POST['nisn'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <label>Kelas</label>
                <input type="text" name="kelas" id="kelas"
                       placeholder="Contoh: XII RPL 2"
                       value="<?= htmlspecialchars($_POST['kelas'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <label>Username</label>
            <input type="text" name="login_username" maxlength="50" required
                   value="<?= htmlspecialchars($_POST['login_username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

            <label>Password</label>
            <input type="password" name="password" minlength="6" required>

            <button type="submit">Daftar Akun</button>
        </form>

        <div class="link">
            Sudah punya akun? <a href="login.php">Login</a>
        </div>
    </div>

    <script>
        function aturForm() {
            const siswa = document.getElementById('jenis').value === 'siswa';
            document.getElementById('dataSiswa').classList.toggle('hidden', !siswa);
            document.getElementById('nisn').required = siswa;
            document.getElementById('kelas').required = siswa;
        }

        aturForm();
    </script>
</body>
</html>