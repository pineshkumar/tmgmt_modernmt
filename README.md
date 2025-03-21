# ModernMT Translator Integration for TMGMT

The `tmgmt_modernmt` module integrates the [ModernMT](https://www.modernmt.com) machine translation service with the
[Translation Management Tool (TMGMT)](https://www.drupal.org/project/tmgmt) in Drupal. It enables seamless translation of content using ModernMT's advanced AI-based translation capabilities.

1. ## Features

- **ModernMT API Integration**: Easily connect your ModernMT account using an API key managed via the [Key module](https://www.drupal.org/project/key).
- **Language Support**: Leverages ModernMT's language mappings for translations.
- **Domain Customization**: Optional domain-specific translation configuration for each language.
- **TMGMT Compatibility**: Fully integrates with TMGMT for streamlined content translation workflows.
- **Secure API Key Management**: Uses the Key module to store and manage API keys securely.

2. ## Requirements

- Drupal 10.x
- [Translation Management Tool (TMGMT)](https://www.drupal.org/project/tmgmt)
- [Key module](https://www.drupal.org/project/key)

## 3. **Installation & Configuration**

### Installation

1. **Download and install the module using Composer:**
   `composer require drupal/tmgmt_modernmt`
 
2. **Enable the module:**
   `drush en tmgmt_modernmt`
 
3. **Ensure the required modules are enabled:**
   - `tmgmt`
   - `key`

### Set up a ModernMT API key in the Key module:
- Navigate to **Configuration > System > Key** (`/admin/config/system/keys`).
- Create a new key with your ModernMT API credentials.

### Configure the tmgmt_modernmt translator:

1. Go to **Translation > Translators** (`/admin/tmgmt/translators`).
2. Add or edit a translator and select ModernMT as the plugin.
3. Choose the ModernMT API key you created earlier.

### API Key Configuration

- The module uses the Key module to securely manage API keys.
- Set up the API key by:
  - Creating a new key in **Configuration > System > Key**.
  - Selecting `ModernMT API Key` when configuring the translator.

### Domain Settings

- Optional domain-specific configuration is available under the translator settings.
- You can specify a domain for translations per language if required.

## 4. **Usage**

1. Navigate to **Translation > Jobs** (`/admin/tmgmt/jobs`) to create translation jobs.
2. Select content to translate and choose the ModernMT translator.
3. Submit the job to initiate translation using the ModernMT service.

## 5. **Developer Notes**

### Configuration Schema

- The module includes a configuration schema for defining settings in a structured way:
  - API key selection is managed using the `key_select` element type.
  - Optional settings like domains can be customized for specific languages.

### Extensibility

- Developers can override or extend functionality using Drupal's plugin and service architecture.

## 6. **Troubleshooting**

### Error: "Invalid API Key"
- Ensure the API key is correctly configured in the Key module and has the necessary permissions.

### Translation Errors
- Verify language mappings and ensure ModernMT supports the selected source and target languages.

## 7. **Support**

- [ModernMT Documentation](https://www.modernmt.com)
- [Drupal TMGMT Module](https://www.drupal.org/project/tmgmt)
- Open an issue in the module's [Drupal.org issue queue](https://www.drupal.org/project)
 
## 8. **License**

This project is licensed under the GNU General Public License, version 2 or later.
