<?php
require_once 'headers.php';
require_once 'db.php';

$pdo = getDatabaseConnection();

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON"]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Добавляем заказ
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            city, street, house, flat,
            date_order, cost, payment_status,
            comment, delivery_method, payment_method
        ) VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['order']['city'] ?? '',
        $data['order']['street'] ?? null,
        $data['order']['house'] ?? null,
        $data['order']['flat'] ?? null,
        $data['order']['cost'] ?? 0,
        'pending',
        $data['order']['comment'] ?? '',
        $data['order']['delivery_method'] ?? '',
        $data['order']['payment_method'] ?? ''
    ]);
    $orderId = $pdo->lastInsertId();

    // 2. Добавляем customer_info
    $stmt = $pdo->prepare("
        INSERT INTO customer_info (
            order_id, name, last_name, patronymic, phone, email
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $orderId,
        $data['customer']['name'] ?? '',
        $data['customer']['last_name'] ?? '',
        $data['customer']['patronymic'] ?? null,
        $data['customer']['phone'] ?? '',
        $data['customer']['email'] ?? ''
    ]);
    $customerId = $pdo->lastInsertId();

    // 3. Добавляем товары
    foreach ($data['items'] as $item) {
        $idConfiguration = $item['id_configuration'] ?? null;
        $idComponent = $item['id_component'] ?? null;
        $quantity = $item['quantity'] ?? 1;

        if ($idConfiguration && !$idComponent) {
            // Это сборка — достаём все компоненты и их количество
            $confStmt = $pdo->prepare("
                SELECT id_component, quantity
                FROM configuration_components
                WHERE id_configuration = ?
            ");
            $confStmt->execute([$idConfiguration]);
            $components = $confStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($components as $component) {
                $componentId = $component['id_component'];
                $componentQuantity = $component['quantity'] ?? 1;

                // Умножаем на количество сборок, если заказано несколько
                for ($i = 0; $i < $quantity * $componentQuantity; $i++) {
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (id_order, id_component, id_configuration)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$orderId, $componentId, $idConfiguration]);
                }
            }
        } elseif ($idComponent) {
            // Одиночный компонент
            for ($i = 0; $i < $quantity; $i++) {
                $stmt = $pdo->prepare("
                    INSERT INTO order_items (id_order, id_component, id_configuration)
                    VALUES (?, ?, NULL)
                ");
                $stmt->execute([$orderId, $idComponent]);
            }
        }
    }

    $pdo->commit();
    echo json_encode(["success" => true, "order_id" => $orderId, "customer_id" => $customerId]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
