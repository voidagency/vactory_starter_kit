<?php

namespace Drupal\vactory_search_overlay\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class SearchOverlayCallback extends ControllerBase {

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * The ModalFormController constructor.
   *
   * @param \Drupal\Core\Form\FormBuilder $formBuilder
   *   The form builder.
   */
  public function __construct(FormBuilder $formBuilder) {
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  /**
   * Do render method.
   */
  public function doRender($variant) {
    if (empty($variant)) {
      // Set default variant to variant1.
      $variant = 'variant1';
    }
    $searchOverlayForm = $this->formBuilder->getForm('Drupal\vactory_search_overlay\Form\SearchOverlayForm', $variant);
    $response = new AjaxResponse();
    $rendered = \Drupal::service('renderer')->renderRoot($searchOverlayForm);
    $response->addCommand(new HtmlCommand('#js-form-search', $rendered));
    return $response;
  }

}
