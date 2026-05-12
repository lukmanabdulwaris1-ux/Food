<?php
require_once 'config.php';

$db = getDB();
$category = isset($_GET['category']) ? $_GET['category'] : null;

if ($category && in_array($category, ['food', 'drink', 'dessert'])) {
    $stmt = $db->prepare("SELECT * FROM menu_items WHERE available=1 AND category=? ORDER BY id");
    $stmt->bind_param('s', $category);
} else {
    $stmt = $db->prepare("SELECT * FROM menu_items WHERE available=1 ORDER BY category, id");
}

$stmt->execute();
$result = $stmt->get_result();
$items = [];
while ($row = $result->fetch_assoc()) {
    $items[] = $row;
}

echo json_encode(['success' => true, 'data' => $items]);
$db->close();
