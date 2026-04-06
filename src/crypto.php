<?php

function encryptValue(string $plaintext): array {
    $key = getenv('APP_KEY');
    if (!$key) throw new RuntimeException('APP_KEY not configured');
    $key = hash('sha256', $key, true);
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    return ['encrypted' => $encrypted, 'iv' => $iv];
}

function decryptValue(string $encrypted, string $iv): string {
    $key = getenv('APP_KEY');
    if (!$key) throw new RuntimeException('APP_KEY not configured');
    $key = hash('sha256', $key, true);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
}

function storeApiKey(string $label, string $plainKey, string $provider = 'runpod'): int {
    $data = encryptValue($plainKey);
    $db = getDb();
    $db->prepare('INSERT INTO api_keys (label, provider, encrypted_key, iv) VALUES (?, ?, ?, ?)')
       ->execute([$label, $provider, $data['encrypted'], $data['iv']]);
    return (int)$db->lastInsertId();
}

function getApiKey(int $id): ?string {
    static $cache = [];
    if (isset($cache[$id])) return $cache[$id];
    $db = getDb();
    $stmt = $db->prepare('SELECT encrypted_key, iv FROM api_keys WHERE id = ? AND is_active = 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;
    $cache[$id] = decryptValue($row['encrypted_key'], $row['iv']);
    return $cache[$id];
}

function getApiKeyForEndpoint(string $endpointId): ?string {
    $db = getDb();
    $stmt = $db->prepare('SELECT api_key_id FROM endpoints WHERE endpoint_id = ? AND is_active = 1');
    $stmt->execute([$endpointId]);
    $keyId = $stmt->fetchColumn();
    if (!$keyId) return null;
    return getApiKey((int)$keyId);
}
