# Changelog

All notable changes to TexoLink Topic Clusters will be documented in this file.

## [2.0.0] - 2024-11-08

### Major Changes
- **Complete rewrite for TexoLink integration**
- Requires TexoLink plugin as dependency
- Uses TexoLink's Railway backend and authentication
- Async processing with real-time progress tracking

### Added
- Dependency check on TexoLink plugin
- Async generation workflow (generate → poll → results)
- Real-time progress bar with status messages
- Integration with TexoLink admin menu
- Site key authentication from TexoLink
- Three new API endpoints for async processing
- Comprehensive error logging
- Auto-cleanup of polling intervals

### Changed
- **Breaking:** Now requires TexoLink plugin to be active
- **Breaking:** Uses TexoLink's API URL (no separate configuration)
- **Breaking:** Uses TexoLink's site_key for authentication
- Moved from standalone plugin to TexoLink companion
- Menu placement: Now under TexoLink menu
- Processing: Changed from synchronous to asynchronous
- Settings: Removed (uses TexoLink's settings instead)

### Removed
- Standalone API configuration settings
- Standalone settings page
- Synchronous search endpoint
- Independent menu item

### Technical Improvements
- Better error handling and logging
- Progress tracking with multiple status phases
- Non-blocking async processing
- Proper cleanup on page navigation
- Improved user feedback during generation
- Multi-tenant data isolation via site_key

### Backend Requirements
- New database table: `topic_cluster_generations`
- Three new endpoints:
  - POST `/generate_topic_cluster`
  - POST `/topic_cluster_status` 
  - POST `/topic_cluster_results`
- Background task processing
- Progress tracking system

### Documentation
- Complete README with architecture overview
- Backend specification document
- Installation guide
- This changelog

### Security
- Uses TexoLink's proven authentication
- Site-key validation on all requests
- Proper data isolation between sites
- Input sanitization and validation

## [1.3.0] - 2024-11-01 (Original Version)

### Initial Features
- Standalone WordPress plugin
- Semantic search for topic clusters
- Link suggestion generation
- Bulk link insertion
- Cluster strength analysis
- Visual results interface
- Direct API communication
- Independent settings page

### Architecture (v1.3)
- Synchronous processing
- Direct API calls from WordPress
- Standalone configuration
- Independent authentication
- Single request/response cycle

---

## Migration from v1.3 to v2.0

### For Users
1. Install TexoLink plugin first
2. Configure TexoLink with site_key
3. Update Topic Clusters plugin
4. Settings automatically inherited

### For Developers
1. Update backend with new endpoints
2. Run database migration
3. Deploy async processing system
4. Test with staging site first

### Breaking Changes Summary
| Feature | v1.3 | v2.0 |
|---------|------|------|
| Requires TexoLink | ❌ No | ✅ Yes |
| Configuration | Own settings | Inherited from TexoLink |
| Processing | Sync | Async with progress |
| Authentication | API URL only | site_key from TexoLink |
| Menu Location | Top level | Under TexoLink |
| Independence | Standalone | Companion plugin |

## Future Plans

### v2.1 (Planned)
- [ ] Cached results for repeat searches
- [ ] Export cluster data to CSV
- [ ] Visual cluster graph
- [ ] Advanced filtering options

### v3.0 (Future)
- [ ] Merge into main TexoLink plugin
- [ ] Become core feature, not separate plugin
- [ ] Unified UI experience
- [ ] Shared data models

### Long-term Vision
- Integration into TexoLink dashboard
- Real-time cluster strength monitoring
- Automated cluster building
- AI-powered cluster recommendations
- Historical cluster analytics
