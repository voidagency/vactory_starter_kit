<?php

namespace Drupal\vactory_content_package\Controller;

use Drupal\Core\Ajax\OpenDialogCommand;
use Drupal\vactory_dynamic_field\ModalEnum;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Dynamic field json data modal.
 */
class DynamicFieldJsonGeneratorModal extends ControllerBase {

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
   * Callback for opening the modal form.
   */
  public function openModalForm(Request $request) {
    $response = new AjaxResponse();
    $widget_id = $request->query->get('widget_id');
    $title = "Json format for template [{$widget_id}]";
    // Get the modal form using the form builder.
    $modal_form = $this->formBuilder->getForm('Drupal\vactory_content_package\Form\DynamicFieldJsonGenerator');

    // Add an AJAX command to open a modal dialog with the form as the content.
    $response->addCommand(new OpenDialogCommand(ModalEnum::MODAL_SELECTOR, $title, $modal_form, [
      'width' => '90%',
      'modal' => TRUE,
    ]));

    return $response;
  }

}
