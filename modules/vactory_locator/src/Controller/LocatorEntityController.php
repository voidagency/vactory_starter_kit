<?php

namespace Drupal\vactory_locator\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\vactory_locator\Entity\LocatorEntityInterface;

/**
 * Class LocatorEntityController.
 *
 *  Returns responses for Locator Entity routes.
 */
class LocatorEntityController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Displays a Locator Entity  revision.
   *
   * @param int $locator_entity_revision
   *   The Locator Entity  revision ID.
   *
   * @return array
   *   An array suitable for drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionShow($locator_entity_revision) {
    $locator_entity = $this->EntityTypeManager()->getStorage('locator_entity')->loadRevision($locator_entity_revision);
    $view_builder = $this->EntityTypeManager()->getViewBuilder('locator_entity');

    return $view_builder->view($locator_entity);
  }

  /**
   * Page title callback for a Locator Entity  revision.
   *
   * @param int $locator_entity_revision
   *   The Locator Entity  revision ID.
   *
   * @return string
   *   The page title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function revisionPageTitle($locator_entity_revision) {
    $locator_entity = $this->EntityTypeManager()->getStorage('locator_entity')->loadRevision($locator_entity_revision);
    return $this->t('Revision of %title from %date', ['%title' => $locator_entity->label(), '%date' => \Drupal::service('date.formatter')->format($locator_entity->getRevisionCreationTime())]);
  }

  /**
   * Generates an overview table of older revisions of a Locator Entity .
   *
   * @param \Drupal\vactory_locator\Entity\LocatorEntityInterface $locator_entity
   *   A Locator Entity  object.
   *
   * @return array
   *   An array as expected by drupal_render().
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function revisionOverview(LocatorEntityInterface $locator_entity) {
    $account = $this->currentUser();
    $langcode = $locator_entity->language()->getId();
    $langname = $locator_entity->language()->getName();
    $languages = $locator_entity->getTranslationLanguages();
    $has_translations = (count($languages) > 1);
    $locator_entity_storage = $this->EntityTypeManager()->getStorage('locator_entity');

    $build['#title'] = $has_translations ? $this->t('@langname revisions for %title', ['@langname' => $langname, '%title' => $locator_entity->label()]) : $this->t('Revisions for %title', ['%title' => $locator_entity->label()]);
    $header = [$this->t('Revision'), $this->t('Operations')];

    $revert_permission = (($account->hasPermission("revert all locator entity revisions") || $account->hasPermission('administer locator entity entities')));
    $delete_permission = (($account->hasPermission("delete all locator entity revisions") || $account->hasPermission('administer locator entity entities')));

    $rows = [];

    $vids = $locator_entity_storage->revisionIds($locator_entity);

    $latest_revision = TRUE;

    foreach (array_reverse($vids) as $vid) {
      /** @var \Drupal\vactory_locator\Entity\LocatorEntityInterface $revision */
      $revision = $locator_entity_storage->loadRevision($vid);
      // Only show revisions that are affected by the language that is being
      // displayed.
      if ($revision->hasTranslation($langcode) && $revision->getTranslation($langcode)->isRevisionTranslationAffected()) {
        $username = [
          '#theme' => 'username',
          '#account' => $revision->getRevisionUser(),
        ];

        // Use revision link to link to revisions that are not active.
        $date = \Drupal::service('date.formatter')->format($revision->getRevisionCreationTime(), 'short');
        if ($vid != $locator_entity->getRevisionId()) {
          $link = Link::fromTextAndUrl($date, new Url('entity.locator_entity.revision', ['locator_entity' => $locator_entity->id(), 'locator_entity_revision' => $vid]));
        }
        else {
          $link = $locator_entity->toLink($date);
        }

        $row = [];
        $column = [
          'data' => [
            '#type' => 'inline_template',
            '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
            '#context' => [
              'date' => $link,
              'username' => \Drupal::service('renderer')->renderPlain($username),
              'message' => ['#markup' => $revision->getRevisionLogMessage(), '#allowed_tags' => Xss::getHtmlTagList()],
            ],
          ],
        ];
        $row[] = $column;

        if ($latest_revision) {
          $row[] = [
            'data' => [
              '#prefix' => '<em>',
              '#markup' => $this->t('Current revision'),
              '#suffix' => '</em>',
            ],
          ];
          foreach ($row as &$current) {
            $current['class'] = ['revision-current'];
          }
          $latest_revision = FALSE;
        }
        else {
          $links = [];
          if ($revert_permission) {
            $links['revert'] = [
              'title' => $this->t('Revert'),
              'url' => $has_translations ?
              Url::fromRoute(
                'entity.locator_entity.translation_revert', [
                  'locator_entity' => $locator_entity->id(),
                  'locator_entity_revision' => $vid,
                  'langcode' => $langcode,
                ]) :
              Url::fromRoute(
                'entity.locator_entity.revision_revert', [
                  'locator_entity' => $locator_entity->id(),
                  'locator_entity_revision' => $vid,
                ]),
            ];
          }

          if ($delete_permission) {
            $links['delete'] = [
              'title' => $this->t('Delete'),
              'url' => Url::fromRoute('entity.locator_entity.revision_delete', ['locator_entity' => $locator_entity->id(), 'locator_entity_revision' => $vid]),
            ];
          }

          $row[] = [
            'data' => [
              '#type' => 'operations',
              '#links' => $links,
            ],
          ];
        }

        $rows[] = $row;
      }
    }

    $build['locator_entity_revisions_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
      '#header' => $header,
    ];

    return $build;
  }

}
