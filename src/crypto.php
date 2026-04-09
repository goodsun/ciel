<?php

function deriveKey(): string {
    $key = getenv('APP_KEY');
    if (!$key) throw new RuntimeException('APP_KEY not configured');
    return hash_hkdf('sha256', hex2bin($key), 32, 'ciel-api-key-v1');
}

function encryptValue(string $plaintext): array {
    $key = deriveKey();
    $iv = random_bytes(12);
    $tag = '';
    $encrypted = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($encrypted === false) throw new RuntimeException('Encryption failed');
    return ['encrypted' => $encrypted, 'iv' => $iv, 'tag' => $tag];
}

function decryptValue(string $encrypted, string $iv, string $tag = ''): string {
    $key = deriveKey();
    // GCM (iv=12 bytes, tag present) or legacy CBC (iv=16 bytes, no tag)
    if (strlen($iv) === 12 && $tag !== '') {
        $result = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    } else {
        // Legacy CBC fallback for pre-migration data
        $legacyKey = hash('sha256', getenv('APP_KEY'), true);
        $result = openssl_decrypt($encrypted, 'aes-256-cbc', $legacyKey, OPENSSL_RAW_DATA, $iv);
    }
    if ($result === false) throw new RuntimeException('Decryption failed');
    return $result;
}

function storeApiKey(string $label, string $plainKey, string $provider = 'runpod'): int {
    $data = encryptValue($plainKey);
    $db = getDb();
    $db->prepare('INSERT INTO api_keys (label, provider, encrypted_key, iv, tag) VALUES (?, ?, ?, ?, ?)')
       ->execute([$label, $provider, $data['encrypted'], $data['iv'], $data['tag']]);
    return (int)$db->lastInsertId();
}

function getApiKey(int $id): ?string {
    static $cache = [];
    if (isset($cache[$id])) return $cache[$id];
    $db = getDb();
    $stmt = $db->prepare('SELECT encrypted_key, iv, tag FROM api_keys WHERE id = ? AND is_active = 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $cache[$id] = decryptValue($row['encrypted_key'], $row['iv'], $row['tag'] ?? '');
    return $cache[$id];
}

function getApiKeyForEndpoint(string $endpointId): ?string {
    $db = getDb();
    if (isAdmin()) {
        $stmt = $db->prepare('SELECT api_key_id FROM endpoints WHERE endpoint_id = ?');
    } else {
        $stmt = $db->prepare('SELECT api_key_id FROM endpoints WHERE endpoint_id = ? AND is_active = 1');
    }
    $stmt->execute([$endpointId]);
    $keyId = $stmt->fetchColumn();
    if (!$keyId) return null;
    return getApiKey((int)$keyId);
}
