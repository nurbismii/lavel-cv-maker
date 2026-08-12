(function () {
    'use strict';

    document.addEventListener('click', function (event) {
        var button = event.target.closest('[data-password-toggle]');

        if (!button) {
            return;
        }

        var input = document.getElementById(button.dataset.passwordTarget || '');

        if (!input || (input.type !== 'password' && input.type !== 'text')) {
            return;
        }

        var showPassword = input.type === 'password';
        var fieldName = input.name === 'password_confirmation' ? 'konfirmasi password' : 'password';
        var label = (showPassword ? 'Sembunyikan ' : 'Tampilkan ') + fieldName;
        var icon = button.querySelector('i');

        input.type = showPassword ? 'text' : 'password';
        button.setAttribute('aria-pressed', showPassword ? 'true' : 'false');
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);

        if (icon) {
            icon.classList.toggle('bi-eye', !showPassword);
            icon.classList.toggle('bi-eye-slash', showPassword);
        }
    });
})();
