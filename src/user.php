<?php
require_once __DIR__ . '/db.php';

function upsertUser(string $googleId, string $email, string $name): array {
    $db = getDb();

    $stmt = $db->prepare('SELECT * FROM users WHERE google_id = ?');
    $stmt->execute([$googleId]);
    $user = $stmt->fetch();

    if ($user) {
        $db->prepare('UPDATE users SET email = ?, name = ?, updated_at = NOW() WHERE id = ?')
           ->execute([$email, $name, $user['id']]);
        $user['email'] = $email;
        $user['name'] = $name;
    } else {
        $db->prepare('INSERT INTO users (google_id, email, name) VALUES (?, ?, ?)')
           ->execute([$googleId, $email, $name]);
        $stmt->execute([$googleId]);
        $user = $stmt->fetch();
    }

    return $user;
}

function getUserById(int $id): ?array {
    $db = getDb();
    $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}
