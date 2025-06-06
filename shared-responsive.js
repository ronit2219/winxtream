// Shared Responsive JavaScript for WinXtream Game Pages

document.addEventListener('DOMContentLoaded', function() {
    // Add Font Awesome if it's not already in the document
    if (!document.querySelector('link[href*="font-awesome"]')) {
        const fontAwesomeLink = document.createElement('link');
        fontAwesomeLink.rel = 'stylesheet';
        fontAwesomeLink.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
        document.head.appendChild(fontAwesomeLink);
    }

    const navbar = document.querySelector('.navbar');
    if (navbar) {
        // Create and append the mobile menu toggle button if it doesn't exist
        if (!document.querySelector('.mobile-menu-toggle')) {
            const mobileMenuToggle = document.createElement('button');
            mobileMenuToggle.className = 'mobile-menu-toggle';
            mobileMenuToggle.id = 'mobile-menu-toggle';
            mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            
            // Find the logo section and insert the toggle button before it
            const logoSection = navbar.querySelector('.logo-section');
            if (logoSection) {
                logoSection.prepend(mobileMenuToggle);
            } else {
                navbar.prepend(mobileMenuToggle);
            }
        }
        
        // Set up event listeners for mobile menu
        const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
        const navLinks = document.querySelector('.nav-links');
        
        if (mobileMenuToggle && navLinks) {
            mobileMenuToggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                navLinks.classList.toggle('active');
                
                // Change icon based on menu state
                const icon = mobileMenuToggle.querySelector('i');
                if (icon) {
                    if (navLinks.classList.contains('active')) {
                        icon.className = 'fas fa-times';
                    } else {
                        icon.className = 'fas fa-bars';
                    }
                }
            });
            
            // Close menu when clicking on a link
            const links = navLinks.querySelectorAll('a');
            links.forEach(link => {
                link.addEventListener('click', function() {
                    navLinks.classList.remove('active');
                    const icon = mobileMenuToggle.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-bars';
                    }
                });
            });
            
            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                if (navLinks.classList.contains('active') && 
                    !navLinks.contains(e.target) && 
                    e.target !== mobileMenuToggle && 
                    !mobileMenuToggle.contains(e.target)) {
                    navLinks.classList.remove('active');
                    const icon = mobileMenuToggle.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-bars';
                    }
                }
            });
        }
    }

    // Additional touch-friendly adjustments for mobile
    const makeElementsTouchFriendly = () => {
        // Make sure all interactive elements have appropriate sizing
        const interactiveElements = document.querySelectorAll('button, input, select, a');
        interactiveElements.forEach(el => {
            if (window.innerWidth <= 767) {
                if (el.tagName === 'BUTTON' || el.tagName === 'A') {
                    el.style.minHeight = '44px';
                }
                if (el.tagName === 'INPUT') {
                    el.style.minHeight = '40px';
                }
            }
        });
    };

    // Run once on load
    makeElementsTouchFriendly();
    
    // Run on window resize
    window.addEventListener('resize', makeElementsTouchFriendly);
}); 