# Vactory Webform Auto Export

Ce module permet d'envoyer des CSV des soumissions des webforms périodiquement.
Cet envoi se fait grace à un cron job.

## Table of Contents
 * [Requirements](#Requirements)
 * [Installation](#Installation)
 * [Configuration](#Configuration)
 * [Maintainers](#Maintainers)


### Requirements

Module :

- webform
- webform_ui


### Installation:
`drush en vactory_webform_auto_export`

#### Configuration:

La configuration se fait au niveau de la page de téléchargement des soumissions du webform
`/admin/structure/webform/manage/{webform_id}/results/download`

Le module permet de définir pour chaque export :
- Le nombre de résultats à exporter (dernier jour, dernière semaine, dernier mois, tout)
- Période d'export (Chaque heure, chaque jour, chaque semaine, chaque mois)
- Date du premier export
- Date du dernier export

Quand l'export automatique d'un webform est activé, le module crée un enregistrement au niveau de la table `webform_auto_exports` de la BDD avec la configuration correspendante.


#### Maintainers
Rida Bouharras <r.bouharras@void.fr>
