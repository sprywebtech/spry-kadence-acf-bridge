<?php
/**
 * Plugin Name: Spry Kadence ACF Bridge
 * Plugin URI: https://github.com/sprywebtech/spry-kadence-acf-bridge
 * Description: Seamlessly connect Kadence Forms to Advanced Custom Fields with automatic webhook generation and field mapping.
 * Version: 1.0.0
 * Author: Spry Web Tech
 * Author URI: https://sprywebtech.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: spry-kadence-acf-bridge
 * Requires at least: 5.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SPRY_KADENCE_ACF_BRIDGE_VERSION', '1.0.0');
define('SPRY_KADENCE_ACF_BRIDGE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SPRY_KADENCE_ACF_BRIDGE_PLUGIN_URL', plugin_dir_url(__FILE__));

class SpryKadenceACFBridge {
    
    public function __construct() {
        add_action('init', array($this, 'spry_init'));
        add_action('admin_menu', array($this, 'spry_add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'spry_enqueue_admin_scripts'));
        add_action('wp_ajax_spry_save_webhook_config', array($this, 'spry_save_webhook_config'));
        add_action('wp_ajax_spry_delete_webhook_config', array($this, 'spry_delete_webhook_config'));
        add_action('wp_ajax_spry_test_webhook', array($this, 'spry_test_webhook'));
        
        // Hook for handling webhooks
        add_action('init', array($this, 'spry_handle_webhook_request'));
    }
    
    public function spry_init() {
        // Check if required plugins are active
        if (!$this->spry_check_dependencies()) {
            add_action('admin_notices', array($this, 'spry_dependency_notice'));
            return;
        }
    }
    
    private function spry_check_dependencies() {
        // Check if ACF is active
        if (!function_exists('get_field')) {
            return false;
        }
        
        // Check if Kadence Blocks is active (optional but recommended)
        if (!defined('KADENCE_BLOCKS_VERSION')) {
            // Still allow plugin to work without Kadence Blocks
        }
        
        return true;
    }
    
    public function spry_dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p><strong>Spry Kadence ACF Bridge:</strong> This plugin requires Advanced Custom Fields (ACF) to be installed and activated.</p>
        </div>
        <?php
    }
    
    public function spry_add_admin_menu() {
        add_options_page(
            'Spry Kadence ACF Bridge',
            'Spry Kadence ACF Bridge',
            'manage_options',
            'spry-kadence-acf-bridge',
            array($this, 'spry_admin_page')
        );
    }
    
    public function spry_enqueue_admin_scripts($hook) {
        if ('settings_page_spry-kadence-acf-bridge' !== $hook) {
            return;
        }
        
        wp_enqueue_script('jquery');
        wp_enqueue_style('spry-kadence-acf-bridge-admin', SPRY_KADENCE_ACF_BRIDGE_PLUGIN_URL . 'assets/admin.css', array(), SPRY_KADENCE_ACF_BRIDGE_VERSION);
        
        wp_localize_script('jquery', 'spryKadenceAcfBridge', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spry_kadence_acf_bridge_nonce')
        ));
    }
    
    public function spry_admin_page() {
        $webhooks = get_option('spry_kadence_acf_bridge_webhooks', array());
        $post_types = get_post_types(array('public' => true), 'objects');
        
        ?>
        <div class="wrap">
            <h1>Spry Kadence ACF Bridge</h1>
            <p>Create webhooks to connect your Kadence Forms to ACF custom fields automatically.</p>
            
            <div class="spry-kadence-acf-bridge-container">
                <div class="spry-webhook-form-section">
                    <h2>Create New Webhook</h2>
                    <form id="spry-webhook-config-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="spry_webhook_name">Webhook Name</label></th>
                                <td>
                                    <input type="text" id="spry_webhook_name" name="webhook_name" class="regular-text" placeholder="Unique Webhook Name" required />
                                    <p class="description">Give this webhook a descriptive name</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="spry_post_type">Target Post Type</label></th>
                                <td>
                                    <select id="spry_post_type" name="post_type" required>
                                        <option value="">Select Post Type</option>
                                        <?php foreach ($post_types as $post_type): ?>
                                            <option value="<?php echo esc_attr($post_type->name); ?>">
                                                <?php echo esc_html($post_type->label); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="description">Posts will be created in this post type</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="spry_acf_fields">ACF Field Names</label></th>
                                <td>
                                    <textarea id="spry_acf_fields" name="acf_fields" rows="6" class="large-text" placeholder="Add the ACF Field Name, one per line." required></textarea>
                                    <p class="description">Enter one ACF field name per line. Use these same names to map your fields in the Kadence form webhook field mapping settings</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="spry_title_field">Title Field (Optional)</label></th>
                                <td>
                                    <input type="text" id="spry_title_field" name="title_field" class="regular-text" placeholder="Custom Post Title" />
                                    <p class="description">ACF field name to use for post title (leave empty for auto-generated titles)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="spry_category_mapping">Category Mapping (Optional). Only supports one taxomony per post-type.</label></th>
                                <td>
                                    <input type="text" id="spry_category_field" name="category_field" class="regular-text" placeholder="ACF Taxonomy Name" />
                                    <p class="description">ACF field taxomony name that should map to categories</p>
                                    <br><br>
                                    <textarea id="spry_category_mapping" name="category_mapping" rows="4" class="large-text" placeholder="example: value:category-slug"></textarea>
                                    <p class="description">Map field values to category slugs (format: value:category-slug, one per line)</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="spry_taxonomy_name">Taxonomy Name (Optional)</label></th>
                                <td>
                                    <input type="text" id="spry_taxonomy_name" name="taxonomy_name" class="regular-text" placeholder="category" />
                                    <p class="description">Taxonomy name for category mapping (default: category)</p>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">Create Webhook</button>
                        </p>
                    </form>
                </div>
                
                <div class="spry-existing-webhooks-section">
                    <h2>Existing Webhooks</h2>
                    <?php if (empty($webhooks)): ?>
                        <p>No webhooks created yet.</p>
                    <?php else: ?>
                        <div class="spry-webhooks-list">
                            <?php foreach ($webhooks as $id => $webhook): ?>
                                <div class="spry-webhook-item" data-id="<?php echo esc_attr($id); ?>">
                                    <div class="spry-webhook-header">
                                        <h3><?php echo esc_html($webhook['name']); ?></h3>
                                        <div class="spry-webhook-actions">
                                            <button class="button spry-test-webhook" data-id="<?php echo esc_attr($id); ?>">Test</button>
                                            <button class="button button-link-delete spry-delete-webhook" data-id="<?php echo esc_attr($id); ?>">Delete</button>
                                        </div>
                                    </div>
                                    <div class="spry-webhook-details">
                                        <p><strong>Post Type:</strong> <?php echo esc_html($webhook['post_type']); ?></p>
                                        <p><strong>Webhook URL:</strong></p>
                                        <code class="spry-webhook-url"><?php echo esc_url(home_url('/?spry_kadence_acf_webhook=' . $id)); ?></code>
                                        <button class="button spry-copy-url" data-url="<?php echo esc_attr(home_url('/?spry_kadence_acf_webhook=' . $id)); ?>">Copy URL</button>
                                        
                                        <details>
                                            <summary>Field Mapping Instructions</summary>
                                            <div class="spry-field-mapping-instructions">
                                                <p>In your Kadence form webhook settings, map these fields:</p>
                                                <ul>
                                                    <?php foreach ($webhook['acf_fields'] as $field): ?>
                                                        <li><code><?php echo esc_html($field); ?></code></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </details>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
        .spry-kadence-acf-bridge-container {
            max-width: 1200px;
        }
        .spry-webhook-form-section {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .spry-existing-webhooks-section {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .spry-webhook-item {
            border: 1px solid #ddd;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .spry-webhook-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
        }
        .spry-webhook-header h3 {
            margin: 0;
        }
        .spry-webhook-actions {
            display: flex;
            gap: 10px;
        }
        .spry-webhook-details {
            padding: 20px;
        }
        .spry-webhook-url {
            display: block;
            background: #f1f1f1;
            padding: 8px 12px;
            border-radius: 4px;
            margin: 5px 0;
            word-break: break-all;
        }
        .spry-field-mapping-instructions {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-top: 10px;
        }
        .spry-field-mapping-instructions ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        .spry-copy-url {
            margin-left: 10px;
        }
        .spry-success-message, .spry-error-message {
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .spry-success-message {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .spry-error-message {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Handle form submission
            $('#spry-webhook-config-form').on('submit', function(e) {
                e.preventDefault();
                
                var formData = {
                    action: 'spry_save_webhook_config',
                    nonce: spryKadenceAcfBridge.nonce,
                    webhook_name: $('#spry_webhook_name').val(),
                    post_type: $('#spry_post_type').val(),
                    acf_fields: $('#spry_acf_fields').val(),
                    title_field: $('#spry_title_field').val(),
                    category_field: $('#spry_category_field').val(),
                    category_mapping: $('#spry_category_mapping').val(),
                    taxonomy_name: $('#spry_taxonomy_name').val()
                };
                
                $.post(spryKadenceAcfBridge.ajax_url, formData, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
            
            // Handle webhook deletion
            $('.spry-delete-webhook').on('click', function() {
                if (!confirm('Are you sure you want to delete this webhook?')) {
                    return;
                }
                
                var webhookId = $(this).data('id');
                var webhookItem = $(this).closest('.spry-webhook-item');
                
                $.post(spryKadenceAcfBridge.ajax_url, {
                    action: 'spry_delete_webhook_config',
                    nonce: spryKadenceAcfBridge.nonce,
                    webhook_id: webhookId
                }, function(response) {
                    if (response.success) {
                        webhookItem.fadeOut();
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });
            
            // Handle URL copying
            $('.spry-copy-url').on('click', function() {
                var url = $(this).data('url');
                navigator.clipboard.writeText(url).then(function() {
                    alert('URL copied to clipboard!');
                });
            });
            
            // Handle webhook testing
            $('.spry-test-webhook').on('click', function() {
                var webhookId = $(this).data('id');
                alert('Test feature coming soon! For now, submit a real form to test the webhook.');
            });
        });
        </script>
        <?php
    }
    
    public function spry_save_webhook_config() {
        check_ajax_referer('spry_kadence_acf_bridge_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $webhook_name = sanitize_text_field($_POST['webhook_name']);
        $post_type = sanitize_text_field($_POST['post_type']);
        $acf_fields = array_map('trim', explode("\n", sanitize_textarea_field($_POST['acf_fields'])));
        $title_field = sanitize_text_field($_POST['title_field']);
        $category_field = sanitize_text_field($_POST['category_field']);
        $category_mapping = sanitize_textarea_field($_POST['category_mapping']);
        $taxonomy_name = sanitize_text_field($_POST['taxonomy_name']) ?: 'category';
        
        // Parse category mapping
        $category_map = array();
        if (!empty($category_mapping)) {
            $lines = explode("\n", $category_mapping);
            foreach ($lines as $line) {
                $parts = explode(':', trim($line));
                if (count($parts) === 2) {
                    $category_map[trim($parts[0])] = trim($parts[1]);
                }
            }
        }
        
        $webhooks = get_option('spry_kadence_acf_bridge_webhooks', array());
        $webhook_id = uniqid();
        
        $webhooks[$webhook_id] = array(
            'name' => $webhook_name,
            'post_type' => $post_type,
            'acf_fields' => array_filter($acf_fields),
            'title_field' => $title_field,
            'category_field' => $category_field,
            'category_mapping' => $category_map,
            'taxonomy_name' => $taxonomy_name,
            'created' => current_time('mysql')
        );
        
        update_option('spry_kadence_acf_bridge_webhooks', $webhooks);
        
        wp_send_json_success('Webhook created successfully!');
    }
    
    public function spry_delete_webhook_config() {
        check_ajax_referer('spry_kadence_acf_bridge_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
        
        $webhook_id = sanitize_text_field($_POST['webhook_id']);
        $webhooks = get_option('spry_kadence_acf_bridge_webhooks', array());
        
        if (isset($webhooks[$webhook_id])) {
            unset($webhooks[$webhook_id]);
            update_option('spry_kadence_acf_bridge_webhooks', $webhooks);
            wp_send_json_success('Webhook deleted successfully!');
        } else {
            wp_send_json_error('Webhook not found');
        }
    }
    
    public function spry_handle_webhook_request() {
        if (!isset($_GET['spry_kadence_acf_webhook'])) {
            return;
        }
        
        $webhook_id = sanitize_text_field($_GET['spry_kadence_acf_webhook']);
        $webhooks = get_option('spry_kadence_acf_bridge_webhooks', array());
        
        if (!isset($webhooks[$webhook_id])) {
            wp_die('Webhook not found', 'Webhook Error', 404);
        }
        
        $config = $webhooks[$webhook_id];
        $this->spry_process_webhook($config);
    }
    
    private function spry_process_webhook($config) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Get webhook data
        $input = file_get_contents('php://input');
        $json_data = json_decode($input, true);
        $post_data = $_POST;
        
        $data = array();
        if (!empty($json_data) && is_array($json_data)) {
            $data = $json_data;
        } elseif (!empty($post_data)) {
            $data = $post_data;
        } else {
            parse_str($input, $parsed_data);
            if (!empty($parsed_data)) {
                $data = $parsed_data;
            }
        }
        
        if (empty($data)) {
            wp_send_json_error('No data received');
            return;
        }
        
        // Create post title
        $post_title = $config['name'];
        if (!empty($config['title_field']) && isset($data[$config['title_field']])) {
            $post_title .= ': ' . $data[$config['title_field']];
        } else {
            $post_title .= ': ' . date('M j, Y g:i A');
        }
        
        // Create post
        $post_id = wp_insert_post(array(
            'post_title' => $post_title,
            'post_type' => $config['post_type'],
            'post_status' => 'publish'
        ));
        
        if (!$post_id || is_wp_error($post_id)) {
            wp_send_json_error('Failed to create post');
            return;
        }
        
        // Save ACF fields
        foreach ($config['acf_fields'] as $field_name) {
            if (isset($data[$field_name]) && !empty($data[$field_name])) {
                // Handle attachments
                if (strpos($field_name, 'attachment') !== false && filter_var($data[$field_name], FILTER_VALIDATE_URL)) {
                    $attachment_value = $this->spry_handle_attachment($data[$field_name], $post_id);
                    update_field($field_name, $attachment_value, $post_id);
                    update_post_meta($post_id, $field_name . '_url', $data[$field_name]);
                } else {
                    update_field($field_name, $data[$field_name], $post_id);
                    update_post_meta($post_id, $field_name, $data[$field_name]);
                }
            }
        }
        
        // Handle category mapping
        if (!empty($config['category_field']) && !empty($config['category_mapping']) && isset($data[$config['category_field']])) {
            $this->spry_assign_category($post_id, $data[$config['category_field']], $config['category_mapping'], $config['taxonomy_name']);
        }
        
        wp_send_json_success(array(
            'message' => 'Post created successfully',
            'post_id' => $post_id
        ));
    }
    
    private function spry_handle_attachment($url, $post_id) {
        if (empty($url)) {
            return '';
        }
        
        // Check if already exists
        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id) {
            return $attachment_id;
        }
        
        // Import file
        $tmp = download_url($url);
        if (is_wp_error($tmp)) {
            return $url;
        }
        
        $file_array = array(
            'name' => basename(parse_url($url, PHP_URL_PATH)),
            'tmp_name' => $tmp
        );
        
        $attachment_id = media_handle_sideload($file_array, $post_id);
        
        if (file_exists($tmp)) {
            unlink($tmp);
        }
        
        return is_wp_error($attachment_id) ? $url : $attachment_id;
    }
    
    private function spry_assign_category($post_id, $field_value, $category_mapping, $taxonomy_name) {
        $field_lower = strtolower(trim($field_value));
        
        if (!isset($category_mapping[$field_lower])) {
            return;
        }
        
        $category_slug = $category_mapping[$field_lower];
        $category = get_term_by('slug', $category_slug, $taxonomy_name);
        
        if (!$category) {
            // Create category if it doesn't exist
            $category_name = ucwords(str_replace('-', ' ', $category_slug));
            $new_category = wp_insert_term($category_name, $taxonomy_name, array('slug' => $category_slug));
            
            if (!is_wp_error($new_category)) {
                $category_id = $new_category['term_id'];
            }
        } else {
            $category_id = $category->term_id;
        }
        
        if (isset($category_id)) {
            wp_set_post_terms($post_id, array($category_id), $taxonomy_name);
        }
    }
}

// Initialize the plugin
new SpryKadenceACFBridge();