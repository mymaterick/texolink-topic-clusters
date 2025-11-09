<?php
/**
 * Admin Page
 * 
 * Handles the Topic Clusters admin interface with async processing
 * 
 * @package TexoLink_Topic_Clusters
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin page class
 */
class TexoLink_Clusters_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_menu_page'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // AJAX handlers
        add_action('wp_ajax_texolink_clusters_generate', array($this, 'ajax_generate'));
        add_action('wp_ajax_texolink_clusters_check_status', array($this, 'ajax_check_status'));
        add_action('wp_ajax_texolink_clusters_get_results', array($this, 'ajax_get_results'));
        add_action('wp_ajax_texolink_clusters_insert_links', array($this, 'ajax_insert_links'));
    }
    
    /**
     * Add menu page
     */
    public function add_menu_page() {
        add_submenu_page(
            'texolink',  // Add under TexoLink menu
            __('Topic Clusters', 'texolink-clusters'),
            __('Topic Clusters', 'texolink-clusters'),
            'manage_options',
            'texolink-topic-clusters',
            array($this, 'render_page')
        );
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Only load on our page
        if ($hook !== 'texolink_page_texolink-topic-clusters') {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'texolink-clusters-css',
            TEXOLINK_CLUSTERS_URL . 'assets/css/topic-clusters.css',
            array(),
            TEXOLINK_CLUSTERS_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'texolink-clusters-js',
            TEXOLINK_CLUSTERS_URL . 'assets/js/topic-clusters.js',
            array('jquery'),
            TEXOLINK_CLUSTERS_VERSION,
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('texolink-clusters-js', 'texolinkClusters', array(
            'apiUrl' => texolink_clusters_get_api_url(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('texolink_clusters_nonce'),
            'siteUrl' => get_site_url(),
            'isConfigured' => texolink_clusters_is_configured(),
            'pollInterval' => 2000  // Poll every 2 seconds
        ));
    }
    
    /**
     * Render the admin page
     */
    public function render_page() {
        // Check if TexoLink is configured
        if (!texolink_clusters_is_configured()) {
            $this->render_setup_notice();
            return;
        }
        
        ?>
        <div class="wrap texolink-clusters-wrap">
            <!-- Header -->
            <div class="texolink-clusters-header">
                <h1>
                    <span class="dashicons dashicons-networking"></span>
                    <?php _e('Topic Clusters', 'texolink-clusters'); ?>
                </h1>
                <p class="subtitle"><?php _e('Build SEO authority by connecting related content', 'texolink-clusters'); ?></p>
            </div>

            <!-- Search Section -->
            <div class="texolink-clusters-search-section">
                <div class="search-container">
                    <div class="search-input-group">
                        <label for="cluster-topic-input"><?php _e('Find posts about:', 'texolink-clusters'); ?></label>
                        <div class="input-wrapper">
                            <input 
                                type="text" 
                                id="cluster-topic-input" 
                                class="cluster-topic-input"
                                placeholder="<?php esc_attr_e('e.g., page speed, SEO, WordPress...', 'texolink-clusters'); ?>" 
                                autocomplete="off"
                            />
                            <button id="search-cluster-btn" class="button button-primary button-hero">
                                <span class="dashicons dashicons-search"></span>
                                <?php _e('Search', 'texolink-clusters'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="search-help">
                        <span class="dashicons dashicons-info"></span>
                        <p><?php _e('Enter any topic to find all related content and build internal link clusters', 'texolink-clusters'); ?></p>
                    </div>
                </div>

                <!-- Example searches -->
                <div class="example-searches">
                    <span class="label"><?php _e('Try these:', 'texolink-clusters'); ?></span>
                    <button class="example-search" data-topic="page speed"><?php _e('page speed', 'texolink-clusters'); ?></button>
                    <button class="example-search" data-topic="SEO"><?php _e('SEO', 'texolink-clusters'); ?></button>
                    <button class="example-search" data-topic="WordPress"><?php _e('WordPress', 'texolink-clusters'); ?></button>
                    <button class="example-search" data-topic="content marketing"><?php _e('content marketing', 'texolink-clusters'); ?></button>
                </div>
            </div>

            <!-- Loading/Progress State -->
            <div id="cluster-loading" class="cluster-loading" style="display: none;">
                <div class="loading-spinner"></div>
                <p class="loading-title"><?php _e('Analyzing your content...', 'texolink-clusters'); ?></p>
                <p class="loading-detail"></p>
                <div class="progress-bar">
                    <div class="progress-fill"></div>
                </div>
                <p class="progress-text">0%</p>
            </div>

            <!-- Results Section -->
            <div id="cluster-results" class="cluster-results" style="display: none;">
                <!-- Results will be inserted here by JavaScript -->
            </div>

            <!-- Empty State -->
            <div id="cluster-empty" class="cluster-empty" style="display: none;">
                <div class="empty-icon">
                    <span class="dashicons dashicons-search"></span>
                </div>
                <h2><?php _e('No Posts Found', 'texolink-clusters'); ?></h2>
                <p id="empty-message"><?php _e('No posts found related to this topic.', 'texolink-clusters'); ?></p>
                <p class="suggestion"><?php _e('Try a different search term or check that your posts have been analyzed by TexoLink.', 'texolink-clusters'); ?></p>
            </div>
        </div>

        <?php $this->render_templates(); ?>
        <?php
    }
    
    /**
     * Render JavaScript templates
     */
    private function render_templates() {
        ?>
        <!-- Results Template -->
        <template id="cluster-results-template">
            <div class="results-header">
                <div class="results-summary">
                    <h2>
                        <?php _e('Found', 'texolink-clusters'); ?> <span class="post-count"></span> <?php _e('posts about', 'texolink-clusters'); ?> 
                        "<span class="topic-name"></span>"
                    </h2>
                    <div class="cluster-strength">
                        <span class="strength-label"><?php _e('Cluster Strength:', 'texolink-clusters'); ?></span>
                        <div class="stars"></div>
                        <span class="strength-text"></span>
                    </div>
                </div>
                <div class="results-actions">
                    <button id="insert-all-btn" class="button button-primary button-large">
                        <span class="dashicons dashicons-admin-links"></span>
                        <?php _e('Insert All Links', 'texolink-clusters'); ?>
                        <span class="opportunities-count"></span>
                    </button>
                </div>
            </div>

            <div class="cluster-analysis">
                <div class="analysis-stat">
                    <span class="stat-value" id="stat-posts"></span>
                    <span class="stat-label"><?php _e('Posts in Cluster', 'texolink-clusters'); ?></span>
                </div>
                <div class="analysis-stat">
                    <span class="stat-value" id="stat-links"></span>
                    <span class="stat-label"><?php _e('Existing Links', 'texolink-clusters'); ?></span>
                </div>
                <div class="analysis-stat">
                    <span class="stat-value" id="stat-density"></span>
                    <span class="stat-label"><?php _e('Link Density', 'texolink-clusters'); ?></span>
                </div>
                <div class="analysis-stat">
                    <span class="stat-value" id="stat-opportunities"></span>
                    <span class="stat-label"><?php _e('Opportunities', 'texolink-clusters'); ?></span>
                </div>
            </div>

            <div class="posts-list">
                <!-- Suggestions table will be inserted here -->
            </div>
        </template>

        <!-- Link Suggestion Template -->
        <template id="link-suggestion-template">
            <div class="link-suggestion">
                <div class="suggestion-content">
                    <span class="anchor-text"></span>
                    <span class="arrow">→</span>
                    <span class="target-title"></span>
                </div>
                <div class="suggestion-meta">
                    <span class="relevance-score"></span>
                </div>
            </div>
        </template>
        <?php
    }
    
    /**
     * Render setup notice
     */
    private function render_setup_notice() {
        ?>
        <div class="wrap">
            <h1><?php _e('Topic Clusters - Setup Required', 'texolink-clusters'); ?></h1>
            <div class="notice notice-warning">
                <p>
                    <?php _e('Please configure TexoLink before using Topic Clusters.', 'texolink-clusters'); ?>
                    <a href="<?php echo admin_url('admin.php?page=texolink'); ?>" class="button button-primary">
                        <?php _e('Configure TexoLink', 'texolink-clusters'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX: Generate topic cluster (start async generation)
     */
    public function ajax_generate() {
        check_ajax_referer('texolink_clusters_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'texolink-clusters'));
            return;
        }
        
        $topic = isset($_POST['topic']) ? sanitize_text_field($_POST['topic']) : '';
        
        if (empty($topic)) {
            wp_send_json_error(__('Topic is required', 'texolink-clusters'));
            return;
        }
        
        // Start generation via connector
        $connector = new TexoLink_Clusters_API_Connector();
        $result = $connector->generate_topic_cluster($topic);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        // Return generation_id for progress tracking
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Check generation status
     */
    public function ajax_check_status() {
        check_ajax_referer('texolink_clusters_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'texolink-clusters'));
            return;
        }
        
        $generation_id = isset($_POST['generation_id']) ? sanitize_text_field($_POST['generation_id']) : '';
        
        if (empty($generation_id)) {
            wp_send_json_error(__('Generation ID is required', 'texolink-clusters'));
            return;
        }
        
        // Check status via connector
        $connector = new TexoLink_Clusters_API_Connector();
        $result = $connector->check_status($generation_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Get results
     */
    public function ajax_get_results() {
        check_ajax_referer('texolink_clusters_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'texolink-clusters'));
            return;
        }
        
        $generation_id = isset($_POST['generation_id']) ? sanitize_text_field($_POST['generation_id']) : '';
        
        if (empty($generation_id)) {
            wp_send_json_error(__('Generation ID is required', 'texolink-clusters'));
            return;
        }
        
        // Get results via connector
        $connector = new TexoLink_Clusters_API_Connector();
        $result = $connector->get_results($generation_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
            return;
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX: Insert cluster links
     */
    public function ajax_insert_links() {
        check_ajax_referer('texolink_clusters_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Insufficient permissions', 'texolink-clusters'));
            return;
        }
        
        $suggestions = isset($_POST['suggestions']) ? json_decode(stripslashes($_POST['suggestions']), true) : array();
        
        if (empty($suggestions)) {
            wp_send_json_error(__('No suggestions provided', 'texolink-clusters'));
            return;
        }
        
        $inserted_count = 0;
        $errors = array();
        
        foreach ($suggestions as $suggestion) {
            $source_id = intval($suggestion['source_wordpress_id']);
            $target_url = esc_url($suggestion['target_url']);
            $anchor_text = sanitize_text_field($suggestion['primary_anchor']);
            
            // Get post
            $post = get_post($source_id);
            if (!$post) {
                $errors[] = sprintf(__('Post %d not found', 'texolink-clusters'), $source_id);
                continue;
            }
            
            // Check if anchor text exists in content
            if (stripos($post->post_content, $anchor_text) === false) {
                continue; // Skip if anchor text not found
            }
            
            // Replace first occurrence with link
            $content = $post->post_content;
            $link_html = '<a href="' . $target_url . '">' . $anchor_text . '</a>';
            
            // Use case-insensitive replacement
            $pattern = '/\b' . preg_quote($anchor_text, '/') . '\b/i';
            $content = preg_replace($pattern, $link_html, $content, 1);
            
            // Update post
            $result = wp_update_post(array(
                'ID' => $source_id,
                'post_content' => $content
            ));
            
            if ($result) {
                $inserted_count++;
            } else {
                $errors[] = sprintf(__('Failed to update post %d', 'texolink-clusters'), $source_id);
            }
        }
        
        wp_send_json_success(array(
            'inserted' => $inserted_count,
            'total' => count($suggestions),
            'errors' => $errors
        ));
    }
}

// Initialize admin
new TexoLink_Clusters_Admin();
