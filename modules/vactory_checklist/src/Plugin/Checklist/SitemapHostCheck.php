<?php

namespace Drupal\vactory_checklist\Plugin\Checklist;

use Drupal\Core\Site\Settings;
use GuzzleHttp\Exception\RequestException;

/**
 * Vérifie que les sitemaps contiennent des URLs valides.
 *
 * @ChecklistPlugin(
 *   id = "sitemap_url_check",
 *   label = @Translation("Sitemap URLs Check"),
 *   description = @Translation("Vérifie que les sitemaps contiennent des URLs valides avec le bon host"),
 *   category = "analytics"
 * )
 */
class SitemapHostCheck extends ChecklistBase {

  /**
   * {@inheritdoc}
   */
  public function runCheck() {
    $frontend_url = Settings::get('BASE_FRONTEND_URL', '');

    // Vérifier si frontend_url est configuré.
    if (empty($frontend_url)) {
      return [
        'status' => FALSE,
        'message' => $this->t("L'URL du frontend n'est pas configurée"),
        'details' => [
          ['error' => "La variable 'BASE_FRONTEND_URL' n'est pas définie"],
        ],
      ];
    }

    $frontend_url = rtrim($frontend_url, '/');
    $languages = \Drupal::languageManager()->getLanguages();
    $errors = [];

    foreach ($languages as $language) {
      $langcode = $language->getId();
      $sitemap_url = $frontend_url . '/' . $langcode . '/sitemap.xml';

      try {
        $response = \Drupal::httpClient()->get($sitemap_url);
        $content = (string) $response->getBody();

        try {
          $xml = new \SimpleXMLElement($content);

          // Vérifier si le XML est un sitemap valide.
          if ($xml->getName() !== 'urlset') {
            $errors[] = [
              'error' => $this->t('Format de sitemap invalide pour la langue @lang : racine urlset manquante',
                ['@lang' => $langcode]),
            ];
            continue;
          }

          // Si aucune URL n'est trouvée.
          if ($xml->count() === 0) {
            $errors[] = [
              'error' => $this->t('Aucune URL trouvée dans le sitemap pour la langue @lang',
                ['@lang' => $langcode]),
            ];
            continue;
          }

          // Vérifier les URLs jusqu'à trouver une erreur.
          foreach ($xml->url as $url_entry) {
            if (empty($url_entry->loc)) {
              continue;
            }

            $url = (string) $url_entry->loc;
            $expected_prefix = $frontend_url . '/' . $langcode;

            if (strpos($url, $expected_prefix) !== 0) {
              $errors[] = [
                'error' => $this->t('URLs invalides trouvées dans le sitemap @lang (doivent commencer par @prefix)',
                  [
                    '@lang' => $langcode,
                    '@prefix' => $expected_prefix,
                  ]),
              ];
              // Arrêter la vérification dès la première erreur.
              break;
            }
          }
        }
        catch (\Exception $e) {
          $errors[] = [
            'error' => $this->t('Erreur de parsing du sitemap pour la langue @lang : @error',
              [
                '@lang' => $langcode,
                '@error' => $e->getMessage(),
              ]),
          ];
        }
      }
      catch (RequestException $e) {
        $errors[] = [
          'error' => $this->t('Impossible de récupérer le sitemap pour la langue @lang : @error',
            [
              '@lang' => $langcode,
              '@error' => $e->getMessage(),
            ]),
        ];
      }
    }

    $status = empty($errors);
    $message = $status
      ? $this->t('Tous les sitemaps contiennent des URLs valides')
      : $this->t('@count sitemaps contiennent des erreurs', ['@count' => count($errors)]);

    return [
      'status' => $status,
      'message' => $message,
      'details' => $errors,
    ];
  }

}
