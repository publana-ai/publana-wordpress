<?php

if (!defined('ABSPATH')) {
    exit;
}

$newToken = get_transient(
    'publana_new_token_' . get_current_user_id()
);

if ($newToken) {
    delete_transient(
        'publana_new_token_' . get_current_user_id()
    );
}

?>

    <div class="wrap publana">

        <h1>
            <?php esc_html_e(
                'API_Manager',
                PUBLANA_API_TEXT_DOMAIN
            ); ?>
        </h1>

        <p>
            <?php esc_html_e(
                'Manage your API access tokens.',
                PUBLANA_API_TEXT_DOMAIN
            ); ?>
        </p>

        <hr>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

            <?php wp_nonce_field('publana_generate_token'); ?>

            <input
                    type="hidden"
                    name="action"
                    value="publana_generate_token">

            <button
                    type="submit"
                    class="button button-primary">
                <?php esc_html_e(
                    'Generate_New_Token',
                    PUBLANA_API_TEXT_DOMAIN
                ); ?>

            </button>

        </form>

        <br>

        <?php if (empty($tokens)) : ?>

            <div class="notice notice-info inline">

                <p>

                    <?php esc_html_e(
                        'No_API_tokens_yet',
                        PUBLANA_API_TEXT_DOMAIN
                    ); ?>

                </p>

            </div>

        <?php else : ?>

            <table class="widefat striped">

                <thead>

                <tr>

                    <th>
                        <?php esc_html_e(
                            'Name',
                            PUBLANA_API_TEXT_DOMAIN
                        ); ?>
                    </th>

                    <th>
                        <?php esc_html_e(
                            'Token',
                            PUBLANA_API_TEXT_DOMAIN
                        ); ?>
                    </th>

                    <th>
                        <?php esc_html_e(
                            'Created',
                            PUBLANA_API_TEXT_DOMAIN
                        ); ?>
                    </th>

                    <th>
                        <?php esc_html_e(
                            'Last Used',
                            PUBLANA_API_TEXT_DOMAIN
                        ); ?>
                    </th>

                    <th width="120"></th>

                </tr>

                </thead>

                <tbody>

                <?php foreach ($tokens as $token) : ?>

                    <?php include PUBLANA_API_PATH . 'templates/token-card.php'; ?>

                <?php endforeach; ?>

                </tbody>

            </table>

        <?php endif; ?>

    </div>

<?php

if ($newToken) {
    include PUBLANA_API_PATH . 'templates/modal-token-created.php';
}