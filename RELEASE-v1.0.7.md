# Release v1.0.7 - Multi-Tenant Site Isolation Fix

**Release Date:** November 13, 2025
**GitHub:** https://github.com/mymaterick/texolink-topic-clusters/releases/tag/v1.0.7

---

## 🚨 CRITICAL FIX: Multi-Tenant Site Isolation

### The Problem
Previous versions (v1.0.6 and earlier) used site domain for identification, which didn't work with the updated backend that requires proper API key authentication for multi-tenant isolation.

### The Solution
Version 1.0.7 now uses the actual API key from the main TexoLink plugin (`texolink_api_key` option) for proper authentication and site identification.

---

## 📋 Changes in v1.0.7

### Fixed
- **CRITICAL**: Now uses `get_option('texolink_api_key')` instead of site domain
- Proper multi-tenant isolation (backend filters by `site_id`)
- Topic clusters now only show posts from the requesting site
- Better error messages when API key not configured

### Technical Changes
- `api-connector.php`: Changed from `$this->site_domain` to `$this->site_key`
- Constructor now reads API key: `get_option('texolink_api_key', '')`
- All API endpoints send proper `site_key` for authentication
- Validation added to check if API key is configured

---

## 📦 Installation

### Auto-Update (Recommended)
WordPress will auto-update to v1.0.7 within 12 hours via the plugin update checker.

### Manual Update
1. Go to **Plugins → Installed Plugins**
2. Find "TexoLink Topic Clusters"
3. Click **Update Now**

---

## ✅ Requirements

- **Main TexoLink plugin** must be installed and configured
- **API key** must be configured in TexoLink settings
- **Backend API** v12c4aab or later (deployed 2025-11-13)

---

## 🧪 Testing Checklist

After updating:
- [ ] Plugin auto-updates to v1.0.7
- [ ] Verify API key is detected from main TexoLink plugin
- [ ] Test topic cluster search (should complete in < 10 seconds)
- [ ] Verify results are ONLY from current site
- [ ] Verify link opportunities are generated
- [ ] Test bulk link insertion works

---

## 🔄 Full Changelog

### v1.0.7 (2025-11-13)
**Fixed:**
- Use API key from main TexoLink plugin for authentication
- Multi-tenant site isolation now works correctly
- Better error handling for missing API key

**Technical:**
- Backend expects `site_key` as API key (not domain)
- Plugin reads from `texolink_api_key` option
- All endpoints updated to use new authentication

### v1.0.6 (Previous)
- Fixed API compatibility with backend
- Used site domain for identification (now changed in v1.0.7)

---

## 🚀 Deployment Status

- ✅ Code committed and pushed to GitHub
- ✅ Backend updated and deployed (commit 12c4aab)
- ✅ Release notes created
- ⏳ Auto-updater will notify users within 12 hours
- ⏳ Ready for manual release on GitHub

---

## 🎯 Next Steps

1. **Create GitHub Release:**
   - Go to https://github.com/mymaterick/texolink-topic-clusters/releases/new
   - Tag: `v1.0.7`
   - Title: "v1.0.7 - Multi-Tenant Site Isolation Fix"
   - Copy description from this file
   - Publish release

2. **Monitor Auto-Updates:**
   - Check WordPress sites for successful updates
   - Verify no errors in logs

3. **User Communication:**
   - Notify users of critical security fix
   - Explain importance of updating

---

**Comparison:** https://github.com/mymaterick/texolink-topic-clusters/compare/v1.0.6...v1.0.7

**Built by:** Claude Code
**Backend Commit:** 12c4aab
**Plugin Version:** 1.0.7
