'use strict';
(() => {
    const message = document.getElementById('global-feedback');
    const pending = new Set();
    document.addEventListener('click', async event => {
        const button = event.target.closest('button[data-favorite]');
        if (!button) return;
        const id = button.dataset.product;
        if (pending.has(id)) return;
        pending.add(id);
        const buttons = () => [...document.querySelectorAll('button[data-favorite]')].filter(item => item.dataset.product === id);
        buttons().forEach(item => { item.disabled = true; });
        if (message) message.textContent = '';
        try {
            const response = await fetch('traitement_favori.php', {
                method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ produit_id: id, favori: button.dataset.state === '1' ? '0' : '1', csrf_token: document.querySelector('meta[name="csrf-token"]').content })
            });
            const data = await response.json();
            if (!response.ok || data.success !== true || typeof data.favori !== 'boolean') throw new Error(data.message || 'Modification impossible.');
            buttons().forEach(item => {
                item.dataset.state = data.favori ? '1' : '0';
                item.setAttribute('aria-pressed', String(data.favori));
                item.setAttribute('aria-label', data.favori ? 'Retirer des favoris' : 'Ajouter aux favoris');
            });
            if (document.querySelector('[data-favorites-page]') && !data.favori) window.location.reload();
        } catch (error) {
            const text = error instanceof TypeError ? 'Connexion interrompue. Actualisez pour vérifier vos favoris.' : error.message;
            if (message) message.textContent = text;
        } finally {
            pending.delete(id);
            buttons().forEach(item => { item.disabled = false; });
        }
    });
    const timers = [...document.querySelectorAll('[data-remaining]')].map(node => ({ node, deadline: performance.now() + Math.max(0, Number(node.dataset.remaining)) * 1000 }));
    const tick = () => {
        for (const item of timers) {
            const remaining = Math.max(0, Math.ceil((item.deadline - performance.now()) / 1000));
            if (remaining === 0) { item.node.textContent = 'Terminée'; continue; }
            const days = Math.floor(remaining / 86400);
            const hours = Math.floor(remaining % 86400 / 3600);
            const minutes = Math.floor(remaining % 3600 / 60);
            const seconds = remaining % 60;
            item.node.textContent = (days ? days + 'j ' : '') + hours + 'h ' + String(minutes).padStart(2, '0') + 'm ' + String(seconds).padStart(2, '0') + 's';
        }
    };
    tick();
    if (timers.length) setInterval(tick, 1000);
    document.addEventListener('click', event => {
        const summary = event.target.closest('.site-header details > summary');
        if (summary) document.querySelectorAll('.site-header details[open]').forEach(details => { if (details !== summary.parentElement) details.open = false; });
        else if (!event.target.closest('.site-header details')) document.querySelectorAll('.site-header details[open]').forEach(details => { details.open = false; });
    });
    document.addEventListener('keydown', event => { if (event.key === 'Escape') document.querySelectorAll('.site-header details[open]').forEach(details => { details.open = false; details.querySelector('summary').focus(); }); });
})();
