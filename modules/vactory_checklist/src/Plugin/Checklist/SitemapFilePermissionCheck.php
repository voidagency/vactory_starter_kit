<?php

namespace Drupal\vactory_checklist\Plugin\Checklist;

use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Vérifie les permissions de création de fichiers pour les sitemaps.
 *
 * @ChecklistPlugin(
 *   id = "sitemap_file_permission_check",
 *   label = @Translation("Sitemap File Permission Check"),
 *   description = @Translation("Vérifie si PHP a la permission de créer des fichiers dans le répertoire public"),
 *   category = "analytics"
 * )
 */
class SitemapFilePermissionCheck extends ChecklistBase {

  /**
   * {@inheritdoc}
   */
  public function runCheck() {
    // Récupérer le chemin du répertoire public.
    $public_directory = PublicStream::basePath();

    // Vérifier que le répertoire public existe et est accessible en écriture.
    if (!is_dir($public_directory)) {
      return [
        'status' => FALSE,
        'message' => $this->t("Le répertoire public n'existe pas"),
        'details' => [
          ['error' => $this->t("Le chemin @path n'est pas un répertoire valide", ['@path' => $public_directory])],
        ],
      ];
    }

    // Vérifier les permissions d'écriture.
    if (!is_writable($public_directory)) {
      return [
        'status' => FALSE,
        'message' => $this->t("Le répertoire public n'est pas accessible en écriture"),
        'details' => [
          ['error' => $this->t("Le chemin @path n'a pas les permissions d'écriture", ['@path' => $public_directory])],
        ],
      ];
    }

    // Tous les tests ont réussi.
    return [
      'status' => TRUE,
      'message' => $this->t('Les permissions de création de fichiers sont correctes'),
      'details' => [],
    ];
  }

}
