<?php

namespace Drupal\vactory_welcome\plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\Core\Datetime\DrupalDateTime;


/**
 * Class EspacePriveBlock.
 *
 * @package Drupal\vactory_welcome\plugin\Block
 * @Block(
 *   id = "vactory_welcome_message",
 *   admin_label = @Translation("Vactory Welcome Message Block"),
 *   category = @Translation("Vactory")
 * )
 */
class WelcomeUserBlock extends BlockBase {

  /**
   * Function block form.
   */
  // public function blockForm($form, FormStateInterface $form_state) {
  //   parent::blockForm($form, $form_state);

  //   $form['welcome_description'] = [
  //     '#type' => 'text_format',
  //     '#title' => 'Enter a welcome description',
  //     '#description' => $this->t('Enter a message, a quote or anything you want to tell to the user about'),
  //     // '#default_value' => $config->get('welcome_description'),
  //   ];
  //   $form['welcome_description']['tree_token'] = get_token_tree();


  //   return $form;

  // }

  // /**
  //  * Function block submit.
  //  */
  // public function blockSubmit($form, FormStateInterface $form_state) {
  //   parent::blockSubmit($form, $form_state);
  //   $this->configuration['rapport_digital_year'] = $form_state->getValue('rapport_digital_year');
  // }

  // /**
  //  * Welcome message block build().
  //  */

  public function build() {

    $welcome_terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->loadTree('welcome');
    $current_lang_code = \Drupal::languageManager()
      ->getCurrentLanguage()
      ->getId();
    $term_info = [];
    if (!empty($welcome_terms) && isset($welcome_terms) ) {
      foreach ($welcome_terms as $term) {
        $term_id = Term::load($term->tid);
        $translated_term = \Drupal::service('entity.repository')
          ->getTranslationFromContext($term_id, $current_lang_code);
          $start_time = new DrupalDateTime($translated_term->get('field_time_range_v')->getValue()[0]["value"], 'UTC');
          $end_time = new DrupalDateTime($translated_term->get('field_time_range_v')->getValue()[0]["end_value"], 'UTC');
        $term_info[] = [
          'name' => $translated_term->getName(),
          'tid' => $translated_term->id(),
          'description' => $translated_term->get('description')->value,
          'start_time' => strtotime($start_time->format('H:i')),
          'end_time'=> strtotime($end_time->format('H:i')),
        ];
      }
    }
    $welcome_value = "";
    $welcome_description= "";
    $current_user = \Drupal::currentUser()->getAccountName();
    $current_time = strtotime(date('H:i'));
    foreach($term_info as $term) {
      if ($current_time >= $term["start_time"] && $current_time < $term["end_time"]) {
        $welcome_value = $term["name"];
        $welcome_description = $term["description"];
      }
    }
    return [
      '#theme' => 'welcome_user',
      "#content" => [
        '#value' => $welcome_value,
        '#user' => $current_user,
        '#description' => $welcome_description,
      ],
    ];
    throw new NotFoundHttpException();
  }


}
