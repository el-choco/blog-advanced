# 📋 TODO - Blog Verbesserungen

## 🎨 CSS / Design
- [ ] **Kommentar-Section Höhe optimieren**
  - Problem: Bei kleinen Posts ist das Kommentar-Feld zu groß
  - Lösung: CSS anpassen in `static/styles/custom1.css`
  - Datei: `static/styles/custom1.css`
  - Zeilen: Comment-Section min-height entfernen

## 🔧 PHP 8.2 Optimierungen (TEILWEISE ERLEDIGT)
- [x] NULL-Parameter Fixes in `post.class.php` (Zeilen 810-833)
- [ ] Weitere NULL-Checks in anderen Dateien
- [ ] Strict Types aktivieren (`declare(strict_types=1);`)
- [ ] Return Type Declarations hinzufügen
- [ ] Property Type Declarations (PHP 7.4+)

## 🚀 Performance
- [ ] Opcache Konfiguration optimieren
- [ ] Session-Handling optimieren
- [ ] Query-Performance analysieren
- [ ] Image-Lazy-Loading implementieren

## 🔒 Security
- [ ] CSRF-Token Rotation
- [ ] Rate-Limiting für Login
- [ ] Content Security Policy (CSP) Header
- [ ] SQL-Injection weitere Tests

## ✨ Features (OPTIONAL)
- [ ] ~~Multi-User-System~~ (VERSCHOBEN - zu komplex)
- [ ] Dark Mode Toggle
- [ ] Post-Drafts (Entwürfe)
- [ ] Scheduled Posts (Zeitgesteuert)
- [ ] RSS Feed

## 📦 Backup-System (KOMPLETT ✅)
- [x] Automatische Backups
- [x] Backup-Download
- [x] Backup-Restore
- [x] Admin-Interface

## 📧 Email-System (KOMPLETT ✅)
- [x] Email-Notifications bei Kommentaren
- [x] SMTP-Konfiguration
- [x] Test-Email-Funktion

## 💬 Comment-System (KOMPLETT ✅)
- [x] Kommentare anzeigen
- [x] Kommentare posten
- [x] Admin-Verwaltung
- [x] Email-Benachrichtigungen

---

## 🎯 PRIORITÄT für nächste Session:
1. ✅ PHP 8.2 NULL-Safety (ERLEDIGT)
2. 🎨 CSS Comment-Section Fix
3. 🔧 Weitere PHP 8.2 Type Declarations
4. 🚀 Performance-Optimierungen

---

**Letzte Aktualisierung:** 2025-11-22  
**Status:** Blog läuft stabil mit PHP 8.2.29 🎉
