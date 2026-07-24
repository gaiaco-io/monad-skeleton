<?php

/**
 * Per-provider LLM credentials (Services\LLM + LLMAdapters\{Anthropic,OpenAI,DeepSeek,
 * Gemini} — Clarity §11). There is no global LLM config to hand to a facade: each adapter
 * is constructed directly wherever it's needed, e.g.
 * `new \Monad\Clarity\Services\LLMAdapters\Anthropic($LLM['anthropic']['apiKey'], new
 * \Monad\Clarity\Services\HttpClient())`. This file only centralises reading the API keys
 * out of the environment.
 */

declare(strict_types=1);

$env = static function (string $key, mixed $default = null): mixed {
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return is_string($value) ? trim($value) : $value;
};

$LLM = [
    'anthropic' => ['apiKey' => $env('ANTHROPIC_API_KEY', '')],
    'openai' => ['apiKey' => $env('OPENAI_API_KEY', '')],
    'deepseek' => ['apiKey' => $env('DEEPSEEK_API_KEY', '')],
    'gemini' => ['apiKey' => $env('GEMINI_API_KEY', '')],
];
