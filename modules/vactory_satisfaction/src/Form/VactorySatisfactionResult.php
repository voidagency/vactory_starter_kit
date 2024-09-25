<?php

namespace Drupal\vactory_satisfaction\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Vactory satisfaction result form.
 */
class VactorySatisfactionResult extends FormBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new SatisfactionTableForm object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vactory_satisfaction_result';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Prepare filter.
    $pages_query = $this->database->select('vactory_satisfaction', 'vs');
    $pages_query->fields('vs', ['nid']);
    $pages_query->distinct();
    $pages_query->addField('nfd', 'title');
    $pages_query->join('node_field_data', 'nfd', 'vs.nid = nfd.nid');

    $pages_result = $pages_query->execute()->fetchAll();
    $pages = [];
    foreach ($pages_result as $page_result) {
      $pages[$page_result->nid] = $page_result->title;
    }

    $form['filter'] = [
      '#type' => 'select',
      '#title' => $this->t('Page'),
      '#options' => $pages,
      '#empty_option' => t('-- Page --'),
    ];

    // Add Export button.
    $form['actions']['export_excel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export as Excel'),
      '#submit' => ['::exportToExcel'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This form doesn't require any submit handling.
  }

  /**
   * Prepare satisfaction result.
   */
  private function prepareResult($nid) {
    $query = $this->database->select('vactory_satisfaction', 'vs');
    $query->fields('vs', ['uid', 'nid', 'response']);
    if (isset($nid) && !empty($nid)) {
      $query->condition('nid', $nid);
    }
    $query->orderBy('id', 'DESC');
    $results = $query->execute()->fetchAll();

    $all_keys = [];

    // First pass: collect all unique keys.
    foreach ($results as $result) {
      $response = json_decode($result->response, TRUE);
      if (is_array($response)) {
        $all_keys = array_unique(array_merge($all_keys, array_keys($response)));
      }
    }

    $header = [
      'User ID',
      'Node ID',
      ...$all_keys,
    ];

    // Build rows.
    $rows = [];
    foreach ($results as $result) {
      $response = json_decode($result->response, TRUE);
      $row = [$result->uid, $result->nid];

      foreach ($all_keys as $key) {
        if (isset($response[$key])) {
          $value = $response[$key];
          if (is_array($value)) {
            $row[] = $this->formatArrayValue($value);
          }
          else {
            $row[] = $value;
          }
        }
        else {
          $row[] = '-';
        }
      }

      $rows[] = $row;
    }

    return [$header, $rows];
  }

  /**
   * Export the table to an Excel file.
   */
  public function exportToExcel(array &$form, FormStateInterface $form_state) {
    $filter = $form_state->getValue('filter');
    list($header, $rows) = $this->prepareResult($filter);

    // Create new Spreadsheet object.
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set header.
    $sheet->fromArray($header, NULL, 'A1');

    // Set rows.
    $rowIndex = 2;
    foreach ($rows as $row) {
      $sheet->fromArray($row, NULL, 'A' . $rowIndex++);
    }

    // Generate Excel file.
    $writer = new Xlsx($spreadsheet);
    $fileName = 'satisfaction_data.xlsx';
    $temp_file = tempnam(sys_get_temp_dir(), $fileName);
    $writer->save($temp_file);

    // Prepare the file download response.
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
    readfile($temp_file);

    // Delete the temp file.
    unlink($temp_file);

    // Stop further Drupal execution (otherwise, the file will be corrupt).
    exit;
  }

  /**
   * Format table values.
   */
  private function formatArrayValue($value) {
    if (!is_array($value) || !isset($value['main'])) {
      return '-';
    }

    $formatted = $value['main'];
    if (isset($value['optional'])) {
      $formatted .= ' (' . $value['optional'] . ')';
    }

    return $formatted;
  }

}
