# Vactory Diff

Module Drupal pour la comparaison de configurations entre différentes instances.

## Description

Vactory Diff est un module Drupal qui permet de comparer les configurations
entre une instance locale et une instance distante.Il se compose d'un module
principal et de deux sous-modules :

- **vactory_diff** : Module principal contenant les services communs
- **vactory_diff_config_server** :
Expose une API REST pour fournir les configurations
- **vactory_diff_config_client** :
Interface pour récupérer et comparer les configurations distantes

## Fonctionnalités

### Module Serveur (vactory_diff_config_server)

- API REST accessible via `/api/vactory-diff/config/export`
- Export de toutes les configurations du site au format JSON
- Métadonnées incluses (nom du site, timestamp, UUID)
- Headers CORS configurés pour l'accès distant

### Module Client (vactory_diff_config_client)

- Interface d'administration pour configurer l'URL distante
- Test de connexion au serveur distant
- Comparaison automatique des configurations
- Affichage détaillé des différences avec interface à onglets
- Sauvegarde des résultats de comparaison

## Installation

1. Placez le module dans `profiles/contrib/vactory_starter_kit/modules/`
2. Activez les modules via Drush ou l'interface d'administration :
   ```bash
   drush en vactory_diff vactory_diff_config_server vactory_diff_config_client
   ```

## Configuration

### Serveur

Aucune configuration requise.
L'API est immédiatement disponible après activation.

### Client

1. Accédez à **Administration > Configuration > Développement > Vactory Diff**
2. Configurez l'URL de l'instance distante
3. Testez la connexion
4. Lancez la comparaison

## Utilisation

### API REST (Serveur)

**Endpoint :** `GET /api/vactory-diff/config/export`

**Réponse :**
```json
{
  "timestamp": "2025-09-25T10:30:00Z",
  "site_name": "Mon Site Drupal",
  "site_uuid": "12345678-1234-1234-1234-123456789012",
  "configs": {
    "system.site": {...},
    "field.storage.node.field_example": {...}
  },
  "count": 150
}
```

### Interface Client

1. **Paramètres** (`/admin/config/development/vactory-diff/settings`)
   - Configuration de l'URL distante
   - Réglage du timeout de connexion
   - Test de connexion

2. **Comparaison** (`/admin/config/development/vactory-diff/compare`)
   - Lancement de la comparaison
   - Affichage des résultats par catégorie :
     - Configurations ajoutées (locales uniquement)
     - Configurations modifiées (différentes)
     - Configurations supprimées (distantes uniquement)

## Permissions

- **Administrer Vactory Diff** : Accès aux interfaces d'administration
- **Accéder à l'export de configuration** : Accès à l'API REST

## Structure des fichiers

```
vactory_diff/
├── vactory_diff.info.yml
├── vactory_diff.module
├── vactory_diff.services.yml
├── vactory_diff.permissions.yml
├── vactory_diff.links.menu.yml
├── vactory_diff.links.task.yml
├── src/Service/ConfigHelperService.php
├── modules/
│   ├── vactory_diff_config_server/
│   │   ├── vactory_diff_config_server.info.yml
│   │   ├── vactory_diff_config_server.routing.yml
│   │   ├── vactory_diff_config_server.services.yml
│   │   └── src/Controller/ConfigExportController.php
│   └── vactory_diff_config_client/
│       ├── vactory_diff_config_client.info.yml
│       ├── vactory_diff_config_client.routing.yml
│       ├── vactory_diff_config_client.services.yml
│       ├── vactory_diff_config_client.libraries.yml
│       ├── config/install/vactory_diff_config_client.settings.yml
│       ├── css/comparison.css
│       └── src/
│           ├── Controller/ConfigCompareController.php
│           ├── Form/VactoryDiffSettingsForm.php
│           └── Service/ConfigComparisonService.php
└── README.md
```

## Compatibilité

- Drupal 9.x et 10.x
- PHP 8.0+
- Dépendances : modules core Drupal (serialization, rest, hal)

## Développement

### Standards de code

Le module respecte les standards Drupal :
- Drupal Coding Standards (PHPCS)
- Documentation PHPDoc
- Architecture Symfony/Drupal
- Dependency Injection

### Tests

Structure préparée pour l'ajout de tests unitaires et fonctionnels.

## Sécurité

**Important :**
La version actuelle ne comprend pas de sécurité avancée sur l'API REST.
Pour un environnement de production, il est recommandé d'ajouter :

- Authentification API (tokens, OAuth)
- Restriction d'accès par IP
- Rate limiting
- Validation des données

## Support

Pour signaler des bugs ou demander des fonctionnalités,
veuillez utiliser l'issue tracker du projet.

## Licence

Module distribué sous licence GPL-2.0+. 
