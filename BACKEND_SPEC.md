# TexoLink Backend - Topic Clusters Integration

## Overview

This document specifies the backend changes needed to support Topic Clusters functionality in the TexoLink Railway backend.

## Architecture

Topic Clusters follows the same async pattern as link suggestion generation:

1. **Generate** - Start processing (returns generation_id)
2. **Poll Status** - Check progress repeatedly
3. **Get Results** - Retrieve final data when complete

## Database Schema

### New Table: topic_cluster_generations

```sql
CREATE TABLE topic_cluster_generations (
    id SERIAL PRIMARY KEY,
    generation_id UUID UNIQUE NOT NULL DEFAULT uuid_generate_v4(),
    site_key VARCHAR(255) NOT NULL,
    topic VARCHAR(500) NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    progress INTEGER DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP,
    
    -- Results (stored as JSON)
    results JSONB,
    
    -- Indexes
    INDEX idx_generation_id (generation_id),
    INDEX idx_site_key (site_key),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

## API Endpoints

### 1. POST /generate_topic_cluster

Start topic cluster generation process.

**Request:**
```json
{
  "site_key": "xxx-xxx-xxx",
  "topic": "page speed optimization"
}
```

**Response (200 OK):**
```json
{
  "generation_id": "550e8400-e29b-41d4-a716-446655440000",
  "status": "pending",
  "progress": 0
}
```

**Errors:**
- 400: Missing site_key or topic
- 404: Site not found
- 500: Server error

**Implementation Steps:**
1. Validate site_key exists
2. Create generation record with status='pending'
3. Queue async processing task
4. Return generation_id immediately

---

### 2. POST /topic_cluster_status

Check generation status and progress.

**Request:**
```json
{
  "site_key": "xxx-xxx-xxx",
  "generation_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response (200 OK):**
```json
{
  "status": "processing",
  "progress": 65,
  "message": "Calculating semantic similarity..."
}
```

**Status Values:**
- `pending` - Queued, not started
- `processing` - General processing
- `finding_posts` - Finding related posts
- `analyzing_keywords` - Extracting keywords
- `calculating_similarity` - Computing similarity
- `generating_suggestions` - Creating suggestions
- `complete` - Finished successfully
- `error` - Failed (check error field)

**Response When Complete:**
```json
{
  "status": "complete",
  "progress": 100,
  "message": "Analysis complete!"
}
```

**Response On Error:**
```json
{
  "status": "error",
  "error": "No posts found for this topic"
}
```

**Errors:**
- 400: Missing parameters
- 404: Generation not found
- 500: Server error

---

### 3. POST /topic_cluster_results

Retrieve final cluster results.

**Request:**
```json
{
  "site_key": "xxx-xxx-xxx",
  "generation_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response (200 OK):**
```json
{
  "topic": "page speed",
  "total_posts": 15,
  "total_opportunities": 42,
  
  "posts": [
    {
      "wordpress_id": 123,
      "title": "Improving Page Speed with CDN",
      "url": "https://site.com/post",
      "relevance_score": 0.95,
      "word_count": 1500
    }
  ],
  
  "suggestions": [
    {
      "source_wordpress_id": 123,
      "target_wordpress_id": 456,
      "target_url": "https://site.com/other-post",
      "primary_anchor": "image optimization",
      "relevance_score": 0.88
    }
  ],
  
  "cluster_analysis": {
    "num_posts": 15,
    "existing_links": 8,
    "link_density_percent": 12,
    "opportunities": 42
  },
  
  "cluster_strength_stars": 4,
  "cluster_strength_label": "Strong"
}
```

**Errors:**
- 400: Missing parameters
- 404: Generation not found or not complete
- 500: Server error

---

## Processing Logic

### Topic Cluster Generation Algorithm

```python
def generate_topic_cluster(site_key, topic, generation_id):
    """
    Generate topic cluster with progress tracking
    """
    try:
        # Update status
        update_status(generation_id, 'finding_posts', 20)
        
        # 1. Find related posts using semantic search
        posts = find_posts_by_topic(site_key, topic)
        
        if not posts:
            set_error(generation_id, "No posts found for this topic")
            return
        
        update_status(generation_id, 'analyzing_keywords', 40)
        
        # 2. Extract keywords from each post
        for post in posts:
            post['keywords'] = extract_keywords(post['content'])
            post['embedding'] = get_embedding(post['content'])
        
        update_status(generation_id, 'calculating_similarity', 60)
        
        # 3. Calculate similarity between all posts
        similarity_matrix = calculate_post_similarities(posts)
        
        update_status(generation_id, 'generating_suggestions', 80)
        
        # 4. Generate link suggestions
        suggestions = generate_link_suggestions(posts, similarity_matrix)
        
        # 5. Calculate cluster metrics
        cluster_analysis = calculate_cluster_metrics(posts, suggestions)
        
        # 6. Calculate cluster strength
        strength = calculate_cluster_strength(cluster_analysis)
        
        # 7. Store results
        results = {
            'topic': topic,
            'total_posts': len(posts),
            'total_opportunities': len(suggestions),
            'posts': posts,
            'suggestions': suggestions,
            'cluster_analysis': cluster_analysis,
            'cluster_strength_stars': strength['stars'],
            'cluster_strength_label': strength['label']
        }
        
        save_results(generation_id, results)
        update_status(generation_id, 'complete', 100)
        
    except Exception as e:
        log_error(e)
        set_error(generation_id, str(e))
```

### Key Functions

#### find_posts_by_topic()
Uses semantic search to find posts related to the topic:
1. Generate embedding for topic
2. Query posts table with vector similarity
3. Also check for keyword matches
4. Combine and deduplicate results
5. Return top 50 most relevant posts

#### calculate_post_similarities()
Compute similarity between all post pairs:
1. Use cosine similarity on embeddings
2. Boost similarity if shared keywords
3. Create NxN matrix of similarity scores

#### generate_link_suggestions()
Create link opportunities:
1. For each post pair with high similarity (>0.6)
2. Find good anchor text in source post
3. Create suggestion if anchor text exists
4. Rank by relevance score

#### calculate_cluster_metrics()
Analyze cluster quality:
```python
{
    'num_posts': len(posts),
    'existing_links': count_existing_links(posts),
    'link_density_percent': (existing / potential) * 100,
    'opportunities': len(new_suggestions)
}
```

#### calculate_cluster_strength()
Determine cluster strength:
```python
def calculate_cluster_strength(analysis):
    density = analysis['link_density_percent']
    num_posts = analysis['num_posts']
    
    # Calculate score
    score = 0
    if num_posts >= 10: score += 1
    if num_posts >= 20: score += 1
    if density >= 10: score += 1
    if density >= 25: score += 1
    if density >= 50: score += 1
    
    labels = ['Weak', 'Fair', 'Good', 'Strong', 'Excellent']
    
    return {
        'stars': min(score, 5),
        'label': labels[min(score, 4)]
    }
```

## Background Task Processing

Use the same async task queue as link generation:

```python
@app.route('/generate_topic_cluster', methods=['POST'])
def start_generation():
    data = request.json
    site_key = data['site_key']
    topic = data['topic']
    
    # Create generation record
    gen_id = create_generation_record(site_key, topic)
    
    # Queue async task
    task_queue.enqueue(
        generate_topic_cluster,
        site_key=site_key,
        topic=topic,
        generation_id=gen_id
    )
    
    return jsonify({
        'generation_id': gen_id,
        'status': 'pending',
        'progress': 0
    })
```

## Performance Considerations

1. **Caching**: Cache embeddings for posts
2. **Limits**: Max 50 posts per cluster
3. **Timeout**: 5 minute processing limit
4. **Cleanup**: Delete old generations after 24 hours
5. **Rate Limiting**: Max 5 concurrent generations per site

## Testing

Test endpoints:
```bash
# Start generation
curl -X POST http://localhost:5000/generate_topic_cluster \
  -H "Content-Type: application/json" \
  -d '{"site_key":"test-key","topic":"SEO"}'

# Check status
curl -X POST http://localhost:5000/topic_cluster_status \
  -H "Content-Type: application/json" \
  -d '{"site_key":"test-key","generation_id":"xxx"}'

# Get results
curl -X POST http://localhost:5000/topic_cluster_results \
  -H "Content-Type: application/json" \
  -d '{"site_key":"test-key","generation_id":"xxx"}'
```

## Migration Path

1. Add database table
2. Implement endpoints
3. Test with sample site
4. Deploy to Railway
5. Update WordPress plugin

## Security

- Validate site_key on every request
- Sanitize topic input (prevent injection)
- Rate limit to prevent abuse
- Clean up old generation data
- Log all access attempts
