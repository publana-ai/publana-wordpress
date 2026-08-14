document.addEventListener('DOMContentLoaded', () => {
    setupTokenModal();
    setupConfirmActions();
});

/*
|--------------------------------------------------------------------------
| Token Modal
|--------------------------------------------------------------------------
*/

function setupTokenModal() {
    const modal = document.getElementById('publana-token-modal');

    if (!modal) {
        return;
    }

    const copyButton = document.getElementById('publana-copy-token');
    const closeButton = document.getElementById('publana-close-modal');
    const tokenInput = document.getElementById('publana-token');

    copyButton?.addEventListener('click', async () => {

        try {

            await navigator.clipboard.writeText(tokenInput.value);

            copyButton.textContent = Publana.i18n.copied;

            setTimeout(() => {
                copyButton.textContent = Publana.i18n.copy;
            }, 2000);

        } catch (error) {

            tokenInput.select();

            document.execCommand('copy');

        }

    });

    closeButton?.addEventListener('click', () => {
        closeModal(modal);
    });

    modal.addEventListener('click', event => {

        if (event.target === modal) {
            closeModal(modal);
        }

    });

    document.addEventListener('keydown', event => {

        if (event.key === 'Escape') {
            closeModal(modal);
        }

    });
}

/*
|--------------------------------------------------------------------------
| Confirmation
|--------------------------------------------------------------------------
*/

function setupConfirmActions() {

    document
        .querySelectorAll('[data-confirm]')
        .forEach(element => {

            element.addEventListener('click', event => {

                if (!confirm(Publana.i18n.confirm)) {
                    event.preventDefault();
                }

            });

        });

}

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/

function closeModal(modal) {
    modal.remove();
}
