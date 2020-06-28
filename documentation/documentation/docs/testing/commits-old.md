Git dispose d’un moyen de lancer des scripts personnalisés quand certaines actions importantes ont lieu.
Il y a deux groupes de crochets : ceux côté client et ceux côté serveur.
- Les crochets côté client concernent les opérations de client telles que la validation et la fusion.
- Les crochets côté serveur concernent les opérations de serveur Git telles que la réception de commits.

Nous utilisons les crochets côté client (notamment le Hook pre-commit) afin de vérifier que l’ensemble
des fichiers qui constituent un projet sont valides syntaxiquement et sémantiquement avant d’établir
un commit sur le projet.

## PERSONNALISER LE HOOK PRE-COMMIT

- Soyez sûr que vous êtes bien sur la racine du projet drupal :
```code
$> cd path/to/drupal_project
```

- Placer le fichier `docs_factory/git-hooks/pre-commit` dans `.git/hooks/pre-commit`
```code
$> cp docs_factory/git-hooks/pre-commit .git/hooks/pre-commit
```

Il s’agit d’un script contenant l’ensemble des traitements nécessaires pour valider les fichiers du projet syntaxiquement
et sémantiquement, le script fait appel à des outils (PHP CodeSniffer, Drupal coder, esvalidate) prévu être
disponibles/installés sur la machine (voir la séction Pré-requis).

```hint
Le script pre-commit ci-dessus est conçu pour vérifier et valider les points suivants :

- Les erreurs relatives au syntaxe PHP
- Le codage standard du drupal (Drupal coding standards)
- Le codage standard du JavaScript et CSS.
```

## TEST

Sur un projet drupal, effectuer des modifications sur quelques fichiers
php, js et css. Essayer par la suite de commiter les modifications en utilisant la commande :

```code
$> git commit -am 'message de commit'
```

Le hooks pre-commit de git sera déclenché directement après avoir cliqué sur `Entrer` et validera toutes les fichiers modifiés (notez que le
hook pre-commit du git valide uniquement les fichiers modifiés de la
commit et non pas l’ensemble des fichiers du projet), si une erreur de
syntaxe est trouvée le script va l’afficher sur terminale et annuler le
commit, sinon c’est-à-dire pas d’erreur le commit va être établi
comme d’habitude.

Pour demander du git de ne pas valider et vérifier les nouvelles
modifications on doit ajouter à la fin de la commande du commit
l’option `–-no-verify`

```code
$> git commit -am 'message de commit' --no-verify
```

