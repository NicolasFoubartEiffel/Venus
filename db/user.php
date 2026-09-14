<?php

function getAdminUsernames(): array
{
    static $cachedAdmins = null;

    if ($cachedAdmins !== null) {
        return $cachedAdmins;
    }

    $path = __DIR__ . '/../config/admins.php';

    if (!is_file($path)) {
        $cachedAdmins = [];
        return $cachedAdmins;
    }

    $admins = require $path;

    $cachedAdmins = is_array($admins) ? array_values(array_filter(array_map('trim', $admins))) : [];

    return $cachedAdmins;
}

function getCurrentLdapUser(): ?object
{
    global $USER;

    if (isset($USER) && is_object($USER)) {
        return $USER;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (isset($_SESSION['ldap_data'][0]) && is_array($_SESSION['ldap_data'][0])) {
        $USER = (object) $_SESSION['ldap_data'][0];
        return $USER;
    }

    return null;
}

function getCurrentLdapUid(): string
{
    $user = getCurrentLdapUser();

    return $user ? trim((string)($user->uid[0] ?? '')) : '';
}

function getCurrentLdapUserIdentity(): array
{
    $user = getCurrentLdapUser();

    $username = $user ? trim((string)($user->uid[0] ?? '')) : '';
    $name = $user ? trim((string)($user->displayname[0] ?? '')) : '';

    if ($name === '') {
        $name = $username !== '' ? $username : 'admin';
    }

    return [
        'username' => substr($username !== '' ? $username : 'admin', 0, 120),
        'name' => substr($name, 0, 160),
    ];
}

function isAdminUsername(string $username): bool
{
    return in_array(trim($username), getAdminUsernames(), true);
}

function isCurrentUserAdmin(): bool
{
    $uid = getCurrentLdapUid();

    return $uid !== '' && isAdminUsername($uid);
}

function redirectToLoginPage(): void
{
    $returnPath = $_SERVER['REQUEST_URI'] ?? '/apps/venus/index.php';

    if ($returnPath === '' || $returnPath[0] !== '/') {
        $returnPath = '/apps/venus/index.php';
    }

    header('Location: /apps/index.php?' . http_build_query(['app' => $returnPath], '', '&', PHP_QUERY_RFC3986));
    exit;
}

function requireCurrentAdminPage(): void
{
    if (!getCurrentLdapUser()) {
        redirectToLoginPage();
    }

    if (!isCurrentUserAdmin()) {
        http_response_code(403);
        echo 'Acces interdit.';
        exit;
    }
}

function requireCurrentAdminJson(): void
{
    header('Content-Type: application/json; charset=utf-8');

    if (!getCurrentLdapUser()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Utilisateur non connecte.',
        ]);
        exit;
    }

    if (!isCurrentUserAdmin()) {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Acces interdit.',
        ]);
        exit;
    }
}

function getCsrfToken(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function isValidCsrfToken($token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $sessionToken = $_SESSION['csrf_token'] ?? '';

    return is_string($sessionToken) && hash_equals($sessionToken, $token);
}

function requireValidCsrfTokenJson(): void
{
    if (isValidCsrfToken($_POST['csrf_token'] ?? '')) {
        return;
    }

    header('Content-Type: application/json; charset=utf-8');
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Jeton de securite invalide.',
    ]);
    exit;
}
