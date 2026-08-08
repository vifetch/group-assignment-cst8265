<?php
declare(strict_types=1);

function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_verify(string $token): bool {
    return isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

function encryption_key(): string {
    $keyFile = __DIR__ . '/../storage/encryption.key';
    if (!is_file($keyFile)) {
        throw new RuntimeException('Encryption key missing. Run setup.php.');
    }
    $key = file_get_contents($keyFile);
    if ($key === false || strlen($key) !== 32) {
        throw new RuntimeException('Encryption key must be exactly 32 bytes.');
    }
    return $key;
}

function encrypt_paste(string $plaintext, string $key): string {
    $cipher = 'aes-256-gcm';
    $iv = random_bytes(12);
    $tag = '';
    $ciphertext = openssl_encrypt($plaintext, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($ciphertext === false) {
        throw new RuntimeException('Encryption failed.');
    }
    return base64_encode($iv . $tag . $ciphertext);
}

function decrypt_paste(string $encoded, string $key): string {
    $data = base64_decode($encoded, true);
    if ($data === false || strlen($data) < 28) {
        throw new RuntimeException('Invalid encrypted paste.');
    }
    $iv = substr($data, 0, 12);
    $tag = substr($data, 12, 16);
    $ciphertext = substr($data, 28);
    $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plaintext === false) {
        throw new RuntimeException('Decryption failed.');
    }
    return $plaintext;
}
