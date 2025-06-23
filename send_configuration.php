<?php
require_once 'headers.php';
require_once 'db.php';

// Получение данных из POST-запроса (ожидается JSON)
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректный JSON']);
    exit;
}

if (
    empty($data['name_configuration']) ||
    !isset($data['type_configuration']) ||
    !isset($data['price_configuration']) ||
    empty($data['components']) ||
    !is_array($data['components'])
) {
    http_response_code(400);
    echo json_encode(['error' => 'Не все обязательные поля заполнены']);
    exit;
}

try {
    $pdo = getDatabaseConnection();

    // 1. Вставляем запись в configuration (итоговая цена приходит с фронта!)
    $stmt = $pdo->prepare(
        "INSERT INTO configuration (name_configuration, price_configuration, type_configuration)
         VALUES (?, ?, ?)"
    );

    $stmt->execute([
        $data['name_configuration'],
        $data['price_configuration'],
        $data['type_configuration'],
    ]);
    $config_id = $pdo->lastInsertId();

    // 2. Вставляем все комплектующие в configuration_components
    $stmt = $pdo->prepare(
        "INSERT INTO configuration_components (id_configuration, id_component, quantity)
         VALUES (?, ?, ?)"
    );
    foreach ($data['components'] as $comp) {
        if (!isset($comp['id_component'])) continue;
        $qty = isset($comp['quantity']) ? (int)$comp['quantity'] : 1;
        $stmt->execute([
            $config_id,
            (int)$comp['id_component'],
            $qty
        ]);
    }

    echo json_encode(['success' => true, 'id_configuration' => $config_id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}
?>