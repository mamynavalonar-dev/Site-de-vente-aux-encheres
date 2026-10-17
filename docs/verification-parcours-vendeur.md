# Parcours visiteur, acheteur et vendeur — 17 octobre 2026

Base : commit `ac47eeab2e675351f08f42c45294ead603d462bd`, branche `finalisation-production`. Référence de présentation : les quatre captures Agorastore fournies par le propriétaire du projet. La page de formules montre Essentiel, Standard et Premium, sans prix affichés. Aucun tarif, périodicité ou prestataire de paiement n’a été fourni.

## Comportement livré

Un visiteur parcourt les biens. L’inscription donne un compte acheteur ; les favoris et les offres exigent la connexion. Le bouton « Devenir vendeur » apparaît dans la navigation après connexion. La demande contient une formule et un projet de vente, reste en attente et doit être validée dans l’administration. L’accès vendeur s’ajoute à l’accès acheteur.

Les demandes et décisions sont enregistrées dans `seller_access` et `seller_events`. Le choix d’une formule ne donne pas de droit de publication. Les décisions administratives périmées sont refusées. Une suspension interdit les nouvelles publications, sans retirer le droit d’acheter ni effacer les anciens lots. Les annonces déjà associées à un vendeur avant cette migration lui conservent un accès historique ; aucune attribution n’est inventée pour les anciens lots sans propriétaire.

La page de formules utilise des conditions sur devis. Aucun paiement n’est simulé, débité ou marqué comme reçu. Les abonnements payants restent à raccorder après définition des tarifs, de la devise, de la périodicité et du prestataire.

## Résultats exécutés

**203 contrôles réussis**, **27 fichiers PHP valides**, **quatre scripts JavaScript valides**. Environnement : PHP 8.3.6, MariaDB 10.11.7, serveur HTTP PHP avec quatre processus, sessions et cookies natifs. Les contrôles passent par les véritables routes et la base isolée ; aucune réponse de l’application n’est simulée.

| Parcours | Contrôle exécuté |
| --- | --- |
| Migration de la version installée | Exécution de la migration précédente, attribution d’un vendeur existant, puis nouvelle migration deux fois ; comparaison de toutes les colonnes existantes des cinq tables initiales |
| Conservation des droits existants | Vendeur déjà associé à une annonce conservé, lot sans vendeur laissé sans propriétaire, aucun abonnement payé créé |
| Inscription → acheteur | Compte enregistré sans accès vendeur ; champs de rôle forgés ignorés ; connexion vers le compte |
| Acheteur → demande vendeur | CSRF, identité de session, choix de formule, demande en attente, double soumission sans doublon |
| Demande → décision | Admin seul, motif de refus obligatoire, version de formulaire, journal de décision et rôle relu sans reconnexion |
| Retrait et nouvelle demande | Retrait par le propriétaire, ancienne décision refusée, changement de formule après retrait, refus puis possibilité de nouvelle demande |
| Publication | Refus HTML et API pour acheteur ou demande en attente ; autorisation après validation ; protections des photos et du vendeur conservées |
| Révocation concurrente | Deux requêtes HTTP attendent le même verrou utilisateur : suspension d’abord, publication ensuite refusée ; aucun lot et aucune photo résiduelle |
| Suspension | Création interdite, anciens lots accessibles à leur propriétaire, possibilité d’enchérir sur un autre lot, réactivation administrative explicite |
| Réexécution de migration | Aucun compte suspendu ou refusé n’est réactivé ; journal inchangé |
| Compte acheteur | Rubriques compte, offres, favoris et profil ; données limitées au compte connecté ; édition du nom public sans changement d’email, de rôle ou d’accès vendeur |
| Catalogue | Filtres catégorie/prix et ordre réel des résultats, vues grille/liste, exclusion des lots archivés/clôturés/expirés, pagination de 25 lots sans doublon ou perte |
| XSS | Nom public, nom de lot, projet vendeur et motif de refus affichés avec échappement HTML |
| Migration oubliée | Page HTTP 503 et JSON HTTP 503 sans trace SQL ; retour au fonctionnement quand la migration est présente |
| Enchères et archivage existants | Offres simultanées, prix et historique atomiques, échéance après verrou, favoris idempotents, archivage/restauration sans destruction de données |

## Limites de vérification

Les pages HTML sont réellement produites par PHP et les scripts externes sont analysés par `node --check`. Un navigateur automatisé local n’a pas pu être installé : le téléchargement de Chromium Headless Shell n’a pas fourni une archive exploitable. Aucun test de clics, capture du nouveau rendu, contrôle visuel mobile ou assertion d’absence d’erreur de console n’est annoncé.

Les essais de cette étape ne valident pas les règles Apache `.htaccess`, MySQL sur un autre serveur, la configuration HTTPS/proxy ou la facturation. Les scripts de pilotage utilisent le runtime PHP/MariaDB isolé de l’environnement de travail ; une commande de CI portable n’est pas livrée. Le fichier `config.php` du dépôt n’est pas modifié.

## Parcours de recette sur le PC du propriétaire

Après `git pull --ff-only`, exécuter `php bin/migrate.php` puis `php bin/verifier-installation.php`, relancer le serveur et actualiser avec Ctrl+F5.

Vérifier un acheteur existant sans annonce, sa demande de formule, la validation depuis « Demandes vendeur », la création avec une vraie photo, puis une offre avec un autre compte en fenêtre privée. Vérifier aussi les favoris et les filtres dans le nouveau catalogue. Le mot de passe communiqué dans la conversation n’est intégré ni aux sources, ni aux rapports.

La visioconférence collective, l’attribution finale du lot, la vérification et récupération du compte par email, les abonnements payants et la préparation de l’hébergement restent des étapes distinctes à réaliser.
