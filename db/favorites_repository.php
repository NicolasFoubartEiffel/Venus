<?php

function createUserIfMissing(string $uid, string $username): void
{
    global $pdo;

    $stmt = $pdo->prepare("
        INSERT INTO users (uid, username)
        VALUES (:uid, :username)
        ON CONFLICT (uid) DO NOTHING
    ");

    $stmt->execute([
        ':uid' => $uid,
        ':username' => $username,
    ]);
}

function addFavorite(string $uid, string $username, int $projectId): void
{
    global $pdo;

    createUserIfMissing($uid, $username);

    $stmt = $pdo->prepare("
        INSERT INTO user_favorite (uid, project_id)
        VALUES (:uid, :project_id)
        ON CONFLICT (uid, project_id) DO NOTHING
    ");

    $stmt->execute([
        ':uid' => $uid,
        ':project_id' => $projectId,
    ]);
}

function deleteFavorite(string $uid, int $projectId): void
{
    global $pdo;

    $stmt = $pdo->prepare("
        DELETE FROM user_favorite
        WHERE uid = :uid
          AND project_id = :project_id
    ");

    $stmt->execute([
        ':uid' => $uid,
        ':project_id' => $projectId,
    ]);
}

function toggleFavorite(string $uid, string $username, int $projectId): bool
{
    global $pdo;

    createUserIfMissing($uid, $username);

    $stmt = $pdo->prepare("
        SELECT 1
        FROM user_favorite
        WHERE uid = :uid
          AND project_id = :project_id
        LIMIT 1
    ");

    $stmt->execute([
        ':uid' => $uid,
        ':project_id' => $projectId,
    ]);

    $exists = (bool) $stmt->fetchColumn();

    if ($exists) {
        deleteFavorite($uid, $projectId);
        return false;
    }

    addFavorite($uid, $username, $projectId);
    return true;
}

function getUserFavoriteProjectIds(string $uid): array
{
    global $pdo;

    $stmt = $pdo->prepare("
        SELECT project_id
        FROM user_favorite
        WHERE uid = :uid
    ");

    $stmt->execute([':uid' => $uid]);

    return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
}
