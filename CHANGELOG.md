# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Planned for v2.0
- Multi-user support with roles (Super Admin, Admin, Editor, Viewer)
- Comment system with nested comments and moderation
- Inline editor with live preview
- Advanced search functionality (fulltext, filters, saved searches)
- Calendar view (posts per day with color coding)
- Export/Import (JSON, CSV, ZIP backup)
- Automated backups (scheduled backups)
- 2-Factor Authentication (2FA)
- IP whitelist & brute-force protection
- Audit log (track all changes)
- Email notifications
- Theme editor (live preview, color customization)
- PWA support (Progressive Web App)

## [1.0.0] - 2025-01-20

### Added
- ✨ Initial release - Complete rewrite of m1k1o/blog
- 📝 Multi-image upload (up to 12 images per post)
- 📎 File attachment support (PDF, DOC, XLS, ZIP, etc.)
- ✏️ Markdown & HTML editor with toolbar
- 😀 Emoji picker (44+ emojis)
- 📌 Sticky posts feature (pin important posts to top)
- 🗑️ Trash system with soft delete & restore functionality
- 📊 Admin dashboard with statistics
- 🔍 Post management (filter, search, bulk actions)
- 📁 Media manager for images & files
- ⚙️ Settings panel (blog configuration)
- 🌍 Multi-language support (10 languages: DE, EN, ES, FR, NL, SK, ZH, BS, CZ, RU)
- 📱 Responsive design (mobile-friendly)
- 🎨 Multiple themes
- 🔒 Basic security (CSRF protection, password hashing)
- 🖼️ Image gallery with lightbox
- 📅 Post scheduling (change dates)
- 👁️ Hide/show posts on timeline
- 🎯 Privacy settings (Public, Friends, Only me)

### Changed
- 🔄 Complete UI/UX redesign
- 📈 Enhanced admin panel
- 🚀 Performance improvements
- 🎨 Modern, clean design

### Security
- 🔐 Improved password hashing (bcrypt)
- 🛡️ CSRF token protection
- 🚫 XSS prevention
- 💉 SQL injection prevention

---

**Based on:** [m1k1o/blog](https://github.com/m1k1o/blog)  
**Author:** el-choco  
**License:** MIT
