<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($uri === '/api/users') {
    $users = [
        ['id' => 1, 'name' => 'Nguyen Van A', 'email' => 'a.nguyen@example.com'],
        ['id' => 2, 'name' => 'Tran Thi B', 'email' => 'b.tran@example.com'],
        ['id' => 3, 'name' => 'Le Van C', 'email' => 'c.le@example.com'],
        ['id' => 4, 'name' => 'Pham Thi D', 'email' => 'd.pham@example.com'],
    ];

    echo json_encode($users);
    exit;
}

if ($uri === '/health') {
    echo json_encode(['status' => 'ok']);
    exit;
}

http_response_code(404);
echo json_encode(['message' => 'Not Found']);
