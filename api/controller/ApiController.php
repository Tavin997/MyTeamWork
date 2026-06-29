<?php

namespace MyTeamWork\Controller;

abstract class ApiController
{
    protected const STATUS_OK = 200;
    protected const STATUS_CREATED = 201;
    protected const STATUS_BAD_REQUEST = 400;
    protected const STATUS_UNAUTHORIZED = 401;
    protected const STATUS_FORBIDDEN = 403;
    protected const STATUS_NOT_FOUND = 404;
    protected const STATUS_SERVER_ERROR = 500;

    protected function jsonResponse(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function success(array $data = [], string $message = 'Operação realizada com sucesso', int $statusCode = 200): void
    {
        $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    protected function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $this->jsonResponse($response, $statusCode);
    }

    protected function validateRequired(array $data, array $requiredFields): ?array
    {
        $errors = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[] = "O campo '$field' é obrigatório";
            }
        }

        return !empty($errors) ? $errors : null;
    }

    protected function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function sanitizeInput(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $value = strip_tags($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    protected function getRequestData(): array
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $data ?? [];
    }

    protected function getUrlParams(): array
    {
        $params = [];
        $path = explode('/', $_SERVER['REQUEST_URI'] ?? '');
        
        foreach ($path as $segment) {
            if (is_numeric($segment)) {
                $params['id'] = (int) $segment;
            }
        }

        return $params;
    }

    protected function logError(string $message, array $context = []): void
    {
        $logMessage = sprintf(
            '[%s] %s | Context: %s',
            date('Y-m-d H:i:s'),
            $message,
            json_encode($context)
        );

        error_log($logMessage);
    }
}