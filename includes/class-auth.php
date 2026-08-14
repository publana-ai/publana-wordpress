<?php

if (!defined('ABSPATH')) {
    exit;
}

class Publana_Auth
{
    /**
     * Token manager.
     */
    private Publana_Token_Manager $tokens;

    /**
     * Constructor.
     */
    public function __construct(Publana_Token_Manager $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * Authenticate the current request.
     */
    public function authenticate(): bool|WP_Error
    {
        $token = $this->bearer_token();

        if (!$token) {
            return new WP_Error(
                'publana_unauthorized',
                __('Authentication required.', PUBLANA_API_TEXT_DOMAIN),
                ['status' => 401]
            );
        }

        $record = $this->tokens->find($token);

        if (!$record) {
            return new WP_Error(
                'publana_invalid_token',
                __('Invalid API token.', PUBLANA_API_TEXT_DOMAIN),
                ['status' => 401]
            );
        }

        $this->tokens->touch($record['id']);

        return true;
    }

    /**
     * Get Bearer token.
     */
    public function bearer_token(): ?string
    {
        $header = $this->authorization_header();

        if (!$header) {
            return null;
        }

        if (!preg_match('/Bearer\s+(.+)$/i', $header, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }

    /**
     * Get Authorization header.
     */
    private function authorization_header(): ?string
    {
        if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['HTTP_AUTHORIZATION']);
        }

        if (!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return trim($_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        if (function_exists('getallheaders')) {

            $headers = getallheaders();

            foreach ($headers as $key => $value) {

                if (strcasecmp($key, 'Authorization') === 0) {
                    return trim($value);
                }

            }

        }

        return null;
    }
}