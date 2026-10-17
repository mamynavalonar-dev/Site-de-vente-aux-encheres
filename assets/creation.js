'use strict';
(() => {
    const form = document.getElementById('create-form');
    const button = form.querySelector('button[type="submit"]');
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (button.disabled) return;
        const message = document.getElementById('form-message');
        button.disabled = true;
        let navigating = false;
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form) });
            const data = await response.json();
            if (!response.ok || data.success !== true) throw new Error(data.message || 'Publication impossible.');
            if (!Number.isSafeInteger(data.id) || data.id < 1) throw new Error('Vérifiez la publication dans « Mes annonces ».');
            message.className = 'notice notice-success';
            message.textContent = data.message;
            navigating = true;
            window.location.assign('detprod.php?id=' + data.id);
        } catch (error) {
            message.className = 'notice notice-error';
            message.textContent = error instanceof TypeError ? 'Connexion interrompue. Vérifiez « Mes annonces » avant de réessayer.' : error.message;
        } finally { if (!navigating) button.disabled = false; }
    });
})();
