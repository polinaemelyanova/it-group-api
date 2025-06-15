<?php

require_once 'headers.php';
require_once 'db.php';

function getAllConfigurationsWithComponents() {
    $pdo = getDatabaseConnection();

    $stmt = $pdo->query("
        SELECT 
            configuration.id_configuration,
            configuration.name_configuration,
            configuration.price_configuration,
            components.id_components,
            components.name_components,
            type_product.name_type,
            components.specs
        FROM components
        RIGHT JOIN configuration_components 
            ON components.id_components = configuration_components.id_component
        LEFT JOIN configuration 
            ON configuration_components.id_configuration = configuration.id_configuration
        LEFT JOIN type_product
        	ON components.type_product = type_product.id_type
        ORDER BY configuration.id_configuration DESC
    ");

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];

    foreach ($rows as $row) {
        $configId = $row['id_configuration'];

        // Если конфигурация ещё не добавлена, инициализируем
        if (!isset($result[$configId])) {
            $result[$configId] = [
                'id_configuration' => $configId,
                'name_configuration' => $row['name_configuration'],
                'price_configuration' => $row['price_configuration'],
                'components' => [],
            ];
        }

        // Добавляем компонент, если он есть
        if ($row['id_components']) {
            $result[$configId]['components'][] = [
                'id_components' => $row['id_components'],
                'name_type' => $row['name_type'],
                'name_components' => $row['name_components'],
                'specs' => $row['specs'],
            ];
        }
    }

    // Переводим ассоциативный массив в индексированный
    return array_values($result);
}

$data = getAllConfigurationsWithComponents();
header('Content-Type: application/json');
echo json_encode($data);
