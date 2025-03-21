<?php

namespace Drupal\Tests\tmgmt_modernmt\Functional;

use GuzzleHttp\Psr7\Request;
use Drupal\tmgmt\JobInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Tests\BrowserTestBase;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;

/**
 * Functional tests for ModernMTTranslator.
 *
 * @group tmgmt_modernmt
 */
class ModernMTTranslatorTest extends BrowserTestBase {

  /**
   * The modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'tmgmt',
    'tmgmt_modernmt',
  ];

  /**
   * A mocked HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * A translator plugin instance.
   *
   * @var \Drupal\tmgmt_modernmt\Plugin\tmgmt\Translator\ModernMTTranslator
   */
  protected $translator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the HTTP client.
    $this->httpClient = $this->createMock(ClientInterface::class);

    // Create an instance of the translator plugin.
    $plugin_manager = $this->container->get('plugin.manager.tmgmt.translator');
    $this->translator = $plugin_manager->createInstance('modernmt', [
      'http_client' => $this->httpClient,
    ]);
  }

  /**
   * Tests the translateText method.
   */
  public function testTranslateText() {
    $source_text = 'Hello world';
    $translated_text = 'Bonjour le monde';

    // Mock the API response.
    $response = new Response(200, [], json_encode([
      'data' => [
        'translation' => $translated_text,
      ],
    ]));
    $this->httpClient->method('request')
      ->willReturn($response);

    // Run the method and assert the result.
    $result = $this->translator->translateText($source_text, $this->createMock(JobInterface::class));
    $this->assertEquals($translated_text, $result, 'The text was translated correctly.');
  }

  /**
   * Tests the translateText method with an error response.
   */
  public function testTranslateTextWithError() {
    $source_text = 'Hello world';

    // Mock an API exception.
    $this->httpClient->method('request')
      ->willThrowException(new RequestException('Error connecting to API', new Request('POST', '')));

    // Run the method and assert the result.
    $result = $this->translator->translateText($source_text, $this->createMock(JobInterface::class));
    $this->assertEquals('', $result, 'The translation returned an empty string on error.');
  }

  /**
   * Tests the requestTranslation method.
   */
  public function testRequestTranslation() {
    // Create a mock job with job items.
    $job = $this->createMock(JobInterface::class);
    $job->method('getTranslator')->willReturn($this->translator);
    $job->method('getItems')->willReturn([]);

    // Assert that no exception is thrown when calling requestTranslation.
    $this->translator->requestTranslation($job);
    $this->assertTrue(TRUE, 'The requestTranslation method executed without errors.');
  }

}
