<?php

/**
 * @file
 * Direct-client regression tests using Guzzle mocks and in-memory credentials.
 *
 * Run with drush php:script. Never sends network requests or stores real keys.
 */

use Drupal\Core\Site\Settings;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Form\FormState;
use Drupal\vactory_webform_typesafe\Exception\JevApiException;
use Drupal\vactory_webform_typesafe\Form\SettingsForm;
use Drupal\vactory_webform_typesafe\Service\ApiKeyStore;
use Drupal\vactory_webform_typesafe\Service\JevResponseValidator;
use Drupal\vactory_webform_typesafe\Service\QuestionDefinition;
use Drupal\vactory_webform_typesafe\Service\TypeSafeClassifier;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ConnectException;

$checks = 0;
$check = static function ($condition, string $label) use (&$checks): void {
  if (!$condition) {
    throw new RuntimeException('FAIL: ' . $label);
  }
  $checks++;
  print "PASS: $label\n";
};
$expect = static function (callable $callback, bool $retry, string $label) use ($check): ?JevApiException {
  try {
    $callback();
  }
  catch (JevApiException $e) {
    $check($e->retryable === $retry, $label);
    return $e;
  }
  throw new RuntimeException('FAIL: Expected a safe failure for ' . $label);
};
$memory = new class() implements StateInterface {
  public array $data = [];
  public function get($key, $default = NULL) { return $this->data[$key] ?? $default; }
  public function getMultiple(array $keys) { return array_intersect_key($this->data, array_flip($keys)); }
  public function set($key, $value) { $this->data[$key] = $value; }
  public function setMultiple(array $data) { $this->data = $data + $this->data; }
  public function delete($key) { unset($this->data[$key]); }
  public function deleteMultiple(array $keys) { foreach ($keys as $key) { $this->delete($key); } }
  public function resetCache() {}
};
$original_settings = Settings::getAll();
$original_environment = getenv('TYPESAFE_API_KEY');
$container = \Drupal::getContainer();
$original_keys = $container->get('vactory_webform_typesafe.api_key');
$original_client = $container->get('vactory_webform_typesafe.classifier');
$original_config = \Drupal::config('vactory_webform_typesafe.settings')->getRawData();
try {
  $test_settings = array_replace($original_settings, ['hash_salt' => 'synthetic-tests-only-salt', 'vactory_webform_typesafe_api_key' => '']);
  new Settings($test_settings);
  putenv('TYPESAFE_API_KEY');
  $keys = new ApiKeyStore($memory);
  $expect(fn() => $keys->get(), FALSE, 'Missing credentials fail before any HTTP request');
  $keys->save(' synthetic-api-key ');
  $ciphertext = $memory->get(ApiKeyStore::STATE_KEY);
  $check(!str_contains($ciphertext, 'synthetic-api-key'), 'Stored credential is encrypted');
  $check((new ApiKeyStore($memory))->get() === 'synthetic-api-key', 'Stored key decrypts across service instances');
  $keys->save('synthetic-api-key');
  $check($ciphertext !== $memory->get(ApiKeyStore::STATE_KEY), 'Each save uses a fresh encryption nonce');
  $good = $memory->get(ApiKeyStore::STATE_KEY);
  $bytes = base64_decode(substr($good, 3));
  $bytes[15] = chr(ord($bytes[15]) ^ 1);
  $memory->set(ApiKeyStore::STATE_KEY, 'v1:' . base64_encode($bytes));
  $expect(fn() => $keys->get(), FALSE, 'Tampered encrypted credentials are rejected');
  $memory->set(ApiKeyStore::STATE_KEY, $good);
  new Settings(array_replace($test_settings, ['hash_salt' => 'different-test-salt']));
  $expect(fn() => $keys->get(), FALSE, 'Salt changes fail safely instead of sending a wrong key');
  new Settings($test_settings);
  $expect(fn() => ApiKeyStore::normalize("bad\nheader"), FALSE, 'Header injection is rejected');
  putenv('TYPESAFE_API_KEY=environment-test-key');
  $check($keys->source() === 'environment' && $keys->get() === 'environment-test-key', 'Environment key overrides stored credentials');
  new Settings(array_replace($test_settings, ['vactory_webform_typesafe_api_key' => 'settings-test-key']));
  $check($keys->source() === 'settings.php' && $keys->get() === 'settings-test-key', 'settings.php key has highest precedence');
  new Settings($test_settings);
  putenv('TYPESAFE_API_KEY');

  $questions = QuestionDefinition::defaults();
  $answers = [];
  foreach ($questions as $id => $q) {
    if ($q['type'] === 'noul') {
      $answers[$id] = ['type' => 'noul', 'noul' => 0.8];
    }
    elseif ($q['type'] === 'score') {
      $answers[$id] = ['type' => 'score', 'score' => 1.2, 'confidence' => 0.8, 'probabilities' => [0.0, 0.8, 0.2], 'legend' => $q['criteria']];
    }
    else {
      $probabilities = array_fill_keys(array_keys($q['criteria']), 0.0);
      $choice = array_key_first($probabilities);
      $probabilities[$choice] = 1.0;
      $answers[$id] = ['type' => 'choice', 'choice' => $choice, 'confidence' => 0.9, 'probabilities' => $probabilities];
    }
  }
  $valid = ['model' => 'jev-test', 'answers' => $answers, 'usage' => ['input_tokens' => 100, 'output_tokens' => 20]];
  $mock = new MockHandler([new Response(200, [], json_encode($valid))]);
  $history = [];
  $stack = HandlerStack::create($mock);
  $stack->push(Middleware::history($history));
  $client = new TypeSafeClassifier(new Client(['handler' => $stack]), $keys, \Drupal::configFactory());
  $result = $client->classify(['message' => 'Synthetic request'], $questions, 'jev-latest');
  $request = $history[0]['request'];
  $payload = json_decode((string) $request->getBody(), TRUE);
  $check((string) $request->getUri() === 'https://api.typesafe.ai/v1/systemone' && $request->getMethod() === 'POST', 'Classification calls the fixed HTTPS Jev endpoint');
  $check($request->getHeaderLine('Authorization') === 'Bearer synthetic-api-key', 'API key is sent as a bearer header');
  $check($payload['model'] === 'jev-latest' && $payload['state']['message'] === 'Synthetic request', 'Model and selected input are serialized correctly');
  $check(!isset($payload['questions']['intent']['confidence_threshold']) && !isset($payload['questions']['intent']['label']), 'Local review settings are excluded from API questions');
  $check($history[0]['options']['allow_redirects'] === FALSE && $history[0]['options']['timeout'] <= 180, 'Redirects are disabled and request time is bounded');
  $check($result['answers']['urgency']['score'] === 1.2 && $result['model'] === 'jev-test', 'Mixed responses preserve scores, confidence and resolved model');
  $mock->append(new Response(200, [], json_encode(['models' => [['name' => 'jev-latest']]])));
  $check($client->getModels() === ['jev-latest'] && $history[1]['request']->getMethod() === 'GET' && (string) $history[1]['request']->getBody() === '', 'Connection test lists models without submission content');
  foreach ([401 => FALSE, 403 => FALSE, 422 => FALSE, 500 => TRUE, 529 => TRUE, 302 => FALSE] as $status => $retry) {
    $mock->append(new Response($status, [], 'sensitive-upstream-error'));
    $error = $expect(fn() => $client->getModels(), $retry, 'HTTP ' . $status . ' uses the correct retry policy');
    $check(!str_contains($error->getMessage(), 'sensitive') && $error->getPrevious() === NULL, 'HTTP ' . $status . ' errors do not expose response bodies');
  }
  $mock->append(new Response(429, ['Retry-After' => '120'], ''));
  $error = $expect(fn() => $client->getModels(), TRUE, 'Rate limits are retryable');
  $check($error->retryAfter === 120, 'Retry-After is retained for the queue');
  $mock->append(new ConnectException('secret-header-and-body', new Request('GET', 'https://api.typesafe.ai/v1/models')));
  $error = $expect(fn() => $client->getModels(), TRUE, 'Network failures are retryable');
  $check(!str_contains($error->getMessage(), 'secret') && !$error->getPrevious(), 'Network errors do not leak request credentials');
  $mock->append(new Response(200, [], 'invalid JSON'));
  $expect(fn() => $client->getModels(), TRUE, 'Malformed JSON is rejected');
  $mock->append(new Response(200, [], str_repeat('x', 2097153)));
  $expect(fn() => $client->getModels(), FALSE, 'Oversized responses are rejected');

  foreach (['missing_answer', 'extra_answer', 'unknown_choice', 'bad_probability', 'bad_score', 'missing_confidence', 'bad_usage'] as $case) {
    $bad = $valid;
    switch ($case) {
      case 'missing_answer': unset($bad['answers']['intent']); break;
      case 'extra_answer': $bad['answers']['unknown'] = ['type' => 'noul', 'noul' => 0.1]; break;
      case 'unknown_choice': $bad['answers']['intent']['choice'] = 'unknown'; break;
      case 'bad_probability': $bad['answers']['callback']['noul'] = 1.5; break;
      case 'bad_score': $bad['answers']['urgency']['score'] = 99; break;
      case 'missing_confidence': unset($bad['answers']['sentiment']['confidence']); break;
      case 'bad_usage': $bad['usage']['input_tokens'] = -1; break;
    }
    $expect(fn() => JevResponseValidator::validate($bad, $questions), TRUE, 'Response validation rejects ' . $case);
  }
  foreach (['type', 'instructions', 'criteria'] as $field) {
    $bad = $questions;
    unset($bad['intent'][$field]);
    try {
      QuestionDefinition::parse(json_encode($bad));
      throw new RuntimeException('Invalid questions unexpectedly accepted');
    }
    catch (InvalidArgumentException) {
      $check(TRUE, 'Question validation rejects missing ' . $field);
    }
  }
  $container->set('vactory_webform_typesafe.api_key', $keys);
  $container->set('vactory_webform_typesafe.classifier', $client);
  $form_object = SettingsForm::create($container);
  $form_state = new FormState();
  $form = $form_object->buildForm([], $form_state);
  $check(!str_contains(serialize($form), 'synthetic-api-key'), 'Settings form never prepopulates or exposes the stored key');
  $form_state->setValues($original_config + ['api_key' => '', 'remove_api_key' => FALSE, 'request_timeout' => 60]);
  $form_object->submitForm($form, $form_state);
  $check($keys->get() === 'synthetic-api-key', 'Blank key input preserves the stored key');
  $form_state->setValue('api_key', 'replacement-test-key');
  $form_state->setUserInput(['api_key' => 'replacement-test-key']);
  $form_object->submitForm($form, $form_state);
  $check($keys->get() === 'replacement-test-key' && !$form_state->hasValue('api_key') && !isset($form_state->getUserInput()['api_key']), 'Replacing a key clears secret data from form state');
  $check(!str_contains(json_encode(\Drupal::config('vactory_webform_typesafe.settings')->getRawData()), 'replacement-test-key'), 'API key never enters exported configuration');
  $form_state->setValue('remove_api_key', TRUE);
  $form_object->submitForm($form, $form_state);
  $check($keys->source() === 'none', 'Explicit removal deletes the stored credential');
  print "\n$checks direct-client checks passed. No network requests made.\n";
}
finally {
  new Settings($original_settings);
  if ($original_environment === FALSE) { putenv('TYPESAFE_API_KEY'); }
  else { putenv('TYPESAFE_API_KEY=' . $original_environment); }
  $container->set('vactory_webform_typesafe.api_key', $original_keys);
  $container->set('vactory_webform_typesafe.classifier', $original_client);
  \Drupal::configFactory()->getEditable('vactory_webform_typesafe.settings')->setData($original_config)->save();
}
