<?php
/**
 * Plugin Name: Polylang DeepL Auto Translator
 * Plugin URI: https://example.com
 * Description: Automatic translation from German to English using DeepL API for Polylang
 * Version: 1.2.2
 * Author: Carol Burri
 * Author URI: https://carolburr.com
 * License: GPL v2 or later
 * Requires PHP: 8.0
 * Text Domain: polylang-deepl-translator
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Polylang_DeepL_Translator {
    
    private $option_name = 'pdt_deepl_api_key';
    
    public function __construct() {
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Add meta box for Gutenberg and Classic editor
        add_action('add_meta_boxes', [$this, 'add_translate_meta_box']);
        
        // Handle AJAX translation request
        add_action('wp_ajax_pdt_translate_post', [$this, 'ajax_translate_post']);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }
    
    public function add_admin_menu() {
        add_options_page(
            'DeepL Translator Settings',
            'DeepL Translator',
            'manage_options',
            'polylang-deepl-translator',
            [$this, 'settings_page']
        );
    }
    
    public function settings_page() {
        if (isset($_POST['pdt_save_settings']) && check_admin_referer('pdt_settings')) {
            update_option($this->option_name, sanitize_text_field($_POST['deepl_api_key']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $api_key = get_option($this->option_name, '');
        ?>
        <div class="wrap">
            <h1>DeepL Translator Settings</h1>
            <form method="post">
                <?php wp_nonce_field('pdt_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="deepl_api_key">DeepL API Key</label>
                        </th>
                        <td>
                            <input type="text" 
                                   id="deepl_api_key" 
                                   name="deepl_api_key" 
                                   value="<?php echo esc_attr($api_key); ?>" 
                                   class="regular-text">
                            <p class="description">
                                Get your free API key from <a href="https://www.deepl.com/pro-api" target="_blank">DeepL API</a>
                            </p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" 
                           name="pdt_save_settings" 
                           class="button button-primary" 
                           value="Save Settings">
                </p>
            </form>
            
            <hr>
            
            <h2>How to Use</h2>
            <ol>
                <li>Sign up for a free DeepL API account at <a href="https://www.deepl.com/pro-api" target="_blank">deepl.com/pro-api</a></li>
                <li>Copy your API key and paste it above</li>
                <li>Edit any German post/page</li>
                <li>Look for the "DeepL Translation" box in the right sidebar</li>
                <li>Click "Translate to English" button</li>
                <li>The plugin will create an English translation automatically</li>
            </ol>
            
            <h2>Requirements</h2>
            <ul>
                <li>✓ Polylang plugin active</li>
                <li>✓ German (de) and English (en) languages configured in Polylang</li>
                <li>✓ DeepL API key (free tier: 500,000 characters/month)</li>
            </ul>
            
            <h2>Troubleshooting</h2>
            <ul>
                <li><strong>Don't see the translation box?</strong> Make sure you're editing a German (de) post/page</li>
                <li><strong>Translation button not working?</strong> Check that your API key is correct</li>
                <li><strong>Using Gutenberg?</strong> The box appears in the right sidebar (you may need to scroll)</li>
            </ul>
        </div>
        <?php
    }
    
    public function add_translate_meta_box() {
        $post_types = get_post_types(['public' => true], 'names');
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'pdt_translate_box',
                '🌐 DeepL Translation',
                [$this, 'render_translate_meta_box'],
                $post_type,
                'side',
                'default'
            );
        }
    }
    
    public function render_translate_meta_box($post) {
        // Check if Polylang is active
        if (!function_exists('pll_get_post_language') || !function_exists('pll_get_post')) {
            echo '<p style="color: #dc3232;">⚠️ Polylang plugin is not active!</p>';
            return;
        }
        
        // Get current post language
        $current_lang = pll_get_post_language($post->ID);
        
        // Only show for German posts
        if ($current_lang !== 'de') {
            echo '<p style="color: #666;">ℹ️ This post is not in German.<br>Translation is only available for German posts.</p>';
            return;
        }
        
        // Check if API key is configured
        $api_key = get_option($this->option_name);
        if (empty($api_key)) {
            echo '<p style="color: #dc3232;">⚠️ DeepL API key not configured!</p>';
            echo '<p><a href="' . admin_url('options-general.php?page=polylang-deepl-translator') . '" class="button">Configure API Key</a></p>';
            return;
        }
        
        // Check if English translation already exists
        $en_post_id = pll_get_post($post->ID, 'en');
        
        // Get list of custom fields that might contain text
        $all_meta = get_post_meta($post->ID);
        $text_fields = [];
        foreach ($all_meta as $key => $value) {
            // Skip internal WordPress and ACF meta
            if (substr($key, 0, 1) === '_') continue;
            $text_fields[] = $key;
        }
        
        wp_nonce_field('pdt_translate_meta_box', 'pdt_translate_nonce');
        
        ?>
        <div id="pdt-translate-wrapper">
            <?php if ($en_post_id): ?>
                <p style="color: #46b450; margin: 10px 0;">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <strong>English translation exists</strong>
                </p>
                <p>
                    <a href="<?php echo get_edit_post_link($en_post_id); ?>" class="button button-secondary" style="width: 100%; text-align: center; margin-bottom: 8px;">
                        Edit English Version
                    </a>
                </p>
                <p>
                    <button type="button" class="button button-primary" id="pdt-retranslate-button" style="width: 100%;">
                        🔄 Re-translate & Update
                    </button>
                </p>
            <?php else: ?>
                <p style="margin: 10px 0 15px 0; color: #666;">
                    Click the button below to automatically translate this content to English using DeepL.
                </p>
                <button type="button" class="button button-primary button-large" id="pdt-translate-button" style="width: 100%; height: auto; padding: 8px 12px;">
                    <span class="dashicons dashicons-translation" style="margin-top: 4px;"></span>
                    Translate to English
                </button>
            <?php endif; ?>
            
            <?php if (!empty($text_fields)): ?>
                <details style="margin-top: 15px;">
                    <summary style="cursor: pointer; color: #666; font-size: 12px;">
                        <strong>Custom Fields</strong> (<?php echo count($text_fields); ?> found)
                    </summary>
                    <div style="margin-top: 10px; max-height: 150px; overflow-y: auto; background: #f5f5f5; padding: 8px; border-radius: 4px;">
                        <?php foreach ($text_fields as $field): ?>
                            <label style="display: block; margin: 4px 0; font-size: 11px;">
                                <input type="checkbox" class="pdt-custom-field" value="<?php echo esc_attr($field); ?>" checked>
                                <?php echo esc_html($field); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p style="font-size: 11px; color: #666; margin: 8px 0 0 0;">
                        Uncheck fields that shouldn't be translated (IDs, URLs, etc.)
                    </p>
                </details>
            <?php endif; ?>
            
            <div id="pdt-translate-status" style="margin-top: 15px;"></div>
            
            <hr style="margin: 15px 0;">
            
            <p style="font-size: 12px; color: #666; margin: 0;">
                <strong>Translation:</strong> DE → EN-US<br>
                <strong>Provider:</strong> DeepL API
            </p>
        </div>
        
        <style>
            #pdt-translate-wrapper .dashicons {
                vertical-align: middle;
            }
            #pdt-translate-status {
                padding: 10px;
                border-radius: 4px;
                font-size: 13px;
            }
            #pdt-translate-status.success {
                background: #d4edda;
                border: 1px solid #c3e6cb;
                color: #155724;
            }
            #pdt-translate-status.error {
                background: #f8d7da;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }
            #pdt-translate-status.loading {
                background: #d1ecf1;
                border: 1px solid #bee5eb;
                color: #0c5460;
            }
        </style>
        <?php
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'post.php' && $hook !== 'post-new.php') {
            return;
        }
        
        global $post;
        if (!$post) {
            return;
        }
        
        wp_enqueue_script(
            'pdt-admin-script',
            plugin_dir_url(__FILE__) . 'admin-script.js',
            ['jquery'],
            '1.2.2',
            true
        );
        
        wp_localize_script('pdt-admin-script', 'pdtData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pdt_translate_nonce'),
            'postId' => $post->ID
        ]);
    }
    
    public function ajax_translate_post() {
        check_ajax_referer('pdt_translate_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }
        
        $post_id = intval($_POST['post_id']);
        $custom_fields = isset($_POST['custom_fields']) ? $_POST['custom_fields'] : [];
        
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(['message' => 'Post not found']);
            return;
        }
        
        // Check if Polylang is active
        if (!function_exists('pll_get_post_language') || !function_exists('pll_set_post_language')) {
            wp_send_json_error(['message' => 'Polylang is not active']);
            return;
        }
        
        // Verify post is in German
        $current_lang = pll_get_post_language($post_id);
        if ($current_lang !== 'de') {
            wp_send_json_error(['message' => 'Post must be in German (de)']);
            return;
        }
        
        // Get API key
        $api_key = get_option($this->option_name);
        if (empty($api_key)) {
            wp_send_json_error(['message' => 'DeepL API key not configured. Please configure it in Settings → DeepL Translator']);
            return;
        }
        
        // Translate title and excerpt
        $translated_title = $this->translate_text($post->post_title, $api_key);
        $translated_excerpt = !empty($post->post_excerpt) ? $this->translate_text($post->post_excerpt, $api_key) : '';
        
        if ($translated_title === false) {
            wp_send_json_error(['message' => 'Translation failed. Please check your API key and try again.']);
            return;
        }
        
        // Parse and translate block content
        $translated_content = $this->translate_blocks_content($post->post_content, $api_key);
        
        if ($translated_content === false) {
            wp_send_json_error(['message' => 'Content translation failed. Please check your API key and try again.']);
            return;
        }
        
        // Debug log the translation
        error_log('=== DeepL Translation Debug ===');
        error_log('Original content length: ' . strlen($post->post_content));
        error_log('Translated content length: ' . strlen($translated_content));
        error_log('First 500 chars of translated: ' . substr($translated_content, 0, 500));
        
        // Check if English translation already exists
        $en_post_id = pll_get_post($post_id, 'en');
        
        if ($en_post_id) {
            // Update existing translation
            wp_update_post([
                'ID' => $en_post_id,
                'post_title' => $translated_title,
                'post_content' => $translated_content,
                'post_excerpt' => $translated_excerpt
            ]);
        } else {
            // Create new translation
            $en_post_data = [
                'post_title' => $translated_title,
                'post_content' => $translated_content,
                'post_excerpt' => $translated_excerpt,
                'post_status' => 'draft',
                'post_type' => $post->post_type,
                'post_author' => $post->post_author
            ];
            
            $en_post_id = wp_insert_post($en_post_data);
            
            if (is_wp_error($en_post_id)) {
                wp_send_json_error(['message' => 'Failed to create English post: ' . $en_post_id->get_error_message()]);
                return;
            }
            
            // Set language and link translations
            pll_set_post_language($en_post_id, 'en');
            pll_save_post_translations([
                'de' => $post_id,
                'en' => $en_post_id
            ]);
            
            // Copy featured image if exists
            $thumbnail_id = get_post_thumbnail_id($post_id);
            if ($thumbnail_id) {
                set_post_thumbnail($en_post_id, $thumbnail_id);
            }
        }
        
        // Translate custom fields
        $translated_fields_count = 0;
        if (!empty($custom_fields)) {
            foreach ($custom_fields as $field_key) {
                $field_value = get_post_meta($post_id, $field_key, true);
                
                if (empty($field_value)) continue;
                
                // Handle different data types
                if (is_string($field_value)) {
                    // Translate string
                    $translated_value = $this->translate_text($field_value, $api_key);
                    if ($translated_value !== false) {
                        update_post_meta($en_post_id, $field_key, $translated_value);
                        $translated_fields_count++;
                    }
                } elseif (is_array($field_value)) {
                    // Recursively translate array values
                    $translated_array = $this->translate_array_values($field_value, $api_key);
                    update_post_meta($en_post_id, $field_key, $translated_array);
                    $translated_fields_count++;
                } else {
                    // Copy non-string values as-is (numbers, booleans, etc.)
                    update_post_meta($en_post_id, $field_key, $field_value);
                }
            }
        }
        
        $message = 'English translation ' . ($en_post_id ? 'updated' : 'created') . ' successfully!';
        if ($translated_fields_count > 0) {
            $message .= ' (' . $translated_fields_count . ' custom field' . ($translated_fields_count > 1 ? 's' : '') . ' translated)';
        }
        
        wp_send_json_success([
            'message' => $message,
            'post_id' => $en_post_id,
            'edit_link' => get_edit_post_link($en_post_id, 'raw'),
            'translated_fields' => $translated_fields_count
        ]);
    }
    
    /**
     * Parse and translate Gutenberg blocks content
     */
    private function translate_blocks_content($content, $api_key) {
        if (empty($content)) {
            return '';
        }
        
        // Parse blocks from content
        $blocks = parse_blocks($content);
        
        if (empty($blocks)) {
            return $content;
        }
        
        // Translate each block
        $translated_blocks = $this->translate_blocks($blocks, $api_key);
        
        // Render blocks back to content
        return $this->render_blocks($translated_blocks);
    }
    
    /**
     * Render blocks back to content, regenerating HTML for custom blocks
     */
    private function render_blocks($blocks) {
        $output = '';
        
        foreach ($blocks as $block) {
            $output .= $this->render_block($block);
        }
        
        return $output;
    }
    
    /**
     * Render a single block
     * For image-with-text, we need to regenerate HTML since attributes changed
     */
    private function render_block($block) {
        $block_name = $block['blockName'] ?? '';
        $attrs = $block['attrs'] ?? [];
        
        // Empty blocks (like spaces between blocks)
        if (empty($block_name)) {
            return $block['innerHTML'] ?? '';
        }
        
        // Build attribute JSON
        $attrs_json = !empty($attrs) ? ' ' . json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        
        // For image-with-text block, regenerate the HTML with translated attributes
        if ($block_name === 'radicle/image-with-text') {
            $inner_html = $this->render_custom_block_html($block_name, $attrs);
            return "<!-- wp:{$block_name}{$attrs_json} -->\n{$inner_html}<!-- /wp:{$block_name} -->\n\n";
        }
        
        // For page-header block, regenerate the HTML with translated attributes
        if ($block_name === 'radicle/page-header') {
            $inner_html = $this->render_custom_block_html($block_name, $attrs);
            return "<!-- wp:{$block_name}{$attrs_json} -->\n{$inner_html}<!-- /wp:{$block_name} -->\n\n";
        }
        
        // For modal and gradient-background, only translate inner blocks
        if (in_array($block_name, ['radicle/modal', 'radicle/gradient-background'])) {
            $inner_blocks_content = !empty($block['innerBlocks']) ? $this->render_blocks($block['innerBlocks']) : '';
            return "<!-- wp:{$block_name}{$attrs_json} -->\n{$inner_blocks_content}<!-- /wp:{$block_name} -->\n\n";
        }
        
        // For core blocks and others, use the existing structure
        $inner_blocks_html = !empty($block['innerBlocks']) ? $this->render_blocks($block['innerBlocks']) : '';
        $inner_html = $block['innerHTML'] ?? '';
        
        // If there are inner blocks, we need to reconstruct innerContent properly
        if (!empty($block['innerBlocks'])) {
            // For blocks with inner blocks, use the first innerHTML part, then inner blocks, then closing part
            $inner_content_parts = $block['innerContent'] ?? [];
            $opening = $inner_content_parts[0] ?? '';
            $closing = !empty($inner_content_parts) ? $inner_content_parts[count($inner_content_parts) - 1] : '';
            
            return "<!-- wp:{$block_name}{$attrs_json} -->\n{$opening}{$inner_blocks_html}{$closing}<!-- /wp:{$block_name} -->\n\n";
        }
        
        return "<!-- wp:{$block_name}{$attrs_json} -->\n{$inner_html}<!-- /wp:{$block_name} -->\n\n";
    }
    
    /**
     * Generate HTML for custom blocks based on their attributes
     */
    private function render_custom_block_html($block_name, $attrs) {
        switch ($block_name) {
            case 'radicle/image-with-text':
                return $this->render_image_with_text_block($attrs);
            
            case 'radicle/page-header':
                return $this->render_page_header_block($attrs);
            
            case 'radicle/modal':
                return ''; // Modal uses InnerBlocks, no custom HTML in save
            
            case 'radicle/gradient-background':
                return ''; // Gradient background uses InnerBlocks, no custom HTML in save
            
            default:
                return '';
        }
    }
    
    /**
     * Render image-with-text block HTML
     * Must match exactly what the JSX save function generates
     * JSX: <div className={`image-and-text ${attributes.image_left ? 'image-left' : 'image-right'}`}>
     */
    private function render_image_with_text_block($attrs) {
        $image_left = $attrs['image_left'] ?? true;
        $class = $image_left ? 'image-left' : 'image-right';
        $image = $attrs['image'] ?? null;
        $image_url = $image['url'] ?? '';
        $title = $attrs['title'] ?? '';
        $text = $attrs['text'] ?? '';
        $post = $attrs['post'] ?? null;
        
        // JSX: <div className={`image-and-text ${...}`}>
        $html = '<div class="image-and-text ' . $class . '">';
        
        // JSX: <img src={attributes.image?.url} alt={attributes.title}/>
        // Always renders img tag even if URL is empty
        $html .= '<img src="' . $image_url . '" alt="' . $title . '"/>';
        
        // JSX: <div className={'text'}>
        $html .= '<div class="text">';
        
        // JSX: {attributes.title && <h3>{attributes.title}</h3>}
        // Empty string is falsy in JS, so check for non-empty
        if (!empty($title)) {
            $html .= '<h3>' . $title . '</h3>';
        }
        
        // JSX: {attributes.text && <p>{attributes.text}</p>}
        if (!empty($text)) {
            $html .= '<p>' . $text . '</p>';
        }
        
        // JSX: {attributes.post && <p className={'more'}><a href={attributes.post.link}>Mehr →</a></p>}
        // Checks if post object exists (not just link)
        if ($post && !empty($post)) {
            $post_link = $post['link'] ?? '';
            $html .= '<p class="more"><a href="' . $post_link . '">Mehr →</a></p>';
        }
        
        $html .= '</div></div>';
        
        return $html;
    }
    
    /**
     * Render page-header block HTML
     */
    private function render_page_header_block($attrs) {
        $images = $attrs['images'] ?? [];
        
        if (empty($images)) {
            return '';
        }
        
        $html = '';
        
        foreach ($images as $image) {
            $url = $image['url'] ?? '';
            $text = $image['text'] ?? '';
            
            $html .= '<div class="image-item">';
            $html .= '<img src="' . esc_attr($url) . '" alt="' . esc_attr($text) . '"/>';
            $html .= '<div class="text"><span>' . esc_html($text) . '</span></div>';
            $html .= '</div>';
        }
        
        return $html;
    }
    
    /**
     * Recursively translate blocks and their attributes
     */
    private function translate_blocks($blocks, $api_key) {
        $translated_blocks = [];
        
        foreach ($blocks as $block) {
            $translated_block = $block;
            
            // Translate block attributes
            if (!empty($block['attrs'])) {
                $translated_block['attrs'] = $this->translate_block_attributes($block['attrs'], $api_key, $block['blockName']);
            }
            
            // Recursively translate inner blocks
            if (!empty($block['innerBlocks'])) {
                $translated_block['innerBlocks'] = $this->translate_blocks($block['innerBlocks'], $api_key);
            }
            
            // For core blocks with innerHTML, translate it
            // For custom blocks (radicle/*), we don't touch innerHTML - let the block re-render
            if (!empty($block['innerHTML']) && $this->should_translate_inner_html($block['blockName'])) {
                $translated_html = $this->translate_text($block['innerHTML'], $api_key);
                if ($translated_html !== false) {
                    $translated_block['innerHTML'] = $translated_html;
                    // Also update innerContent array
                    if (isset($block['innerContent'][0])) {
                        $translated_block['innerContent'][0] = $translated_html;
                    }
                }
            }
            
            $translated_blocks[] = $translated_block;
        }
        
        return $translated_blocks;
    }
    
    /**
     * Translate block attributes based on their type
     */
    private function translate_block_attributes($attrs, $api_key, $block_name = '') {
        $translated_attrs = [];
        
        // Define which attributes should be translated for custom blocks
        $translatable_keys = [
            'heading', 'buttonText', 'title', 'text', 'content', 
            'description', 'label', 'placeholder', 'caption'
        ];
        
        foreach ($attrs as $key => $value) {
            if (is_string($value)) {
                // Check if this is a translatable attribute
                if (in_array($key, $translatable_keys) && !empty($value) && !$this->is_non_translatable_string($value)) {
                    $translated_value = $this->translate_text($value, $api_key);
                    $translated_attrs[$key] = $translated_value !== false ? $translated_value : $value;
                } else {
                    $translated_attrs[$key] = $value;
                }
            } elseif (is_array($value)) {
                // Handle arrays (like images array in page-header block)
                $translated_attrs[$key] = $this->translate_array_attributes($value, $api_key);
            } else {
                // Keep non-string values as-is
                $translated_attrs[$key] = $value;
            }
        }
        
        return $translated_attrs;
    }
    
    /**
     * Translate array attributes (for nested structures like images with text)
     */
    private function translate_array_attributes($array, $api_key) {
        $translated = [];
        
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                // Translate string values in arrays (like 'text' in images array)
                if ($key === 'text' || $key === 'title' || $key === 'description' || $key === 'caption') {
                    if (!empty($value) && !$this->is_non_translatable_string($value)) {
                        $translated_value = $this->translate_text($value, $api_key);
                        $translated[$key] = $translated_value !== false ? $translated_value : $value;
                    } else {
                        $translated[$key] = $value;
                    }
                } else {
                    $translated[$key] = $value;
                }
            } elseif (is_array($value)) {
                // Recursively handle nested arrays
                $translated[$key] = $this->translate_array_attributes($value, $api_key);
            } else {
                $translated[$key] = $value;
            }
        }
        
        return $translated;
    }
    
    /**
     * Check if inner HTML should be translated for this block type
     */
    private function should_translate_inner_html($block_name) {
        // Only translate innerHTML for core blocks that don't have innerBlocks
        $core_blocks = [
            'core/paragraph', 'core/heading', 'core/list', 'core/quote',
            'core/pullquote', 'core/verse', 'core/preformatted'
        ];
        
        return in_array($block_name, $core_blocks);
    }
    
    /**
     * Check if a string should not be translated (URLs, IDs, etc.)
     */
    private function is_non_translatable_string($value) {
        // Skip URLs
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return true;
        }
        
        // Skip short strings that look like IDs or codes (less than 3 chars)
        if (strlen($value) < 3) {
            return true;
        }
        
        // Skip numeric strings
        if (is_numeric($value)) {
            return true;
        }
        
        return false;
    }
    
    private function translate_array_values($array, $api_key) {
        $translated = [];
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $translated_value = $this->translate_text($value, $api_key);
                $translated[$key] = $translated_value !== false ? $translated_value : $value;
            } elseif (is_array($value)) {
                $translated[$key] = $this->translate_array_values($value, $api_key);
            } else {
                $translated[$key] = $value;
            }
        }
        return $translated;
    }
    
    private function translate_text($text, $api_key) {
        if (empty($text)) {
            return '';
        }
        
        // Determine API endpoint (free vs pro)
        $is_free_key = strpos($api_key, ':fx') !== false;
        $api_url = $is_free_key 
            ? 'https://api-free.deepl.com/v2/translate'
            : 'https://api.deepl.com/v2/translate';
        
        $response = wp_remote_post($api_url, [
            'timeout' => 30,
            'headers' => [
                'Authorization' => 'DeepL-Auth-Key ' . $api_key,
                'Content-Type' => 'application/json'
            ],
            'body' => json_encode([
                'text' => [$text],
                'source_lang' => 'DE',
                'target_lang' => 'EN-US',
                'preserve_formatting' => true,
                'tag_handling' => 'html'
            ])
        ]);
        
        if (is_wp_error($response)) {
            error_log('DeepL Translation Error: ' . $response->get_error_message());
            return false;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        if ($status_code !== 200) {
            error_log('DeepL API Error: HTTP ' . $status_code);
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['translations'][0]['text'])) {
            error_log('DeepL Translation Error: Invalid response format');
            return false;
        }
        
        return $body['translations'][0]['text'];
    }
}

// Initialize plugin
new Polylang_DeepL_Translator();