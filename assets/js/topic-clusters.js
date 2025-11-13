/**
 * Topic Clusters JavaScript
 * Handles async generation, progress tracking, display, and bulk insertion
 * File: assets/js/topic-clusters.js
 */

(function($, window) {
    'use strict';
    
    // Create isolated namespace
    window.TexoLinkTopicClusters = window.TexoLinkTopicClusters || {};
    
    let currentTopic = '';
    let currentResults = null;
    let generationId = null;
    let statusCheckInterval = null;
    
    const $topicInput = $('#cluster-topic-input');
    const $clusterSizeSelect = $('#cluster-size-select');
    const $searchBtn = $('#search-cluster-btn');
    const $loadingDiv = $('#cluster-loading');
    const $resultsDiv = $('#cluster-results');
    const $emptyDiv = $('#cluster-empty');

    // Search button click
    $searchBtn.on('click', function() {
        const topic = $topicInput.val().trim();
        if (topic) {
            const clusterSize = parseInt($clusterSizeSelect.val()) || 20;
            startGeneration(topic, clusterSize);
        }
    });
    
    // Enter key
    $topicInput.on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $searchBtn.click();
        }
    });
    
    // Example searches
    $('.example-search').on('click', function() {
        const topic = $(this).data('topic');
        $topicInput.val(topic);
        $searchBtn.click();
    });
    
    /**
     * Start generation process
     */
    function startGeneration(topic, clusterSize) {
        console.log('🚀 Starting generation for topic:', topic, 'with cluster size:', clusterSize);

        currentTopic = topic;
        generationId = null;

        // Show loading state
        showLoading('Initializing search...', 0);
        $resultsDiv.hide();
        $emptyDiv.hide();

        // Disable search button
        $searchBtn.prop('disabled', true);

        // Start generation
        $.ajax({
            url: texolinkClusters.ajaxUrl,
            method: 'POST',
            data: {
                action: 'texolink_clusters_generate',
                nonce: texolinkClusters.nonce,
                topic: topic,
                cluster_size: clusterSize
            },
            timeout: 120000,  // 2 minutes to match backend
            success: function(response) {
                console.log('✅ Generation started:', response);
                
                if (response.success && response.data.generation_id) {
                    generationId = response.data.generation_id;
                    
                    // Update status
                    const status = response.data.status || 'pending';
                    const progress = response.data.progress || 0;
                    updateProgressDisplay(status, progress);
                    
                    // Start polling
                    startStatusPolling();
                } else {
                    showError(response.data || 'Failed to start generation');
                    $searchBtn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Generation error:', error);
                showError('Failed to start generation. Please try again.');
                $searchBtn.prop('disabled', false);
            }
        });
    }
    
    /**
     * Start polling for status updates
     */
    function startStatusPolling() {
        // Clear any existing interval
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }
        
        // Poll every 2 seconds
        statusCheckInterval = setInterval(checkStatus, texolinkClusters.pollInterval);
        
        // Also check immediately
        checkStatus();
    }
    
    /**
     * Check generation status
     */
    function checkStatus() {
        if (!generationId) {
            console.error('No generation ID');
            stopPolling();
            return;
        }
        
        $.ajax({
            url: texolinkClusters.ajaxUrl,
            method: 'POST',
            data: {
                action: 'texolink_clusters_check_status',
                nonce: texolinkClusters.nonce,
                generation_id: generationId
            },
            timeout: 10000,
            success: function(response) {
                if (!response.success) {
                    console.error('Status check failed:', response.data);
                    stopPolling();
                    showError(response.data || 'Failed to check status');
                    return;
                }
                
                const status = response.data.status;
                const progress = response.data.progress || 0;
                
                console.log('📊 Status:', status, 'Progress:', progress + '%');
                
                updateProgressDisplay(status, progress);
                
                // Check if complete
                if (status === 'complete') {
                    stopPolling();
                    fetchResults();
                } else if (status === 'error') {
                    stopPolling();
                    const errorMsg = response.data.error || 'Generation failed';
                    showError(errorMsg);
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Status check error:', error);
                // Don't stop polling on transient errors
            }
        });
    }
    
    /**
     * Stop polling
     */
    function stopPolling() {
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
            statusCheckInterval = null;
        }
        $searchBtn.prop('disabled', false);
    }
    
    /**
     * Update progress display
     */
    function updateProgressDisplay(status, progress) {
        const statusMessages = {
            'pending': 'Queued for processing...',
            'processing': 'Analyzing your content...',
            'finding_posts': 'Finding related posts...',
            'analyzing_keywords': 'Extracting keywords and topics...',
            'calculating_similarity': 'Calculating semantic similarity...',
            'generating_suggestions': 'Creating link suggestions...',
            'complete': 'Analysis complete!',
            'error': 'An error occurred'
        };
        
        const message = statusMessages[status] || 'Processing...';
        
        $('.loading-detail').text(message);
        $('.progress-fill').css('width', progress + '%');
        $('.progress-text').text(Math.round(progress) + '%');
    }
    
    /**
     * Fetch final results
     */
    function fetchResults() {
        console.log('📥 Fetching results...');
        
        updateProgressDisplay('complete', 100);
        
        $.ajax({
            url: texolinkClusters.ajaxUrl,
            method: 'POST',
            data: {
                action: 'texolink_clusters_get_results',
                nonce: texolinkClusters.nonce,
                generation_id: generationId
            },
            timeout: 30000,
            success: function(response) {
                console.log('✅ Results received:', response);
                
                if (response.success) {
                    currentResults = response.data;
                    
                    // Check if we have posts
                    if (response.data.posts && response.data.posts.length > 0) {
                        displayResults(response.data);
                    } else {
                        showEmptyState(currentTopic);
                    }
                } else {
                    showError(response.data || 'Failed to fetch results');
                }
            },
            error: function(xhr, status, error) {
                console.error('❌ Results fetch error:', error);
                showError('Failed to fetch results. Please try again.');
            },
            complete: function() {
                $loadingDiv.fadeOut(200);
            }
        });
    }
    
    /**
     * Show loading state
     */
    function showLoading(message, progress) {
        $('.loading-detail').text(message);
        $('.progress-fill').css('width', progress + '%');
        $('.progress-text').text(Math.round(progress) + '%');
        $loadingDiv.fadeIn(200);
    }
    
    /**
     * Display search results
     */
    function displayResults(data) {
        const $template = $('#cluster-results-template').contents().clone();
        
        $template.find('.post-count').text(data.total_posts);
        $template.find('.topic-name').text(data.topic);
        $template.find('.opportunities-count').text('(' + data.total_opportunities + ')');
        
        displayClusterStrength(
            $template.find('.stars'),
            $template.find('.strength-text'),
            data.cluster_strength_stars,
            data.cluster_strength_label
        );
        
        $template.find('#stat-posts').text(data.cluster_analysis.num_posts);
        $template.find('#stat-links').text(data.cluster_analysis.existing_links);
        $template.find('#stat-density').text(data.cluster_analysis.link_density_percent + '%');
        $template.find('#stat-opportunities').text(data.cluster_analysis.opportunities);
        
        // Build suggestions table
        const $postsList = $template.find('.posts-list');
        $postsList.empty();
        
        if (data.suggestions && data.suggestions.length > 0) {
            const $table = buildSuggestionsTable(data.suggestions, data.posts);
            $postsList.append($table);
        } else {
            $postsList.append('<p class="no-suggestions">No link opportunities found.</p>');
        }
        
        $resultsDiv.empty().append($template).fadeIn(300);
        attachResultsHandlers();
    }
    
    /**
     * Build suggestions table
     */
    function buildSuggestionsTable(suggestions, posts) {
        // Create post lookup map
        const postMap = {};
        posts.forEach(function(post) {
            postMap[post.wordpress_id] = post;
        });
        
        const $table = $('<table>').addClass('suggestions-table');
        
        // Table header
        const $thead = $('<thead>').append(
            $('<tr>')
                .append($('<th>').text('Source Post'))
                .append($('<th>').text('Suggested Link To'))
                .append($('<th>').text('Similarity'))
                .append($('<th>').text('Suggested Anchor'))
                .append($('<th>').text('Actions'))
        );
        $table.append($thead);
        
        // Table body
        const $tbody = $('<tbody>');
        
        suggestions.forEach(function(suggestion) {
            const sourcePost = postMap[suggestion.source_wordpress_id];
            const targetPost = postMap[suggestion.target_wordpress_id];
            
            if (!sourcePost) return;
            
            const $row = $('<tr>').attr('data-suggestion-id', suggestion.source_wordpress_id + '-' + suggestion.target_wordpress_id);
            
            // Source post
            $row.append(
                $('<td>').addClass('source-post').text(sourcePost.title || 'Untitled')
            );
            
            // Target post
            $row.append(
                $('<td>').addClass('target-post').text(targetPost ? targetPost.title : 'Unknown')
            );
            
            // Similarity score
            const similarityPercent = Math.round(suggestion.relevance_score * 100);
            const similarityClass = similarityPercent >= 70 ? 'high' : (similarityPercent >= 50 ? 'medium' : 'low');
            $row.append(
                $('<td>').addClass('similarity-score')
                    .append($('<span>').addClass('score-badge ' + similarityClass).text(similarityPercent + '%'))
            );
            
            // Suggested anchor
            $row.append(
                $('<td>').addClass('suggested-anchor').text(suggestion.primary_anchor || suggestion.anchor_text)
            );
            
            // Actions
            const $insertBtn = $('<button>')
                .addClass('button button-primary button-small insert-single-link')
                .text('Insert Link')
                .data('suggestion', suggestion);
            
            $row.append($('<td>').addClass('actions').append($insertBtn));
            
            $tbody.append($row);
        });
        
        $table.append($tbody);
        return $table;
    }
    
    /**
     * Display cluster strength
     */
    function displayClusterStrength($starsDiv, $textSpan, stars, label) {
        $starsDiv.empty();
        for (let i = 0; i < 5; i++) {
            const $star = $('<span>').addClass('star');
            if (i < stars) {
                $star.addClass('filled').html('⭐');
            } else {
                $star.addClass('empty').html('☆');
            }
            $starsDiv.append($star);
        }
        $textSpan.text(label).removeClass('weak fair good strong excellent')
               .addClass(label.toLowerCase());
    }
    
    /**
     * Attach event handlers
     */
    function attachResultsHandlers() {
        // Bulk insert handler
        $('#insert-all-btn').on('click', function() {
            if (currentResults && currentResults.suggestions) {
                insertClusterLinks(currentResults.suggestions);
            }
        });
        
        // Individual insert handler
        $(document).on('click', '.insert-single-link', function() {
            const suggestion = $(this).data('suggestion');
            if (suggestion) {
                insertSingleLink(suggestion, $(this));
            }
        });
    }
    
    /**
     * Insert all cluster links
     */
    function insertClusterLinks(suggestions) {
        if (!suggestions || suggestions.length === 0) {
            alert('No link suggestions to insert');
            return;
        }
        
        const confirmMsg = `Insert ${suggestions.length} links across your cluster posts?\n\n` +
                          `This will strengthen your "${currentTopic}" topic cluster.`;
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        const $btn = $('#insert-all-btn');
        const originalText = $btn.html();
        $btn.prop('disabled', true).html(
            '<span class="dashicons dashicons-update spin"></span> Inserting...'
        );
        
        $.ajax({
            url: texolinkClusters.ajaxUrl,
            method: 'POST',
            data: {
                action: 'texolink_clusters_insert_links',
                nonce: texolinkClusters.nonce,
                suggestions: JSON.stringify(suggestions)
            },
            success: function(response) {
                if (response.success) {
                    const inserted = response.data.inserted;
                    const total = response.data.total;
                    alert(`Success! Inserted ${inserted} of ${total} links.\n\n` +
                          `Your "${currentTopic}" cluster is now stronger!`);
                    
                    // Re-generate to get updated results
                    startGeneration(currentTopic);
                } else {
                    alert('Error: ' + (response.data || 'Failed to insert links'));
                }
            },
            error: function() {
                alert('Failed to insert links. Please try again.');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    }
    
    /**
     * Insert a single link
     */
    function insertSingleLink(suggestion, $button) {
        if (!suggestion) {
            alert('Invalid suggestion');
            return;
        }
        
        const originalText = $button.html();
        $button.prop('disabled', true).html(
            '<span class="dashicons dashicons-update spin"></span> Inserting...'
        );
        
        $.ajax({
            url: texolinkClusters.ajaxUrl,
            method: 'POST',
            data: {
                action: 'texolink_clusters_insert_links',
                nonce: texolinkClusters.nonce,
                suggestions: JSON.stringify([suggestion])
            },
            success: function(response) {
                if (response.success) {
                    // Mark as inserted
                    $button.closest('tr').addClass('link-inserted');
                    $button.replaceWith(
                        $('<span>').addClass('inserted-badge').html('✓ Inserted')
                    );
                    
                    // Show success message
                    showNotification('Link inserted successfully!', 'success');
                    
                    // Update count
                    const remaining = $('.insert-single-link').length;
                    $('#insert-all-btn .opportunities-count').text('(' + remaining + ')');
                } else {
                    alert('Error: ' + (response.data || 'Failed to insert link'));
                    $button.prop('disabled', false).html(originalText);
                }
            },
            error: function() {
                alert('Failed to insert link. Please try again.');
                $button.prop('disabled', false).html(originalText);
            }
        });
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        const $notification = $('<div>')
            .addClass('texolink-notification notification-' + type)
            .text(message)
            .appendTo('body');
        
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * Show empty state
     */
    function showEmptyState(topic) {
        $('#empty-message').text(`No posts found related to "${topic}".`);
        $emptyDiv.fadeIn(300);
        stopPolling();
    }
    
    /**
     * Show error
     */
    function showError(message) {
        alert('Error: ' + message);
        $loadingDiv.fadeOut(200);
        stopPolling();
    }
    
    // Initialize on document ready
    $(document).ready(function() {
        console.log('🚀 Topic Clusters initialized');
        $topicInput.focus();
    });
    
    // Cleanup on page unload
    $(window).on('beforeunload', function() {
        stopPolling();
    });
    
})(jQuery, window);
