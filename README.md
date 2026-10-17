# Site de vente aux enchères

Application PHP/MySQL existante, en cours de finalisation. La branche `finalisation-production` contient les corrections des enchères, puis la sécurisation des comptes et des annonces. Cette étape ne constitue pas encore une validation de mise en production.

## Récupérer cette étape sur une installation existante

Depuis le dossier du projet, sur la branche `finalisation-production` :

```bash
git pull --ff-only
```

Si le téléchargement réussit, appliquer les ajouts de structure à la base déjà configurée :

```bash
php bin/migrate.php
php bin/verifier-installation.php
```

Le `config.php` n’est pas modifié par cette étape. Les anciens fichiers locaux de reconstitution ne sont ni importés, ni exécutés par ces commandes. Aucun compte et aucune annonce ne sont ajoutés par la migration.

La migration ajoute `produits.vendeur_id`, `produits.archive_le`, les compteurs de connexion et un registre des migrations. Elle conserve les colonnes et les données existantes. Les anciens lots restent avec un vendeur « Non renseigné » : leur propriétaire ne peut pas être déduit de leur catégorie. Les nouveaux lots sont attribués au compte connecté.

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

## Comptes et administration

L’inscription crée uniquement un membre. Le nom public apparaît dans les offres. Les nouveaux mots de passe comportent 15 à 128 caractères ; si Argon2id est disponible, il est utilisé. Sinon, le repli bcrypt refuse les inscriptions dépassant sa limite de 72 octets. Les anciens hachages restent vérifiables.

Les formulaires disposent d’un jeton CSRF. La connexion renouvelle l’identifiant de session ; la déconnexion passe par POST. Les sessions ont une limite d’inactivité de 30 minutes et une durée maximale de huit heures. Les droits sont relus en base : une révocation administrative s’applique à la prochaine requête.

La limitation des tentatives est stockée en base : cinq tentatives par combinaison adresse IP/email et 50 par IP sur 15 minutes pour la connexion ; cinq inscriptions par IP sur 15 minutes. Changer de cookie ne remet pas le compteur à zéro. La prise en compte d’un éventuel proxy devra être configurée avec l’hébergement.

Pour attribuer le rôle administrateur à **son compte existant**, depuis le terminal du serveur :

```bash
php bin/promouvoir-admin.php votre-adresse@example.com
```

Cette commande ne crée pas de compte et ne change pas son mot de passe. Les scripts `bin/` refusent leur exécution par HTTP.

L’administration permet de clôturer, d’archiver et de restaurer une annonce. L’archivage conserve les offres, la photo et le propriétaire. Restaurer une annonce archivée ne rouvre pas ses enchères. Les anciens liens de suppression par GET sont refusés.

## Annonces, photos et enchères

« Mes annonces » affiche uniquement les annonces du compte connecté. Les nouvelles annonces disposent d’un vendeur ; celui-ci ne peut pas enchérir sur son propre lot, y compris par une requête directe à l’API.

Le contrôle des photos utilise le contenu du fichier, son type réel, ses dimensions et sa taille. Formats : JPEG, PNG et WebP ; limite de 5 Mo, 8192 pixels par côté et 20 millions de pixels. Les noms sont générés par le serveur. Une photo JPEG appelée `photo.php` est enregistrée comme une image sous un nom aléatoire ; un programme PHP déguisé en JPEG est refusé.

Le dossier par défaut est `encheres-stockage/photos`, à côté du dossier du projet. Si ce parent fait partie du répertoire public de l’hébergement, définir `ENCHERES_UPLOAD_DIR` vers un emplacement privé. Par exemple, pour une session Git Bash sous Windows, adapter le chemin à un disque et un dossier existants :

```bash
export ENCHERES_UPLOAD_DIR='E:/Encheres_stockage/photos'
```

Ce réglage doit aussi être fourni au processus Apache/PHP quand ce serveur est utilisé. Le contrôle HTTP refuse un dossier de photos situé sous son `DOCUMENT_ROOT`. Le contrôle CLI ne connaît pas forcément cette racine HTTP : vérifier également la création d’une annonce depuis le site.

Les nouvelles photos passent par `photo.php?id=…`. Les anciennes photos présentes dans `img-prod/` restent lisibles. Une erreur SQL avant validation de la publication nettoie la nouvelle photo provisoire. Si la confirmation d’un commit est incertaine, la photo est conservée pour éviter de casser une annonce éventuellement enregistrée.

Les enchères conservent le verrou SQL, les montants comparés en centimes et le contrôle de l’heure après l’attente du verrou. Les favoris demandent explicitement l’état souhaité : répéter un ajout ne le transforme pas en retrait. La recherche conserve le filtre de catégorie et construit les résultats avec `textContent`.

## Vérification manuelle après installation

1. Actualiser le site avec Ctrl+F5, puis se déconnecter et se reconnecter.
2. Créer une annonce avec une vraie photo. Vérifier sa présence dans « Mes annonces » et son vendeur sur la fiche.
3. Vérifier que le vendeur ne peut pas enchérir sur son annonce.
4. Ouvrir un autre compte dans une fenêtre privée, déposer une offre puis vérifier le prix et l’historique dans les deux fenêtres.
5. Vérifier la recherche, l’effacement de la recherche et les favoris dans une catégorie.
6. Avec le compte administrateur, archiver un lot puis le restaurer ; vérifier que l’historique est conservé et que les enchères restent clôturées.

Le détail des contrôles réalisés est dans `docs/verification-comptes-annonces.md` et son rapport JSON. Les requêtes HTTP, PHP et SQL sont réelles ; les données d’essai ne sont créées que dans une base isolée. L’interaction dans un vrai navigateur et les règles Apache n’ont pas été validées automatiquement.

## Étapes restantes

L’attribution définitive du lot et du gagnant, l’authentification par email avec récupération de compte, la visioconférence collective et la refonte de l’interface restent à réaliser. Avant déploiement, préparer la configuration de production, les droits SQL, HTTPS et les cookies derrière le proxy, les sauvegardes de la base et des photos, la tâche de clôture planifiée et des essais dans un vrai navigateur sur l’hébergement cible.

La configuration locale de reconstitution, si elle est encore utilisée, reste réservée aux connexions locales. Elle devra être remplacée par une configuration adaptée lors de l’étape de déploiement.
