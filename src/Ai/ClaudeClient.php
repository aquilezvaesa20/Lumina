<?php

declare(strict_types=1);

namespace Lumina\Ai;

use Lumina\Core\Config;

class ClaudeClient implements AiClientInterface
{
    private string $apiKey;
    private string $model;
    private string $apiVersion;
    private int $maxTokens;

    public function __construct(Config $config)
    {
        $this->apiKey = $config->get('ai.anthropic.api_key', $_ENV['ANTHROPIC_API_KEY'] ?? '');
        $this->model = $config->get('ai.anthropic.model', 'claude-3-5-sonnet-20241022');
        $this->apiVersion = $config->get('ai.anthropic.api_version', '2023-06-01');
        $this->maxTokens = (int) $config->get('ai.anthropic.max_tokens', 4096);
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    public function getProviderName(): string
    {
        return 'anthropic-claude';
    }

    public function complete(string $prompt, array $options = []): array
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('ANTHROPIC_API_KEY no está configurada');
        }

        $temperature = $options['temperature'] ?? 0.3; // Baja temperatura para respuestas consistentes
        $maxTokens = $options['max_tokens'] ?? $this->maxTokens;

        $payload = [
            'model' => $this->model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . $this->apiVersion,
            ],
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Error de conexión con Claude API: ' . $error);
        }

        $data = json_decode($response, true);

        if ($httpCode !== 200) {
            $errorMessage = $data['error']['message'] ?? 'Error desconocido';
            throw new \RuntimeException("Claude API error ({$httpCode}): {$errorMessage}");
        }

        return [
            'content' => $data['content'][0]['text'] ?? '',
            'usage' => [
                'input_tokens' => $data['usage']['input_tokens'] ?? 0,
                'output_tokens' => $data['usage']['output_tokens'] ?? 0,
            ],
            'model' => $data['model'] ?? $this->model,
            'stop_reason' => $data['stop_reason'] ?? null,
        ];
    }
}
