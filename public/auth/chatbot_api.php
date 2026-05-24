<?php
require_once __DIR__ . '/../../config.php';

require_once ROOT_DIR . '/includes/security/auth_middleware.php';
require_once ROOT_DIR . '/includes/helpers/chatbot_helper.php';

// Only logged in users can use the chatbot
checkAuth();

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$userMessage = strtolower(trim($input['message'] ?? ''));

if (!$userMessage) {
    echo json_encode(['response' => "I didn't quite catch that. How can I help you today?"]);
    exit();
}

$data = getChatbotData();
$response = "";
$navLink = null;

// 1. Check for exact FAQ match
foreach ($data['faqs'] as $question => $answer) {
    if (strtolower($question) === $userMessage) {
        $response = $answer;
        break;
    }
}

// 2. Check for Navigation keywords if no FAQ match
if (!$response) {
    foreach ($data['navigation'] as $keyword => $nav) {
        if (strpos($userMessage, $keyword) !== false) {
            $response = "I can help with that! Click the button below to go to " . $nav['text'] . ".";
            $navLink = $nav;
            break;
        }
    }
}

// 3. Fallback
if (!$response) {
    $response = "I'm sorry, I don't have an answer for that yet. Please contact the administration office for further assistance.";
}

echo json_encode([
    'response' => $response,
    'navLink' => $navLink
]);
?>
