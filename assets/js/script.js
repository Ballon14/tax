document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    const mobileMenu = document.getElementById('mobileMenu');
    
    if (mobileMenuButton && mobileMenu) {
        mobileMenuButton.addEventListener('click', function() {
            mobileMenu.classList.toggle('hidden');
            mobileMenu.classList.toggle('mobile-menu-open');
            
            // Toggle icon
            const icon = mobileMenuButton.querySelector('i');
            if (mobileMenu.classList.contains('hidden')) {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            } else {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            }
        });
    }
    
    // Theme Toggle
    const themeToggle = document.getElementById('themeToggle');
    const html = document.documentElement;
    
    function applyTheme(isDark) {
        if (isDark) {
            html.classList.add('dark');
            localStorage.setItem('darkMode', 'true');
            document.cookie = "darkMode=true; path=/; max-age=31536000";
        } else {
            html.classList.remove('dark');
            localStorage.setItem('darkMode', 'false');
            document.cookie = "darkMode=false; path=/; max-age=31536000";
        }
    }
    
    // Initialize theme
    const darkMode = localStorage.getItem('darkMode') === 'true' || 
                    (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches);
    applyTheme(darkMode);
    
    // Toggle theme
    if (themeToggle) {
        themeToggle.addEventListener('click', function() {
            const isDark = !html.classList.contains('dark');
            applyTheme(isDark);
        });
    }
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.group') && !e.target.closest('#mobileMenuButton')) {
            // Close all dropdowns
            document.querySelectorAll('.group .absolute').forEach(dropdown => {
                dropdown.classList.add('hidden');
            });
            
            // Close mobile menu if clicking outside
            if (mobileMenu && !mobileMenu.contains(e.target)) {
                mobileMenu.classList.add('hidden');
                mobileMenu.classList.remove('mobile-menu-open');
                const icon = mobileMenuButton.querySelector('i');
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        }
    });
});