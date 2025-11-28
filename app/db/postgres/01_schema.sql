-- ============================================
-- Blog Advanced - PostgreSQL Schema
-- Version: 1.0
-- ============================================

-- Drop existing types if they exist
DROP TYPE IF EXISTS privacy_t CASCADE;
DROP TYPE IF EXISTS comment_status_t CASCADE;
DROP TYPE IF EXISTS user_role_t CASCADE;
DROP TYPE IF EXISTS user_status_t CASCADE;

-- Create custom types
CREATE TYPE privacy_t AS ENUM('private','friends','public');
CREATE TYPE comment_status_t AS ENUM('pending','approved','spam','trash');
CREATE TYPE user_role_t AS ENUM('super_admin','admin','editor','viewer');
CREATE TYPE user_status_t AS ENUM('active','inactive','locked');

-- ============================================
-- Table: posts
-- ============================================
CREATE TABLE IF NOT EXISTS posts (
  id SERIAL PRIMARY KEY,
  text TEXT NOT NULL,
  plain_text TEXT NOT NULL,
  feeling VARCHAR(255) NOT NULL DEFAULT '',
  persons VARCHAR(255) NOT NULL DEFAULT '',
  location VARCHAR(255) NOT NULL DEFAULT '',
  content TEXT NOT NULL DEFAULT '',
  content_type VARCHAR(50) NOT NULL DEFAULT '',
  privacy VARCHAR(20) NOT NULL DEFAULT 'public',
  status INTEGER NOT NULL DEFAULT 1,
  is_sticky BOOLEAN NOT NULL DEFAULT FALSE,
  datetime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_posts_sticky_datetime ON posts(is_sticky, datetime);
CREATE INDEX IF NOT EXISTS idx_posts_status ON posts(status);
CREATE INDEX IF NOT EXISTS idx_posts_sticky ON posts(is_sticky);

-- ============================================
-- Table: images
-- ============================================
CREATE TABLE IF NOT EXISTS images (
  id SERIAL PRIMARY KEY,
  name TEXT NOT NULL,
  path TEXT DEFAULT NULL,
  thumb TEXT DEFAULT NULL,
  type VARCHAR(10) NOT NULL,
  md5 CHAR(32) NOT NULL,
  status INTEGER NOT NULL DEFAULT 1,
  datetime TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_images_status ON images(status);

-- ============================================
-- Table: comments
-- ============================================
CREATE TABLE IF NOT EXISTS comments (
  id SERIAL PRIMARY KEY,
  post_id INTEGER NOT NULL,
  parent_id INTEGER DEFAULT NULL,
  author_name VARCHAR(100) NOT NULL,
  author_email VARCHAR(100) DEFAULT NULL,
  author_website VARCHAR(255) DEFAULT NULL,
  author_ip VARCHAR(45) DEFAULT NULL,
  content TEXT NOT NULL,
  status comment_status_t NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT NULL,
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
  id SERIAL PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  email VARCHAR(100) NOT NULL UNIQUE,
  pass VARCHAR(255) NOT NULL,
  role user_role_t DEFAULT 'viewer',
  display_name VARCHAR(100) DEFAULT NULL,
  bio TEXT DEFAULT NULL,
  avatar VARCHAR(255) DEFAULT NULL,
  status user_status_t DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT NULL,
  last_login TIMESTAMP DEFAULT NULL,
  failed_login_attempts INTEGER DEFAULT 0,
  locked_until TIMESTAMP DEFAULT NULL,
  email_verified BOOLEAN DEFAULT FALSE,
  two_factor_enabled BOOLEAN DEFAULT FALSE,
  two_factor_secret VARCHAR(255) DEFAULT NULL,
  is_active BOOLEAN DEFAULT TRUE
);

CREATE INDEX IF NOT EXISTS idx_users_role ON users(role);
CREATE INDEX IF NOT EXISTS idx_users_active ON users(is_active);
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);

-- ============================================
-- Default Admin User
-- Username: admin
-- Password: admin (CHANGE IMMEDIATELY!)
-- ============================================
INSERT INTO users (name, email, pass, role, display_name, status, is_active, email_verified) 
VALUES ('admin', 'admin@blog.local', '$2y$10$92IXUNpkjO0rOQ5byMi. Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'super_admin', 'Administrator', 'active', TRUE, TRUE)
ON CONFLICT (name) DO NOTHING;
