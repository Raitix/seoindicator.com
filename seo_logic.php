<?php
// Existing logic for AI integration - expanding meta description analysis

// Fetch input from AI analysis request
function analyze_with_ai($input_data) {
    $ai_provider = AI_PROVIDER;
    switch ($ai_provider) {
        case 'vercel':
            $api_key = VERCEL_AI_GATEWAY_API_KEY;
            $api_url = 'https://api.example.com/analyze';
            break;
        case 'openrouter':
            $api_key = OPENROUTER_API_KEY;
            $api_url = 'https://api.openrouter.ai/analyze';
            break;
        default:
            throw new Exception('Unsupported AI provider');
    }

    $headers = [
        'Authorization: Bearer ' . $api_key,
        'Content-Type: application/json'
    ];

    $postData = json_encode([
        'url' => $input_data['url'],
        'title' => $input_data['title'],
        'meta_description' => $input_data['meta_description'],
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_code !== 200) {
        throw new Exception('AI analysis failed: ' . curl_error($ch));
    }

    return json_decode($response, true);
}

// Existing code...

?>