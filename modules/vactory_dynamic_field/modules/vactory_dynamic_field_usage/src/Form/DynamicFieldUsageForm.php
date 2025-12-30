<?php

namespace Drupal\vactory_dynamic_field_usage\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\vactory_dynamic_field_usage\VactoryDynamicFieldUsageService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Vactory Dynamic field usage form.
 */
final class DynamicFieldUsageForm extends FormBase {

  /**
   * Pager class.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The dynamic field usage service.
   *
   * @var \Drupal\vactory_dynamic_field_usage\VactoryDynamicFieldUsageService
   */
  protected $dynamicFieldUsage;

  /**
   * DynamicFieldUsageForm construct.
   */
  public function __construct(PagerManagerInterface $pager_manager, VactoryDynamicFieldUsageService $dynamic_field_usage) {
    $this->pagerManager = $pager_manager;
    $this->dynamicFieldUsage = $dynamic_field_usage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('pager.manager'),
      $container->get('vactory_dynamic_field_usage.report')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'vactory_dynamic_field_usage_dynamic_field_usage';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $tabular = $this->dynamicFieldUsage->getTabularData();
    $per_page = 10;

    $current_page = $this->pagerManager
      ->createPager($tabular['total'], $per_page)
      ->getCurrentPage();

    $chunks = !empty($tabular['rows']) ? array_chunk($tabular['rows'], $per_page, TRUE) : [];

    $widgets['all'] = $this->t('All');

    $widgets = [
      ...$widgets,
      ...$this->dynamicFieldUsage->getUsedWidgets() ?? [],
    ];
    $form['widget'] = [
      '#type' => 'select',
      '#options' => $widgets,
      '#default_value' => \Drupal::request()->query->get('widget'),
      '#title' => $this->t('Filter by Type'),
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Filtrer'),
    ];

    $form['table'] = [
      '#type' => 'table',
      '#title' => $this->t('Paragraphs Report'),
      '#header' => $tabular['header'],
      '#sticky' => TRUE,
      '#rows' => $chunks[$current_page] ?? [],
      '#empty' => $this->t('No components found.'),
    ];
    $form['pager'] = [
      '#type' => 'pager',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect('vactory_dynamic_field_usage.dynamic_field_usage', ['widget' => $form_state->getValue('widget')]);
  }

}
