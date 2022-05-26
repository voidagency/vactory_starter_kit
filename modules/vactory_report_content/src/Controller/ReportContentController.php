<?php

namespace Drupal\vactory_report_content\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;

/**
 * Report content ajax controller.
 */
class ReportContentController extends ControllerBase {

  /**
   * Render report content form.
   */
  public function renderReportForm() {
    $report_content_form = \Drupal::formBuilder()->getForm('Drupal\vactory_report_content\Form\ReportContentSubmitForm');
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('#js-form-report-content', $report_content_form));
    return $response;
  }
}