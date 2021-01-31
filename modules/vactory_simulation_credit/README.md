## Vactory Simulation Crédit

Le module simulateur de crédit permet aux internautes (clients et prospects)
de simuler leurs prêts et de faire leurs demandes de crédit en ligne.

Le module simulateur de crédit offre également une calculette pour
simuler la capacité d’emprunt en renseignant la durée et la mensualité.

Le simulateur de crédit permet aux utilisateurs d’avoir une visibilité sur
les caractéristiques principales de l’emprunt souhaité :

- La capacité d'emprunt.

- Le taux.

- La durée.

- La mensualité.

- Le coût total du crédit.

## Table ds matière

 * [Installation](#installation)

 * [Configuration](#configuration)

 * [Theming] (#Theming)

 * [Formules de calcul] (#Formules de calcul)

 * [Maintainers](#Maintainers)

### Installation

Activation du module via drush :  `drush en vactory_simulation_credit`

### Configuration

La page de configuration de module est accessible via le chemin suivant:

  `/admin/config/vactory-simulation-credit`

Vous pouvez configurer le module pour préciser:

* Les profils avec leurs caractéristiques (Les profiles ce sont des termes
de la taxonomie : simulation_credit_profiles).

* L'activation du mode profil.

* L'activation du mode simulation sans demande.

### Theming

On a 3 blocs :

- Bloc pour la simulation de credit.

- Bloc pour La simulation de la capacité d'emprunt.

- Bloc engendre les 2 blocs (Simulation crédit et Capacité d'emprunt).

Pour chaque bloc que ça soit (Block de simulation de crédit ou bien Bloc
de simulation de capacité de d'emprunt) on distingue 3 templates :

- X-form.html.twig : Template correspond au formulaire.

- X-summary.html.twig : Template correspond au récapulatif de la simulation
(les caractéristiques de la simulation).

- X-block.html.twig : Template correspond au bloc.

On disting aussi la template qui engendre les 2 block :
    - simulation-block.html.twig.

### Formule de calcul

* Formulaire pour calculer la mensualité :

    - M = (C * (T / 12)) / (1 - pow (1 + T / 12, - N))

* Formulaire pour calculter la capacité d'emprunt :

    - C = 12 * M * [(1 - pow ( 1 + T/12, - N)) / T]

    M : Mensualité

    C : Capacité emprunté

    T : Taux en %

    N : Durée en mois.

### Maintainers

BOUHOUCH Khalid

<k.bouhouch@void.fr>
