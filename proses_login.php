```php
<?php
session_start();
require_once 'koneksi.php';

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    header("Location: login.php?pesan=gagal");
    exit;
}

function cekPassword($password, $hash) {
    return hash_equals((string) $hash, md5($password))
        || password_verify($password, $hash);
}

// LOGIN ADMIN
$query = mysqli_prepare(
    $koneksi,
    "SELECT id_admin, username, password
     FROM admin
     WHERE username = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($query, "s", $username);
mysqli_stmt_execute($query);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($query));

if ($data && cekPassword($password, $data['password'])) {
    session_regenerate_id(true);

    $_SESSION['login'] = true;
    $_SESSION['tipe'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['id_admin'] = $data['id_admin'];
    $_SESSION['username'] = $data['username'];

    header("Location: admin/index.php");
    exit;
}

// LOGIN PETUGAS
$query = mysqli_prepare(
    $koneksi,
    "SELECT id_petugas, username, password
     FROM petugas
     WHERE username = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($query, "s", $username);
mysqli_stmt_execute($query);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($query));

if ($data && cekPassword($password, $data['password'])) {
    session_regenerate_id(true);

    $_SESSION['login'] = true;
    $_SESSION['tipe'] = 'petugas';
    $_SESSION['role'] = 'petugas';
    $_SESSION['id_petugas'] = $data['id_petugas'];
    $_SESSION['username'] = $data['username'];

    header("Location: petugas/index.php");
    exit;
}

// LOGIN PEMINJAM
$query = mysqli_prepare(
    $koneksi,
    "SELECT id_peminjam, username, login_username, password, jenis_peminjam
     FROM peminjam
     WHERE login_username = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($query, "s", $username);
mysqli_stmt_execute($query);
$data = mysqli_fetch_assoc(mysqli_stmt_get_result($query));

if (
    $data &&
    !empty($data['password']) &&
    password_verify($password, $data['password'])
) {
    session_regenerate_id(true);

    $_SESSION['login'] = true;
    $_SESSION['tipe'] = 'peminjam';
    $_SESSION['role'] = 'peminjam';
    $_SESSION['id_peminjam'] = $data['id_peminjam'];
    $_SESSION['username'] = $data['username'];
    $_SESSION['login_username'] = $data['login_username'];
    $_SESSION['jenis_peminjam'] = $data['jenis_peminjam'];

    header("Location: peminjam/index.php");
    exit;
}

header("Location: login.php?pesan=gagal");
exit;
?>
```