<?php
namespace Drupal\vactory_notifications\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Relative date filter.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("notification_concerned_user")
 */
class notificationConcernedUserFilter extends FilterPluginBase {

  /**
   * Form with all possible filter values.
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#tree' => TRUE,
      'concerned_user' => [
        '#type' => 'select',
        '#title' => $this->t('Concerned User'),
        '#options' => [
          'current_user' => $this->t('Current user'),
          'selected_user' => $this->t('Select a user'),
        ],
        '#default_value' => !empty($this->value['concerned_user']) ? $this->value['concerned_user'] : 'current_user',
      ],
      'user' => [
        '#type' => 'entity_autocomplete',
        '#title' => t('User'),
        '#target_type' => 'user',
        '#validate_reference' => FALSE,
        '#maxlength' => 60,
        '#description' => $this->t("Please select the user concerned by the result notifications."),
        '#default_value' => !empty($this->value['user']) ? User::load($this->value['user']) : NULL,
        '#states' => [
          'visible' => [
            '[name="options[value][concerned_user]"]' => ['value' => 'selected_user'],
          ],
        ],
      ],
    ];
  }

  /**
   * Adds conditions to the query based on the selected filter option.
   */
  public function query() {
    $this->ensureMyTable();
    switch ($this->value['concerned_user']) {
      case 'current_user':
        $current_uid = '"' . \Drupal::currentUser()->id() . '"';
        $this->query->addWhereExpression($this->options['group'], "notification_concerned_users LIKE '%" . $current_uid . "%'");
        break;
      case 'selected_user':
        $user = $this->value['user'];
        if (!empty($user)) {
          $uid = '"' . $user . '"';
          $this->query->addWhereExpression($this->options['group'], "notification_concerned_users LIKE '%" . $uid . "%'");
        }
        break;
    }
  }

  public function adminSummary() {
    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed') . ', ' . $this->t('default state') . ': ';// . $this->value['concerned_user'];
    }
    else {
      return $this->t('concerned_user') . ': ';// . $this->value['user'];
    }
  }

}