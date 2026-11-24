<?php

namespace Drupal\Tests\vactory_quiz_certificat\Kernel;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\KernelTests\KernelTestBase;
use Drupal\vactory_quiz\Form\QuizForm;

/**
 * Tests where certificates are stored and how they are linked.
 *
 * New files get a name that cannot be guessed. The values already recorded in
 * the quiz history, a path relative to the working directory or a stream URI,
 * are read as they are: nothing is renamed or rewritten (Redmine #71157).
 *
 * @group vactory_quiz_certificat
 */
class CertificatStorageTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'vactory_quiz',
    'vactory_quiz_certificat',
  ];

  /**
   * The schema of the module settings does not describe every key.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    parent::register($container);
    $container->register('stream_wrapper.private', PrivateStream::class)
      ->addTag('stream_wrapper', ['scheme' => 'private']);
  }

  /**
   * {@inheritdoc}
   */
  protected function setUpFilesystem() {
    parent::setUpFilesystem();
    mkdir($this->siteDirectory . '/private', 0775);
    $this->setSetting('file_private_path', $this->siteDirectory . '/private');
  }

  /**
   * Returns the link the quiz form shows for a recorded certificate value.
   */
  protected function certificatLink(string $recorded) {
    $results = ['certificat' => $recorded];
    // Only the branch reading an existing certificate is exercised: it uses
    // none of the services of the form.
    $form = (new \ReflectionClass(QuizForm::class))->newInstanceWithoutConstructor();
    $quiz = ['entity_id' => 1];
    return $form->generateCertificatUsingMpdfQueue($results, new FormState(), $quiz);
  }

  /**
   * Two same-named users in the same second get distinct file names.
   */
  public function testNewFileNamesAreDistinctAndNotGuessable(): void {
    $manager = $this->container->get('vactory_quiz_certificat.manager');
    $time = new \DateTime('2026-11-23 10:00:00');
    $first = $manager->buildOutputFile('Jane', 'Doe', 12, $time);
    $second = $manager->buildOutputFile('Jane', 'Doe', 13, $time);
    $again = $manager->buildOutputFile('Jane', 'Doe', 12, $time);

    $this->assertNotSame($first, $second);
    $this->assertNotSame($first, $again);
    $prefix = 'private://quiz_certificat/2026-11/Jane-Doe-Certif-' . $time->format('U');
    $this->assertMatchesRegularExpression('@^' . preg_quote($prefix, '@') . '-12-[0-9a-f]{16}\.pdf$@', $first);
    $this->assertMatchesRegularExpression('@^' . preg_quote($prefix, '@') . '-13-[0-9a-f]{16}\.pdf$@', $second);
    // Without names the file still has the account ID and the random part.
    $this->assertMatchesRegularExpression('@/Certif-\d+-12-[0-9a-f]{16}\.pdf$@', $manager->buildOutputFile(NULL, '', 12, $time));
  }

  /**
   * A relative path recorded before the fix gives the same link as before.
   */
  public function testOldRelativeValueKeepsItsLink(): void {
    $recorded = 'private/quiz_certificat/2026-10/Jane-Doe-Certif-1700000000.pdf';
    // The old value is relative to the working directory: use a temporary
    // one, never the real one.
    $previous = getcwd();
    $dir = sys_get_temp_dir() . '/quiz-certificat-test-' . getmypid();
    mkdir($dir . '/private/quiz_certificat/2026-10', 0775, TRUE);
    file_put_contents($dir . '/' . $recorded, '%PDF');
    chdir($dir);
    try {
      $link = $this->certificatLink($recorded);
    }
    finally {
      chdir($previous);
      exec('rm -rf ' . escapeshellarg($dir));
    }

    $this->assertSame($GLOBALS['base_url'] . '/' . $recorded, $link);
  }

  /**
   * A recorded stream URI, with an old file name, is still linked.
   */
  public function testRecordedStreamUriKeepsItsLink(): void {
    $recorded = 'private://quiz_certificat/2026-11/Jane-Doe-Certif-1700000000.pdf';
    mkdir('private://quiz_certificat/2026-11', 0775, TRUE);
    file_put_contents($recorded, '%PDF');

    $link = $this->certificatLink($recorded);
    $this->assertStringEndsWith('/system/files/quiz_certificat/2026-11/Jane-Doe-Certif-1700000000.pdf', $link);
  }

  /**
   * With a private file path the status report has nothing to say.
   */
  public function testNoRequirementErrorWithPrivatePath(): void {
    include_once $this->root . '/core/includes/install.inc';
    $this->container->get('module_handler')
      ->loadInclude('vactory_quiz_certificat', 'install');
    $this->config('vactory_quiz_certificat.settings')
      ->set('method', 'html2pdf')
      ->save();

    $this->assertSame([], vactory_quiz_certificat_requirements('runtime'));
  }

}
