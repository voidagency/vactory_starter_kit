# Vactory Appointment
Ce module a été développé afin de permettre la prise des rendez-vous.

### Installation
Activation du module via la commande drush suivante :
`drush en -y vactory_appointment`

### Configuration
1- Ajouter les motifs de RDV dans la taxonomie RDF - Motifs:
`/admin/structure/taxonomy/manage/vactory_appointment_motifs/add`

**NB**: Le "Path motif name" ne doit pas contenir des espaces ou des 
caractères spéciaux.

2- Vérifier l'activation de l'option 'Prise de RDV' pour les/l'agence(s)
concernée(s) au niveau de "Locator Entities"

3- Ajouter un utilisateur de type "Conseiller" et vérifier l'existence
des champs [agences, firstname, lastname].

**NB**: Si les champs [agences, firstname, lastname] n’apparaissent pas
sur le formulaire, il faut les ajouter suivant le lien :
`admin/config/people/accounts/form-display`

### Guide d'utilisation
Aller sur le lien : `/prendre-un-rendez-vous`
* Choisir une agence
* Prendre le rendez-vous
* Choisir le conseiller
* Choisir la date et l'heure du rendez-vous
* Saisir les informations personnelles
* Dernière étape pour la confirmation du rendez-vous

**NB**: Vous pouvez modifier votre RDV en cliquant sur le boutton "Modifier
votre RDV", qui vous vous demande le numéro de téléphone avec lequel le RDV
est pris.

Vous pouvez consulter la liste des RDV prise par les utilisateurs suivant 
ce lien : `/admin/structure/vactory_appointment`

### Docs on Gitbook
https://voidagency.gitbook.io/factory/modules-vactory/untitled/appointment

### Maintainers
Brahim Khouy <b.khouy@void.fr>
