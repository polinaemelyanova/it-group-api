<?php

require_once 'headers.php'; // Подключаем заголовки (CORS, Content-Type и т.п.)
require_once 'db.php';      // Подключаем функцию подключения к базе данных

// Функция: получить один товар по ID
function getProductById($id) {
    $pdo = getDatabaseConnection(); // Получаем подключение к базе

    // SQL-запрос: выбрать товар с нужным id_component
    $stmt = $pdo->prepare("SELECT * FROM components WHERE id_components = ?");
    $stmt->execute([$id]);

    // Возвращаем первую (и единственную) строку
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Получаем параметр id из URL, например: product.php?id=5
$id = $_GET['id'] ?? '';

// Проверяем, что id есть и оно — число
if ($id && is_numeric($id)) {
    $product = getProductById($id); // Получаем товар из БД
    if ($product) {
        echo json_encode([$product]); // Отправляем данные товара на фронт
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Товар не найден']);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Некорректный или отсутствующий ID']);
}
