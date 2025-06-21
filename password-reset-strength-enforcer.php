<?php
/**
 * Plugin Name: Password Reset Strength Enforcer
 * Plugin URI: https://sparkwebstudio.com/password-reset-strength-enforcer
 * Description: Adds frontend password strength validation to the WordPress Reset Password page with customizable minimum requirements. Includes admin settings panel and server-side validation.
 * Version: 1.0.0
 * Author: SPARKWEB Studio
 * Author URI: https://sparkwebstudio.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: password-reset-strength-enforcer
 * Domain Path: /languages
 * Requires at least: 4.0
 * Tested up to: 6.4
 * Requires PHP: 5.6
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Password_Reset_Strength_Enforcer {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize the plugin
     */
    public function init() {
        // Hook into the reset password page - both WordPress and WooCommerce
        add_action('resetpass_form', array($this, 'add_password_validation'));
        add_action('woocommerce_reset_password_form', array($this, 'add_password_validation'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Additional hooks for frontend forms
        add_action('wp_footer', array($this, 'add_frontend_validation_support'));
        add_action('woocommerce_edit_account_form_start', array($this, 'add_password_validation'));
        add_action('woocommerce_save_account_details', array($this, 'add_password_validation'));
        
        // Specific hook for WooCommerce reset password form
        add_action('woocommerce_resetpassword_form', array($this, 'add_password_validation'));
        
        // Add custom CSS for styling
        add_action('wp_head', array($this, 'add_custom_styles'));
        
        // Server-side password validation hooks
        add_action('validate_password_reset', array($this, 'validate_password_strength'), 10, 2);
        add_action('user_profile_update_errors', array($this, 'validate_profile_password'), 10, 3);
        add_action('woocommerce_save_account_details_errors', array($this, 'validate_woocommerce_password'), 10, 1);
        
        // Admin settings page
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }
    
    /**
     * Add password validation elements to the reset password form
     */
    public function add_password_validation() {
        ?>
        <div id="password-strength-message" style="color: red; font-size: 0.9em;">
            <?php _e('Das Passwort muss mindestens 10 Zeichen lang sein und 2 Zahlen sowie 2 Sonderzeichen enthalten.', 'password-reset-strength-enforcer'); ?>
        </div>
        <?php
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        $should_enqueue = $this->should_enqueue_scripts();
        
        if ($should_enqueue) {
            wp_enqueue_script(
                'password-reset-validator',
                plugin_dir_url(__FILE__) . 'assets/js/validate-reset.js',
                array('jquery'),
                '1.0.0',
                true
            );
            
            // Get settings from database
            $settings = $this->get_password_settings();
            
            // Localize script with password requirements and translations
            wp_localize_script('password-reset-validator', 'passwordStrengthConfig', array(
                'minLength' => $settings['min_length'],
                'minNumeric' => $settings['min_numeric'],
                'minSpecial' => $settings['min_special'],
                'messages' => array(
                    'requirements_met' => __('Alle Anforderungen erfüllt! ✓', 'password-reset-strength-enforcer'),
                    'requirements_not_met' => __('Das Passwort erfüllt nicht alle Anforderungen', 'password-reset-strength-enforcer'),
                    'submit_disabled' => __('Bitte erfüllen Sie alle Passwort-Anforderungen, um fortzufahren', 'password-reset-strength-enforcer')
                )
            ));
        }
    }
    
    /**
     * Determine if scripts should be enqueued
     */
    private function should_enqueue_scripts() {
        global $pagenow;
        
        // WordPress login page with reset password action
        if ($pagenow === 'wp-login.php' && isset($_GET['action']) && $_GET['action'] === 'rp') {
            return true;
        }
        
        // WooCommerce account page with reset password endpoints
        if (function_exists('is_account_page') && is_account_page()) {
            if (class_exists('WC') && function_exists('is_wc_endpoint_url')) {
                if (is_wc_endpoint_url('lost-password') || is_wc_endpoint_url('set-password')) {
                    return true;
                }
            }
        }
        
        // Frontend pages with reset password action parameter
        if (isset($_GET['action']) && $_GET['action'] === 'rp') {
            return true;
        }
        
        // Check for reset password query parameters (WordPress email links)
        if (isset($_GET['key']) && isset($_GET['login'])) {
            return true;
        }
        
        // Check if current page contains password reset forms (by checking for specific form elements)
        if (is_admin()) {
            return false; // Skip admin pages for performance
        }
        
        // For frontend pages, we'll also check via JavaScript if password fields exist
        // This ensures we catch custom implementations
        return $this->page_might_have_password_form();
    }
    
    /**
     * Check if the current page might have a password reset form
     */
    private function page_might_have_password_form() {
        // Check URL patterns that commonly indicate password reset
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        $reset_patterns = array(
            '/reset',
            '/lost-password',
            '/set-password',
            '/change-password',
            '/reset-password',
            'action=rp',
            'show-reset-form=true',
            'wc_reset_password'
        );
        
        foreach ($reset_patterns as $pattern) {
            if (stripos($request_uri, $pattern) !== false) {
                return true;
            }
        }
        
        // Check POST data for reset password indicators
        if (isset($_POST['reset_key']) || isset($_POST['reset_login']) || isset($_POST['wc_reset_password'])) {
            return true;
        }
        
        // Check GET parameters for reset indicators
        if (isset($_GET['show-reset-form']) || isset($_GET['reset-link-sent'])) {
            return true;
        }
        
        // Only load on specific pages that are likely to have password reset forms
        // Exclude general account pages which may have login forms
        if (is_page()) {
            // Only if the page content contains password reset indicators
            global $post;
            if ($post && (
                stripos($post->post_content, 'password') !== false || 
                stripos($post->post_content, 'reset') !== false ||
                stripos($post->post_title, 'password') !== false ||
                stripos($post->post_title, 'reset') !== false
            )) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add frontend validation support
     */
    public function add_frontend_validation_support() {
                 // Check if we have password fields that need validation
         ?>
         <script type="text/javascript">
         jQuery(document).ready(function($) {
             // Function to check if a form is a login form
             function isLoginForm(form) {
                 // Check form classes
                 if (form.hasClass('login') || form.hasClass('woocommerce-form-login') || form.hasClass('loginform') || form.hasClass('wp-login-form')) {
                     return true;
                 }
                 
                 // Check for username/email field
                 var hasUsernameField = form.find('input[name="username"], input[name="user_login"], input[name="email"], input[type="email"]').length > 0;
                 
                 // Check for login submit button
                 var hasLoginSubmit = form.find('input[name="login"], button[name="login"], input[value*="Login"], input[value*="Anmelden"]').length > 0;
                 
                 // Check for remember me checkbox
                 var hasRememberMe = form.find('input[name="rememberme"], input[name="remember"]').length > 0;
                 
                 // Check for single password field with username (login pattern)
                 var passwordFields = form.find('input[type="password"]');
                 
                 // If has username AND (login submit OR remember me) AND single password, it's a login form
                 if (hasUsernameField && (hasLoginSubmit || hasRememberMe) && passwordFields.length === 1) {
                     return true;
                 }
                 
                 return false;
             }
             
             // Function to add validation message
             function addValidationMessage() {
                 // Check if we already have our message div
                 if ($('#password-strength-message').length > 0) {
                     return;
                 }
                 
                 // Look for password reset forms and specific password fields
                 var wooResetForm = $('.woocommerce-ResetPassword, .lost_reset_password');
                 var passwordFields = $('#password_1, #password_2, input[name="pass1"], input[name="pass2"]');
                 
                 // If no specific reset fields, look for generic password fields but exclude login forms
                 if (passwordFields.length === 0) {
                     $('input[type="password"]').each(function() {
                         var field = $(this);
                         var form = field.closest('form');
                         
                         // Skip if this is a login form
                         if (form.length > 0 && isLoginForm(form)) {
                             return; // continue to next field
                         }
                         
                         // Add this field to our collection
                         passwordFields = passwordFields.add(field);
                     });
                 }
                 
                 if (passwordFields.length > 0) {
                     console.log('Found password fields for validation:', passwordFields.length);
                     
                     // For WooCommerce forms, insert after the first password field container
                     var insertAfter = null;
                     
                     if (wooResetForm.length > 0) {
                         // WooCommerce specific placement - after first form-row
                         insertAfter = $('#password_1').closest('.form-row, p');
                     } else {
                         // Generic placement - use first valid password field
                         insertAfter = passwordFields.first().closest('.form-row, p, .field').length > 0 
                             ? passwordFields.first().closest('.form-row, p, .field')
                             : passwordFields.first().parent();
                     }
                     
                     // Double-check that we're not inserting into a login form
                     if (insertAfter && insertAfter.length > 0) {
                         var parentForm = insertAfter.closest('form');
                         if (parentForm.length > 0 && isLoginForm(parentForm)) {
                             console.log('Skipping validation message - detected login form');
                             return false;
                         }
                         
                         // Create the message div with better styling
                         var messageDiv = $('<div id="password-strength-message" class="password-validation-message" style="color: #d94c4c; font-size: 0.9em; margin: 8px 0; padding: 8px 12px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; display: block;"><?php 
                         $settings = $this->get_password_settings();
                         echo esc_js(sprintf(__('Das Passwort muss mindestens %d Zeichen lang sein und %d Zahlen sowie %d Sonderzeichen enthalten.', 'password-reset-strength-enforcer'), $settings['min_length'], $settings['min_numeric'], $settings['min_special'])); 
                         ?></div>');
                         
                         // Insert after the container
                         insertAfter.after(messageDiv);
                         
                         console.log('Password validation message added successfully');
                         return true;
                     }
                 }
                 
                 console.log('No suitable location found for password validation message');
                 return false;
             }
             
             // Try multiple times to ensure message gets added
             var attempts = 0;
             var maxAttempts = 5;
             
             function tryAddMessage() {
                 attempts++;
                 if (addValidationMessage() || attempts >= maxAttempts) {
                     return;
                 }
                 setTimeout(tryAddMessage, 200 * attempts);
             }
             
             // Start trying to add the message
             tryAddMessage();
         });
         </script>
         <?php
    }
    
    /**
     * Add custom CSS styles
     */
    public function add_custom_styles() {
        // Only add styles on reset password page
        if ((is_page() && isset($_GET['action']) && $_GET['action'] === 'rp') || 
            (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php' && isset($_GET['action']) && $_GET['action'] === 'rp')) {
            ?>
            <style type="text/css">
                .password-requirements {
                    margin: 15px 0;
                    padding: 15px;
                    background: #f9f9f9;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                
                .password-requirements h4 {
                    margin: 0 0 10px 0;
                    font-size: 14px;
                    color: #333;
                }
                
                .password-requirements ul {
                    margin: 0;
                    padding: 0;
                    list-style: none;
                }
                
                .requirement {
                    padding: 5px 0;
                    font-size: 13px;
                    color: #666;
                }
                
                .requirement.met {
                    color: #46b450;
                }
                
                .requirement.not-met {
                    color: #dc3232;
                }
                
                .req-icon {
                    display: inline-block;
                    width: 16px;
                    font-weight: bold;
                    margin-right: 5px;
                }
                
                .requirement.met .req-icon {
                    color: #46b450;
                }
                
                .requirement.not-met .req-icon {
                    color: #dc3232;
                }
                
                .strength-message {
                    margin-top: 10px;
                    padding: 8px;
                    border-radius: 3px;
                    font-weight: bold;
                    text-align: center;
                }
                
                .strength-message.success {
                    background: #d4edda;
                    color: #155724;
                    border: 1px solid #c3e6cb;
                }
                
                .strength-message.error {
                    background: #f8d7da;
                    color: #721c24;
                    border: 1px solid #f5c6cb;
                }
                
                .submit-disabled {
                    opacity: 0.6;
                    cursor: not-allowed !important;
                }
                
                .password-input-wrapper {
                    position: relative;
                }
            </style>
                         <?php
         }
     }
     
     /**
      * Validate password strength for WordPress password reset
      */
     public function validate_password_strength($errors, $user) {
         if (isset($_POST['pass1']) && !empty($_POST['pass1'])) {
             $password = $_POST['pass1'];
             $validation_result = $this->check_password_strength($password);
             
             if (is_wp_error($validation_result)) {
                 $errors->add('weak_password', $validation_result->get_error_message());
             }
         }
         
         return $errors;
     }
     
     /**
      * Validate password strength for user profile updates
      */
     public function validate_profile_password($errors, $update, $user) {
         if (isset($_POST['pass1']) && !empty($_POST['pass1'])) {
             $password = $_POST['pass1'];
             $validation_result = $this->check_password_strength($password);
             
             if (is_wp_error($validation_result)) {
                 $errors->add('weak_password', $validation_result->get_error_message());
             }
         }
     }
     
     /**
      * Validate password strength for WooCommerce account details
      */
     public function validate_woocommerce_password($errors) {
         if (isset($_POST['password_1']) && !empty($_POST['password_1'])) {
             $password = $_POST['password_1'];
             $validation_result = $this->check_password_strength($password);
             
             if (is_wp_error($validation_result)) {
                 $errors->add('weak_password', $validation_result->get_error_message());
             }
         }
         
         return $errors;
     }
     
     /**
      * Check password strength against requirements
      * 
      * @param string $password The password to validate
      * @return bool|WP_Error True if valid, WP_Error if invalid
      */
     private function check_password_strength($password) {
         // Get settings from database
         $settings = $this->get_password_settings();
         
         $min_length = $settings['min_length'];
         $min_numeric = $settings['min_numeric'];
         $min_special = $settings['min_special'];
         
         // Check length
         if (strlen($password) < $min_length) {
             return new WP_Error(
                 'password_too_short',
                 sprintf(
                     __('Ihr Passwort muss mindestens %d Zeichen, %d Zahlen und %d Sonderzeichen enthalten.', 'password-reset-strength-enforcer'),
                     $min_length,
                     $min_numeric,
                     $min_special
                 )
             );
         }
         
         // Check numeric characters
         $numeric_count = preg_match_all('/\d/', $password);
         if ($numeric_count < $min_numeric) {
             return new WP_Error(
                 'password_insufficient_numbers',
                 sprintf(
                     __('Ihr Passwort muss mindestens %d Zeichen, %d Zahlen und %d Sonderzeichen enthalten.', 'password-reset-strength-enforcer'),
                     $min_length,
                     $min_numeric,
                     $min_special
                 )
             );
         }
         
         // Check special characters - using the same regex as JavaScript
         $special_count = preg_match_all('/[!@#$%^&*(),.?":{}|<>]/', $password);
         if ($special_count < $min_special) {
             return new WP_Error(
                 'password_insufficient_special',
                 sprintf(
                     __('Ihr Passwort muss mindestens %d Zeichen, %d Zahlen und %d Sonderzeichen enthalten.', 'password-reset-strength-enforcer'),
                     $min_length,
                     $min_numeric,
                     $min_special
                 )
             );
         }
         
         return true;
     }
     
     /**
      * Add admin menu page
      */
     public function add_admin_menu() {
         add_options_page(
             __('Passwort-Stärke Einstellungen', 'password-reset-strength-enforcer'),
             __('Passwort-Stärke', 'password-reset-strength-enforcer'),
             'manage_options',
             'password-strength-settings',
             array($this, 'settings_page')
         );
     }
     
     /**
      * Initialize settings
      */
     public function settings_init() {
         register_setting('password_strength_settings', 'password_strength_options');
         
         add_settings_section(
             'password_strength_requirements_section',
             __('Passwort-Anforderungen', 'password-reset-strength-enforcer'),
             array($this, 'settings_section_callback'),
             'password_strength_settings'
         );
         
         add_settings_field(
             'min_length',
             __('Mindestlänge', 'password-reset-strength-enforcer'),
             array($this, 'min_length_render'),
             'password_strength_settings',
             'password_strength_requirements_section'
         );
         
         add_settings_field(
             'min_numeric',
             __('Mindestanzahl Zahlen', 'password-reset-strength-enforcer'),
             array($this, 'min_numeric_render'),
             'password_strength_settings',
             'password_strength_requirements_section'
         );
         
         add_settings_field(
             'min_special',
             __('Mindestanzahl Sonderzeichen', 'password-reset-strength-enforcer'),
             array($this, 'min_special_render'),
             'password_strength_settings',
             'password_strength_requirements_section'
         );
     }
     
     /**
      * Render minimum length field
      */
     public function min_length_render() {
         $options = get_option('password_strength_options');
         $value = isset($options['min_length']) ? $options['min_length'] : 10;
         ?>
         <input type='number' name='password_strength_options[min_length]' value='<?php echo esc_attr($value); ?>' min='1' max='50'>
         <p class="description"><?php _e('Mindestanzahl der im Passwort erforderlichen Zeichen.', 'password-reset-strength-enforcer'); ?></p>
         <?php
     }
     
     /**
      * Render minimum numeric characters field
      */
     public function min_numeric_render() {
         $options = get_option('password_strength_options');
         $value = isset($options['min_numeric']) ? $options['min_numeric'] : 2;
         ?>
         <input type='number' name='password_strength_options[min_numeric]' value='<?php echo esc_attr($value); ?>' min='0' max='20'>
         <p class="description"><?php _e('Mindestanzahl der im Passwort erforderlichen Zahlen (0-9).', 'password-reset-strength-enforcer'); ?></p>
         <?php
     }
     
     /**
      * Render minimum special characters field
      */
     public function min_special_render() {
         $options = get_option('password_strength_options');
         $value = isset($options['min_special']) ? $options['min_special'] : 2;
         ?>
         <input type='number' name='password_strength_options[min_special]' value='<?php echo esc_attr($value); ?>' min='0' max='20'>
         <p class="description"><?php _e('Mindestanzahl der im Passwort erforderlichen Sonderzeichen (!@#$%^&*(),.?":{}|<>).', 'password-reset-strength-enforcer'); ?></p>
         <?php
     }
     
     /**
      * Settings section callback
      */
     public function settings_section_callback() {
         echo '<p>' . __('Konfigurieren Sie die Passwort-Stärke-Anforderungen für Ihre Website.', 'password-reset-strength-enforcer') . '</p>';
     }
     
     /**
      * Render settings page
      */
     public function settings_page() {
         ?>
         <div class="wrap">
             <h1><?php _e('Passwort-Stärke Einstellungen', 'password-reset-strength-enforcer'); ?></h1>
             
             <form action='options.php' method='post'>
                 <?php
                 settings_fields('password_strength_settings');
                 do_settings_sections('password_strength_settings');
                 submit_button();
                 ?>
             </form>
             
             <div class="notice notice-info">
                 <p>
                     <strong><?php _e('Hinweis:', 'password-reset-strength-enforcer'); ?></strong>
                     <?php _e('Diese Einstellungen gelten für Passwort-Zurücksetzen-Formulare und Benutzerprofile-Passwort-Änderungen. Sonderzeichen umfassen: !@#$%^&*(),.?":{}|<>', 'password-reset-strength-enforcer'); ?>
                 </p>
             </div>
         </div>
         <?php
     }
     
     /**
      * Get password settings with defaults
      */
     private function get_password_settings() {
         $options = get_option('password_strength_options');
         
         return array(
             'min_length' => isset($options['min_length']) ? (int) $options['min_length'] : 10,
             'min_numeric' => isset($options['min_numeric']) ? (int) $options['min_numeric'] : 2,
             'min_special' => isset($options['min_special']) ? (int) $options['min_special'] : 2,
         );
     }
 }
 
 // Initialize the plugin
 new Password_Reset_Strength_Enforcer(); 