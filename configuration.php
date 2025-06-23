<?php

require_once 'headers.php'; // Подключаем заголовки (CORS, Content-Type и т.п.)
require_once 'db.php';      // Подключаем функцию подключения к базе данных

// Функция: получить один товар по ID
function getConfigurationById($id) {
    $pdo = getDatabaseConnection(); // Получаем подключение к базе

    // SQL-запрос: выбрать товар с нужным id_component
    $stmt = $pdo->prepare("SELECT configuration.id_configuration, configuration.name_configuration, configuration.type_configuration, 
    configuration.price_configuration, components.id_components, components.name_components, components.type_product, components.specs 
    FROM components RIGHT JOIN configuration_components ON components.id_components = configuration_components.id_component 
    LEFT JOIN configuration ON configuration_components.id_configuration = configuration.id_configuration 
    WHERE configuration.id_configuration = ?");
    $stmt->execute([$id]);

    // Возвращаем первую (и единственную) строку
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Получаем параметр id из URL, например: product.php?id=5
$id = $_GET['id'] ?? '';



// Проверяем, что id есть и оно — число
if ($id && is_numeric($id)) {
    $product = getConfigurationById($id); // Получаем товар из БД
    if ($product) {
        echo json_encode($product); // Отправляем данные товара на фронт
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Товар не найден']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректный или отсутствующий ID']);
}
