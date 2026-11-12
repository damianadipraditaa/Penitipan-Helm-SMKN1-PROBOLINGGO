<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $name = getenv('DB_NAME') ?: 'db_penitipan_helm';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    $pdo = new PDO("mysql:host={$host};dbname={$name};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function take_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return is_array($flash) ? $flash : null;
}

function redirect(string $location = 'index.php'): never
{
    header('Location: ' . $location);
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $submitted = (string) ($_POST['csrf_token'] ?? '');
    if ($submitted === '' || !hash_equals(csrf_token(), $submitted)) {
        set_flash('Permintaan tidak valid. Muat ulang halaman lalu coba lagi.', 'error');
        redirect();
    }
}

function database_error(PDOException $exception): never
{
    error_log($exception->getMessage());
    $driverCode = $exception->errorInfo[1] ?? null;
    if ($driverCode === 1062) {
        $detail = (string) ($exception->errorInfo[2] ?? '');
        if (str_contains($detail, 'uq_users_nisn')) {
            $message = 'NISN tersebut sudah terdaftar. Gunakan NISN yang berbeda.';
        } elseif (str_contains($detail, 'uq_users_username')) {
            $message = 'Username tersebut sudah digunakan. Pilih username lain.';
        } elseif (str_contains($detail, 'uq_penitipan_kode')) {
            $message = 'Kode penitipan sudah digunakan. Coba simpan kembali.';
        } elseif (str_contains($detail, 'uq_penitipan_slot_aktif')) {
            $message = 'Slot baru saja terpakai. Pilih slot lain.';
        } else {
            $message = 'Ada data unik yang sudah digunakan. Periksa NISN atau username.';
        }
        set_flash($message, 'error');
    } elseif ($driverCode === 1452) {
        set_flash('Data siswa atau slot tidak ditemukan.', 'error');
    } else {
        set_flash('Terjadi kesalahan saat menyimpan data. Periksa koneksi database.', 'error');
    }
    redirect();
}
