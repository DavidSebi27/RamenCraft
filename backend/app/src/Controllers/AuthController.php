<?php

namespace App\Controllers;

use App\Config\Database;
use App\Config\JWT;
use App\Framework\Controller;

/**
 * AuthController — handles registration and login
 *
 * Endpoints:
 *   POST /api/auth/register — create a new user account
 *   POST /api/auth/login    — authenticate and receive a JWT
 *   GET  /api/auth/me       — get current user from token
 */
class AuthController extends Controller
{
    /**
     * POST /api/auth/register
     *
     * Expects JSON: { "username": "...", "email": "...", "password": "..." }
     * Returns: the new user + a JWT token
     */
    public function register(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            // Validate required fields
            if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
                $this->sendErrorResponse('Username, email, and password are required', 400);
                return;
            }

            $username = trim($input['username']);
            $email = trim($input['email']);
            $password = $input['password'];

            // Validate username length
            if (strlen($username) < 3 || strlen($username) > 50) {
                $this->sendErrorResponse('Username must be between 3 and 50 characters', 400);
                return;
            }

            // Validate email format
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->sendErrorResponse('Invalid email format', 400);
                return;
            }

            // Validate password length
            if (strlen($password) < 6) {
                $this->sendErrorResponse('Password must be at least 6 characters', 400);
                return;
            }

            $db = Database::getConnection();

            // Check if username or email already exists
            $check = $db->prepare(
                "SELECT id FROM users WHERE username = :username OR email = :email"
            );
            $check->execute([':username' => $username, ':email' => $email]);

            if ($check->fetch()) {
                $this->sendErrorResponse('Username or email already taken', 409);
                return;
            }

            // Hash the password with bcrypt
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);

            // Insert the new user
            $stmt = $db->prepare(
                "INSERT INTO users (username, email, password_hash, role, total_xp, current_rank)
                 VALUES (:username, :email, :password_hash, 'player', 0, 'minarai')"
            );
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $passwordHash,
            ]);

            $userId = (int) $db->lastInsertId();

            // Fetch the created user (without password_hash)
            $fetch = $db->prepare(
                "SELECT id, username, email, role, total_xp, current_rank, created_at, updated_at
                 FROM users WHERE id = :id"
            );
            $fetch->bindValue(':id', $userId, \PDO::PARAM_INT);
            $fetch->execute();
            $user = $fetch->fetch();

            // Generate JWT
            $token = JWT::generate($user);

            $this->sendSuccessResponse([
                'user' => $this->formatUser($user),
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * POST /api/auth/login
     *
     * Expects JSON: { "email": "...", "password": "..." }
     * Returns: the user + a JWT token
     */
    public function login(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['email']) || empty($input['password'])) {
                $this->sendErrorResponse('Email and password are required', 400);
                return;
            }

            $db = Database::getConnection();

            // Fetch user by email (including password_hash for verification)
            $stmt = $db->prepare(
                "SELECT id, username, email, password_hash, role, total_xp, current_rank, created_at, updated_at
                 FROM users WHERE email = :email"
            );
            $stmt->execute([':email' => trim($input['email'])]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->sendErrorResponse('Invalid email or password', 401);
                return;
            }

            // Verify password against the stored hash
            if (!password_verify($input['password'], $user['password_hash'])) {
                $this->sendErrorResponse('Invalid email or password', 401);
                return;
            }

            // Generate JWT
            $token = JWT::generate($user);

            $this->sendSuccessResponse([
                'user' => $this->formatUser($user),
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            $this->sendErrorResponse('Login failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * GET /api/auth/me
     *
     * Returns the current authenticated user based on the JWT token.
     * Requires a valid Authorization: Bearer <token> header.
     */
    public function me(): void
    {
        try {
            $payload = $this->getAuthenticatedUser();
            if (!$payload) {
                $this->sendErrorResponse('Unauthorized', 401);
                return;
            }

            $db = Database::getConnection();

            $stmt = $db->prepare(
                "SELECT u.id, u.username, u.email, u.role, u.total_xp, u.current_rank,
                        u.created_at, u.updated_at,
                        COUNT(sb.id) AS bowls_served
                 FROM users u
                 LEFT JOIN served_bowls sb ON sb.user_id = u.id
                 WHERE u.id = :id
                 GROUP BY u.id"
            );
            $stmt->bindValue(':id', (int) $payload->sub, \PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();

            if (!$user) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            $this->sendSuccessResponse($this->formatUser($user));
        } catch (\Exception $e) {
            $this->sendErrorResponse('Failed to fetch user: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Extract and validate JWT from the Authorization header.
     *
     * @return object|null The decoded JWT payload, or null if invalid
     */
    private function getAuthenticatedUser(): ?object
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return JWT::validate($matches[1]);
    }

    /**
     * Format a user row to camelCase (never expose password_hash).
     */
    private function formatUser(array $row): array
    {
        $formatted = [
            'id' => (int) $row['id'],
            'username' => $row['username'],
            'email' => $row['email'],
            'role' => $row['role'],
            'totalXp' => (int) $row['total_xp'],
            'currentRank' => $row['current_rank'],
            'createdAt' => $row['created_at'],
            'updatedAt' => $row['updated_at'],
        ];

        if (isset($row['bowls_served'])) {
            $formatted['bowlsServed'] = (int) $row['bowls_served'];
        }

        return $formatted;
    }
}
