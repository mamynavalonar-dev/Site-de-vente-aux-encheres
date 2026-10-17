# Site de vente aux enchères

Application PHP/MySQL existante, en cours de finalisation. La branche `finalisation-production` contient les corrections des enchères, la sécurisation des comptes et le parcours visiteur → acheteur → vendeur. Cette étape ne constitue pas encore une validation de mise en production.

## Récupérer cette étape sur une installation existante

Arrêter le serveur PHP avec Ctrl+C s’il est lancé. Depuis le dossier du projet, sur la branche `finalisation-production` :

```bash
git pull --ff-only
```

Si le téléchargement réussit, appliquer les ajouts de structure à la base déjà configurée :

```bash
php bin/migrate.php
php bin/verifier-installation.php
```

Le `config.php` n’est pas modifié par cette étape. Les anciens fichiers locaux de reconstitution ne sont ni importés, ni exécutés par ces commandes. Aucun compte et aucune annonce ne sont ajoutés par la migration.

La migration ajoute, si nécessaire, `produits.vendeur_id`, `produits.archive_le`, les compteurs de connexion et un registre des migrations, puis les tables `seller_access` et `seller_events`. Elle conserve les colonnes et les données existantes. Les anciens lots sans vendeur restent « Non renseigné » : leur propriétaire ne peut pas être déduit de leur catégorie. Les comptes déjà associés à une annonce conservent leur accès vendeur avec une origine historique, sans paiement ou abonnement inventé. Les nouvelles inscriptions restent uniquement acheteur.

Les changements de structure MySQL/MariaDB ne sont pas annulables comme une transaction métier. Si le script s’interrompt, lire son erreur, corriger la cause puis le relancer : il reconnaît ses ajouts déjà effectués. Il refuse une base dont les cinq tables initiales sont absentes ou ne sont pas en InnoDB. Une sauvegarde de la base existante doit accompagner une intervention sur une installation utilisée.

## Prérequis locaux

- PHP 8.2 ou supérieur ; extensions `pdo_mysql` et `fileinfo`.
- MySQL/MariaDB avec les tables existantes en InnoDB. Cette version a été vérifiée sous PHP 8.3.6 et MariaDB 10.11.7 ; MySQL n’a pas été exécuté pendant ces essais.
- Un accès SQL autorisé à ajouter des colonnes, des contraintes et des tables pour la migration.
- Un dossier de photos accessible en écriture, situé hors du répertoire public.

Si la commande `php` est introuvable dans Git Bash, utiliser le chemin réel du PHP installé avec XAMPP/WAMP. Vérifier `php -v` et `php --ini` pour contrôler la version et le fichier de configuration utilisés en ligne de commande.

Le serveur de développement PHP peut servir à une vérification locale :

```bash
php -S 127.0.0.1:8000 -t .
```

Il ne remplace pas un serveur d’hébergement. Il ignore les règles `.htaccess`.

## Visiteur, acheteur et vendeur

- Le visiteur consulte le catalogue, les catégories, les filtres et les fiches. Pour déposer une offre ou conserver des favoris, il se connecte.
- L’inscription crée un compte acheteur. Après connexion, `compte.php` propose « Mes enchères », « Mes favoris », « Mes informations » et le bouton « Devenir vendeur ».
- `devenir_vendeur.php` présente les formules Essentiel, Standard et Premium. Le compte choisit une formule et transmet son projet ; la demande reste en attente.
- Un administrateur examine les demandes dans `admin_vendeurs.php`, accessible depuis son menu de compte ou depuis l’administration des annonces.
- Seule une validation administrative donne accès à la publication. Un vendeur conserve ses droits d’acheteur et ne peut pas enchérir sur son propre lot.
- Une suspension bloque les nouvelles publications, y compris une requête directe et une publication qui attend un verrou SQL. Les annonces et les offres existantes sont conservées et restent consultables selon leur visibilité.

Les captures de référence ne définissent ni tarifs, ni périodicité, ni prestataire de paiement. Cette étape utilise donc de vraies demandes et décisions enregistrées, avec des conditions sur devis. Elle ne simule aucun paiement et ne crée aucun abonnement facturé. Le raccordement d’un abonnement payant nécessitera les tarifs, la devise, la périodicité, le prestataire et une confirmation serveur du paiement.

Les demandes peuvent être retirées puis représentées. Un refus indique son motif au propriétaire de la demande. Une suspension nécessite une réactivation administrative. Les décisions utilisent une version de formulaire pour refuser les décisions périmées, et un journal SQL conserve leurs événements.

La navigation horizontale, le catalogue avec vues grille/liste, les filtres, le compte, les formules et la fiche produit partagent une présentation bleu/jaune et des ressources locales. Les images affichées restent celles des véritables lots de la base. Aucun logo, témoignage ou chiffre commercial d’Agorastore n’est repris.

## Comptes et administration

L’inscription crée uniquement un acheteur ; le rôle administrateur et l’accès vendeur ne sont pas acceptés depuis le formulaire. Le nom public apparaît dans les offres. Les nouveaux mots de passe comportent 15 à 128 caractères ; si Argon2id est disponible, il est utilisé. Sinon, le repli bcrypt refuse les inscriptions dépassant sa limite de 72 octets. Les anciens hachages restent vérifiables.

Les formulaires disposent d’un jeton CSRF. La connexion renouvelle l’identifiant de session ; la déconnexion passe par POST. Les sessions ont une limite d’inactivité de 30 minutes et une durée maximale de huit heures. Les droits sont relus en base : une révocation administrative s’applique à la prochaine requête.

La limitation des tentatives est stockée en base : cinq tentatives par combinaison adresse IP/email et 50 par IP sur 15 minutes pour la connexion ; cinq inscriptions par IP sur 15 minutes. Changer de cookie ne remet pas le compteur à zéro. La prise en compte d’un éventuel proxy devra être configurée avec l’hébergement.

Pour attribuer le rôle administrateur à **son compte existant**, depuis le terminal du serveur :

```bash
php bin/promouvoir-admin.php votre-adresse@example.com
```

Cette commande ne crée pas de compte et ne change pas son mot de passe. Les scripts `bin/` refusent leur exécution par HTTP.

L’administration permet de clôturer, d’archiver et de restaurer une annonce. L’archivage conserve les offres, la photo et le propriétaire. Restaurer une annonce archivée ne rouvre pas ses enchères. Les anciens liens de suppression par GET sont refusés.

## Annonces, photos et enchères

« Mes annonces » affiche uniquement les annonces du compte connecté. La création HTML et l’API de publication exigent un accès vendeur actif. Les nouvelles annonces disposent d’un vendeur ; celui-ci ne peut pas enchérir sur son propre lot, y compris par une requête directe à l’API. Le droit de publication est revérifié dans la transaction d’insertion, après toute attente de verrou.

Le contrôle des photos utilise le contenu du fichier, son type réel, ses dimensions et sa taille. Formats : JPEG, PNG et WebP ; limite de 5 Mo, 8192 pixels par côté et 20 millions de pixels. Les noms sont générés par le serveur. Une photo JPEG appelée `photo.php` est enregistrée comme une image sous un nom aléatoire ; un programme PHP déguisé en JPEG est refusé.

Le dossier par défaut est `encheres-stockage/photos`, à côté du dossier du projet. Si ce parent fait partie du répertoire public de l’hébergement, définir `ENCHERES_UPLOAD_DIR` vers un emplacement privé. Par exemple, pour une session Git Bash sous Windows, adapter le chemin à un disque et un dossier existants :

```bash
export ENCHERES_UPLOAD_DIR='E:/Encheres_stockage/photos'
```

Ce réglage doit aussi être fourni au processus Apache/PHP quand ce serveur est utilisé. Le contrôle HTTP refuse un dossier de photos situé sous son `DOCUMENT_ROOT`. Le contrôle CLI ne connaît pas forcément cette racine HTTP : vérifier également la création d’une annonce depuis le site.

Les nouvelles photos passent par `photo.php?id=…`. Les anciennes photos présentes dans `img-prod/` restent lisibles. Une erreur SQL avant validation de la publication nettoie la nouvelle photo provisoire. Si la confirmation d’un commit est incertaine, la photo est conservée pour éviter de casser une annonce éventuellement enregistrée.

Les enchères conservent le verrou SQL, les montants comparés en centimes et le contrôle de l’heure après l’attente du verrou. Les favoris demandent explicitement l’état souhaité : répéter un ajout ne le transforme pas en retrait. La recherche conserve le filtre de catégorie et construit les résultats avec `textContent`.

## Vérification manuelle après installation

1. Actualiser avec Ctrl+F5. En visiteur, vérifier l’absence du bouton vendeur dans l’en-tête, puis consulter un lot.
2. S’inscrire ou ouvrir un compte acheteur sans annonce existante. Vérifier le bouton « Devenir vendeur » et les rubriques du compte.
3. Choisir une formule, décrire le projet et envoyer la demande. Vérifier son état en attente et l’impossibilité de publier.
4. Depuis un compte administrateur, ouvrir « Demandes vendeur », puis valider cette demande.
5. Actualiser le compte demandeur : « Espace vendeur » et « Créer une annonce » deviennent accessibles sans changer de compte.
6. Créer une annonce avec une vraie photo. Vérifier « Mes annonces », son vendeur et l’interdiction d’enchérir sur son propre lot.
7. Ouvrir un autre compte dans une fenêtre privée, déposer une offre et contrôler le prix ainsi que l’historique dans les deux fenêtres.
8. Vérifier recherche, catégorie, fourchette de prix, tri, vues grille/liste et favoris. Vérifier les rubriques « Mes enchères » et « Mes favoris ».
9. Suspendre le vendeur depuis l’administration : ses anciennes annonces restent accessibles, la création est bloquée, son compte reste acheteur.
10. Archiver un lot puis le restaurer : l’historique reste conservé et les enchères restent clôturées.

La dernière étape comporte 203 contrôles natifs réussis et 27 fichiers PHP valides. Le détail est dans `docs/verification-parcours-vendeur.md` et son rapport JSON. Les requêtes HTTP, PHP et SQL sont réelles ; les données d’essai ne sont créées que dans une base isolée. L’interaction dans un vrai navigateur, la présentation sur mobile et les règles Apache n’ont pas été validées automatiquement. Les rapports de l’étape précédente restent dans `docs/verification-comptes-annonces.*`.

Si les fichiers ont été mis à jour avant la migration, le site répond HTTP 503 avec une page de maintenance, et les API répondent JSON 503. Les journaux PHP indiquent la commande `php bin/migrate.php`, sans exposer de trace SQL au visiteur.

## Étapes restantes

L’attribution définitive du lot et du gagnant, l’authentification par email avec récupération de compte, la visioconférence collective et la refonte de l’interface restent à réaliser. Avant déploiement, préparer la configuration de production, les droits SQL, HTTPS et les cookies derrière le proxy, les sauvegardes de la base et des photos, la tâche de clôture planifiée et des essais dans un vrai navigateur sur l’hébergement cible.

La configuration locale de reconstitution, si elle est encore utilisée, reste réservée aux connexions locales. Elle devra être remplacée par une configuration adaptée lors de l’étape de déploiement.
