<?php
// Отключаем вывод ошибок на экран, чтобы они НЕ подмешивались в JSON-ответ.
// Вместо этого ошибки будут писаться в системный лог сервера (error.log)
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

// --- ПОДДЕРЖКА RAW JSON ЗАПРОСОВ ---
if (empty($_POST)) {
    $raw_input = file_get_contents('php://input');
    $input_data = json_decode($raw_input, true);
    if (is_array($input_data)) {
        $_POST = $input_data;
    }
}

// Проверяем существование файла подключения к БД перед импортом
if (!file_exists('connect.php')) {
    echo json_encode([
        'success' => false, 
        'message' => 'Критическая ошибка: Файл connect.php не найден.'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once('connect.php'); 

if (!isset($connection)) {
    if (isset($conn)) { $connection = $conn; } 
    elseif (isset($db)) { $connection = $db; } 
    else {
        echo json_encode([
            'success' => false, 
            'message' => 'Критическая ошибка: Переменная подключения к БД ($connection) не найдена.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$lecture_id = $_POST['lecture_id'] ?? '';
if (empty($lecture_id)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Не указан ID лекции в запросе. Полученные данные: ' . json_encode($_POST)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Извлечение контента лекции
try {
    $query = "SELECT lectureContent FROM lecture WHERE id = ?"; 
    $stmt = $connection->prepare($query);
    if (!$stmt) {
        throw new Exception("Ошибка подготовки запроса SQL: " . $connection->error);
    }
    $stmt->bind_param("i", $lecture_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Лекция с таким ID не найдена в базе данных.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $lecture = $result->fetch_assoc();
    $lecture_text = $lecture['lectureContent'];
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// Запрос к API нейросети Groq
$groq_api_key = ""; // <-- ЗАМЕНИ ЭТОТ КЛЮЧ ПРИ ПЕРВОЙ ВОЗМОЖНОСТИ!
$model = "llama-3.1-8b-instant";  

$prompt = "Проанализируй следующий текст лекции и сгенерируй по нему 3 вопроса для тестирования студентов. "
        . "Для каждого вопроса предложи 4 варианта ответа. В поле 'is_correct' укажи 1 для правильного ответа, и 0 для неправильных. "
        . "Ответ верни СТРОГО в формате JSON без markdown-оберток. Структура JSON должна быть точно такой:\n"
        . '{"questions": [{"text": "Текст вопроса", "answers": [{"text": "Вариант 1", "is_correct": 1}, {"text": "Вариант 2", "is_correct": 0}, {"text": "Вариант 3", "is_correct": 0}, {"text": "Вариант 4", "is_correct": 0}]}]}'
        . "\n\nТекст лекции:\n" . $lecture_text;

$data = [
    'model' => $model,
    'messages' => [
        ['role' => 'user', 'content' => $prompt]
    ],
    'temperature' => 0.3,
    'response_format' => ['type' => 'json_object']
];

if (!function_exists('curl_init')) {
    echo json_encode(['success' => false, 'message' => 'Критическая ошибка: Расширение php_curl не включено.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data, JSON_UNESCAPED_UNICODE));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $groq_api_key,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    $curl_error = curl_error($ch);
    echo json_encode(['success' => false, 'message' => 'Ошибка сетевого запроса к ИИ: ' . $curl_error], JSON_UNESCAPED_UNICODE);
    exit;
}

$response_data = json_decode($response, true);

if (isset($response_data['error'])) {
    echo json_encode(['success' => false, 'message' => 'Ошибка API Groq: ' . $response_data['error']['message']], JSON_UNESCAPED_UNICODE);
    exit;
}

$ai_content = $response_data['choices'][0]['message']['content'] ?? '';
if (empty($ai_content)) {
    echo json_encode(['success' => false, 'message' => 'Нейросеть вернула пустой ответ.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$ai_content = trim($ai_content);
if (strpos($ai_content, '```') === 0) {
    $ai_content = preg_replace('/^```json?\s*|```$/', '', $ai_content);
    $ai_content = trim($ai_content);
}

$questions_data = json_decode($ai_content, true);

if (!$questions_data || !isset($questions_data['questions'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Ошибка: Нейросеть вернула некорректный формат структуры вопросов.',
        'raw_ai_response' => $ai_content
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =========================================================================
// === АДАПТИРОВАННЫЙ БЛОК: СОХРАНЕНИЕ В ТВОЮ БД (таблицы quetions и options) ===
// =========================================================================
try {
    // Включаем транзакцию для безопасности данных
    $connection->begin_transaction();

    // Подготавливаем стейтменты под ТВОЮ структуру
    // Таблица: quetions (поля: quetionContent, lectureId)
    $stmtQ = $connection->prepare("INSERT INTO quetions (quetionContent, lectureId) VALUES (?, ?)");
    
    // Таблица: options (поля: optionContent, quetionId, correctness)
    $stmtA = $connection->prepare("INSERT INTO options (optionContent, quetionId, correctness) VALUES (?, ?, ?)");

    if (!$stmtQ || !$stmtA) {
        throw new Exception("Ошибка подготовки SQL-запросов: " . $connection->error);
    }

    // Перебираем вопросы, пришедшие от ИИ
    foreach ($questions_data['questions'] as $q) {
        $q_text = $q['text'];
        
        // Привязываем параметры: s (string - текст вопроса), i (integer - ID лекции)
        $stmtQ->bind_param("si", $q_text, $lecture_id);
        $stmtQ->execute();
        
        // Получаем ID только что добавленного вопроса
        $question_id = $connection->insert_id;

        // Перебираем варианты ответов для этого вопроса
        foreach ($q['answers'] as $ans) {
            $a_text = $ans['text'];
            $correctness = (int)$ans['is_correct']; // Из JSON берем is_correct и превращаем в 0 или 1

            // Привязываем параметры: s (текст ответа), i (ID вопроса), i (правильность)
            $stmtA->bind_param("sii", $a_text, $question_id, $correctness);
            $stmtA->execute();
        }
    }

    // Если всё записалось без ошибок, фиксируем транзакцию
    $connection->commit();

} catch (Exception $db_err) {
    // Если что-то пошло не так — откатываем изменения, чтобы не засорять базу
    $connection->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка сохранения тестов в базу данных: ' . $db_err->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
// =========================================================================

// ЕДИНЫЙ ПРАВИЛЬНЫЙ ВЫВОД ДЛЯ ФРОНТЕНДА
echo json_encode([
    'success' => true,
    'questions' => $questions_data['questions']
], JSON_UNESCAPED_UNICODE);

exit;