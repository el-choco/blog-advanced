# Theme Editor Feature

## Overview

The Theme Editor is an admin-only feature that provides a comprehensive interface for customizing the blog's appearance. It includes live preview, color customization via CSS variables, a theme gallery, and the ability to upload and edit custom CSS.

## Features

### 1. Theme Gallery
- **Automatic Theme Detection**: Scans `static/styles/` directory for `theme*.css` files
- **Visual Preview**: Shows preview images for themes (from `static/images/{theme}/preview.png`)
- **Fallback Display**: Uses colored tiles with theme initials when preview images aren't available
- **Quick Actions**:
  - **Preview**: Applies theme in iframe only (non-destructive)
  - **Apply**: Sets theme as default for the entire blog

### 2. Color Customization
- **CSS Variable Editor**: Provides color pickers for key CSS variables:
  - `--bg`: Background color
  - `--text`: Text color
  - `--surface`: Primary surface color
  - `--surface-2`: Secondary surface color
  - `--border`: Border color
  - `--muted`: Muted text color
  - `--link`: Link color
  - `--primary`: Primary brand color
- **Live Preview**: Changes reflect immediately in the preview iframe
- **Dual Input**: Color picker and hex code text input for precise control
- **Persistence**: Colors saved to `config.ini` under `[theme_editor]` section

### 3. Custom CSS
- **Inline Editor**: Built-in CSS editor with syntax-friendly monospace font
- **File Upload**: Upload CSS files directly
- **Live Preview**: Custom CSS applies immediately to preview
- **Storage**: Saved to `static/styles/custom-theme.css`
- **Additive**: Custom CSS applies on top of the selected theme

### 4. Live Preview
- **Real-time Updates**: Preview iframe updates instantly as changes are made
- **Full Blog Preview**: Loads actual blog content in iframe
- **Preview Controls**:
  - Refresh: Reload the preview
  - Open: Open preview in new tab
- **Non-destructive**: Changes only visible in preview until explicitly applied

## File Structure

```
admin/
  └── theme.php                    # Main Theme Editor admin page

static/
  └── styles/
      ├── theme-editor.css         # Theme Editor UI styles
      └── custom-theme.css         # User's custom CSS (created on save)

static/images/
  ├── theme01/
  │   └── preview.png             # Optional theme preview image
  └── theme02/
      └── preview.png             # Optional theme preview image
```

## Configuration

Theme Editor settings are stored in `config.ini` under the `[theme_editor]` section:

```ini
[theme_editor]
custom_colors = "{\"--bg\":\"#e9eaed\",\"--primary\":\"#1877f2\"}"
custom_css_enabled = "1"
```

## Usage

### Accessing the Theme Editor

1. Navigate to the admin dashboard
2. Click on **"🎨 Theme Editor"** in the sidebar menu
3. The Theme Editor page will load with three tabs:
   - **Gallery**: Select and apply base themes
   - **Colors**: Customize CSS variables
   - **Custom CSS**: Add custom styles

### Applying a Theme

1. Go to the **Gallery** tab
2. Click **"Preview"** to see the theme in the iframe
3. Click **"Apply"** to set it as the default theme
4. The theme will be applied site-wide immediately

### Customizing Colors

1. Go to the **Colors** tab
2. Use color pickers or enter hex codes manually
3. Changes appear instantly in the preview
4. Click **"Save"** to persist the changes
5. Click **"Reset"** to revert to default colors

### Adding Custom CSS

1. Go to the **Custom CSS** tab
2. Type CSS rules directly in the editor, or
3. Use the file upload to load a CSS file
4. Changes preview immediately
5. Click **"Save"** to apply the custom CSS site-wide
6. Click **"Clear"** to remove all custom CSS

## Technical Details

### Frontend Integration

Custom colors and CSS are automatically loaded in `index.php`:

```php
// Custom CSS (if enabled)
if (custom_css_enabled && file_exists(custom-theme.css))
    <link href="static/styles/custom-theme.css" />

// Custom colors (inline style)
<style id="theme-editor-colors">
:root {
    --bg: #e9eaed;
    --primary: #1877f2;
    /* ... other custom colors ... */
}
</style>
```

### AJAX Endpoints

The Theme Editor uses AJAX POST requests to `admin/theme.php` with the following actions:

- `apply_theme`: Set a theme as default
- `save_colors`: Save custom CSS variables
- `save_custom_css`: Save custom CSS to file
- `reset_customizations`: Remove all customizations

### Security

- **Authentication**: Requires admin login (enforced by `admin/common.php`)
- **Input Sanitization**: Theme names sanitized to alphanumeric + dash/underscore
- **Path Safety**: File operations use absolute paths with `PROJECT_PATH`
- **CSRF Protection**: Inherits CSRF token from admin session

## Backward Compatibility

The Theme Editor feature is **fully backward compatible**:

- ✅ Existing themes continue to work without modification
- ✅ Default blog appearance unchanged until admin applies customizations
- ✅ Theme settings stored in dedicated `[theme_editor]` section
- ✅ No breaking changes to existing config structure
- ✅ Custom CSS and colors are optional and additive

## Browser Support

- Modern browsers (Chrome, Firefox, Safari, Edge)
- Requires JavaScript enabled
- Color picker input type support (fallback to text input)

## Limitations

- Custom CSS must be valid CSS syntax
- Color customization limited to predefined CSS variables
- Preview iframe may have same-origin policy restrictions
- Theme preview images must be manually created

## Future Enhancements

Possible improvements for future versions:

- [ ] Visual CSS variable inspector
- [ ] Theme export/import functionality
- [ ] More CSS variables for customization
- [ ] Undo/redo functionality
- [ ] CSS syntax highlighting
- [ ] Auto-generate theme preview images
- [ ] Dark mode-specific color customization
- [ ] Font customization options

## Troubleshooting

### Preview iframe not loading
- Check that `index.php` is accessible
- Verify browser console for errors
- Ensure same-origin policy allows iframe

### Changes not persisting
- Verify write permissions on `config.ini`
- Check write permissions on `static/styles/` directory
- Review browser console for AJAX errors

### Custom CSS not applying
- Ensure CSS syntax is valid
- Check that `custom_css_enabled` is set to `1` in config
- Verify `custom-theme.css` file exists in `static/styles/`

### Colors not updating
- Verify `custom_colors` JSON is valid in config
- Check browser console for parsing errors
- Ensure color values are valid hex codes

## Support

For issues or questions:
1. Check this documentation
2. Review browser console for errors
3. Check server error logs
4. Verify file permissions
5. Open an issue on the repository
