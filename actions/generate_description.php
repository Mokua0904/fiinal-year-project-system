<?php
// actions/generate_description.php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Get the JSON data sent from the fetch() call
$data = json_decode(file_get_contents('php://input'), true);
$product_name = isset($data['product_name']) ? trim($data['product_name']) : '';

if (empty($product_name)) {
    echo json_encode(['success' => false, 'error' => 'Product name is required.']);
    exit;
}

/* 
=============================================================
 OPTION 1: LOCAL MOCK AI (FREE - Works immediately)
=============================================================
 This creates a smart template based on keywords in the name.
*/
$name_lower = strtolower($product_name);
$description = "";

if (strpos($name_lower, 'iphone') !== false || strpos($name_lower, 'samsung') !== false || strpos($name_lower, 'phone') !== false) {
    $description = "The $product_name is built for users who demand the best. Its expansive Super Retina display features a high refresh rate for smooth scrolling and an Always-On display. Inside is a powerful processor paired with an advanced Pro camera system, making the $product_name a serious tool for photography, gaming and demanding mobile workflows.\n\nKey Features:\n• Premium display with high peak brightness\n• Advanced multi-lens camera system\n• All-day battery life with fast charging\n• eSIM and 5G connectivity\n• IP68 water and dust resistance";
} elseif (strpos($name_lower, 'mac') !== false || strpos($name_lower, 'book') !== false || strpos($name_lower, 'laptop') !== false) {
    $description = "The $product_name delivers incredible performance and amazing battery life. With a stunning Liquid Retina display and a sleek, durable design, it’s built for everything from everyday tasks to intensive creative workflows. The advanced thermal system ensures it stays cool under pressure.\n\nKey Features:\n• Ultra-fast processor and graphics\n• Stunning high-resolution display\n• Up to 18 hours of battery life\n• MagSafe charging and Thunderbolt ports\n• Backlit Magic Keyboard with Touch ID";
} else {
    $description = "Elevate your setup with the $product_name. Engineered for premium performance, unmatched reliability, and a sleek design, this is a must-have item. Perfect for both casual users and professionals, the $product_name seamlessly integrates into your daily life, offering top-tier features that deliver exceptional value.\n\nKey Features:\n• Premium build quality\n• High-performance output\n• Sleek and modern design\n• Reliable daily operation";
}

// Simulate network delay so the loading spinner shows
sleep(1);

echo json_encode([
    'success' => true,
    'description' => $description
]);
exit;


/* 
=============================================================
 OPTION 2: REAL OPENAI API (Requires API Key)
=============================================================
 If you want real AI (ChatGPT), delete everything above this 
 comment, uncomment the code below, and add your API key.
*/

/*
$api_key = 'sk-YOUR_OPENAI_API_KEY_HERE'; // Replace with your actual key

$prompt = "Write a professional, detailed e-commerce product description for a tech product named: '$product_name'. Include a short overview paragraph and a bulleted list of 4 key features. Do not include the price.";

$post_data = [
    "model" => "gpt-3.5-turbo",
    "messages" => [
        ["role" => "system", "content" => "You are a professional tech copywriter for an electronics store in Kenya."],
        ["role" => "user", "content" => $prompt]
    ],
    "temperature" => 0.7
];

$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code === 200) {
    $result = json_decode($response, true);
    $generated_text = $result['choices'][0]['message']['content'];
    echo json_encode(['success' => true, 'description' => trim($generated_text)]);
} else {
    echo json_encode(['success' => false, 'error' => 'OpenAI API Error. Check your API key.']);
}
*/
?>