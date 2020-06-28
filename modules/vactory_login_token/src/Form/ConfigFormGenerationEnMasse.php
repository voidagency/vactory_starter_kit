<?php

namespace Drupal\vactory_login_token\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\vactory_login_token\Controller\TokenGenerateController;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Config Form Generation En Masse.
 */
class ConfigFormGenerationEnMasse extends FormBase {

  /**
   * Variable token generator.
   *
   * @var TokenGenerator
   */
  private  $tokenGenerator;

  /**
   * Construct of The Classe.
   */
  public function __construct(TokenGenerateController $tokenGenerator) {
    $this->tokenGenerator = $tokenGenerator;
  }

  /**
   * Function Create().
   */
  public static function create(ContainerInterface $container) {
    $tokenGenerator = $container->get('vactory_login_token.token_generator');
    return new static($tokenGenerator);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'token_login_generation_en_masse_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['token_generer'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sélectionnez token à générer.'),
      '#options' => [
        $this->t("Générer un token uniquement pour les utilisateurs qui n'ont pas encore"),
        $this->t('Mettre à jour token pour les utilisateurs ayant déjà un token'),
        $this->t('Régénérer les token pour tous les utilisateurs'),
      ],
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Mettre à jour'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $selected = $form_state->getValue('token_generer');
    if ($selected != NULL) {
      $this->tokenGenerator->generateTokenEnMasse($selected);
    }
    else {
      \Drupal::messenger()->addMessage(t('rien a été selectionner'), MessengerInterface::TYPE_STATUS);
    }
  }

}
