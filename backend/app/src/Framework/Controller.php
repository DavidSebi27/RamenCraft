<?php

namespace App\Framework;

use App\Config\JWT;

class Controller
{
    public function __construct()
    {
    }

    /**
     * Require a valid JWT token. Returns the decoded payload or sends 401 and exits.
     *
     * @return object The decoded JWT payload (with ->sub, ->role, etc.)
     */
    protected function authenticate(): object
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $this->sendErrorResponse('Unauthorized — no token provided', 401);
            exit;
        }

        $payload = JWT::validate($matches[1]);

        if (!$payload) {
            $this->sendErrorResponse('Unauthorized — invalid or expired token', 401);
            exit;
        }

        return $payload;
    }

    /**
     * Require the authenticated user to have the 'admin' role.
     * Calls authenticate() first, then checks the role.
     *
     * @return object The decoded JWT payload
     */
    protected function requireAdmin(): object
    {
        $payload = $this->authenticate();

        if ($payload->role !== 'admin') {
            $this->sendErrorResponse('Forbidden — admin access required', 403);
            exit;
        }

        return $payload;
    }

    protected function sendSuccessResponse($data = [], $code = 200)
    {
        header('Content-Type: application/json');
        http_response_code($code);
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    protected function sendErrorResponse($message, $code = 500)
    {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($code);
        echo json_encode(['error' => $message], JSON_PRETTY_PRINT);
    }

    /**
     * Maps POST data (JSON) to an instance of the specified class
     * 
     * @param string $className The fully qualified class name
     * @return object|null Returns an instance of the class or null if data is invalid
     */
    protected function mapPostDataToClass(string $className): ?object
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        $instance = new $className();
        
        foreach ($data as $key => $value) {
            if (property_exists($instance, $key)) {
                $instance->$key = $value;
            }
        }

        return $instance;
    }
}