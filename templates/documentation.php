<?php

if (!defined('ABSPATH')) {
    exit;
}

?>

<div class="wrap publana">

    <h1>
        <?php esc_html_e(
            'Connect Your Website',
            PUBLANA_API_TEXT_DOMAIN
        ); ?>
    </h1>

    <p>
        <?php esc_html_e(
            'Follow these steps to connect this website with Publana.',
            PUBLANA_API_TEXT_DOMAIN
        ); ?>
    </p>


    <hr>


    <h2>
        1. <?php esc_html_e(
            'Copy Website Address',
            PUBLANA_API_TEXT_DOMAIN
        ); ?>
    </h2>

    <p>
        <?php esc_html_e(
            'Copy your website address below and add it to your Publana channel.',
            PUBLANA_API_TEXT_DOMAIN
        ); ?>
    </p>


    <div class="publana-copy-box">

        <input
                type="text"
                readonly
                class="regular-text code"
                value="<?php echo esc_attr(home_url()); ?>"
                id="publana-site-url">

        <button
                type="button"
                class="button"
                data-copy="publana-site-url">

            <?php esc_html_e(
                'Copy',
                PUBLANA_API_TEXT_DOMAIN
            ); ?>

        </button>

    </div>


    <hr>


    <h2>
        2. <?php esc_html_e(
            'Create API Token',
            PUBLANA_API_TEXT_DOMAIN
        ); ?>
    </h2>

    <p>
        <?php esc_html_e(
            'Create a new token from the Tokens section and copy it.',
            PUBLANA_API_TEXT_DOMAIN
        ); ?>
    </p>


    <hr>


    <h2>
        3. <?php esc_html_e(
            'Add Token to Publana',
            PUBLANA_API_TEXT_DOMAIN
        ); ?>
    </h2>

    <p>
        <?php esc_html_e(
            'Paste the copied token into your Publana channel settings.',
            PUBLANA_API_TEXT_DOMAIN
        ); ?>
    </p>


    <div class="notice notice-success inline">

        <p>
            <strong>
                <?php esc_html_e(
                    'Done! Your website is now connected to Publana.',
                    PUBLANA_API_TEXT_DOMAIN
                ); ?>
            </strong>
        </p>

    </div>

</div>

<script>
    document.querySelectorAll('[data-copy]').forEach(button => {

        button.addEventListener('click', async () => {

            const id = button.dataset.copy;
            const input = document.getElementById(id);

            await navigator.clipboard.writeText(input.value);

            const oldText = button.textContent;

            button.textContent = Publana.i18n.copied;

            setTimeout(() => {
                button.textContent = oldText;
            }, 2000);

        });

    });
</script>