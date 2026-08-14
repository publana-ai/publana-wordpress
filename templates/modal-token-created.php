<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div id="publana-token-modal" class="publana-modal">

    <div class="publana-modal-dialog">

        <div class="publana-modal-header">

            <h2>

                <?php esc_html_e(
                    'API Token Created',
                    PUBLANA_API_TEXT_DOMAIN
                ); ?>

            </h2>

        </div>

        <div class="publana-modal-body">

            <div class="notice notice-warning inline">

                <p>

                    <strong>

                        <?php esc_html_e(
                            'This token will only be shown once.',
                            PUBLANA_API_TEXT_DOMAIN
                        ); ?>

                    </strong>

                </p>

                <p>

                    <?php esc_html_e(
                        'Copy it now and store it in a safe place. After closing this window you will not be able to see it again.',
                        PUBLANA_API_TEXT_DOMAIN
                    ); ?>

                </p>

            </div>

            <textarea
                id="publana-token"
                class="large-text code"
                rows="3"
                readonly><?php echo esc_textarea($newToken); ?></textarea>

        </div>

        <div class="publana-modal-footer">

            <button
                type="button"
                class="button button-primary"
                id="publana-copy-token">

                <?php esc_html_e(
                    'Copy',
                    PUBLANA_API_TEXT_DOMAIN
                ); ?>

            </button>

            <button
                type="button"
                class="button"
                id="publana-close-modal">

                <?php esc_html_e(
                    'Close',
                    PUBLANA_API_TEXT_DOMAIN
                ); ?>

            </button>

        </div>

    </div>

</div>