window.setButtonLoading = function (button, isLoading, text) {
    var element = button instanceof HTMLElement ? button : document.querySelector(button);
    var loadingText = text || 'Memproses...';

    if (!element) {
        return;
    }

    if (isLoading) {
        element.dataset.originalText = element.innerHTML;
        element.disabled = true;
        element.innerHTML = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>' + loadingText;
        return;
    }

    element.disabled = false;
    element.innerHTML = element.dataset.originalText || element.innerHTML;
};

document.addEventListener('click', function (event) {
    var submitButton = event.target.closest('button[type="submit"], input[type="submit"]');

    if (submitButton && submitButton.form) {
        submitButton.form.dataset.clickedSubmit = submitButton.dataset.loadingText ? submitButton.name || submitButton.textContent.trim() : '';
        submitButton.form._clickedSubmitButton = submitButton;
    }
});

document.addEventListener('submit', function (event) {
    var form = event.target;
    var submitButton = form._clickedSubmitButton || form.querySelector('[data-loading-text]');

    if (submitButton) {
        window.setButtonLoading(submitButton, true, submitButton.dataset.loadingText);
    }
});
