<?php

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require __DIR__ . '/db.php';
require __DIR__ . '/UserRepository.php';

$pdo = getConnection();

$pdo->exec('
    CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        email TEXT NOT NULL UNIQUE
    )
');

$userRepository = new UserRepository($pdo);

$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uriParts = array_values(array_filter(explode('/', $uri)));

if (!isset($uriParts[0]) || $uriParts[0] !== 'users') {
    http_response_code(404);
    echo json_encode([
        'error' => 'Not found',
    ]);
    exit;
}

$id = null;
if (isset($uriParts[1]) && is_numeric($uriParts[1])) {
    $id = (int) $uriParts[1];
}

try {
    if ($method === 'GET' && $id === null) {
        $users = $userRepository->getAll();

        http_response_code(200);
        echo json_encode([
            'data' => $users,
        ]);
        exit;
    }

    if ($method === 'GET' && $id !== null) {
        $user = $userRepository->getById($id);

        if ($user === null) {
            http_response_code(404);
            echo json_encode([
                'error' => 'User not found',
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'data' => $user,
        ]);
        exit;
    }

    if ($method === 'POST' && $id === null) {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid JSON',
            ]);
            exit;
        }

        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));

        if ($name === '' || $email === '') {
            http_response_code(400);
            echo json_encode([
                'error' => 'Name and email are required',
            ]);
            exit;
        }

        $newId = $userRepository->create($name, $email);

        http_response_code(201);
        echo json_encode([
            'message' => 'User created',
            'id' => $newId,
        ]);
        exit;
    }

    if ($method === 'PUT' && $id !== null) {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Invalid JSON',
            ]);
            exit;
        }

        $name = trim((string)($input['name'] ?? ''));
        $email = trim((string)($input['email'] ?? ''));

        if ($name === '' || $email === '') {
            http_response_code(400);
            echo json_encode([
                'error' => 'Name and email are required',
            ]);
            exit;
        }

        $updated = $userRepository->update($id, $name, $email);

        if (!$updated) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Could not update user',
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'message' => 'User updated',
        ]);
        exit;
    }

    if ($method === 'DELETE' && $id !== null) {
        $deleted = $userRepository->delete($id);

        if (!$deleted) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Could not delete user',
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'message' => 'User deleted',
        ]);
        exit;
    }

    http_response_code(405);
    echo json_encode([
        'error' => 'Method not allowed',
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
    ]);
}
