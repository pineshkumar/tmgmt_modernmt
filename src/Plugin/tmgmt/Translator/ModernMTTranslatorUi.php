<?php

namespace Drupal\tmgmt_modernmt\Plugin\tmgmt\Translator;

use Drupal\tmgmt\TranslatorInterface;
use Drupal\tmgmt\TranslatorPluginUiBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * ModernMT translator UI.
 */
class ModernMTTranslatorUi extends TranslatorPluginUiBase {

  /**
   * The translator plugin.
   *
   * @var \Drupal\tmgmt_modernmt\Plugin\tmgmt\Translator\ModernMTTranslator
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\tmgmt\TranslatorInterface $translator */
    $translator = $form_state->getFormObject()->getEntity();

    $instruction_url = 'https://www.modernmt.com/api/';
    $form['api_key_id'] = [
      '#type' => 'key_select',
      '#title' => $this->t('ModernMT API Key'),
      '#default_value' => $translator->getSetting('api_key_id'),
      '#required' => TRUE,
      '#description' => $this->t(
        'Please select your ModernMT API key. Instructions on how to obtain your API key can be found <a href="@link" target="_blank">here</a>.',
        ['@link' => $instruction_url]
      ),
    ];

    $form += parent::addConnectButton();

    // Support for optional domain customization.
    $form['domains'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Domains'),
      '#description' => $this->t('Specify a domain for translations if applicable.'),
    ];

    // Get ModernMT-supported languages.
    $remote_languages = $translator->getRemoteLanguagesMappings();
    // Get the domains configuration.
    $domains_settings = $translator->getSetting('domains');
    foreach ($remote_languages as $local_language => $remote_language) {
      $form['domains'][$local_language] = [
        '#type' => 'textfield',
        '#title' => \Drupal::languageManager()
          ->getLanguage($local_language)
          ->getName() . ' (' . $local_language . ')',
        '#default_value' => $domains_settings[$local_language] ?? '',
        '#size' => 50,
        '#description' => $this->t('Optional. Specify the domain for @lang.', ['@lang' => $local_language]),
      ];
    }

    return $form;
  }

  /**
   * Get the translator plugin.
   *
   * @param \Drupal\tmgmt\TranslatorInterface $translator
   *   The translator entity.
   *
   * @return \Drupal\tmgmt_modernmt\Plugin\tmgmt\Translator\ModernMTTranslator
   *   The translator plugin.
   */
  protected function getPlugin(TranslatorInterface $translator) {
    if (!isset($this->plugin)) {
      $this->plugin = $translator->getPlugin();
    }
    return $this->plugin;
  }

}
