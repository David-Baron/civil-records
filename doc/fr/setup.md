# Setup de Civil-Records

## Installation propre ou nouvelle

- Téléchargez le package
- Décompressez les fichiers dans un dossier vide sur votre serveur.
- Créez un domaine ou un sous-domaine pointant vers le dossier public Civil-Records.
- Démarrez Civil-records à l'adresse http://votre-sous-domaine.
- Le programme d'installation démarre automatiquement.
- Il vous suffit de suivre les instructions.

## Mise à jour

- Créez un dossier vide sur votre serveur.
- Téléchargez le package
- Décompressez les fichiers dans le dossier que vous avez créé.
- Déplacez ou copiez votre dernier répertoire _storage dans le nouveau dossier.
- Déplacez ou copiez votre dernier fichier .env dans le nouveau dossier.
- Renommez le dernier répertoire Civil-Record en le plus récent.
- Renommez le nouveau dossier comme votre dernier nom.
- Démarrez Civil-records à l'adresse http://votre-sous-domaine.
- Le programme de mise à jour de l'installation démarre automatiquement.
- Il vous suffit de suivre les instructions.

## Mise à jour d'Expoactes 3.2.6

- Bientôt disponible

## Particularités Serveur

Civil-Record est développé pour être une application et non un module.
Civil-record ne gère (pour le moment) qu'une seule base de données.

Si vous avez un serveur Apache, vous aurez besoin d'un fichier htaccess dans le dossier public.
Un example est fourni avec le package.

Si vous êtes sous Nginx, nous ne testons pas Civil-Record sur ce serveur.
Veuillez adapter le fichier htaccess en config pour Nginx.
