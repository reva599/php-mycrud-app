    </main>
    
    <!-- Footer -->
    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>PHP CRUD Blog</h3>
                    <p>A comprehensive blog application built with PHP and MySQL as part of the ApexPlanet Internship program.</p>
                    <div class="social-links">
                        <a href="#" class="social-link" title="GitHub">
                            <i class="fab fa-github"></i>
                        </a>
                        <a href="#" class="social-link" title="LinkedIn">
                            <i class="fab fa-linkedin"></i>
                        </a>
                        <a href="#" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="<?php echo getBasePath(); ?>index.php">Home</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="<?php echo getBasePath(); ?>dashboard.php">Dashboard</a></li>
                            <li><a href="<?php echo getBasePath(); ?>posts/create.php">Create Post</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo getBasePath(); ?>auth/login.php">Login</a></li>
                            <li><a href="<?php echo getBasePath(); ?>auth/register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Features</h4>
                    <ul class="footer-links">
                        <li>User Authentication</li>
                        <li>CRUD Operations</li>
                        <li>Secure Password Hashing</li>
                        <li>Session Management</li>
                        <li>Responsive Design</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Technologies</h4>
                    <div class="tech-stack">
                        <span class="tech-badge">PHP</span>
                        <span class="tech-badge">MySQL</span>
                        <span class="tech-badge">HTML5</span>
                        <span class="tech-badge">CSS3</span>
                        <span class="tech-badge">JavaScript</span>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <div class="footer-bottom-content">
                    <p>&copy; <?php echo date('Y'); ?> PHP CRUD Blog - ApexPlanet Internship Project. Built with ❤️ for learning.</p>
                    <div class="footer-meta">
                        <span>Version 1.0.0</span>
                        <span>•</span>
                        <span>Last updated: <?php echo date('M Y'); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript -->
    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const navMenu = document.getElementById('nav-menu');
            navMenu.classList.toggle('active');
        }
        
        // User dropdown toggle
        function toggleUserMenu() {
            const dropdown = document.getElementById('user-dropdown');
            dropdown.classList.toggle('show');
        }
        
        // Close dropdown when clicking outside
        window.onclick = function(event) {
            if (!event.target.matches('.user-btn') && !event.target.closest('.user-btn')) {
                const dropdown = document.getElementById('user-dropdown');
                if (dropdown && dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        }
        
        // Auto-hide flash messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const flashMessages = document.querySelectorAll('.flash-message');
            flashMessages.forEach(function(message) {
                setTimeout(function() {
                    message.style.opacity = '0';
                    setTimeout(function() {
                        message.remove();
                    }, 300);
                }, 5000);
            });
        });
        
        // Form validation helper
        function validateForm(formId) {
            const form = document.getElementById(formId);
            const inputs = form.querySelectorAll('input[required], textarea[required]');
            let isValid = true;
            
            inputs.forEach(function(input) {
                if (!input.value.trim()) {
                    input.classList.add('error');
                    isValid = false;
                } else {
                    input.classList.remove('error');
                }
            });
            
            return isValid;
        }
        
        // Password strength indicator
        function checkPasswordStrength(password) {
            let strength = 0;
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            return strength;
        }
        
        // Confirm delete action
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }
        
        // Loading state for buttons
        function setLoadingState(button, loading = true) {
            if (loading) {
                button.disabled = true;
                button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
            } else {
                button.disabled = false;
                button.innerHTML = button.getAttribute('data-original-text') || 'Submit';
            }
        }
        
        // Character counter for textareas
        function setupCharacterCounter() {
            const textareas = document.querySelectorAll('textarea[data-max-length]');
            textareas.forEach(function(textarea) {
                const maxLength = parseInt(textarea.getAttribute('data-max-length'));
                const counter = document.createElement('div');
                counter.className = 'character-counter';
                textarea.parentNode.appendChild(counter);
                
                function updateCounter() {
                    const remaining = maxLength - textarea.value.length;
                    counter.textContent = remaining + ' characters remaining';
                    counter.className = 'character-counter ' + (remaining < 50 ? 'warning' : '');
                }
                
                textarea.addEventListener('input', updateCounter);
                updateCounter();
            });
        }
        
        // Initialize character counters on page load
        document.addEventListener('DOMContentLoaded', setupCharacterCounter);
    </script>
</body>
</html>
