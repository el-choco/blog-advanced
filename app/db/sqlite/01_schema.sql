-- ============================================
-- Blog Advanced - SQLite Schema
-- Version: 1.0
-- ============================================

-- ============================================
-- Table: categories
-- ============================================
CREATE TABLE IF NOT EXISTS categories (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  slug TEXT NOT NULL UNIQUE,
  created_at TEXT NOT NULL DEFAULT (datetime('now', 'localtime'))
);

CREATE INDEX IF NOT EXISTS idx_categories_name ON categories(name);

-- ============================================
-- Table: posts
-- ============================================
CREATE TABLE IF NOT EXISTS posts (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  text TEXT NOT NULL,
  plain_text TEXT NOT NULL,
  feeling TEXT NOT NULL DEFAULT '',
  persons TEXT NOT NULL DEFAULT '',
  location TEXT NOT NULL DEFAULT '',
  content TEXT NOT NULL DEFAULT '',
  content_type TEXT NOT NULL DEFAULT '',
  privacy TEXT NOT NULL DEFAULT 'public',
  status INTEGER NOT NULL DEFAULT 1,
  is_sticky INTEGER NOT NULL DEFAULT 0,
  datetime INTEGER NOT NULL,
  category_id INTEGER DEFAULT NULL REFERENCES categories(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_posts_sticky_datetime ON posts(is_sticky, datetime);
CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status);
CREATE INDEX IF NOT EXISTS idx_posts_sticky ON posts(is_sticky);
CREATE INDEX IF NOT EXISTS idx_posts_category ON posts(category_id);

-- ============================================
-- Table: images
-- ============================================
CREATE TABLE IF NOT EXISTS images (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL,
  path TEXT DEFAULT NULL,
  thumb TEXT DEFAULT NULL,
  type TEXT NOT NULL,
  md5 TEXT NOT NULL,
  status INTEGER NOT NULL DEFAULT 1,
  datetime INTEGER NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_images_status ON images(status);

-- ============================================
-- Table: comments
-- ============================================
CREATE TABLE IF NOT EXISTS comments (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  post_id INTEGER NOT NULL,
  parent_id INTEGER DEFAULT NULL,
  author_name TEXT NOT NULL,
  author_email TEXT DEFAULT NULL,
  author_website TEXT DEFAULT NULL,
  author_ip TEXT DEFAULT NULL,
  content TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'pending',
  created_at INTEGER NOT NULL,
  updated_at INTEGER DEFAULT NULL,
  FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE
);

CREATE INDEX IF NOT EXISTS idx_comments_post_id ON comments(post_id);
CREATE INDEX IF NOT EXISTS idx_comments_parent_id ON comments(parent_id);
CREATE INDEX IF NOT EXISTS idx_comments_status ON comments(status);
CREATE INDEX IF NOT EXISTS idx_comments_post_status ON comments(post_id, status);

-- ============================================
-- Table: users
-- ============================================
CREATE TABLE IF NOT EXISTS users (
  id INTEGER PRIMARY KEY AUTOINCREMENT,
  name TEXT NOT NULL UNIQUE,
  email TEXT NOT NULL UNIQUE,
  pass TEXT NOT NULL,
  role TEXT DEFAULT 'viewer',
  display_name TEXT DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  avatar TEXT DEFAULT NULL,
  status TEXT DEFAULT 'active',
  created_at INTEGER NOT NULL,
  updated_at INTEGER DEFAULT NULL,
  last_login INTEGER DEFAULT NULL,
  failed_login_attempts INTEGER DEFAULT 0,
  locked_until INTEGER DEFAULT NULL,
  email_verified INTEGER DEFAULT 0,
  two_factor_enabled INTEGER DEFAULT 0,
  two_factor_secret TEXT DEFAULT NULL,
  is_active INTEGER DEFAULT 1
);

CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- ============================================
-- Default Admin User
-- Username: admin
-- Password: admin (CHANGE IMMEDIATELY!)
-- ============================================
INSERT OR IGNORE INTO users (name, email, pass, role, display_name, status, is_active, email_verified, created_at) 
VALUES ('admin', 'admin@blog. local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Administrator', 'active', 1, 1, strftime('%s', 'now'));
