<?php

namespace Drupal\vactory_jsonapi_extras\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;

/**
 * API Packages form.
 */
class ApiPackagesForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $this->entity->label(),
      '#description' => $this->t('Label for the api packages.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#machine_name' => [
        'exists' => '\Drupal\vactory_jsonapi_extras\Entity\ApiPackages::load',
      ],
      '#disabled' => !$this->entity->isNew(),
    ];

    $roles = \Drupal::entityTypeManager()->getStorage('user_role')
      ->loadMultiple();
    $roles = array_map(function ($role) {
      return $role->label();
    }, $roles);
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Authorized roles'),
      '#description' => $this->t('Users with given roles could access all this package routes (of course an api key or jsonapi token is required when the original jsonapi resource is protected)'),
      '#options' => $roles ,
      '#default_value' => $this->entity->roles() ?? [],
    ];

    $form['generate_role'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Generate new role with this package name and use it as default authorized role'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $generate_role = $form_state->getValue('generate_role');
    if ($generate_role) {
      $roles = $form_state->getValue('roles');
      $label = $form_state->getValue('label');
      $id = $form_state->getValue('id');
      $role = Role::load("package_{$id}");
      if (!$role) {
        $role = Role::create([
          'id' => "package_{$id}",
          'label' => "Package API - $label",
        ]);
        $role->save();
      }
      $roles["package_{$id}"] = "package_{$id}";
      $this->entity->setRoles($roles);
    }
    $result = parent::save($form, $form_state);
    $message_args = ['%label' => $this->entity->label()];
    $message = $result == SAVED_NEW
      ? $this->t('Created new api packages %label.', $message_args)
      : $this->t('Updated api packages %label.', $message_args);
    $this->messenger()->addStatus($message);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    drupal_flush_all_caches();
    return $result;
  }

}
