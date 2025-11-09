# TexoLink Topic Clusters v2.0

AI-powered topic cluster building for WordPress, integrated with TexoLink backend.

## Overview

This is a reconfigured version of the Topic Clusters plugin that works seamlessly with TexoLink's architecture and Railway backend. It requires TexoLink to be installed and active.

## Key Changes from v1.3

### Architecture
- **Requires TexoLink**: Won't activate without TexoLink plugin
- **Shared Authentication**: Uses TexoLink's site_key for API authentication
- **Same Backend**: Uses TexoLink's Railway backend URL
- **Async Processing**: Implements async generation with progress tracking
- **Multi-tenant**: Integrates with TexoLink's multi-tenant architecture

### Technical Improvements

#### 1. Dependency Management
- Checks for TexoLink plugin on activation
- Shows admin notice if TexoLink is missing/inactive
- Uses TexoLink's configuration (no separate settings needed)

#### 2. API Integration
- Uses TexoLink's `site_key` for authentication
- Uses TexoLink's `api_url` setting
- Three new endpoints:
  - `/generate_topic_cluster` - Start async generation
  - `/topic_cluster_status` - Check progress
  - `/topic_cluster_results` - Get final results

#### 3. Async Processing with Progress Tracking
- Non-blocking generation process
- Real-time progress updates
- Status phases:
  - `pending` - Queued for processing
  - `processing` - Initial processing
  - `finding_posts` - Finding related posts
  - `analyzing_keywords` - Extracting keywords
  - `calculating_similarity` - Computing semantic similarity
  - `generating_suggestions` - Creating link suggestions
  - `complete` - Finished
  - `error` - Failed

#### 4. User Interface
- Progress bar with percentage
- Status message updates
- Smooth transitions between states
- Polls backend every 2 seconds for updates

### Files Changed

#### Main Plugin File
- Added TexoLink dependency check
- Removed standalone settings
- Uses TexoLink's helper functions

#### API Connector
- Three new methods for async workflow
- Uses site_key authentication
- Improved error logging

#### Admin Page
- New AJAX handlers for async flow
- Progress tracking UI
- Integrated under TexoLink menu

#### JavaScript
- Async generation workflow
- Status polling with automatic cleanup
- Progress bar animations
- Better error handling

### Backend Requirements

The Railway backend needs three new endpoints:

#### 1. POST /generate_topic_cluster
```json
Request:
{
  "site_key": "xxx",
  "topic": "page speed"
}

Response:
{
  "generation_id": "uuid",
  "status": "pending",
  "progress": 0
}
```

#### 2. POST /topic_cluster_status
```json
Request:
{
  "site_key": "xxx",
  "generation_id": "uuid"
}

Response:
{
  "status": "processing",
  "progress": 45,
  "message": "Analyzing keywords..."
}
```

#### 3. POST /topic_cluster_results
```json
Request:
{
  "site_key": "xxx",
  "generation_id": "uuid"
}

Response:
{
  "topic": "page speed",
  "total_posts": 15,
  "posts": [...],
  "suggestions": [...],
  "cluster_analysis": {...},
  "cluster_strength_stars": 4,
  "cluster_strength_label": "Strong"
}
```

## Installation

1. Ensure TexoLink plugin is installed and active
2. Upload this plugin to `/wp-content/plugins/`
3. Activate through WordPress admin
4. Access via TexoLink menu → Topic Clusters

## Benefits

### For Users
- Seamless integration with TexoLink
- No duplicate settings to manage
- Consistent experience across both tools
- Single authentication system

### For Development
- Shared codebase and patterns
- Centralized backend logic
- Easier maintenance
- Better scalability

## Future Plans

This will eventually be merged into the main TexoLink plugin as an integrated feature.

## Technical Notes

### Multi-tenancy
- Uses same site_key system as TexoLink
- All data scoped to user's site
- No data leakage between sites

### Performance
- Async processing prevents timeouts
- Progress tracking provides transparency
- Efficient polling with automatic cleanup

### Error Handling
- Comprehensive error logging
- User-friendly error messages
- Graceful degradation

## Version History

- **v2.0.0** - Complete rewrite for TexoLink integration
- **v1.3.0** - Original standalone version
