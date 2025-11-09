<?php
/**
 * Plugin Name: TexoLink Topic Clusters
 * Plugin URI: https://texolink.com/topic-clusters
 * Description: AI-powered topic cluster building for WordPress. Requires TexoLink plugin. Find related content and build internal link clusters using semantic search.
 * Version: 1.0.6
 * Author: TexoLink
 * Author URI: https://texolink.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: texolink-clusters
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Requires Plugins: texolink-internal-links
 * 
 * @package TexoLink_Topic_Clusters
 */

// ============================================================================
// AUTO-UPDATE CHECKER - Checks GitHub for new releases
// ============================================================================
require 'plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/mymaterick/texolink-topic-clusters',
    __FILE__,
    'texolink-topic-clusters'
);

// Enable release assets (so users can download from GitHub releases)
$updateChecker->getVcsApi()->enableReleaseAssets();

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('TEXOLINK_CLUSTERS_VERSION', '1.0.6');
define('TEXOLINK_CLUSTERS_FILE', __FILE__);
define('TEXOLINK_CLUSTERS_PATH', plugin_dir_path(__FILE__));
define('TEXOLINK_CLUSTERS_URL', plugin_dir_url(__FILE__));
define('TEXOLINK_CLUSTERS_BASENAME', plugin_basename(__FILE__));

/**
 * Check if TexoLink plugin is active
 */
function texolink_clusters_check_dependencies() {
    // Method 1: Check if TexoLink plugin is in active plugins list
    $active_plugins = get_option('active_plugins');
    $texolink_active = false;
    
    // Check for the plugin by slug
    foreach ($active_plugins as $plugin) {
        if (strpos($plugin, 'texolink') !== false) {
            $texolink_active = true;
            break;
        }
    }
    
    // Method 2: Check for common TexoLink functions/constants
    if (!$texolink_active) {
        if (function_exists('texolink_get_site_key') || 
            function_exists('texolink_get_api_url') ||
            defined('TEXOLINK_VERSION') ||
            defined('TEXOLINK_PLUGIN_FILE')) {
            $texolink_active = true;
        }
    }
    
    if (!$texolink_active) {
        add_action('admin_notices', 'texolink_clusters_dependency_notice');
        return false;
    }
    
    return true;
}

/**
 * Display admin notice if TexoLink is not active
 */
function texolink_clusters_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong><?php _e('TexoLink Topic Clusters', 'texolink-clusters'); ?>:</strong>
            <?php _e('This plugin requires the TexoLink Internal Links plugin to be installed and activated.', 'texolink-clusters'); ?>
            <a href="<?php echo admin_url('plugins.php'); ?>"><?php _e('Manage Plugins', 'texolink-clusters'); ?></a>
        </p>
    </div>
    <?php
}

/**
 * Main plugin class
 */
class TexoLink_Topic_Clusters {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Check dependencies before initializing
        if (!texolink_clusters_check_dependencies()) {
            return;
        }
        
        $this->includes();
        $this->hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
        require_once TEXOLINK_CLUSTERS_PATH . 'includes/api-connector.php';
        
        if (is_admin()) {
            require_once TEXOLINK_CLUSTERS_PATH . 'includes/admin-page.php';
        }
    }
    
    /**
     * Set up hooks
     */
    private function hooks() {
        // Activation/deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Add settings link on plugins page
        add_filter('plugin_action_links_' . TEXOLINK_CLUSTERS_BASENAME, array($this, 'add_settings_link'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check if TexoLink is active
        if (!texolink_clusters_check_dependencies()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(
                __('TexoLink Topic Clusters requires the TexoLink plugin to be installed and activated.', 'texolink-clusters'),
                __('Plugin Activation Error', 'texolink-clusters'),
                array('back_link' => true)
            );
        }
        
        // Set activation timestamp
        update_option('texolink_clusters_activated', time());
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
    }
    
    /**
     * Add settings link to plugins page
     */
    public function add_settings_link($links) {
        $clusters_link = '<a href="admin.php?page=texolink-topic-clusters">' . __('Topic Clusters', 'texolink-clusters') . '</a>';
        array_unshift($links, $clusters_link);
        return $links;
    }
}

/**
 * Initialize the plugin
 */
function texolink_clusters_init() {
    // Only initialize if TexoLink is active
    if (texolink_clusters_check_dependencies()) {
        return TexoLink_Topic_Clusters::get_instance();
    }
}

// Start the plugin
add_action('plugins_loaded', 'texolink_clusters_init');

/**
 * Helper function to get site key from TexoLink
 */
function texolink_clusters_get_site_key() {
    // Check if TexoLink provides a function
    if (function_exists('texolink_get_site_key')) {
        return texolink_get_site_key();
    }
    // Fallback to direct option access
    return get_option('texolink_site_id', '');
}

/**
 * Helper function to get API URL from TexoLink
 */
function texolink_clusters_get_api_url() {
    // Check if TexoLink provides a function
    if (function_exists('texolink_get_api_url')) {
        return texolink_get_api_url();
    }
    // Fallback to direct option access
    return get_option('texolink_api_url', '');
}

/**
 * Helper function to check if plugin is configured
 */
function texolink_clusters_is_configured() {
    $api_url = texolink_clusters_get_api_url();
    // Just check if API URL is set - that's the main requirement
    return !empty($api_url);
}
