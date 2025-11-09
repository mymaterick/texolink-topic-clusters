<?php
/**
 * API Connector
 * 
 * Handles communication with the TexoLink Railway backend
 * Uses TexoLink's site_key authentication and async processing patterns
 * 
 * @package TexoLink_Topic_Clusters
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * API Connector class
 */
class TexoLink_Clusters_API_Connector {
    
    /**
     * API URL from TexoLink
     */
    private $api_url;
    
    /**
     * Site key from TexoLink
     */
    private $site_key;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->api_url = texolink_clusters_get_api_url();
        $this->site_key = texolink_clusters_get_site_key();
    }
    
    /**
     * Generate topic cluster (async)
     * Returns a generation_id for tracking progress
     * 
     * @param string $topic Topic to search for
     * @return array|WP_Error Response with generation_id or error
     */
    public function generate_topic_cluster($topic) {
        if (empty($this->site_key)) {
            return new WP_Error(
                'no_site_key',
                __('TexoLink is not configured. Please configure TexoLink first.', 'texolink-clusters')
            );
        }
        
        $endpoint = trailingslashit($this->api_url) . 'generate_topic_cluster';
        
        error_log('TexoLink Clusters - Generate endpoint: ' . $endpoint);
        error_log('TexoLink Clusters - Topic: ' . $topic);
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'site_key' => $this->site_key,
                'topic' => $topic
            ))
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Check generation status
     * 
     * @param string $generation_id Generation ID to check
     * @return array|WP_Error Response with status or error
     */
    public function check_status($generation_id) {
        if (empty($this->site_key)) {
            return new WP_Error(
                'no_site_key',
                __('TexoLink is not configured.', 'texolink-clusters')
            );
        }
        
        $endpoint = trailingslashit($this->api_url) . 'topic_cluster_status';
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'site_key' => $this->site_key,
                'generation_id' => $generation_id
            ))
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Get topic cluster results
     * 
     * @param string $generation_id Generation ID to retrieve results for
     * @return array|WP_Error Response with results or error
     */
    public function get_results($generation_id) {
        if (empty($this->site_key)) {
            return new WP_Error(
                'no_site_key',
                __('TexoLink is not configured.', 'texolink-clusters')
            );
        }
        
        $endpoint = trailingslashit($this->api_url) . 'topic_cluster_results';
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => 30,
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'site_key' => $this->site_key,
                'generation_id' => $generation_id
            ))
        ));
        
        return $this->handle_response($response);
    }
    
    /**
     * Handle API response
     * 
     * @param array|WP_Error $response Response from wp_remote_post
     * @return array|WP_Error Parsed response or error
     */
    private function handle_response($response) {
        if (is_wp_error($response)) {
            error_log('TexoLink Clusters - WP Error: ' . $response->get_error_message());
            return new WP_Error(
                'api_error',
                __('Failed to connect to API: ', 'texolink-clusters') . $response->get_error_message()
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        error_log('TexoLink Clusters - Status code: ' . $status_code);
        error_log('TexoLink Clusters - Response body: ' . substr($body, 0, 500));
        
        if ($status_code !== 200) {
            return new WP_Error(
                'api_error',
                sprintf(__('API returned status code %d', 'texolink-clusters'), $status_code)
            );
        }
        
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('TexoLink Clusters - JSON error: ' . json_last_error_msg());
            return new WP_Error(
                'json_error',
                __('Failed to parse API response', 'texolink-clusters')
            );
        }
        
        // Handle error in response
        if (isset($data['error'])) {
            error_log('TexoLink Clusters - API error: ' . $data['error']);
            return new WP_Error('api_error', $data['error']);
        }
        
        return $data;
    }
    
    /**
     * Test API connection (uses TexoLink's health check)
     * 
     * @return bool|WP_Error True if connected, error otherwise
     */
    public function test_connection() {
        if (empty($this->site_key)) {
            return new WP_Error(
                'no_site_key',
                __('TexoLink is not configured. Please configure TexoLink first.', 'texolink-clusters')
            );
        }
        
        // Use TexoLink's health endpoint
        $endpoint = trailingslashit($this->api_url) . 'health';
        
        $response = wp_remote_get($endpoint, array(
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        
        if ($status_code === 200) {
            return true;
        }
        
        return new WP_Error(
            'connection_failed',
            sprintf(__('Connection failed with status code %d', 'texolink-clusters'), $status_code)
        );
    }
}
