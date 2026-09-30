<?php

namespace Drupal\vactory_admin_hardening\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Denies web access to sensitive administration surfaces.
 *
 * Two families of admin routes are equivalent to code execution for whoever
 * can reach them and are never needed over the web here:
 *
 * - The migrate administration UI. Migrations run arbitrary process plugins;
 *   they are driven through Drush (which bypasses the router).
 * - The core configuration UI (import/export/sync). Configuration is managed
 *   through Features and Drush; the single-import page in particular can create
 *   arbitrary config entities. Features has its own UI under
 *   "/admin/config/development/features" (routes named "features.*") which is
 *   intentionally NOT blocked.
 *
 * Every matching route is denied for everyone, including uid 1.
 */
class SensitiveAdminRouteSubscriber extends RouteSubscriberBase {

  /**
   * Path prefixes denied over HTTP.
   *
   * "/admin/config/development/configuration" matches the core config UI but
   * not "/admin/config/development/features" (Features), nor
   * "/admin/config/development/backup_migrate" (the unrelated backup module).
   */
  protected const DENIED_PATH_PREFIXES = [
    // Migrate framework UI.
    '/admin/structure/migrate',
    '/admin/structure/migration',
    '/admin/config/system/migrate-plugin',
    '/admin/reports/migration-messages',
    // Core configuration manager UI (config.* routes).
    '/admin/config/development/configuration',
  ];

  /**
   * Route name prefixes denied over HTTP.
   *
   * The migration config entities exposed over JSON:API.
   */
  protected const DENIED_ROUTE_NAME_PREFIXES = [
    'jsonapi.migration--',
    'jsonapi.migration_group--',
  ];

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($collection as $name => $route) {
      if ($this->matchesPath($route->getPath()) || $this->matchesName($name)) {
        // Deny for everyone, including uid 1: use Drush or Features instead.
        $route->setRequirement('_access', 'FALSE');
      }
    }
  }

  /**
   * Whether a path is under one of the denied prefixes.
   */
  protected function matchesPath(string $path): bool {
    foreach (self::DENIED_PATH_PREFIXES as $prefix) {
      if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Whether a route name starts with one of the denied prefixes.
   */
  protected function matchesName(string $name): bool {
    foreach (self::DENIED_ROUTE_NAME_PREFIXES as $prefix) {
      if (str_starts_with($name, $prefix)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
