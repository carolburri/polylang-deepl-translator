# Polylang DeepL Auto Translator

Automatic translation from German to English using DeepL API for Polylang-enabled WordPress sites.

## Features

✅ **Automatic Translation**: Translate posts, pages, and custom post types with one click  
✅ **Custom Block Support**: Translates Gutenberg block attributes (including custom blocks)  
✅ **Custom Fields**: Select which custom fields to translate  
✅ **Nested Data**: Handles complex array structures and nested content  
✅ **Block Preservation**: Maintains block structure and formatting  
✅ **Free & Pro API**: Works with both free and pro DeepL API keys  

## Requirements

- PHP 8.0 or higher
- WordPress 5.8 or higher
- Polylang plugin active
- German (de) and English (en) languages configured in Polylang
- DeepL API key (free tier: 500,000 characters/month)

## Installation via Composer

Add this repository to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/carolburri/polylang-deepl-translator.git"
        }
    ],
    "require": {
        "carolburr/polylang-deepl-translator": "dev-main"
    }
}
```

Then run:

```bash
composer require carolburr/polylang-deepl-translator:dev-main
```

## Manual Installation

1. Download the plugin files
2. Upload to `/wp-content/plugins/polylang-deepl-translator/`
3. Activate the plugin through the WordPress admin

## Setup

1. Get a free DeepL API key from [deepl.com/pro-api](https://www.deepl.com/pro-api)
2. In WordPress admin, go to **Settings → DeepL Translator**
3. Enter your API key and save

## Usage

1. Edit any German (de) post or page
2. Look for the **"🌐 DeepL Translation"** box in the right sidebar
3. Select which custom fields to translate (optional)
4. Click **"Translate to English"**
5. The plugin creates an English translation automatically
6. Review and publish the translated content

## Custom Block Support

The plugin intelligently translates custom Gutenberg blocks by:

- Parsing block attributes
- Translating text-based attributes (title, text, heading, description, etc.)
- Regenerating block HTML with translated content
- Preserving non-translatable data (URLs, IDs, images, etc.)
- Handling nested blocks and InnerBlocks

### Supported Custom Blocks

The plugin has built-in support for:
- `radicle/page-header` - Translates image captions
- `radicle/image-with-text` - Translates title and text
- `radicle/modal` - Translates heading and button text
- `radicle/gradient-background` - Translates inner content

You can extend support by adding more block renderers to the code.

## How It Works

1. **Parse**: The plugin parses the German post content into blocks
2. **Translate**: Each translatable text is sent to DeepL API
3. **Reconstruct**: Blocks are reconstructed with translated attributes
4. **Create**: A new English post is created and linked via Polylang

## Troubleshooting

### Translation button not visible?
- Make sure you're editing a German (de) post/page
- Check that Polylang is active and configured

### Translation fails?
- Verify your DeepL API key is correct
- Check you haven't exceeded your API character limit
- Look in WordPress error logs for details

### Block validation errors?
- Make sure custom blocks are properly registered
- Check that block HTML structure matches the save function
- Try re-saving the original German post first

## Development

```bash
# Clone the repository
git clone git@github.com:carolburri/polylang-deepl-translator.git

# The plugin is ready to use - no build process needed
```

## API Limits

**Free DeepL API:**
- 500,000 characters/month
- Perfect for most blogs and small sites

**Pro DeepL API:**
- Pay-as-you-go pricing
- Higher limits and additional features

## License

GPL v2 or later

## Author

Carol Burri - [carolburr.com](https://carolburr.com)

## Support

For issues and feature requests, please use the [GitHub Issues](https://github.com/carolburri/polylang-deepl-translator/issues) page.

