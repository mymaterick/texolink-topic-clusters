# TexoLink Topic Clusters - Installation Guide

## Prerequisites

Before installing Topic Clusters, you must have:

1. **WordPress 5.8+** with PHP 7.4+
2. **TexoLink Plugin** installed and activated
3. **TexoLink Configured** with valid site_key and API URL

## Installation Steps

### Step 1: Upload Plugin

Upload the plugin to your WordPress installation:

```bash
# Via WP-CLI
wp plugin install texolink-topic-clusters.zip

# Or manually
1. Download the plugin zip file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the zip file and click "Install Now"
```

### Step 2: Activate Plugin

```bash
# Via WP-CLI
wp plugin activate texolink-topic-clusters

# Or manually
1. Go to WordPress Admin → Plugins
2. Find "TexoLink Topic Clusters"
3. Click "Activate"
```

If TexoLink is not installed/activated, you'll see an error message with instructions.

### Step 3: Verify Installation

1. Go to WordPress Admin
2. Look for **TexoLink** in the admin menu
3. You should see a **Topic Clusters** submenu item
4. Click it to access the Topic Clusters page

## Backend Setup

The Railway backend needs to be updated with Topic Clusters support.

### Database Migration

Run this SQL on your Railway database:

```sql
CREATE TABLE IF NOT EXISTS topic_cluster_generations (
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
    results JSONB,
    
    INDEX idx_generation_id (generation_id),
    INDEX idx_site_key (site_key),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at)
);
```

### Deploy Backend Code

Deploy the new endpoints to Railway:
1. `/generate_topic_cluster`
2. `/topic_cluster_status`
3. `/topic_cluster_results`

See `BACKEND_SPEC.md` for implementation details.

## Configuration

### No Additional Settings Required!

Topic Clusters uses TexoLink's configuration:
- ✅ Site Key from TexoLink
- ✅ API URL from TexoLink
- ✅ Authentication from TexoLink

Just make sure TexoLink is properly configured.

## Testing

### 1. Test Connection

1. Go to TexoLink → Settings
2. Click "Test Connection"
3. Should see success message

### 2. Test Topic Cluster

1. Go to TexoLink → Topic Clusters
2. Enter a topic (e.g., "SEO")
3. Click "Search"
4. You should see:
   - Progress bar
   - Status updates
   - Results when complete

### 3. Test Link Insertion

1. After getting results, try:
   - Click "Insert All Links" (bulk)
   - Or click "Insert Link" on individual suggestions
2. Verify links are inserted in posts

## Troubleshooting

### Plugin Won't Activate

**Error:** "This plugin requires the TexoLink plugin"

**Solution:**
1. Install TexoLink first
2. Activate TexoLink
3. Then activate Topic Clusters

### No Menu Item

**Problem:** Can't find Topic Clusters in admin menu

**Solution:**
1. Check if TexoLink menu exists
2. Topic Clusters appears as a submenu under TexoLink
3. Refresh the page after activation

### Connection Failed

**Error:** "Failed to connect to API"

**Solution:**
1. Check TexoLink settings (TexoLink → Settings)
2. Verify API URL is correct
3. Test connection in TexoLink settings
4. Check Railway backend is running

### Generation Stuck

**Problem:** Progress bar stuck at same percentage

**Solutions:**
1. Check browser console for JavaScript errors
2. Verify backend is processing (check Railway logs)
3. Wait 2-3 minutes (large sites take time)
4. Refresh page and try different topic

### No Results Found

**Problem:** "No posts found related to this topic"

**Solutions:**
1. Make sure posts have been analyzed by TexoLink
2. Try a more general topic
3. Check if posts actually contain related content
4. Verify embeddings exist in database

### Insert Link Failed

**Problem:** "Failed to insert link"

**Solutions:**
1. Check WordPress post permissions
2. Verify anchor text exists in source post
3. Make sure post is published (not draft)
4. Check for conflicting plugins

## Uninstallation

### Keep Data
Simply deactivate the plugin:
```bash
wp plugin deactivate texolink-topic-clusters
```

### Remove Everything
1. Deactivate plugin
2. Delete plugin files
3. Backend data remains (for reactivation)

### Clean Database
To remove ALL topic cluster data from backend:
```sql
DROP TABLE IF EXISTS topic_cluster_generations;
```
⚠️ Warning: This deletes all generation history!

## Updates

Plugin updates work like any WordPress plugin:

```bash
# Via WP-CLI
wp plugin update texolink-topic-clusters

# Or manually
1. Download new version
2. Deactivate old version
3. Delete old version
4. Upload new version
5. Activate
```

## Support

### Documentation
- README.md - Overview and changelog
- BACKEND_SPEC.md - Backend implementation
- This file - Installation guide

### Logs
Check these for debugging:
- WordPress debug.log (`wp-content/debug.log`)
- Railway application logs
- Browser console (F12)

### Common Issues

**Issue:** Slow performance
- Large sites with 1000+ posts may be slow
- Backend processes 50 posts max per cluster
- Consider caching strategies

**Issue:** Memory errors
- Increase PHP memory limit
- Reduce post count per cluster
- Optimize database queries

**Issue:** Permission errors
- Check user capabilities (needs 'manage_options')
- Verify file permissions
- Check database permissions

## Best Practices

1. **Start Small**: Test with small topics first
2. **Monitor Resources**: Watch memory and CPU usage
3. **Regular Updates**: Keep plugin and backend updated
4. **Backup First**: Always backup before major updates
5. **Test Staging**: Test on staging site first

## Performance Tips

1. **Caching**: Backend caches embeddings automatically
2. **Limits**: Max 50 posts per cluster keeps it fast
3. **Async**: Non-blocking processing prevents timeouts
4. **Cleanup**: Old generations auto-delete after 24 hours

## Security Notes

1. **Authentication**: Uses TexoLink's site_key system
2. **Validation**: All input sanitized and validated
3. **Permissions**: Requires 'manage_options' capability
4. **Rate Limiting**: Backend prevents abuse
5. **Data Isolation**: Multi-tenant data separation

## Next Steps

After installation:
1. ✅ Test basic functionality
2. ✅ Try different topics
3. ✅ Insert some test links
4. ✅ Monitor performance
5. ✅ Review results

Then you're ready to build topic clusters at scale!

## Questions?

Refer to:
- Main README for features
- Backend spec for technical details
- TexoLink documentation for core setup
