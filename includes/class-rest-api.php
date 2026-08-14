<?php

if (!defined('ABSPATH')) {
    exit;
}

class Publana_Rest_API
{
    /**
     * API Namespace.
     */
    private const NAMESPACE = 'publana/v1';

    /**
     * Authentication service.
     */
    private Publana_Auth $auth;

    /**
     * Constructor.
     */
    public function __construct(Publana_Auth $auth)
    {
        $this->auth = $auth;

        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Register API routes.
     */
    public function register_routes(): void
    {
        register_rest_route(
            self::NAMESPACE,
            '/posts',
            [
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'create_post'],
                'permission_callback' => [$this, 'authorize'],
            ]
        );

        register_rest_route(
            self::NAMESPACE,
            '/validate',
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [$this, 'validate_token'],
                'permission_callback' => [$this, 'authorize'],
            ]
        );
    }

    /**
     * Permission callback.
     */
    public function authorize(): bool|WP_Error
    {
        return $this->auth->authenticate();
    }

    /**
     * Validate token endpoint.
     */
    public function validate_token(WP_REST_Request $request): WP_REST_Response
    {
        return new WP_REST_Response([
            'success'   => true,
            'message'   => __('Token is valid.', PUBLANA_API_TEXT_DOMAIN),
            'timestamp' => current_time('c'),
        ]);
    }

    /**
     * Create post.
     */
    public function create_post(WP_REST_Request $request)
    {
        $data = $request->get_json_params();

        $post = [
            'post_title'   => sanitize_text_field($data['title'] ?? ''),
            'post_name'    => sanitize_title($data['slug'] ?? ''),
            'post_content' => $data['content'] ?? '',
            'post_excerpt' => wp_kses_post($data['description'] ?? ''),
            'post_status'  => sanitize_key($data['status'] ?? 'draft'),
            'post_author'  => absint($data['author'] ?? 1),
            'post_type'    => 'post',
        ];

        if (empty($post['post_title'])) {

            return new WP_Error(
                'publana_missing_title',
                __('Post title is required.', PUBLANA_API_TEXT_DOMAIN),
                [
                    'status' => 400,
                ]
            );

        }

        if (!empty($data['keywords'])) {
            $post['tax_input'] = [
                'post_tag' => (array) $data['keywords'],
            ];
        }

        $postId = wp_insert_post($post, true);

        if (is_wp_error($postId)) {
            return new WP_Error(
                'publana_create_failed',
                $postId->get_error_message(),
                [
                    'status' => 500,
                ]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Post created successfully.', PUBLANA_API_TEXT_DOMAIN),
            'data' => [
                'post_id'   => $postId,
                'permalink' => get_permalink($postId),
                'edit_link' => get_edit_post_link($postId, 'raw'),
            ],
        ]);
    }
}