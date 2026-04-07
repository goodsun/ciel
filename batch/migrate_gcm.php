#!/usr/bin/env php
<?php
// One-time migration: re-encrypt api_keys from CBC to GCM
// Also usable as APP_KEY rotation tool:
//   OLD_APP_KEY=xxx php batch/migrate_gcm.php

require __DIR__ . '/../src/bootstrap.php';

$db = getDb();
$rows = $db->query('SELECT id, encrypted_key, iv, tag FROM api_keys')->fetchAll();

echo "Migrating " . count($rows) . " key(s)...\n";

foreach ($rows as $row) {
    // Decrypt with current/legacy method
    $plain = decryptValue($row['encrypted_key'], $row['iv'], $row['tag'] ?? '');
    echo "  id={$row['id']}: decrypted OK (" . substr($plain, 0, 8) . "...)\n";

    // Re-encrypt with GCM
    $data = encryptValue($plain);
    $db->prepare('UPDATE api_keys SET encrypted_key = ?, iv = ?, tag = ? WHERE id = ?')
       ->execute([$data['encrypted'], $data['iv'], $data['tag'], $row['id']]);
    echo "  id={$row['id']}: re-encrypted with GCM\n";
}

echo "Done.\n";
