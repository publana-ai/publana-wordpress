<?php

if (!defined('ABSPATH')) {
    exit;
}

class Publana_Token_Manager
{
    /**
     * Option key.
     */
    private const OPTION_KEY = 'publana_api_tokens';

    /**
     * Generate a new token.
     *
     * @return array
     */
    public function generate(?string $name = null): array
    {
        $plain = bin2hex(random_bytes(32));

        $token = [
            'id'         => wp_generate_uuid4(),
            'name'       => $name ?: __('Unnamed Token', PUBLANA_API_TEXT_DOMAIN),
            'hash'       => hash('sha256', $plain),
            'suffix'     => substr($plain, -5),
            'created_at' => current_time('mysql'),
            'last_used'  => null,
            'expires_at' => null,
        ];

        $tokens = $this->all();
        $tokens[] = $token;

        $this->save($tokens);

        return [
            'plain' => $plain,
            'token' => $token,
        ];
    }

    /**
     * Return all tokens.
     */
    public function all(): array
    {
        $tokens = get_option(self::OPTION_KEY, []);

        return array_values(array_filter($tokens, function ($token) {
            return is_array($token)
                && isset($token['id'])
                && isset($token['hash']);
        }));
    }

    /**
     * Save all tokens.
     */
    private function save(array $tokens): void
    {
        update_option(self::OPTION_KEY, $tokens, false);
    }

    /**
     * Delete a token.
     */
    public function revoke(string $id): bool
    {
        $tokens = array_filter(
            $this->all(),
            fn ($token) => $token['id'] !== $id
        );

        $this->save(array_values($tokens));

        return true;
    }

    /**
     * Find token by plain value.
     */
    public function find(string $plain): ?array
    {
        $hash = hash('sha256', $plain);

        foreach ($this->all() as $token) {

            if (hash_equals($token['hash'], $hash)) {
                return $token;
            }

        }

        return null;
    }

    /**
     * Update last usage.
     */
    public function touch(string $id): void
    {
        $tokens = $this->all();

        foreach ($tokens as &$token) {

            if ($token['id'] === $id) {
                $token['last_used'] = current_time('mysql');
                break;
            }

        }

        $this->save($tokens);
    }

    /**
     * Mask a token.
     */
    public function mask(array $token): string
    {
        return str_repeat('•', 32) . $token['suffix'];
    }

    /**
     * Count tokens.
     */
    public function count(): int
    {
        return count($this->all());
    }
}