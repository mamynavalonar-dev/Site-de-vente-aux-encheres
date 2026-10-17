# Vérification des comptes et des annonces — 17 octobre 2026

Parcours contrôlé : un membre s’inscrit, se connecte, publie une annonce avec une photo, reçoit une offre d’un autre membre et retrouve son annonce ; un administrateur peut l’archiver sans perdre ses offres ni sa photo.

Base du changement : commit `63dd228f9a20f80c02d491e465fcb359b32922ed` de `finalisation-production`.

## Résultat

129 contrôles passent, avec 21 fichiers PHP vérifiés syntaxiquement. Exécution sur PHP 8.3.6, MariaDB 10.11.7 et le serveur HTTP natif de PHP avec quatre processus. Les appels utilisent de vrais cookies de session, des formulaires multipart, des fichiers JPEG et des transactions InnoDB. Aucune réponse de l’application n’est simulée.

Les comptes et lots de contrôle sont créés dans une base isolée, sur un port local réservé aux essais. Le fichier `config.php` est remplacé uniquement dans la copie de test ; celui des sources livrées est inchangé. Aucun compte prédéfini ni génération de données ne sont intégrés aux parcours de l’application.

| Frontière | Résultat | Preuve |
| --- | --- | --- |
| Migration → données existantes | Vérifié | Comparaison des cinq tables sur leurs colonnes initiales avant et après migration ; deuxième exécution réussie |
| Formulaire → authentification | Vérifié par HTTP | Inscription, hachage, connexion, CSRF, changement de session, déconnexion et ancien cookie invalidé |
| Session → droits | Vérifié | Rôle imposé à membre ; promotion CLI ; révocation d’un administrateur appliquée sans reconnexion |
| Formulaire → annonce et stockage | Vérifié par HTTP/SQL | Vendeur issu de la session, prix décimal exact, image enregistrée hors du répertoire public |
| Fichier → photo publique | Vérifié | Faux JPEG et dépassement de taille refusés ; contenu JPEG réel et en-têtes GET/HEAD corrects |
| Offre → transaction et historique | Vérifié | Refus du vendeur ; offre d’un autre compte ; contrôle du prix et de l’historique renvoyés |
| Échec SQL → cohérence | Vérifié | Erreurs SQL provoquées dans la base isolée : offre et prix annulés ensemble ; photo provisoire nettoyée lors d’un échec de publication |
| Administration → conservation | Vérifié | GET et CSRF invalides refusés ; archivage conservant offres et photo ; restauration sans réouverture |
| PHP → HTML et JavaScript | Partiellement vérifié | Pages rendues sans erreur PHP, scripts rendus analysés par `node --check`, structure du titre et de la grille contrôlée |
| Navigateur → clics → rendu final | À vérifier | Aucun navigateur local utilisable n’est installé dans l’environnement d’essai ; aucun test visuel ou d’interaction navigateur n’est annoncé |
| Apache → règles d’accès | À vérifier | Le serveur natif PHP ignore `.htaccess` ; valider ces règles sur le serveur cible |

## Offres concurrentes

Un client SQL conserve d’abord le verrou du lot. Deux requêtes HTTP arrivent ensuite et restent en attente. La présence des deux requêtes dans `information_schema.PROCESSLIST` et d’au moins une attente dans `INNODB_LOCK_WAITS` est contrôlée avant de libérer le verrou.

| Ordre d’arrivée des offres | Réponses HTTP | Prix final | Offres enregistrées |
| --- | --- | --- | --- |
| 120,00 puis 110,00 | 200 puis 409 | 120,00 | 120,00 |
| 110,00 puis 120,00 | 200 puis 200 | 120,00 | 110,00 ; 120,00 |
| Même session : 120,00 puis 110,00 | 200 puis 409 | 120,00 | 120,00 |

Un quatrième scénario conserve le verrou jusqu’après l’heure de fin. L’offre est alors refusée avec HTTP 409 sans modification du prix ni ajout d’historique. Cette vérification utilise le temps réel de MariaDB et des requêtes HTTP, sans horloge ni réponse simulées.

## Limites et suite

Ces résultats portent sur cette étape, pas sur l’ensemble des exigences de production. Le rapport JSON détaille chaque contrôle. Les scripts qui pilotent les essais utilisent un runtime PHP/MariaDB isolé de l’environnement de développement ; une commande de CI portable n’est pas encore livrée.

L’interface doit encore être parcourue dans un vrai navigateur, notamment la recherche, les favoris, les messages de succès et les deux sessions d’enchérisseurs. MySQL n’a pas été exécuté pendant ces essais. La configuration d’hébergement, les règles Apache, le proxy HTTPS, les sauvegardes et la clôture planifiée nécessitent leur propre validation.

Documentation primaire consultée : [hachage PHP](https://www.php.net/manual/en/function.password-hash.php), [transferts de fichiers PHP](https://www.php.net/manual/en/features.file-upload.php), [lectures verrouillées InnoDB](https://dev.mysql.com/doc/refman/8.4/en/innodb-locking-reads.html).
