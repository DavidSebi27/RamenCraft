<?php

namespace App\Controllers;

use App\Config\JWT;
use App\Framework\Controller;
use App\Models\User;
use App\Repositories\UserRepository;

/**
 * AuthController — HTTP layer for authentication.
 *
 * No SQL here. User data access goes through UserRepository.
 */
class AuthController extends Controller
{
    /**
     * POST /api/auth/register
     */
    public function register(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['username']) || empty($input['email']) || empty($input['password'])) {
                $this->sendErrorResponse('All fields are required', 400);
                return;
            }

            if (strlen($input['password']) < 6) {
                $this->sendErrorResponse('Password must be at least 6 characters', 400);
                return;
            }

            $repo = new UserRepository();
            $passwordHash = password_hash($input['password'], PASSWORD_BCRYPT);
            $user = $repo->register($input['username'], $input['email'], $passwordHash);

            $token = JWT::generate([
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
            ]);

            $this->sendSuccessResponse([
                'user' => $user->toArray(),
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Registration failed', 500);
        }
    }

    /**
     * POST /api/auth/login
     */
    public function login(): void
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);

            if (empty($input['email']) || empty($input['password'])) {
                $this->sendErrorResponse('Email and password are required', 400);
                return;
            }

            $repo = new UserRepository();
            $user = $repo->findByEmail($input['email']);

            if (!$user) {
                $this->sendErrorResponse('Invalid email or password', 401);
                return;
            }

            if (!password_verify($input['password'], $user->password_hash)) {
                $this->sendErrorResponse('Invalid email or password', 401);
                return;
            }

            $token = JWT::generate([
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
            ]);

            $this->sendSuccessResponse([
                'user' => $user->toArray(),
                'token' => $token,
            ]);
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Login failed', 500);
        }
    }

    /**
     * GET /api/auth/me
     */
    public function me(): void
    {
        try {
            $payload = $this->getAuthenticatedUser();
            if (!$payload) {
                $this->sendErrorResponse('Unauthorized', 401);
                return;
            }

            $repo = new UserRepository();
            $user = $repo->findByIdWithBowlCount((int) $payload->sub);

            if (!$user) {
                $this->sendErrorResponse('User not found', 404);
                return;
            }

            $this->sendSuccessResponse($user->toArray());
        } catch (\Exception $e) {
            error_log($e->getMessage());
            $this->sendErrorResponse('Failed to fetch user', 500);
        }
    }

    /**
     * Extract and validate JWT from the Authorization header.
     */
    private function getAuthenticatedUser(): ?object
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return JWT::validate($matches[1]);
    }
}
