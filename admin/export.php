<?php
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    exit;
}

$host = 'localhost';
$dbname = 'u82814';
$username = 'u82814';
$password = '3096918';

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных");
}

// Получаем все данные
$stmt = $pdo->query("
    SELECT a.id, a.fullname, a.email, a.phone, a.birthdate, a.gender, 
           a.biography, a.contract_agreed, a.created_at,
           GROUP_CONCAT(DISTINCT pl.name) as languages,
           u.login as user_login
    FROM applications a
    LEFT JOIN application_languages al ON a.id = al.application_id
    LEFT JOIN programming_languages pl ON al.language_id = pl.id
    LEFT JOIN users u ON a.id = u.application_id
    GROUP BY a.id
    ORDER BY a.created_at DESC
");

$data = $stmt->fetchAll();

// Устанавливаем заголовки для CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="applications_export_' . date('Y-m-d') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM для UTF-8

// Заголовки
fputcsv($output, [
    'ID', 'ФИО', 'Email', 'Телефон', 'Дата рождения', 'Пол', 
    'Языки программирования', 'Биография', 'Согласие с контрактом', 
    'Дата создания', 'Логин пользователя'
]);

// Данные
foreach ($data as $row) {
    fputcsv($output, [
        $row['id'],
        $row['fullname'],
        $row['email'],
        $row['phone'] ?? '',
        $row['birthdate'] ?? '',
        $row['gender'],
        $row['languages'] ?? '',
        strip_tags($row['biography'] ?? ''),
        $row['contract_agreed'] ? 'Да' : 'Нет',
        date('d.m.Y H:i:s', strtotime($row['created_at'])),
        $row['user_login'] ?? ''
    ]);
}

fclose($output);
exit;
?>