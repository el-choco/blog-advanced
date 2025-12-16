<?php
/**
 * Theme Editor - Admin Only
 * Provides live preview, color customization, theme gallery, and custom CSS
 */
require_once 'common.php';

// Set Content Security Policy for additional XSS protection
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $config_file = PROJECT_PATH . 'config.ini';
    $config = parse_ini_file($config_file, true);
    
    // Helper to write config with validation
    $writeConfig = function($file, $config) {
        $content = '';
        foreach ($config as $key => $value) {
            // Validate section/key names to prevent INI injection
            if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $key)) {
                throw new Exception('Invalid config key: ' . $key);
            }
            
            if (is_array($value)) {
                $content .= "[$key]\n";
                foreach ($value as $k => $v) {
                    // Validate nested key names
                    if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $k)) {
                        throw new Exception('Invalid config key: ' . $k);
                    }
                    $v = (string)$v;
                    // Escape special characters and prevent INI syntax injection
                    $v = str_replace(["\\", "\"", "\r", "\n", "[", "]"], ["\\\\", "\\\"", "", "\\n", "\\[", "\\]"], $v);
                    $content .= "$k = \"$v\"\n";
                }
                $content .= "\n";
            } else {
                $v = (string)$value;
                $v = str_replace(["\\", "\"", "\r", "\n", "[", "]"], ["\\\\", "\\\"", "", "\\n", "\\[", "\\]"], $v);
                $content .= "$key = \"$v\"\n";
            }
        }
        $content = rtrim($content) . "\n";
        return file_put_contents($file, $content) !== false;
    };
    
    try {
        switch ($action) {
            case 'apply_theme':
                $theme = $_POST['theme'] ?? '';
                $theme = preg_replace('/\.css$/i', '', trim($theme));
                $theme = preg_replace('/[^a-zA-Z0-9_-]/', '', $theme);
                
                if (empty($theme)) {
                    throw new Exception('Invalid theme name');
                }
                
                // Verify theme file exists
                $theme_file = PROJECT_PATH . 'static/styles/' . $theme . '.css';
                if (!file_exists($theme_file)) {
                    throw new Exception('Theme file does not exist');
                }
                
                if (!isset($config['custom'])) {
                    $config['custom'] = [];
                }
                $config['custom']['theme'] = $theme;
                
                if ($writeConfig($config_file, $config)) {
                    echo json_encode(['success' => true, 'message' => 'Theme applied successfully']);
                } else {
                    throw new Exception('Failed to save configuration');
                }
                break;
                
            case 'save_colors':
                $colors = $_POST['colors'] ?? [];
                
                // Validate colors array
                if (!is_array($colors)) {
                    throw new Exception('Invalid colors data');
                }
                
                // Validate each color value
                $validated_colors = [];
                foreach ($colors as $var => $value) {
                    // Only allow CSS variable names starting with --
                    if (!preg_match('/^--[a-z0-9-]+$/i', $var)) {
                        continue; // Skip invalid variable names
                    }
                    // Only allow valid hex color values
                    if (preg_match('/^#[0-9a-f]{6}$/i', $value)) {
                        $validated_colors[$var] = strtolower($value);
                    }
                }
                
                if (!isset($config['theme_editor'])) {
                    $config['theme_editor'] = [];
                }
                
                // Store validated colors as JSON string
                $config['theme_editor']['custom_colors'] = json_encode($validated_colors);
                
                if ($writeConfig($config_file, $config)) {
                    echo json_encode(['success' => true, 'message' => 'Colors saved successfully']);
                } else {
                    throw new Exception('Failed to save colors');
                }
                break;
                
            case 'save_custom_css':
                $css = $_POST['css'] ?? '';
                
                // Basic validation: ensure it's valid UTF-8 and doesn't contain null bytes
                if (!mb_check_encoding($css, 'UTF-8') || strpos($css, "\0") !== false) {
                    throw new Exception('Invalid CSS content');
                }
                
                // Optional: Add length limit to prevent DoS
                if (strlen($css) > 1048576) { // 1MB limit
                    throw new Exception('CSS file too large (max 1MB)');
                }
                
                // Save to custom CSS file (fixed path prevents path traversal)
                $custom_css_file = PROJECT_PATH . 'static/styles/custom-theme.css';
                
                if (file_put_contents($custom_css_file, $css) !== false) {
                    if (!isset($config['theme_editor'])) {
                        $config['theme_editor'] = [];
                    }
                    $config['theme_editor']['custom_css_enabled'] = '1';
                    
                    if ($writeConfig($config_file, $config)) {
                        echo json_encode(['success' => true, 'message' => 'Custom CSS saved successfully']);
                    } else {
                        throw new Exception('Failed to update configuration');
                    }
                } else {
                    throw new Exception('Failed to save CSS file');
                }
                break;
                
            case 'reset_customizations':
                if (isset($config['theme_editor'])) {
                    unset($config['theme_editor']);
                }
                
                // Remove custom CSS file if exists
                $custom_css_file = PROJECT_PATH . 'static/styles/custom-theme.css';
                if (file_exists($custom_css_file)) {
                    @unlink($custom_css_file);
                }
                
                if ($writeConfig($config_file, $config)) {
                    echo json_encode(['success' => true, 'message' => 'Customizations reset successfully']);
                } else {
                    throw new Exception('Failed to reset customizations');
                }
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Load current configuration
$config = parse_ini_file(PROJECT_PATH . 'config.ini', true);
$current_theme = $config['custom']['theme'] ?? 'theme01';
$custom_colors = isset($config['theme_editor']['custom_colors']) 
    ? json_decode($config['theme_editor']['custom_colors'], true) 
    : [];
$custom_css_enabled = ($config['theme_editor']['custom_css_enabled'] ?? '0') === '1';

// Load custom CSS if exists
$custom_css = '';
$custom_css_file = PROJECT_PATH . 'static/styles/custom-theme.css';
if ($custom_css_enabled && file_exists($custom_css_file)) {
    $custom_css = file_get_contents($custom_css_file);
}

// Scan for available themes
$themes = [];
foreach (glob(PROJECT_PATH . 'static/styles/theme*.css') as $theme_file) {
    $theme_name = basename($theme_file, '.css');
    $theme_img = PROJECT_PATH . 'static/images/' . $theme_name . '/preview.png';
    
    $themes[] = [
        'name' => $theme_name,
        'display_name' => ucfirst(str_replace(['theme', '-', '_'], ['Theme ', ' ', ' '], $theme_name)),
        'has_image' => file_exists($theme_img)
    ];
}

// Define CSS variables for color customization
$css_variables = [
    '--bg' => ['label' => 'Background', 'default' => '#e9eaed'],
    '--text' => ['label' => 'Text Color', 'default' => '#1d2129'],
    '--surface' => ['label' => 'Surface', 'default' => '#ffffff'],
    '--surface-2' => ['label' => 'Surface 2', 'default' => '#f6f7f9'],
    '--border' => ['label' => 'Border', 'default' => '#d0d1d5'],
    '--muted' => ['label' => 'Muted Text', 'default' => '#90949c'],
    '--link' => ['label' => 'Links', 'default' => '#365899'],
    '--primary' => ['label' => 'Primary Color', 'default' => '#4267b2'],
];

// Helper function
function t($key, $fallback = '') {
    global $lang;
    return $lang[$key] ?? ($fallback !== '' ? $fallback : $key);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>🎨 <?php echo escape(t('Theme Editor', 'Theme Editor')); ?> - <?php echo escape(Config::get('title')); ?></title>
    <meta name="robots" content="noindex, nofollow">
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    
    <link href="../static/styles/main.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/<?php echo htmlspecialchars($current_theme, ENT_QUOTES, 'UTF-8'); ?>.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/admin.css" rel="stylesheet" type="text/css" />
    <link href="../static/styles/theme-editor.css" rel="stylesheet" type="text/css" />
</head>
<body class="admin-body">
    
    <div class="admin-header">
        <div class="admin-container">
            <h1>🎨 <?php echo escape(t('Theme Editor', 'Theme Editor')); ?></h1>
            <div class="admin-user">
                <span>👤 <?php echo escape(Config::get('name')); ?></span>
                <a href="../" class="btn btn-sm">← <?php echo escape(t('Back to Blog', 'Back to Blog')); ?></a>
            </div>
        </div>
    </div>

    <div class="admin-layout">
        <aside class="admin-sidebar">
            <nav class="admin-nav">
                <a href="index.php">📊 <?php echo escape(t('Dashboard', 'Dashboard')); ?></a>
                <a href="posts.php">📝 <?php echo escape(t('Posts', 'Posts')); ?></a>
                <a href="comments.php">💬 <?php echo escape(t('Comments', 'Comments')); ?></a>
                <a href="media.php">📁 <?php echo escape(t('Files', 'Files')); ?></a>
                <a href="backups.php">💾 <?php echo escape(t('Backups', 'Backups')); ?></a>
                <a href="trash.php">🗑️ <?php echo escape(t('Trash', 'Trash')); ?></a>
                <a href="categories.php">🏷️ <?php echo escape(t('Categories', 'Categories')); ?></a>
                <a href="settings.php">⚙️ <?php echo escape(t('Settings', 'Settings')); ?></a>
                <a href="theme.php" class="active">🎨 <?php echo escape(t('Theme Editor', 'Theme Editor')); ?></a>
            </nav>
        </aside>

        <main class="admin-content">
            <div id="theme-message" style="display:none;"></div>
            
            <div class="theme-editor-layout">
                <!-- Left Panel: Controls -->
                <div class="theme-editor-controls">
                    <div class="theme-editor-tabs">
                        <button class="theme-editor-tab active" data-tab="gallery">
                            🖼️ <?php echo escape(t('Gallery', 'Gallery')); ?>
                        </button>
                        <button class="theme-editor-tab" data-tab="colors">
                            🎨 <?php echo escape(t('Colors', 'Colors')); ?>
                        </button>
                        <button class="theme-editor-tab" data-tab="custom-css">
                            📝 <?php echo escape(t('Custom CSS', 'Custom CSS')); ?>
                        </button>
                    </div>
                    
                    <div class="theme-editor-content">
                        <!-- Theme Gallery Section -->
                        <div class="theme-editor-section active" id="section-gallery">
                            <h4 style="margin-top:0;">Select Base Theme</h4>
                            <div class="theme-gallery">
                                <?php foreach ($themes as $theme): ?>
                                    <div class="theme-card <?php echo $theme['name'] === $current_theme ? 'active' : ''; ?>" 
                                         data-theme="<?php echo escape($theme['name']); ?>">
                                        <div class="theme-preview-img">
                                            <?php if ($theme['has_image']): ?>
                                                <img src="../static/images/<?php echo escape($theme['name']); ?>/preview.png" 
                                                     alt="<?php echo escape($theme['display_name']); ?>">
                                            <?php else: ?>
                                                <span><?php echo substr($theme['display_name'], 0, 2); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="theme-card-name"><?php echo escape($theme['display_name']); ?></div>
                                        <div class="theme-card-actions">
                                            <button class="btn btn-sm btn-theme-preview" data-theme="<?php echo escape($theme['name']); ?>">
                                                👁️ <?php echo escape(t('Preview', 'Preview')); ?>
                                            </button>
                                            <button class="btn btn-sm btn-primary btn-theme-apply" data-theme="<?php echo escape($theme['name']); ?>">
                                                ✓ <?php echo escape(t('Apply', 'Apply')); ?>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Color Customization Section -->
                        <div class="theme-editor-section" id="section-colors">
                            <h4 style="margin-top:0;">Customize Colors</h4>
                            <p style="color:#666;font-size:13px;margin-bottom:20px;">
                                Adjust theme colors using CSS variables. Changes apply to the preview in real-time.
                            </p>
                            <div class="color-controls">
                                <?php foreach ($css_variables as $var => $info): ?>
                                    <div class="color-control">
                                        <label><?php echo escape($info['label']); ?></label>
                                        <input type="color" 
                                               class="color-picker" 
                                               data-variable="<?php echo escape($var); ?>"
                                               value="<?php echo escape($custom_colors[$var] ?? $info['default']); ?>">
                                        <input type="text" 
                                               class="color-value" 
                                               data-variable="<?php echo escape($var); ?>"
                                               value="<?php echo escape($custom_colors[$var] ?? $info['default']); ?>"
                                               placeholder="#000000">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <div class="theme-actions">
                                <button class="btn-theme-action btn-theme-secondary" id="reset-colors">
                                    🔄 <?php echo escape(t('Reset', 'Reset')); ?>
                                </button>
                                <button class="btn-theme-action btn-theme-primary" id="save-colors">
                                    💾 <?php echo escape(t('Save', 'Save')); ?>
                                </button>
                            </div>
                        </div>
                        
                        <!-- Custom CSS Section -->
                        <div class="theme-editor-section" id="section-custom-css">
                            <h4 style="margin-top:0;">Custom CSS</h4>
                            <p style="color:#666;font-size:13px;margin-bottom:15px;">
                                Add your own CSS rules. These will be applied on top of the selected theme.
                            </p>
                            
                            <div class="custom-css-editor">
                                <textarea id="custom-css-textarea" placeholder="/* Enter your custom CSS here... */"><?php echo escape($custom_css); ?></textarea>
                                
                                <div class="css-upload-section">
                                    <label>Or upload a CSS file:</label>
                                    <input type="file" id="css-file-upload" accept=".css">
                                </div>
                            </div>
                            
                            <div class="theme-actions">
                                <button class="btn-theme-action btn-theme-secondary" id="clear-css">
                                    🗑️ <?php echo escape(t('Clear', 'Clear')); ?>
                                </button>
                                <button class="btn-theme-action btn-theme-primary" id="save-css">
                                    💾 <?php echo escape(t('Save', 'Save')); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Right Panel: Preview -->
                <div class="theme-editor-preview">
                    <div class="preview-header">
                        <h3>👁️ <?php echo escape(t('Live Preview', 'Live Preview')); ?></h3>
                        <div class="preview-actions">
                            <button class="btn btn-sm" id="refresh-preview">
                                🔄 <?php echo escape(t('Refresh', 'Refresh')); ?>
                            </button>
                            <button class="btn btn-sm btn-primary" id="open-new-tab">
                                ↗️ <?php echo escape(t('Open', 'Open')); ?>
                            </button>
                        </div>
                    </div>
                    <iframe id="preview-iframe" class="preview-iframe" src="../"></iframe>
                </div>
            </div>
        </main>
    </div>

    <script src="../static/scripts/jquery.min.js"></script>
    <script>
    (function($) {
        'use strict';
        
        // Tab switching
        $('.theme-editor-tab').on('click', function() {
            const tab = $(this).data('tab');
            $('.theme-editor-tab').removeClass('active');
            $(this).addClass('active');
            $('.theme-editor-section').removeClass('active');
            $('#section-' + tab).addClass('active');
        });
        
        // Theme preview/apply
        let previewTheme = '<?php echo $current_theme; ?>';
        
        $('.btn-theme-preview').on('click', function() {
            const theme = $(this).data('theme');
            previewTheme = theme;
            updatePreviewTheme(theme);
            $('.theme-card').removeClass('active');
            $(this).closest('.theme-card').addClass('active');
        });
        
        $('.btn-theme-apply').on('click', function() {
            const theme = $(this).data('theme');
            
            $.post('theme.php', {
                action: 'apply_theme',
                theme: theme
            }, function(response) {
                if (response.success) {
                    showMessage('success', response.message);
                    previewTheme = theme;
                    updatePreviewTheme(theme);
                    $('.theme-card').removeClass('active');
                    $('[data-theme="' + theme + '"]').addClass('active');
                } else {
                    showMessage('error', response.message);
                }
            }, 'json');
        });
        
        // Color customization
        let customColors = <?php echo json_encode($custom_colors); ?> || {};
        
        $('.color-picker').on('input', function() {
            const variable = $(this).data('variable');
            const value = $(this).val();
            $(this).siblings('.color-value').val(value);
            customColors[variable] = value;
            applyCustomColors();
        });
        
        $('.color-value').on('input', function() {
            const variable = $(this).data('variable');
            let value = $(this).val().toLowerCase();
            // Accept both 3-char (#RGB) and 6-char (#RRGGBB) hex codes
            if (/^#[0-9a-f]{3}$/.test(value)) {
                // Expand 3-char hex to 6-char for consistency
                value = '#' + value[1] + value[1] + value[2] + value[2] + value[3] + value[3];
                $(this).val(value);
            }
            if (/^#[0-9a-f]{6}$/.test(value)) {
                $(this).siblings('.color-picker').val(value);
                customColors[variable] = value;
                applyCustomColors();
            }
        });
        
        $('#save-colors').on('click', function() {
            $.post('theme.php', {
                action: 'save_colors',
                colors: customColors
            }, function(response) {
                if (response.success) {
                    showMessage('success', response.message);
                } else {
                    showMessage('error', response.message);
                }
            }, 'json');
        });
        
        $('#reset-colors').on('click', function() {
            customColors = {};
            const defaults = <?php echo json_encode(array_map(function($v) { return $v['default']; }, $css_variables)); ?>;
            $('.color-picker').each(function() {
                const variable = $(this).data('variable');
                const defaultVal = defaults[variable] || '#000000';
                $(this).val(defaultVal);
                $(this).siblings('.color-value').val(defaultVal);
            });
            removeCustomColors();
        });
        
        // Custom CSS
        let customCssContent = '';
        
        $('#custom-css-textarea').on('input', function() {
            customCssContent = $(this).val();
            applyCustomCss();
        });
        
        $('#css-file-upload').on('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    customCssContent = e.target.result;
                    $('#custom-css-textarea').val(customCssContent);
                    applyCustomCss();
                };
                reader.readAsText(file);
            }
        });
        
        $('#save-css').on('click', function() {
            $.post('theme.php', {
                action: 'save_custom_css',
                css: customCssContent
            }, function(response) {
                if (response.success) {
                    showMessage('success', response.message);
                } else {
                    showMessage('error', response.message);
                }
            }, 'json');
        });
        
        $('#clear-css').on('click', function() {
            if (confirm('Are you sure you want to clear all custom CSS?')) {
                customCssContent = '';
                $('#custom-css-textarea').val('');
                removeCustomCss();
            }
        });
        
        // Preview controls
        $('#refresh-preview').on('click', function() {
            $('#preview-iframe')[0].contentWindow.location.reload();
        });
        
        $('#open-new-tab').on('click', function() {
            window.open('../', '_blank');
        });
        
        // Helper functions
        function updatePreviewTheme(theme) {
            const iframe = $('#preview-iframe')[0];
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            // Update theme stylesheet in iframe
            const themeLink = iframeDoc.querySelector('link[href*="/theme"]');
            if (themeLink) {
                themeLink.href = '../static/styles/' + theme + '.css';
            }
        }
        
        function applyCustomColors() {
            const iframe = $('#preview-iframe')[0];
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            let styleEl = iframeDoc.getElementById('theme-editor-colors');
            if (!styleEl) {
                styleEl = iframeDoc.createElement('style');
                styleEl.id = 'theme-editor-colors';
                iframeDoc.head.appendChild(styleEl);
            }
            
            let css = ':root {\n';
            for (const [variable, value] of Object.entries(customColors)) {
                css += `  ${variable}: ${value};\n`;
            }
            css += '}';
            
            styleEl.textContent = css;
        }
        
        function removeCustomColors() {
            const iframe = $('#preview-iframe')[0];
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            const styleEl = iframeDoc.getElementById('theme-editor-colors');
            if (styleEl) {
                styleEl.remove();
            }
        }
        
        function applyCustomCss() {
            const iframe = $('#preview-iframe')[0];
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            
            let styleEl = iframeDoc.getElementById('theme-editor-custom-css');
            if (!styleEl) {
                styleEl = iframeDoc.createElement('style');
                styleEl.id = 'theme-editor-custom-css';
                iframeDoc.head.appendChild(styleEl);
            }
            
            styleEl.textContent = customCssContent;
        }
        
        function removeCustomCss() {
            const iframe = $('#preview-iframe')[0];
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;
            const styleEl = iframeDoc.getElementById('theme-editor-custom-css');
            if (styleEl) {
                styleEl.remove();
            }
        }
        
        function showMessage(type, message) {
            const $msg = $('#theme-message');
            $msg.removeClass('success error')
                .addClass('theme-editor-message ' + type)
                .text(message)
                .fadeIn();
            
            setTimeout(function() {
                $msg.fadeOut();
            }, 3000);
        }
        
        // Initialize preview with saved customizations
        $('#preview-iframe').on('load', function() {
            if (Object.keys(customColors).length > 0) {
                applyCustomColors();
            }
            if (customCssContent) {
                applyCustomCss();
            }
        });
        
    })(jQuery);
    </script>
</body>
</html>
