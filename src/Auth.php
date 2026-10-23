<?php

declare(strict_types=1);

namespace App;

use PDO;
use RuntimeException;

final class Auth
{
    public const SESSION_USER = 'user_id';
    public const SESSION_CSRF = 'csrf_token';

    public function __construct(private PDO $pdo) {}

    /** Start (or resume) the session with safe cookie settings. */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            // 'secure' => true,   // enable in production over HTTPS
            'path'     => '/',
        ]);

        session_start();
    }

    public function currentUserId(): ?string
    {
        $id = $_SESSION[self::SESSION_USER] ?? null;
        return is_string($id) && $id !== '' ? $id : null;
    }

    public function currentUser(): ?array
    {
        $id = $this->currentUserId();
        if ($id === null) {
            return null;
        }
        $stmt = $this->pdo->prepare('SELECT id, username, created_at FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** @throws RuntimeException if rate limit exceeded */
    public function attemptLogin(string $username, string $password): bool
    {
        $username = trim($username);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if ($this->tooManyAttempts($username, $ip)) {
            throw new RuntimeException('Too many login attempts. Try again in a few minutes.');
        }

        $stmt = $this->pdo->prepare('SELECT id, password_hash FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->recordAttempt($username, $ip);
            return false;
        }

        // Success: rotate session ID (prevents session fixation),
        // wipe old session data, set new user.
        session_regenerate_id(true);
        $_SESSION = [];
        $_SESSION[self::SESSION_USER] = $user['id'];

        return true;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
    }

    /**
     * Register a new user. Returns the new user id on success.
     * @throws RuntimeException on duplicate username or bad input
     */
    public function register(string $username, string $password): string
    {
        $username = trim($username);

        if (strlen($username) < 3 || strlen($username) > 64) {
            throw new RuntimeException('Username must be 3–64 characters.');
        }
        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $username)) {
            throw new RuntimeException('Username may contain letters, digits, dots, dashes and underscores.');
        }
        if (strlen($password) < 8) {
            throw new RuntimeException('Password must be at least 8 characters.');
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $id = bin2hex(random_bytes(8));
            $stmt = $this->pdo->prepare(
                'INSERT INTO users (id, username, password_hash, created_at) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$id, $username, $hash, gmdate('Y-m-d H:i:s')]);
            return $id;
        } catch (\PDOException $e) {
            // 23000 = integrity constraint (duplicate username)
            if ($e->getCode() === '23000') {
                throw new RuntimeException('That username is already taken.');
            }
            throw $e;
        }
    }

    // --- CSRF ---------------------------------------------------------

    public function csrfToken(): string
    {
        if (empty($_SESSION[self::SESSION_CSRF])) {
            $_SESSION[self::SESSION_CSRF] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::SESSION_CSRF];
    }

    public function verifyCsrf(?string $token): void
    {
        $expected = $_SESSION[self::SESSION_CSRF] ?? null;
        if (!is_string($expected) || !is_string($token) || !hash_equals($expected, $token)) {
            http_response_code(419);
            header('Content-Type: text/plain');
            echo "419 Page Expired — please reload and try again.";
            exit;
        }
    }

    // --- Rate limiting ------------------------------------------------

    private function tooManyAttempts(string $username, string $ip): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM login_attempts
             WHERE (username = ? OR ip = ?)
               AND attempted_at > ?'
        );
        $stmt->execute([$username, $ip, gmdate('Y-m-d H:i:s', time() - 300)]);
        return (int)$stmt->fetchColumn() >= 10;
    }

    private function recordAttempt(string $username, string $ip): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO login_attempts (username, ip, attempted_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$username, $ip, gmdate('Y-m-d H:i:s')]);
    }
}
