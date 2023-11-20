<?php

namespace Drupal\vactory_core\Controller;

use Drupal\Core\Controller\ControllerBase;

use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Filigrane pour téléchargement de documents.
 */
class VactoryWatermarkController extends ControllerBase {

  /**
   * Filigrane pour téléchargement de documents.
   */
  public function pdfWatermark(Request $request, $mid) {
    $user_id = $this->currentUser()->id();
    $user = $this->entityTypeManager()->getStorage('user')->load($user_id);
    if (!$user->isAuthenticated()) {
      return new JsonResponse([], 400);
    }
    $file_system = \Drupal::service('file_system');
    $media = $this->entityTypeManager()->getStorage('media')->load($mid);
    $fid = $media->getSource()->getSourceFieldValue($media);
    $file = File::load($fid);
    if ($file instanceof FileInterface) {
      $path = $file->getFileUri();
      $path = $file_system->realpath($path);

      // Path to the original PDF file.
      $originalPdf = $path;

      // Path to the output PDF file with watermark.
      $output_uri = 'public://watermarked';
      if (!file_exists($output_uri)) {
        mkdir($output_uri, 0777, TRUE);
      }
      $output_path = $file_system->realpath($output_uri);
      $time = time();
      $filename = "{$time}-{$user->id()}.pdf";
      $outputPdf = "{$output_path}/{$filename}";

      $mpdf = new Mpdf();

      // Get the total number of pages in the original PDF.
      $totalPages = $mpdf->setSourceFile($originalPdf);

      // Loop through each page.
      for ($i = 1; $i <= $totalPages; $i++) {
        // Add a page to the mPDF instance.
        $mpdf->AddPage();

        // Import the existing PDF content for each page.
        $tplId = $mpdf->importPage($i, '/MediaBox');

        // Use the imported page as a template.
        $mpdf->UseTemplate($tplId, 0, 0, NULL, NULL, TRUE);

        $mpdf->watermark($user->getAccountName(), 45, 40);
      }

      // Output the PDF with the watermark to a file.
      $mpdf->Output($outputPdf, 'F');

      // Download file.
      $response = new BinaryFileResponse($outputPdf, 200, [], FALSE);
      $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
      $response->deleteFileAfterSend(TRUE);
      $response->send();
    }
  }

}
