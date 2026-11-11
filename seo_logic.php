<?php
/**
 * SEOIndicator - legacy plain-PHP AI integration.
 *
 * This layer is the lightweight alternative to the Laravel backend. It is used
 * by the endpoints under `api/` when the project is deployed without the
 * Laravel application (see README, "Plain-PHP layer").
 *
 * Configuration comes from `config.php` (copy `config.php.example`); the
 * canonical Laravel backend reads `backend/.env` instead.
 *
 * @see .gitignore
 */

// Load the local configuration when present. The file must stay parseable
// without it (for example when the endpoint is probed directly), so every
// provider constant falls back to a safe default.
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/config.php';
}

defined('AI_PROVIDER') || define('AI_PROVIDER', 'openrouter');
defined('VERCEL_AI_GATEWAY_API_KEY') || define('VERCEL_AI_GATEWAY_API_KEY', '');
defined('VERCEL_AI_MODEL') || define('VERCEL_AI_MODEL', 'openai/gpt-4o-mini');
defined('OPENROUTER_API_KEY') || define('OPENROUTER_API_KEY', '');
defined('OPENROUTER_MODEL') || define('OPENROUTER_MODEL', 'baidu/ernie-5.5-flash-preview:free');
defined('GOOGLE_PAGESPEED_API_KEY') || define('GOOGLE_PAGESPEED_API_KEY', '');

/**
 * Resolve the active provider's credentials and chat-completions endpoint.
 *
 * @return array{key:string,url:string,model:string}
 * @throws Exception When the configured provider is unknown.
 */
function ai_provider_config(): array
{
    switch (AI_PROVIDER) {
        case 'vercel':
            return [
                'key'   => VERCEL_AI_GATEWAY_API_KEY,
                'url'   => 'https://ai-gateway.vercel.sh/v1/chat/completions',
                'model' => VERCEL_AI_MODEL,
            ];

        case 'openrouter':
            return [
                'key'   => OPENROUTER_API_KEY,
                'url'   => 'https://openrouter.ai/api/v1/chat/completions',
                'model' => OPENROUTER_MODEL,
            ];

        default:
            throw new Exception('Unsupported AI provider: ' . AI_PROVIDER);
    }
}

/**
 * Send one chat-completion request to the configured provider.
 *
 * @param string $prompt User prompt.
 * @param int $maxTokens Maximum tokens to generate.
 * @param float|null $temperature Optional sampling temperature.
 * @return array Decoded response payload.
 * @throws Exception On transport failure, a non-2xx response, or a provider error.
 */
function ai_chat_completion(string $prompt, int $maxTokens = 16000, ?float $temperature = null): array
{
    $provider = ai_provider_config();
    $errors = [];

    foreach (ai_model_chain($provider['model']) as $model) {
        $payload = [
            'model'    => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => $maxTokens,
        ];

        if ($temperature !== null) {
            $payload['temperature'] = $temperature;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $provider['url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $provider['key'],
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 180);

        $response = curl_exec($ch);

        if ($response === false) {
            $errors[] = $model . ': ' . curl_error($ch);
            curl_close($ch);
            continue;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode < 200 || $httpCode >= 300) {
            $errors[] = $model . ': HTTP ' . $httpCode . ' ' . substr((string) $response, 0, 200);
            continue;
        }

        $decoded = json_decode((string) $response, true);
        if (!is_array($decoded)) {
            $errors[] = $model . ': response was not valid JSON';
            continue;
        }

        if (isset($decoded['error'])) {
            $message = is_string($decoded['error'])
                ? $decoded['error']
                : (string) json_encode($decoded['error']);
            $errors[] = $model . ': ' . $message;
            continue;
        }

        // A shared free-tier endpoint can answer with HTTP 200 and an empty
        // body. Treat that as a failure and try the next model.
        if (trim((string) ai_message_content($decoded)) === '') {
            $errors[] = $model . ': empty response';
            continue;
        }

        return $decoded;
    }

    throw new Exception('AI request failed for every model: ' . implode(' | ', $errors));
}

/**
 * Build the ordered list of models to try.
 *
 * The configured model comes first, followed by the shared fallback list.
 * A model is never tried twice.
 *
 * @param string $primary The configured model identifier.
 * @return string[] Model identifiers in the order they should be tried.
 */
function ai_model_chain(string $primary): array
{
    $candidates = array_merge([$primary], [
        'baidu/ernie-5.5-flash-preview:free',
        'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
        'liquid/lfm-2.5-2.6b:free',
        'liquid/lfm-3-1.7b:free',
    ]);

    $chain = [];

    foreach ($candidates as $model) {
        if (is_string($model) && trim($model) !== '' && !in_array($model, $chain, true)) {
            $chain[] = $model;
        }
    }

    return $chain;
}

/**
 * Extract the assistant message content from a chat-completion payload.
 *
 * @param array $response Decoded chat-completion payload.
 * @return string Message content (empty string when absent).
 */
function ai_message_content(array $response): string
{
    $content = $response['choices'][0]['message']['content'] ?? '';

    return is_string($content) ? $content : '';
}

/**
 * Strip a fenced code block, if present, and return the raw JSON text.
 *
 * @param string $text Model output.
 * @return string Candidate JSON text.
 */
function ai_strip_code_fence(string $text): string
{
    $text = trim($text);

    if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/is', $text, $m)) {
        return trim($m[1]);
    }

    return $text;
}

/**
 * Decode a JSON object from model output, tolerating surrounding prose.
 *
 * @param string $text Model output.
 * @return array<string,mixed>|null Decoded object, or null when unparsable.
 */
function ai_decode_json_object(string $text): ?array
{
    $text = ai_strip_code_fence($text);

    $decoded = json_decode($text, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    $start = strpos($text, '{');
    $end = strrpos($text, '}');
    if ($start !== false && $end !== false && $end > $start) {
        $decoded = json_decode(substr($text, $start, $end - $start + 1), true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return null;
}

/**
 * Analyze a single SEO indicator with the configured AI provider.
 *
 * @param array $input_data Indicator payload (url, title, meta_description).
 * @return array Decoded provider response.
 */
function analyze_with_ai($input_data)
{
    $prompt = "Analyze this page for SEO.\n"
        . 'URL: ' . ($input_data['url'] ?? '') . "\n"
        . 'Title: ' . ($input_data['title'] ?? '') . "\n"
        . 'Meta description: ' . ($input_data['meta_description'] ?? '') . "\n\n"
        . 'Reply with JSON only: {"score": 0-100, "details": "...", "suggestion": "..."}';

    $response = ai_chat_completion($prompt, 1000, 0.7);

    return ai_decode_json_object(ai_message_content($response)) ?? [];
}

/**
 * The SEO indicators analysed for every job.
 *
 * @return string[] Indicator identifiers.
 */
function seo_indicator_list(): array
{
    return [
        'url', 'title', 'meta_description', 'robots', 'sitemap', 'h1', 'filenames', 'image_alt',
        'links', '404_page', 'mobile_friendly', 'language', 'favicon', 'canonical',
        'social_meta_tags', 'aria', 'page_speed',
    ];
}

/**
 * Analyse every SEO indicator for a page in a single AI request.
 *
 * The model is asked to return one object keyed by indicator name; any
 * indicator the model omits falls back to a neutral "not analysed" result so
 * the caller always receives a complete set.
 *
 * @param string $url Final URL of the analysed page.
 * @param string $pageContent Raw HTML of the analysed page.
 * @return array<string,array{score:int,details:string,suggestion:string}> Results keyed by indicator.
 */
function analyze_all_indicators_single_call(string $url, string $pageContent): array
{
    $indicators = seo_indicator_list();

    // Keep the prompt within a sane size; the model sees the head of the page.
    $excerpt = mb_substr($pageContent, 0, 60000);

    $prompt = "You are an SEO auditor. Analyse the page below and score EVERY listed indicator.\n"
        . "Reply with JSON only, one key per indicator, in exactly this shape:\n"
        . '{"url": {"score": 0, "details": "...", "suggestion": "..."}, "title": {...}, ...}' . "\n"
        . 'Indicators: ' . implode(', ', $indicators) . "\n\n"
        . 'URL: ' . $url . "\n\n"
        . "PAGE HTML:\n" . $excerpt;

    $response = ai_chat_completion($prompt, 16000, 0.2);
    $decoded = ai_decode_json_object(ai_message_content($response));

    $results = [];
    foreach ($indicators as $indicator) {
        $entry = is_array($decoded) ? ($decoded[$indicator] ?? null) : null;

        if (!is_array($entry)) {
            $results[$indicator] = [
                'score'      => 0,
                'details'    => 'Not analysed by the AI provider.',
                'suggestion' => 'No suggestion returned for this indicator.',
            ];
            continue;
        }

        $score = isset($entry['score']) ? (int) $entry['score'] : 0;
        $score = max(0, min(100, $score));

        $results[$indicator] = [
            'score'      => $score,
            'details'    => (string) ($entry['details'] ?? ''),
            'suggestion' => (string) ($entry['suggestion'] ?? ''),
        ];
    }

    return $results;
}
