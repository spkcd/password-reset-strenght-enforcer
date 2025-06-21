jQuery(document).ready(function($) {
    
    // Password strength validation script
    class PasswordResetValidator {
        constructor() {
            // Get configuration from localized script or use defaults
            const config = window.passwordStrengthConfig || {
                minLength: 10,
                minNumeric: 2,
                minSpecial: 2,
                messages: {}
            };
            
            this.minLength = config.minLength;
            this.minNumeric = config.minNumeric;
            this.minSpecial = config.minSpecial;
            this.messages = config.messages || {};
            
            // Regex patterns as specified
            this.numericRegex = /\d/g;
            this.specialCharRegex = /[!@#$%^&*(),.?":{}|<>]/g;
            
            this.init();
        }
        
        init() {
            // Target password input fields with multiple fallbacks
            this.password1 = this.findPasswordField1();
            this.password2 = this.findPasswordField2();
            this.messageDiv = $('#password-strength-message');
            this.submitButton = this.findSubmitButton();
            
            if (this.password1.length === 0) {
                console.log('Primäres Passwort-Feld nicht gefunden');
                return;
            }
            
            this.bindEvents();
            this.initialCheck();
        }
        
        findPasswordField1() {
            // Primary password field selectors - prioritize specified IDs
            const selectors = [
                '#password_1',
                '#pass1',
                'input[name="password_1"]',
                'input[name="pass1"]',
                '.woocommerce-ResetPassword input[type="password"]',
                '.password-input input[type="password"]'
            ];
            
            // Check for specific password fields first (these are always valid targets)
            for (let selector of selectors) {
                const field = $(selector);
                if (field.length > 0) {
                    return field.first();
                }
            }
            
            // For generic password fields, check if they're in a login form
            const genericSelectors = [
                '#password',
                'input[name="password"]'
            ];
            
            for (let selector of genericSelectors) {
                const field = $(selector);
                if (field.length > 0) {
                    // Check if this password field is NOT in a login form
                    if (!this.isInLoginForm(field)) {
                        return field.first();
                    }
                }
            }
            
            return $();
        }
        
        /**
         * Check if a password field is part of a login form
         */
        isInLoginForm(passwordField) {
            const form = passwordField.closest('form');
            if (form.length === 0) return false;
            
            // Check form classes that indicate login forms
            const loginFormClasses = [
                'login',
                'woocommerce-form-login',
                'loginform',
                'wp-login-form'
            ];
            
            for (let className of loginFormClasses) {
                if (form.hasClass(className)) {
                    return true;
                }
            }
            
            // Check for username/email field in the same form (login indicator)
            const hasUsernameField = form.find('input[name="username"], input[name="user_login"], input[name="email"], input[type="email"]').length > 0;
            
            // Check for login-specific submit buttons
            const hasLoginSubmit = form.find('input[name="login"], button[name="login"], input[value*="Login"], input[value*="Anmelden"], button[value*="Login"], button[value*="Anmelden"]').length > 0;
            
            // Check for "remember me" checkbox (common in login forms)
            const hasRememberMe = form.find('input[name="rememberme"], input[name="remember"]').length > 0;
            
            // If it has username field AND (login submit OR remember me), it's likely a login form
            if (hasUsernameField && (hasLoginSubmit || hasRememberMe)) {
                return true;
            }
            
            // Check for single password field with username (typical login pattern)
            const passwordFields = form.find('input[type="password"]');
            if (passwordFields.length === 1 && hasUsernameField) {
                return true;
            }
            
            return false;
        }
        
        findPasswordField2() {
            // Confirm password field selectors - prioritize specified IDs
            const selectors = [
                '#password_2',
                '#pass2',
                '#password-confirm',
                'input[name="password_2"]',
                'input[name="pass2"]',
                'input[name="password_confirm"]',
                '.woocommerce-ResetPassword input[type="password"]:eq(1)',
                '.password-input input[type="password"]:eq(1)'
            ];
            
            for (let selector of selectors) {
                const field = $(selector);
                if (field.length > 0) {
                    return field.first();
                }
            }
            
            return $();
        }
        
        findSubmitButton() {
            // Submit button selectors
            const selectors = [
                'input[type="submit"]',
                'button[type="submit"]',
                '.woocommerce-Button',
                '.wp-pwd button',
                '.button-primary',
                '.btn-primary'
            ];
            
            for (let selector of selectors) {
                const button = $(selector);
                if (button.length > 0) {
                    return button.first();
                }
            }
            
            return $();
        }
        
        bindEvents() {
            const self = this;
            
            // Bind to primary password field
            this.password1.on('input keyup paste', function() {
                self.validatePasswords();
            });
            
            // Bind to confirm password field if it exists
            if (this.password2.length > 0) {
                this.password2.on('input keyup paste', function() {
                    self.validatePasswords();
                });
            }
            
            // Prevent form submission if validation fails
            const form = this.password1.closest('form');
            if (form.length > 0) {
                form.on('submit', function(e) {
                    if (!self.isValid) {
                        e.preventDefault();
                        self.showMessage('Bitte erfüllen Sie alle Passwort-Anforderungen vor dem Absenden.', 'error');
                        return false;
                    }
                });
            }
        }
        
        initialCheck() {
            this.isValid = false;
            this.updateSubmitButton();
        }
        
        validatePasswords() {
            const password1 = this.password1.val() || '';
            const password2 = this.password2.length > 0 ? this.password2.val() || '' : password1;
            
            // Check password strength requirements
            const lengthValid = password1.length >= this.minLength;
            const numericCount = (password1.match(this.numericRegex) || []).length;
            const specialCount = (password1.match(this.specialCharRegex) || []).length;
            const numericValid = numericCount >= this.minNumeric;
            const specialValid = specialCount >= this.minSpecial;
            
            // Check if passwords match (if confirm field exists)
            const passwordsMatch = this.password2.length > 0 ? password1 === password2 : true;
            
            // Overall validation
            const strengthValid = lengthValid && numericValid && specialValid;
            this.isValid = strengthValid && passwordsMatch;
            
            // Update UI
            this.updateMessage(password1, lengthValid, numericValid, specialValid, passwordsMatch, numericCount, specialCount);
            this.updateSubmitButton();
        }
        
        updateMessage(password, lengthValid, numericValid, specialValid, passwordsMatch, numericCount, specialCount) {
            console.log('Deutsche Validierung läuft');
            
            if (!password) {
                this.showMessage(`Das Passwort muss mindestens ${this.minLength} Zeichen lang sein und ${this.minNumeric} Zahlen sowie ${this.minSpecial} Sonderzeichen enthalten.`, 'error');
                return;
            }
            
            const issues = [];
            
            if (!lengthValid) {
                issues.push(`mindestens ${this.minLength} Zeichen (aktuell ${password.length})`);
            }
            
            if (!numericValid) {
                issues.push(`mindestens ${this.minNumeric} Zahlen (aktuell ${numericCount})`);
            }
            
            if (!specialValid) {
                issues.push(`mindestens ${this.minSpecial} Sonderzeichen (aktuell ${specialCount})`);
            }
            
            if (this.password2.length > 0 && !passwordsMatch) {
                issues.push('Passwörter müssen übereinstimmen');
            }
            
            if (issues.length > 0) {
                this.showMessage(`Das Passwort benötigt: ${issues.join(', ')}.`, 'error');
            } else {
                this.showMessage('✓ Alle Anforderungen erfüllt!', 'success');
            }
        }
        
        showMessage(message, type) {
            if (this.messageDiv.length > 0) {
                const color = type === 'success' ? 'green' : 'red';
                this.messageDiv.css('color', color).text(message);
            }
        }
        
        updateSubmitButton() {
            if (this.submitButton.length > 0) {
                if (this.isValid) {
                    this.submitButton.prop('disabled', false).removeClass('submit-disabled');
                } else {
                    this.submitButton.prop('disabled', true).addClass('submit-disabled');
                }
            }
        }
    }
    
    // Initialize validator function
    function initializeValidator() {
        // Check if we already have a validator instance
        if (window.passwordValidatorInstance) {
            console.log('Passwort-Validator bereits vorhanden');
            return true;
        }
        
        // Make sure the configuration is available
        if (typeof window.passwordStrengthConfig === 'undefined') {
            console.log('Warte auf Passwort-Konfiguration...');
            return false;
        }
        
        // Clear any existing messages from other validators
        $('#password-strength-message').text('');
        
        // Create new validator instance
        window.passwordValidatorInstance = new PasswordResetValidator();
        console.log('Neuer deutscher Passwort-Validator erstellt');
        return true;
    }
    
    // Robust initialization with retry logic
    function tryInitialization() {
        var attempts = 0;
        var maxAttempts = 10;
        
        function attemptInit() {
            attempts++;
            
            if (initializeValidator()) {
                console.log('Passwort-Validator erfolgreich initialisiert');
                return;
            }
            
            if (attempts < maxAttempts) {
                setTimeout(attemptInit, 100 * attempts); // Increasing delay
            } else {
                console.log('Passwort-Validator konnte nicht initialisiert werden');
            }
        }
        
        attemptInit();
    }
    
    // Try to initialize on DOM ready
    $(document).ready(function() {
        tryInitialization();
    });
    
    // Also try on window load as backup
    $(window).on('load', function() {
        if (!window.passwordValidatorInstance) {
            tryInitialization();
        }
    });
    
    // Watch for dynamically loaded forms (AJAX, etc.)
    const observer = new MutationObserver(function(mutations) {
        let shouldReinitialize = false;
        
        mutations.forEach(function(mutation) {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(function(node) {
                    if (node.nodeType === 1) { // Element node
                        // Check if new node contains password inputs
                        const passwordInputs = $(node).find('input[type="password"]');
                        if (passwordInputs.length > 0) {
                            shouldReinitialize = true;
                        }
                    }
                });
            }
        });
        
        if (shouldReinitialize) {
            // Reinitialize after a short delay to allow DOM to settle
            setTimeout(function() {
                window.passwordValidatorInstance = null;
                initializeValidator();
            }, 100);
        }
    });
    
    // Start observing
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
}); 