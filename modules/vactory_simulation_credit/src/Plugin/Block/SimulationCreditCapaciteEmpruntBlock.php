<?php

namespace Drupal\vactory_simulation_credit\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provide a block of Simulation Credit.
 *
 * @Block(
 *  id = "vactory_simulation_credit_capacite_emprunt",
 *  admin_label = @Translation("Vactory Simulation Credit (Capacité d'emprunt)"),
 *  category = "Simulation Credit"
 * )
 */
class SimulationCreditCapaciteEmpruntBlock extends BlockBase {

  /**
   * Build function Simulation Credit Capacite Emprunt.
   */
  public function build() {
    $content = [];
    $webform = \Drupal::entityTypeManager()->getStorage('webform')->load('vactory_simulation_capacite_empr');
    $webform = $webform->getSubmissionForm();
    $content['form'] = $webform;
    // Get profiles data.
    $profiles = \Drupal::config('vactory_simulation_credit.settings')->get('profiles');
    $mode_profile = \Drupal::config('vactory_simulation_credit.settings')->get('v_simulateur_cf_mode_profile');

    return [
      '#theme'   => 'simulation_credit_capacite_emprunt_block',
      '#content' => [
        'simulateur_form' => $content,
      ],
      '#attached' => [
        'library' => [
          'vactory_simulation_credit/vactory_simulation_credit.simulation_capacite_emprunt',
        ],
        'drupalSettings' => [
          'vactory_simulateur' => [
            'profiles' => $profiles,
            'mode_profile' => $mode_profile,
          ],
        ],
      ],
    ];
  }

}
