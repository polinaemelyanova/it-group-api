<?php

require_once 'headers.php';
require_once 'db.php';

function getProductsByCategoryName($categoryName) {
    $pdo = getDatabaseConnection();

    // Получаем ID категории по её имени
    $stmt = $pdo->prepare("SELECT id_type FROM type_product WHERE slug = ?");
    $stmt->execute([$categoryName]);
    $type = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$type) {
        // Если категория не найдена — вернём пустой массив или ошибку
        return [];
    }

    // Теперь ищем товары с нужным type_product
    $stmt = $pdo->prepare("SELECT * FROM components WHERE type_product = ? ORDER BY id_components DESC");
    $stmt->execute([$type['id_type']]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Получаем имя категории из GET-запроса, например: ?category=cpu
$category = $_GET['category'] ?? '';

if ($category) {
    $products = getProductsByCategoryName($category);
    echo json_encode($products);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Не указана категория']);
}



