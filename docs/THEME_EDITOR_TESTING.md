# Theme Editor - Testing Guide

This guide will help you test the Theme Editor feature to verify it works correctly.

## Prerequisites

Before testing, ensure:
- [ ] You have admin credentials
- [ ] The server is running
- [ ] PHP is working correctly
- [ ] Write permissions exist on `config.ini` and `static/styles/`

## Access the Theme Editor

1. **Log in as admin**
   - Navigate to your blog homepage
   - Click "Login" or access `/admin/`
   - Enter admin credentials

2. **Navigate to Theme Editor**
   - From the admin dashboard, look at the left sidebar
   - Click on "🎨 Theme Editor" (should be at the bottom of the menu)
   - The Theme Editor page should load

## Test 1: Theme Gallery

**Objective**: Verify theme selection and application works

### Steps:
1. On the Theme Editor page, ensure you're on the "🖼️ Gallery" tab
2. You should see theme cards for theme01, theme02, and any other themes
3. Each card should display:
   - A preview image or colored tile
   - Theme name
   - "👁️ Preview" button
   - "✓ Apply" button

### Test Cases:

**TC1.1: Preview a theme**
- Click "👁️ Preview" on any theme card
- ✅ **Expected**: 
  - The card gets highlighted (active state)
  - The preview iframe on the right updates with the new theme
  - No error messages appear

**TC1.2: Apply a theme**
- Click "✓ Apply" on any theme card
- ✅ **Expected**:
  - Success message appears: "Theme applied successfully"
  - The theme is now the default for the entire blog
  - Opening the blog in a new tab shows the new theme

**TC1.3: Invalid theme handling**
- This is handled automatically - invalid themes won't appear in gallery
- ✅ **Expected**: Only valid theme files from `static/styles/theme*.css` are shown

## Test 2: Color Customization

**Objective**: Verify color picker functionality and persistence

### Steps:
1. Click on the "🎨 Colors" tab
2. You should see 8 color controls:
   - Background
   - Text Color
   - Surface
   - Surface 2
   - Border
   - Muted Text
   - Links
   - Primary Color

### Test Cases:

**TC2.1: Change a color using color picker**
- Click on any color picker (colored square)
- Select a different color
- ✅ **Expected**:
  - The hex input field updates to match
  - The preview iframe updates in real-time
  - No page reload occurs

**TC2.2: Change a color using hex input**
- Type a valid hex code in any hex input (e.g., `#ff0000`)
- ✅ **Expected**:
  - The color picker updates to match
  - The preview iframe updates in real-time
  - Red color appears where expected

**TC2.3: Change a color using 3-char hex**
- Type a 3-character hex code (e.g., `#f00`)
- ✅ **Expected**:
  - Automatically expands to 6-char hex (`#ff0000`)
  - Color picker and preview update correctly

**TC2.4: Invalid hex code**
- Type an invalid hex code (e.g., `#gggggg` or `invalid`)
- ✅ **Expected**:
  - No update occurs
  - Previous valid value remains
  - No error message (just ignored)

**TC2.5: Save colors**
- Make several color changes
- Click "💾 Save" button
- ✅ **Expected**:
  - Success message: "Colors saved successfully"
  - Refresh the page - colors should persist
  - Open blog in new tab - colors should be applied

**TC2.6: Reset colors**
- Make several color changes
- Click "🔄 Reset" button
- ✅ **Expected**:
  - All colors revert to default values
  - Preview updates immediately
  - No success message (just resets)

## Test 3: Custom CSS

**Objective**: Verify CSS editor and file upload work

### Steps:
1. Click on the "📝 Custom CSS" tab
2. You should see:
   - A large textarea (CSS editor)
   - File upload input
   - "🗑️ Clear" and "💾 Save" buttons

### Test Cases:

**TC3.1: Add custom CSS via editor**
- Type valid CSS in the editor:
  ```css
  .b_post {
      border: 3px solid red !important;
  }
  ```
- ✅ **Expected**:
  - Preview iframe updates in real-time
  - Posts in preview have red borders

**TC3.2: Save custom CSS**
- Add CSS as in TC3.1
- Click "💾 Save" button
- ✅ **Expected**:
  - Success message: "Custom CSS saved successfully"
  - File `static/styles/custom-theme.css` is created
  - CSS persists after page refresh
  - Blog in new tab shows custom CSS applied

**TC3.3: Upload CSS file**
- Create a file `test.css` with content:
  ```css
  body { background: linear-gradient(45deg, #ff0000, #0000ff); }
  ```
- Click "Choose File" and select `test.css`
- ✅ **Expected**:
  - Editor populates with file content
  - Preview updates immediately
  - Background shows red-to-blue gradient

**TC3.4: Clear CSS**
- Add some CSS
- Click "🗑️ Clear" button
- Confirm the dialog
- ✅ **Expected**:
  - Editor clears
  - Preview reverts to no custom CSS
  - Confirmation dialog appears before clearing

**TC3.5: Large CSS file (edge case)**
- Create a CSS file larger than 1MB
- Try to save it
- ✅ **Expected**:
  - Error message: "CSS file too large (max 1MB)"
  - CSS is not saved

**TC3.6: Invalid characters (edge case)**
- Try to paste CSS with null bytes or invalid UTF-8
- Click "💾 Save"
- ✅ **Expected**:
  - Error message: "Invalid CSS content"
  - CSS is not saved

## Test 4: Live Preview

**Objective**: Verify preview iframe works correctly

### Steps:
1. Look at the right side of the Theme Editor page
2. You should see a preview iframe with "👁️ Live Preview" header

### Test Cases:

**TC4.1: Initial load**
- Open Theme Editor page
- ✅ **Expected**:
  - Preview iframe loads blog homepage
  - Posts are visible
  - Navigation works in preview

**TC4.2: Refresh preview**
- Make some changes (theme, colors, or CSS)
- Click "🔄 Refresh" button
- ✅ **Expected**:
  - Iframe reloads
  - Changes are still visible after reload

**TC4.3: Open in new tab**
- Click "↗️ Open" button
- ✅ **Expected**:
  - New browser tab opens with blog homepage
  - Applied changes are visible (if saved)
  - Preview changes are NOT visible (if not saved)

**TC4.4: Real-time updates**
- Switch between tabs and make changes
- ✅ **Expected**:
  - Color changes update immediately
  - CSS changes update immediately
  - Theme previews update immediately
  - No page reload or flicker

## Test 5: Integration Testing

**Objective**: Verify theme customizations work site-wide

### Steps:
1. Apply a complete set of customizations
2. Test throughout the blog

### Test Cases:

**TC5.1: Full customization workflow**
- Select theme02
- Change primary color to `#ff6600`
- Add custom CSS: `.bluebar { display: none; }`
- Save both colors and CSS
- ✅ **Expected**:
  - Theme Editor shows all changes
  - Blog homepage shows all changes
  - All blog pages show changes
  - Changes persist after logout/login

**TC5.2: Backward compatibility**
- Note the current theme and colors
- Clear all customizations (reset colors, clear CSS)
- ✅ **Expected**:
  - Blog reverts to default appearance
  - No errors in browser console
  - All blog features still work
  - Admin panel still works

## Test 6: Error Handling

**Objective**: Verify graceful error handling

### Test Cases:

**TC6.1: Network error simulation**
- Open browser dev tools
- Go to Network tab, enable "Offline" mode
- Try to save colors or CSS
- ✅ **Expected**:
  - Error message appears
  - No JavaScript errors in console
  - Page remains functional

**TC6.2: Invalid theme name**
- This is prevented by validation
- Try to apply theme that doesn't exist (via API)
- ✅ **Expected**:
  - Error message: "Theme file does not exist"
  - No changes applied

**TC6.3: Permission issues**
- Remove write permissions on `config.ini` (temporarily)
- Try to save colors
- ✅ **Expected**:
  - Error message: "Failed to save configuration"
  - User is informed of the issue

## Test 7: Responsive Design

**Objective**: Verify mobile/tablet compatibility

### Test Cases:

**TC7.1: Mobile view (< 1200px)**
- Resize browser to mobile width (e.g., 375px)
- ✅ **Expected**:
  - Layout switches to single column
  - Controls stack above preview
  - All buttons remain accessible
  - Tabs remain usable

**TC7.2: Tablet view (768px - 1200px)**
- Resize browser to tablet width (e.g., 768px)
- ✅ **Expected**:
  - Layout adapts appropriately
  - No horizontal scrolling
  - All features work

## Test 8: Browser Compatibility

**Objective**: Verify cross-browser support

### Test Cases:

**TC8.1: Chrome/Edge**
- Test all features in Chrome or Edge
- ✅ **Expected**: All features work perfectly

**TC8.2: Firefox**
- Test all features in Firefox
- ✅ **Expected**: All features work perfectly

**TC8.3: Safari**
- Test all features in Safari
- ✅ **Expected**: All features work perfectly

## Test 9: Security

**Objective**: Verify security measures are effective

### Test Cases:

**TC9.1: Admin-only access**
- Log out from admin
- Try to access `/admin/theme.php` directly
- ✅ **Expected**:
  - Redirected to login or homepage
  - Theme Editor not accessible

**TC9.2: XSS attempt via CSS**
- Try to inject `<script>alert('XSS')</script>` in CSS
- Save the CSS
- ✅ **Expected**:
  - CSS is saved as plain text
  - No JavaScript execution
  - No XSS vulnerability

**TC9.3: CSS injection attempt**
- Try to inject INI syntax in CSS variable names
- Try to save colors with malicious names
- ✅ **Expected**:
  - Invalid names are rejected
  - Only valid CSS variable names accepted
  - No config corruption

## Expected Outcomes

### All Tests Pass ✅
If all tests pass, the Theme Editor is working correctly and ready for production use.

### Some Tests Fail ❌
If any tests fail:
1. Note which test failed
2. Check browser console for JavaScript errors
3. Check PHP error logs for server errors
4. Verify file permissions
5. Report the issue with test case number

## Performance Benchmarks

### Page Load
- Theme Editor page should load in < 2 seconds
- Preview iframe should load in < 3 seconds

### Real-time Updates
- Color changes should update in < 100ms
- CSS changes should update in < 200ms
- Theme changes should update in < 500ms

### Save Operations
- Save colors: < 1 second
- Save CSS: < 2 seconds (depending on size)
- Apply theme: < 1 second

## Accessibility Testing

### Keyboard Navigation
- Tab through all controls
- ✅ **Expected**: All interactive elements are reachable

### Screen Reader
- Use screen reader to navigate
- ✅ **Expected**: All labels and controls are announced

### High Contrast Mode
- Enable high contrast mode
- ✅ **Expected**: All text remains readable

## Cleanup After Testing

After testing is complete:
1. Reset colors to defaults
2. Clear custom CSS
3. Apply original theme
4. Verify blog returns to original state
5. Optional: Delete `static/styles/custom-theme.css`

## Reporting Issues

When reporting issues, include:
- Test case number that failed
- Browser and version
- PHP version
- Screenshot of error
- Browser console output
- Server error logs (if accessible)

---

**Happy Testing!** 🎨✨
