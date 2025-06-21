# Password Reset Strength Enforcer

A comprehensive WordPress plugin that adds both frontend and server-side password strength validation to password reset pages with customizable requirements and admin settings panel.

**Author:** SPARKWEB Studio  
**Website:** [https://sparkwebstudio.com](https://sparkwebstudio.com)  
**Version:** 1.0.0  
**License:** GPL v2 or later

## Features

### 🔐 Password Validation
- **Real-time password validation** on WordPress and WooCommerce reset password pages
- **Server-side validation** to prevent JavaScript bypass
- **Customizable requirements** with admin settings panel
- **Live visual feedback** with color-coded messages
- **Submit button control** - disabled until all requirements are met

### ⚙️ Admin Settings Panel
- **WordPress admin integration** at `Settings > Password Strength`
- **Configurable minimum requirements**:
  - Minimum length (default: 10 characters)
  - Minimum numeric characters (default: 2)
  - Minimum special characters (default: 2)
- **Real-time updates** - changes take effect immediately

### 🎯 Compatibility
- **WordPress Core** password reset forms
- **WooCommerce** account password forms
- **User profile** password changes
- **Responsive design** that works with most WordPress themes
- **Translation ready** with proper internationalization support

### 🛡️ Security Features
- **Dual validation** - both client-side and server-side
- **Cannot be bypassed** by disabling JavaScript
- **WP_Error integration** for proper WordPress error handling
- **Sanitized inputs** and secure coding practices

## Installation

1. Download the plugin files
2. Upload the `password-reset-strength-enforcer` directory to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. The plugin will automatically start working on password reset pages

## File Structure

```
password-reset-strength-enforcer/
├── password-reset-strength-enforcer.php  # Main plugin file
├── assets/
│   └── js/
│       └── validate-reset.js             # JavaScript validation logic
└── README.md                             # Documentation
```

## How It Works

### Frontend Validation
The plugin hooks into WordPress's `resetpass_form` action to inject password requirement indicators and validation logic directly into the reset password page.

### Real-time Feedback
As users type their new password, the plugin provides immediate visual feedback:

- ✗ **Red indicators** for unmet requirements
- ✓ **Green indicators** for met requirements  
- **Submit button** is disabled until all requirements are satisfied
- **Clear messaging** explains what needs to be fixed

### Requirements Validation

1. **Length Check**: Password must be at least 10 characters long
2. **Numeric Check**: Password must contain at least 2 numeric characters (0-9)
3. **Special Character Check**: Password must contain at least 2 special characters from the set: `!@#$%^&*()_+-=[]{};':"\\|,.<>/?`

## Technical Details

### WordPress Hooks Used
- `resetpass_form` - Adds validation HTML to reset password form
- `wp_enqueue_scripts` - Loads JavaScript and localizes text strings
- `wp_head` - Injects custom CSS for styling

### JavaScript Features
- Uses jQuery for DOM manipulation and event handling
- Class-based architecture for clean, maintainable code
- Multiple selector fallbacks to work with different themes
- Form submission prevention when requirements aren't met
- Real-time validation on input, keyup, and paste events

### Styling
- Custom CSS provides clean, accessible styling
- Uses WordPress admin color scheme for consistency
- Responsive design works on mobile devices
- Visual indicators clearly show requirement status

## Compatibility

- **WordPress Version**: 4.0 and higher
- **PHP Version**: 5.6 and higher
- **Dependencies**: jQuery (included with WordPress)
- **Theme Compatibility**: Works with most WordPress themes

## Browser Support

- Chrome (all recent versions)
- Firefox (all recent versions)  
- Safari (all recent versions)
- Edge (all recent versions)
- Internet Explorer 11+

## Troubleshooting

### Plugin Not Working
1. Ensure the plugin is activated
2. Check if you're on the correct reset password page (`wp-login.php?action=rp`)
3. Verify JavaScript is enabled in your browser
4. Check browser console for any JavaScript errors

### Styling Issues
The plugin includes comprehensive CSS that should work with most themes. If you experience styling conflicts:

1. Check if your theme has custom login page styling
2. Use browser developer tools to inspect CSS conflicts
3. Add custom CSS to your theme if needed

### Password Field Not Detected
The plugin looks for multiple common password field selectors. If it's not working:

1. Check if your theme uses custom password reset forms
2. The plugin looks for these selectors: `#pass1`, `#password`, `input[name="pass1"]`, `input[type="password"]`

## Customization

### Modifying Requirements
To change the password requirements, edit the `requirements` object in `assets/js/validate-reset.js`:

```javascript
this.requirements = {
    length: 10,    // Minimum character length
    numbers: 2,    // Minimum numeric characters
    special: 2     // Minimum special characters
};
```

### Styling Customization
Add custom CSS to your theme's `style.css` or use the WordPress Customizer to override the plugin's default styles.

## Security Notes

- This plugin provides **frontend validation only**
- WordPress's built-in password strength requirements still apply
- This is a user experience enhancement, not a security replacement
- Server-side validation should always be your primary security measure

## Translation

The plugin is translation-ready and includes proper WordPress internationalization functions. Text domain: `password-reset-strength-enforcer`

## Support

For issues, feature requests, or questions, please contact **SPARKWEB Studio**:
- **Website:** [https://sparkwebstudio.com](https://sparkwebstudio.com)
- **Email:** Contact through our website
- **GitHub:** Create an issue in the plugin's repository

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### 1.0.0 - 2024-01-15
#### ✨ New Features
- **Frontend password validation** with real-time checking
- **Server-side validation** to prevent JavaScript bypass
- **WordPress admin settings panel** at `Settings > Password Strength`
- **Configurable requirements** for length, numbers, and special characters
- **Multi-platform support** for WordPress core and WooCommerce
- **Dynamic error messages** with current vs required counts
- **Submit button control** with validation-based enable/disable
- **Translation support** with internationalization functions

#### 🔧 Technical Features
- **Multiple password field detection** with fallback selectors
- **Regex validation** using `\d` for numbers and `[!@#$%^&*(),.?":{}|<>]` for special characters
- **WordPress Settings API** integration for admin panel
- **WP_Error handling** for proper error reporting
- **jQuery-based** frontend validation with class architecture
- **Hook integration** for `validate_password_reset`, `user_profile_update_errors`, and WooCommerce
- **Secure coding practices** with input sanitization and capability checks

#### 🎯 Compatibility
- **WordPress:** 4.0+ (tested up to 6.4)
- **PHP:** 5.6+
- **WooCommerce:** Full compatibility with account forms
- **Browsers:** Chrome, Firefox, Safari, Edge, IE11+

#### 📁 Files Structure
- `password-reset-strength-enforcer.php` - Main plugin file
- `assets/js/validate-reset.js` - Frontend validation script
- `README.md` - Documentation

#### 🚀 Initial Release Highlights
- Complete dual-validation system (frontend + backend)
- User-friendly admin interface for configuration
- Comprehensive error handling and user feedback
- Professional code architecture with WordPress standards
- Ready for production use with enterprise-level features 