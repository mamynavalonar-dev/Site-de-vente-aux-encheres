'use strict';
(() => {
const initialAuction = JSON.parse(document.getElementById('auction-config').textContent);
        const form = document.getElementById('bid-form');
        const submitButton = form?.querySelector('button[type="submit"], button');
        const amountInput = document.getElementById('montant');
        const messageDiv = document.getElementById('bid-message');
        const priceSpan = document.getElementById('current-price');
        const historyList = document.getElementById('history-list');
        const historyFeedback = document.getElementById('history-feedback');
        const timerElement = document.getElementById('countdown');
        const closedMessage = document.getElementById('auction-closed-message');
        let closed = initialAuction.closed;
        let deadline = Date.now() + initialAuction.remainingSeconds * 1000;
        let submitting = false;
        let syncing = false;
        let revision = 0;
        let polling = null;

        function closeAuction() {
            closed = true;
            const status = document.getElementById('auction-status');
            if (status) { status.textContent = 'Terminée'; status.classList.add('status-muted'); }
            if (form) form.hidden = true;
            if (closedMessage) closedMessage.hidden = false;
            timerElement.textContent = 'TERMINÉE';
            timerElement.style.color = 'var(--red)';
        }

        function updateTimer() {
            const remaining = Math.max(0, deadline - Date.now());
            if (closed || remaining === 0) {
                closeAuction();
                return;
            }
            const seconds = Math.ceil(remaining / 1000);
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor(seconds % 86400 / 3600);
            const minutes = Math.floor(seconds % 3600 / 60);
            timerElement.textContent = String(days).padStart(2, '0') + 'j ' +
                String(hours).padStart(2, '0') + 'h ' +
                String(minutes).padStart(2, '0') + 'm ' +
                String(seconds % 60).padStart(2, '0') + 's';
        }

        function showBidMessage(success, message) {
            messageDiv.className = success ? 'success' : 'error';
            messageDiv.textContent = message;
        }

        function renderHistory(bids) {
            if (!historyList) return;
            const fragment = document.createDocumentFragment();
            if (bids.length === 0) {
                const empty = document.createElement('li');
                empty.className = 'empty';
                empty.textContent = 'Aucune offre pour le moment.';
                fragment.appendChild(empty);
            }
            for (const bid of bids) {
                const row = document.createElement('li');
                for (const [className, value] of [
                    ['user-name', bid.nom],
                    ['bid-amount', bid.montant + ' €'],
                    ['bid-date', bid.date],
                ]) {
                    const span = document.createElement('span');
                    span.className = className;
                    span.textContent = value;
                    row.appendChild(span);
                }
                fragment.appendChild(row);
            }
            historyList.replaceChildren(fragment);
        }

        // Les erreurs de synchronisation n'effacent jamais une confirmation d'offre.
        async function refreshAuction() {
            if (submitting || syncing) return;
            syncing = true;
            const requestedRevision = revision;
            try {
                const response = await fetch('get_live_data.php?id=' + initialAuction.id, { cache: 'no-store' });
                const data = await response.json();
                if (!response.ok || data.error || !Array.isArray(data.historique) || typeof data.prix_actuel !== 'string') {
                    throw new Error('Réponse de synchronisation invalide.');
                }
                // Une lecture commencée avant l'envoi d'une offre peut être périmée.
                if (requestedRevision !== revision) return;
                priceSpan.textContent = data.prix_actuel + ' €';
                renderHistory(data.historique);
                if (historyFeedback) historyFeedback.textContent = '';
                deadline = Date.now() + Math.max(0, Number(data.secondes_restantes)) * 1000;
                if (data.statut === 'terminee') {
                    closeAuction();
                    if (polling !== null) clearInterval(polling);
                } else {
                    updateTimer();
                }
            } catch (error) {
                if (requestedRevision === revision && historyFeedback) {
                    historyFeedback.textContent = 'Actualisation temporairement indisponible. Les offres affichées peuvent dater.';
                }
            } finally {
                syncing = false;
            }
        }

        form?.addEventListener('submit', async function(event) {
            event.preventDefault();
            if (submitting || closed) return;
            submitting = true;
            revision++;
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.textContent = 'Envoi…';
            }

            let data;
            try {
                const response = await fetch('traitement_enchere.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        produit_id: initialAuction.id,
                        montant: amountInput.value,
                        csrf_token: document.getElementById('csrf-enchere').value,
                    }),
                });
                data = await response.json();
                if (typeof data.success !== 'boolean' || typeof data.message !== 'string' ||
                    (data.success && (!response.ok || typeof data.nouveau_prix !== 'string'))) {
                    throw new Error('Réponse de confirmation invalide.');
                }
            } catch (error) {
                showBidMessage(false, 'Impossible de confirmer la réponse du serveur. Actualisez la page pour vérifier votre offre avant de réessayer.');
                return;
            } finally {
                submitting = false;
                if (submitButton) {
                    submitButton.disabled = false;
                    submitButton.textContent = 'Enchérir';
                }
            }

            if (data.success) {
                priceSpan.textContent = data.nouveau_prix + ' €';
                amountInput.value = '';
            }
            showBidMessage(data.success, data.message || (data.success ? 'Enchère réussie !' : 'Offre refusée.'));
            // La confirmation est déjà affichée ; l'historique se recharge séparément.
            void refreshAuction();
        });

        updateTimer();
        setInterval(updateTimer, 1000);
        if (!initialAuction.closed) polling = setInterval(refreshAuction, 5000);

})();
