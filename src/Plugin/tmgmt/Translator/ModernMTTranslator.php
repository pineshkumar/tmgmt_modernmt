<?php

namespace Drupal\tmgmt_modernmt\Plugin\tmgmt\Translator;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\key\KeyRepositoryInterface;
use Drupal\tmgmt\TranslatorPluginBase;
use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\JobInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\tmgmt\Translator\AvailableResult;

/**
 * ModernMT translator plugin.
 *
 * @TranslatorPlugin(
 *   id = "modernmt",
 *   label = @Translation("ModernMT"),
 *   description = @Translation("ModernMT Translator service."),
 *   ui = "Drupal\tmgmt_modernmt\Plugin\tmgmt\Translator\ModernMTTranslatorUi",
 *   logo = "icons/modernmt.svg",
 * )
 */
class ModernMTTranslator extends TranslatorPluginBase implements ConfigurableInterface, ContainerFactoryPluginInterface {

  /**
   * Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $client;

  /**
   * Key repository service.
   *
   * @var \Drupal\key\KeyRepositoryInterface
   */
  protected $keyRepository;

  /**
   * Constructs a ModernMTTranslator object.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   The Guzzle HTTP client.
   * @param \Drupal\key\KeyRepositoryInterface $key_repository
   *   The key repository service.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(ClientInterface $client, KeyRepositoryInterface $key_repository, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->client = $client;
    $this->keyRepository = $key_repository;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('http_client'),
      $container->get('key.repository'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key_id' => '',
      'translate_url' => 'https://api.modernmt.com/translate',
      'validate_url' => 'https://api.modernmt.com/users/me',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function checkAvailable(TranslatorInterface $translator) {
    $api_key = $translator->getSetting('api_key_id');
    // dump($api_key); exit;.
    if (!empty($api_key)) {
      try {
        $this->validateApiKey($api_key);
        return AvailableResult::yes();
      }
      catch (RequestException $e) {
        return AvailableResult::no(t('ModernMT service is not available: @error', ['@error' => $e->getMessage()]));
      }
    }
    return AvailableResult::no(t('ModernMT service is not configured.'));
  }

  /**
   * Validates the API key by making a request to ModernMT.
   *
   * @param string $api_key
   *   The API key to validate.
   *
   * @throws \GuzzleHttp\Exception\ClientException
   *   If the API key is invalid or the request fails.
   */
  protected function validateApiKey($api_key) {
    // Retrieve the API key from the Key module using its ID.
    $api_key = $this->keyRepository->getKey($api_key)->getKeyValue();
    $url = $this->configuration['validate_url'];

    try {
      $response = $this->client->request('GET', $url, [
        'headers' => [
      // Correct header for ModernMT.
          'MMT-ApiKey' => $api_key,
      // Add MMT-Platform header.
          'MMT-Platform' => 'simple-cat-tool',
      // Add MMT-PlatformVersion header.
          'MMT-PlatformVersion' => '1.2.8',
        ],
      ]);

      // Ensure the status code is 200.
      if ($response->getStatusCode() !== 200) {
        throw new \RuntimeException('Unexpected response from ModernMT API.');
      }
    }
    catch (ClientException $e) {
      // Extract the error message from the response.
      $response = $e->getResponse();
      $message = $response ? $response->getBody()->getContents() : $e->getMessage();

      // Throw a clear and concise error for debugging purposes.
      throw new \RuntimeException("ModernMT API validation failed: $message", 0, $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function requestTranslation(JobInterface $job) {
    $this->requestJobItemsTranslation($job->getItems());
    if (!$job->isRejected()) {
      $job->submitted('The translation job has been submitted to ModernMT.');
    }
  }

  /**
   * Sends translation requests to ModernMT.
   *
   * @param \Drupal\tmgmt\Entity\JobItem[] $job_items
   *   The job items to translate.
   */
  public function requestJobItemsTranslation(array $job_items) {
    foreach ($job_items as $job_item) {
      $data = \Drupal::service('tmgmt.data')->filterTranslatable($job_item->getData());
      $translated_data = [];

      foreach ($data as $key => $value) {
        try {
          $translated_text = $this->translateText($value['#text'], $job_item->getJob());
          $translated_data[$key]['#text'] = $translated_text;
        }
        catch (RequestException $e) {
          $job_item->getJob()->rejected('ModernMT translation failed: @error', ['@error' => $e->getMessage()], 'error');
        }
      }

      $job_item->addTranslatedData(\Drupal::service('tmgmt.data')->unflatten($translated_data));
    }
  }

  /**
   * Translates text using ModernMT.
   *
   * @param string $text
   *   The text to translate.
   * @param \Drupal\tmgmt\JobInterface $job
   *   The translation job.
   *
   * @return string
   *   The translated text.
   */
  protected function translateText($text, JobInterface $job) {
    $translator = $job->getTranslator();
    $api_key_id = $translator->getSetting('api_key_id');
    $api_key = $this->keyRepository->getKey($api_key_id)->getKeyValue();

    $source = $job->getRemoteSourceLanguage();
    $target = $job->getRemoteTargetLanguage();

    // Update the ModernMT API endpoint if necessary.
    // Verify this URL.
    $url = $this->configuration['translate_url'];

    // Prepare the request body.
    $body = [
      'source' => $source,
      'target' => $target,
      'q' => $text,
    ];

    try {
      // Send the request to ModernMT with header.
      $response = $this->client->request('POST', $url, [
        'headers' => [
          'MMT-ApiKey' => $api_key,
          'Content-Type' => 'application/json',
      // Adding the override header.
          'X-HTTP-Method-Override' => 'GET',
        ],
        'json' => $body,
      ]);

      // Decode the response.
      $result = json_decode($response->getBody()->getContents(), TRUE);

      // Return the translated text from the 'translation' key in 'data'.
      return $result['data']['translation'] ?? '';
    }
    catch (\Exception $e) {
      // Handle errors (e.g., invalid API key, network issues, etc.)
      \Drupal::logger('ModernMT')->error('Translation failed: @message', ['@message' => $e->getMessage()]);
      return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
