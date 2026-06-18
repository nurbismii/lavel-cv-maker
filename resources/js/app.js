require('./bootstrap');

window.setButtonLoading = function (button, isLoading, text = 'Memproses...') {
    const element = button instanceof HTMLElement ? button : document.querySelector(button);

    if (!element) {
        return;
    }

    if (isLoading) {
        element.dataset.originalText = element.innerHTML;
        element.disabled = true;
        element.innerHTML = `<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>${text}`;
        return;
    }

    element.disabled = false;
    element.innerHTML = element.dataset.originalText || element.innerHTML;
};

document.addEventListener('submit', function (event) {
    const form = event.target;
    const submitButton = form._clickedSubmitButton || form.querySelector('[data-loading-text]');

    if (submitButton) {
        window.setButtonLoading(submitButton, true, submitButton.dataset.loadingText);
    }
});

document.addEventListener('click', function (event) {
    const submitButton = event.target.closest('button[type="submit"], input[type="submit"]');

    if (submitButton && submitButton.form) {
        submitButton.form._clickedSubmitButton = submitButton;
    }
});
