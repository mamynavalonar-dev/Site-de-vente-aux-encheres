'use strict';
document.querySelectorAll('[data-auth-tab]').forEach(button => {
    button.addEventListener('click', () => {
        document.querySelectorAll('[data-auth-tab]').forEach(tab => {
            const active = tab === button;
            tab.setAttribute('aria-selected', String(active));
            document.getElementById(tab.dataset.authTab + '-form').hidden = !active;
        });
    });
});
