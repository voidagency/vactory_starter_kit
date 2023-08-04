<?php

namespace Drupal\vactory_ux_notes\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An example controller.
 */
class UserExperienceController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a UserExperienceController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Returns a renderable array for a test page.
   */
  public function getAllWebformSubmissions() {
    $submissions = $this->entityTypeManager
      ->getStorage('webform_submission')
      ->loadByProperties(['webform_id' => 'ux_note_form']);
    $submission_data = [];
    $moyenne = 0;
    $somme = 0;
    $notePercentages = [];

    foreach ($submissions as $submission) {
      $data = $submission->getData();
      $submission_data[] = $data;
      $note = $data['note'];
      $somme += $note;
    }

    // Calculate the percentage for each note value.
    foreach ($submission_data as $data) {
      $note = $data['note'];
      if (isset($notePercentages[$note])) {
        $notePercentages[$note]++;
      }
      else {
        $notePercentages[$note] = 1;
      }
    }

    $totalSubmissions = count($submission_data);

    // Convert occurrences to percentages.
    foreach ($notePercentages as &$occurrence) {
      $occurrence = round(($occurrence / $totalSubmissions) * 100, 2);
    }

    // Sort note percentages in numerical order.
    ksort($notePercentages, SORT_NUMERIC);

    $moyenne = $totalSubmissions > 0 ? round($somme / $totalSubmissions, 2) : 0;

    return [
      '#theme' => 'vactory_ux_stats',
      '#submission_data' => $submission_data,
      '#note_percentages' => $notePercentages,
      '#somme' => $somme,
      '#moyenne' => $moyenne,
      '#total_rows' => $totalSubmissions,
    ];
  }

}
