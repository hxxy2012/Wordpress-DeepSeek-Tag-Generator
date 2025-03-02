<?php
/*
Plugin Name: DeepSeek Tag Generator
Description: Automatically generate SEO-friendly tags for your posts using DeepSeek AI.
Version: 1.0
Author: hxxy2012
Text Domain: deepseek-tag-generator
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

class DeepSeek_Tag_Generator {
    
    // Constructor
    public function __construct() {
        // Register activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Add settings page
        add_action('admin_menu', array($this, 'add_settings_page'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add meta box to post editor
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        
        // Register AJAX handler
        add_action('wp_ajax_generate_deepseek_tags', array($this, 'ajax_generate_tags'));
        
        // Add admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    // Plugin activation
    public function activate() {
        // Set default options
        add_option('deepseek_api_url', 'https://ark.cn-beijing.volces.com/api/v3/chat/completions');
        add_option('deepseek_api_key', '');
        add_option('deepseek_model', 'deepseek-v3-241226');
        add_option('deepseek_tag_count', '5');
    }
    
    // Add settings page
    public function add_settings_page() {
        add_options_page(
            __('DeepSeek Tag Generator Settings', 'deepseek-tag-generator'),
            __('DeepSeek Tags', 'deepseek-tag-generator'),
            'manage_options',
            'deepseek-tag-generator',
            array($this, 'render_settings_page')
        );
    }
    
    // Render settings page
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('DeepSeek Tag Generator Settings', 'deepseek-tag-generator'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('deepseek_tag_generator_options');
                do_settings_sections('deepseek-tag-generator');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }
    
    // Register settings
    public function register_settings() {
        register_setting('deepseek_tag_generator_options', 'deepseek_api_url');
        register_setting('deepseek_tag_generator_options', 'deepseek_api_key');
        register_setting('deepseek_tag_generator_options', 'deepseek_model');
        register_setting('deepseek_tag_generator_options', 'deepseek_tag_count');
        
        add_settings_section(
            'deepseek_tag_generator_main',
            __('API Settings', 'deepseek-tag-generator'),
            array($this, 'render_section_info'),
            'deepseek-tag-generator'
        );
        
        add_settings_field(
            'deepseek_api_url',
            __('DeepSeek API URL', 'deepseek-tag-generator'),
            array($this, 'render_api_url_field'),
            'deepseek-tag-generator',
            'deepseek_tag_generator_main'
        );
        
        add_settings_field(
            'deepseek_api_key',
            __('API Key', 'deepseek-tag-generator'),
            array($this, 'render_api_key_field'),
            'deepseek-tag-generator',
            'deepseek_tag_generator_main'
        );
        
        add_settings_field(
            'deepseek_model',
            __('AI Model', 'deepseek-tag-generator'),
            array($this, 'render_model_field'),
            'deepseek-tag-generator',
            'deepseek_tag_generator_main'
        );
        
        add_settings_field(
            'deepseek_tag_count',
            __('Number of Tags', 'deepseek-tag-generator'),
            array($this, 'render_tag_count_field'),
            'deepseek-tag-generator',
            'deepseek_tag_generator_main'
        );
    }
    
    public function render_section_info() {
        echo '<p>' . esc_html__('Configure your DeepSeek API settings below.', 'deepseek-tag-generator') . '</p>';
    }
    
    public function render_api_url_field() {
        $api_url = get_option('deepseek_api_url');
        echo '<input type="text" id="deepseek_api_url" name="deepseek_api_url" value="' . esc_attr($api_url) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('The URL for the DeepSeek API.', 'deepseek-tag-generator') . '</p>';
    }
    
    public function render_api_key_field() {
        $api_key = get_option('deepseek_api_key');
        echo '<input type="password" id="deepseek_api_key" name="deepseek_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Your DeepSeek API key.', 'deepseek-tag-generator') . '</p>';
    }
    
    public function render_model_field() {
        $model = get_option('deepseek_model');
        echo '<input type="text" id="deepseek_model" name="deepseek_model" value="' . esc_attr($model) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('The AI model to use for tag generation.', 'deepseek-tag-generator') . '</p>';
    }
    
    public function render_tag_count_field() {
        $count = get_option('deepseek_tag_count');
        echo '<input type="number" id="deepseek_tag_count" name="deepseek_tag_count" value="' . esc_attr($count) . '" class="small-text" min="1" max="20" />';
        echo '<p class="description">' . esc_html__('Maximum number of tags to generate.', 'deepseek-tag-generator') . '</p>';
    }
    
    // Add meta box to post editor
    public function add_meta_box() {
        add_meta_box(
            'deepseek_tag_generator_box',
            __('DeepSeek Tag Generator', 'deepseek-tag-generator'),
            array($this, 'render_meta_box'),
            'post',
            'side',
            'default'
        );
    }
    
    // Render meta box
    public function render_meta_box($post) {
        wp_nonce_field('deepseek_tag_generator_nonce', 'deepseek_tag_generator_nonce');
        ?>
        <div id="deepseek-tag-generator-container">
            <p><?php echo esc_html__('Click the button below to generate tags based on your post content.', 'deepseek-tag-generator'); ?></p>
            <button type="button" id="deepseek-generate-tags" class="button button-primary">
                <?php echo esc_html__('Generate Tags', 'deepseek-tag-generator'); ?>
            </button>
            <div id="deepseek-tags-loading" style="display: none; margin-top: 10px;">
                <span class="spinner is-active" style="float: none;"></span>
                <?php echo esc_html__('Generating tags...', 'deepseek-tag-generator'); ?>
            </div>
            <div id="deepseek-tags-result" style="display: none; margin-top: 10px;">
                <p><strong><?php echo esc_html__('Suggested Tags:', 'deepseek-tag-generator'); ?></strong></p>
                <div id="deepseek-tags-list"></div>
                <div id="deepseek-tags-error" class="notice notice-error" style="display: none;"></div>
            </div>
        </div>
        <?php
    }
    
    // Enqueue admin scripts
    public function enqueue_admin_scripts($hook) {
        global $post;
        
        if (!in_array($hook, array('post.php', 'post-new.php')) || !$post || $post->post_type !== 'post') {
            return;
        }
        
        wp_enqueue_script('deepseek-tag-generator', plugins_url('js/tag-generator.js', __FILE__), array('jquery'), '1.0', true);
        wp_localize_script('deepseek-tag-generator', 'deepseekTagGenerator', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('deepseek_tag_generator_nonce'),
            'postId' => $post->ID,
            'i18n' => array(
                'error' => __('Error generating tags:', 'deepseek-tag-generator'),
                'noApiKey' => __('API key is not configured. Please set it in the plugin settings.', 'deepseek-tag-generator'),
                'addTags' => __('Add these tags', 'deepseek-tag-generator'),
            )
        ));

        // Inline CSS for the tag generator
        wp_add_inline_style('wp-admin', '
            #deepseek-tags-list {
                margin-bottom: 10px;
            }
            #deepseek-tags-list .tag-item {
                display: inline-block;
                background: #f0f0f1;
                border-radius: 3px;
                padding: 2px 8px;
                margin: 0 5px 5px 0;
                cursor: pointer;
            }
            #deepseek-tags-list .tag-item:hover {
                background: #e0e0e1;
            }
        ');
    }
    
    // AJAX handler for tag generation
    public function ajax_generate_tags() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'deepseek_tag_generator_nonce')) {
            wp_send_json_error(__('Security check failed', 'deepseek-tag-generator'));
        }
        
        // Check if post ID is provided
        if (!isset($_POST['post_id'])) {
            wp_send_json_error(__('No post ID provided', 'deepseek-tag-generator'));
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(__('Post not found', 'deepseek-tag-generator'));
        }
        
        // Get API settings
        $api_url = get_option('deepseek_api_url');
        $api_key = get_option('deepseek_api_key');
        $model = get_option('deepseek_model');
        $tag_count = intval(get_option('deepseek_tag_count', 5));
        
        if (empty($api_key)) {
            wp_send_json_error(__('API key is not configured', 'deepseek-tag-generator'));
        }
        
        // Prepare post content for analysis
        $content = strip_tags($post->post_title . ' ' . $post->post_content);
        $content = 'Please analyze the following content and provide ' . $tag_count . ' relevant SEO-friendly tags or keywords separated by commas. Focus on keywords that will improve search visibility: ' . $content;
        
        // Make API request
        $response = wp_remote_post($api_url, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array('role' => 'system', 'content' => '你是专业的内容创作者和SEO专家'),
                    array('role' => 'user', 'content' => $content)
                )
            ))
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        // Process the response
        if (!isset($data['choices'][0]['message']['content'])) {
            wp_send_json_error(__('Invalid response from DeepSeek API', 'deepseek-tag-generator'));
        }
        
        $response_text = $data['choices'][0]['message']['content'];
        
        // Extract tags from the response
        $tags = $this->extract_tags($response_text);
        
        // Limit to the specified number of tags
        $tags = array_slice($tags, 0, $tag_count);
        
        // Get existing post tags
        $existing_tags = wp_get_post_tags($post_id, array('fields' => 'names'));
        
        wp_send_json_success(array(
            'tags' => $tags,
            'existing_tags' => $existing_tags
        ));
    }
    
    // Helper function to extract tags from API response
    private function extract_tags($text) {
        // Remove common elements like "Tags:", "Keywords:", numbering, etc.
        $text = preg_replace('/^(tags|keywords|suggested tags|relevant tags)[\s:]+/i', '', $text);
        $text = preg_replace('/^\d+[\.\)]\s+/m', '', $text);
        
        // Split by commas and clean up
        $tags = array_map('trim', explode(',', $text));
        
        // Remove empty tags and quotes
        $tags = array_filter($tags, function($tag) {
            $tag = trim($tag, " \t\n\r\0\x0B\"'");
            return !empty($tag);
        });
        
        // Clean up tags
        $tags = array_map(function($tag) {
            return trim($tag, " \t\n\r\0\x0B\"'");
        }, $tags);
        
        return array_values($tags);
    }
}

// Initialize the plugin
$deepseek_tag_generator = new DeepSeek_Tag_Generator();