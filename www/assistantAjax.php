<?php
require_once(__DIR__ . '/../include/head.php');

$data = json_decode(file_get_contents('php://input'));

if (isset($data->op) && $data->op == 'add_titles' && isset($data->items)) {
    foreach ($data->items as $title) {
        $stmt = $mysqli->prepare('INSERT INTO titles (userId, categoryId, title) VALUES (?, ?, ?)');
        $stmt->bind_param("iis", $user['id'], $title->categoryId, $title->text);
        $stmt->execute();
    }
    echo '{"status": "ok"}';
    exit;
}

$systemPrompt = $TITLE_PROMPT;
if (isset($data->description)) {
    $examples = [];

    $stmt = $mysqli->prepare('SELECT * FROM titles WHERE userId = ?');
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $titleRes = $stmt->get_result();
    $myTitles = $titleRes->fetch_all(MYSQLI_ASSOC);

    foreach ($myTitles as $title) {
        array_push($examples, $title['title']);
    }

    $examplePrompt = 'Here are some examples: ' . implode(",", $examples);

    $request = [
        "model" => "gpt-4o",
        "messages" => [
            [
                "role" => "system",
                "content" => $systemPrompt
            ],
            [
                "role" => "system",
                "content" => $examplePrompt
            ],
            [
                "role" => "user",
                "content" => "Description: " . $data->description
            ]
        ],
        "n" => 10,
    ];
    
    $resquestJSON = json_encode($request);

    $headers = [
        "Authorization: Bearer " . $OPENAI_API_KEY
    ];

    $result = curlCall('https://api.openai.com/v1/chat/completions', 'POST', $headers, $resquestJSON);

    echo $result[1];
}