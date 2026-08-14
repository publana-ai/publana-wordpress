<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<tr>

    <td>
        <?php echo esc_html($token['name']); ?>
    </td>

    <td>

        <code>
            <?php echo esc_html(
                str_repeat('*', 32) . $token['suffix']
            ); ?>
        </code>

    </td>

    <td>

        <?php
        echo esc_html(
            mysql2date(
                get_option('date_format'),
                $token['created_at']
            )
        );
        ?>

    </td>

    <td>

        <?php

        if ($token['last_used']) {

            echo esc_html(
                mysql2date(
                    get_option('date_format') . ' ' . get_option('time_format'),
                    $token['last_used']
                )
            );

        } else {

            esc_html_e(
                'Never',
                PUBLANA_API_TEXT_DOMAIN
            );

        }

        ?>

    </td>

    <td>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

            <?php wp_nonce_field('publana_revoke_token'); ?>

            <input type="hidden" name="action" value="publana_revoke_token">

            <input type="hidden" name="token" value="<?php echo esc_attr($token['id']); ?>">

            <button type="submit" class="button button-link-delete">
                <?php esc_html_e(
                    'Revoke',
                    PUBLANA_API_TEXT_DOMAIN
                ); ?>
            </button>

        </form>

    </td>

</tr>