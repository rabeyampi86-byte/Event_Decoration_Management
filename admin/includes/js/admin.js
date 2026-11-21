
document.addEventListener('DOMContentLoaded', function () {

    // =====================================
    // 1. SIDEBAR TOGGLE
    // =====================================

    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');

    if (sidebar && sidebarToggle) {

        sidebarToggle.addEventListener('click', function () {

            if (window.innerWidth <= 992) {
                sidebar.classList.toggle('show');
            } else {
                sidebar.classList.toggle('collapsed');

                if (mainContent) {
                    mainContent.classList.toggle('expanded');
                }
            }

        });

    }


    // =====================================
    // 2. SIDEBAR DROPDOWN
    // =====================================

    const dropdownButtons = document.querySelectorAll('.nav-dropdown');

    dropdownButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const submenu = button.nextElementSibling;

            if (!submenu || !submenu.classList.contains('nav-submenu')) {
                return;
            }

            // Close other dropdowns
            dropdownButtons.forEach(function (otherButton) {

                if (otherButton !== button) {

                    otherButton.classList.remove('active');

                    const otherSubmenu = otherButton.nextElementSibling;

                    if (otherSubmenu && otherSubmenu.classList.contains('nav-submenu')) {
                        otherSubmenu.classList.remove('show');
                    }

                }

            });

            // Toggle current dropdown
            button.classList.toggle('active');
            submenu.classList.toggle('show');

        });

    });


    // =====================================
    // 3. ACTIVE SIDEBAR LINK
    // =====================================

    const currentPage = window.location.pathname.split('/').pop();

    const sidebarLinks = document.querySelectorAll(
        '.sidebar-nav a.nav-link, .nav-submenu a'
    );

    sidebarLinks.forEach(function (link) {

        const linkPage = link.getAttribute('href');

        if (linkPage && linkPage === currentPage) {

            link.classList.add('active');

            // Open parent dropdown if submenu link
            const parentSubmenu = link.closest('.nav-submenu');

            if (parentSubmenu) {

                parentSubmenu.classList.add('show');

                const parentButton = parentSubmenu.previousElementSibling;

                if (parentButton && parentButton.classList.contains('nav-dropdown')) {
                    parentButton.classList.add('active');
                }

            }

        } else if (linkPage === 'admin_dashboard.php' && currentPage === '') {

            link.classList.add('active');

        }

    });


    // =====================================
    // 4. ADMIN PROFILE DROPDOWN
    // =====================================

    const profileDropdown = document.getElementById('profileDropdown');
    const profileToggle = document.getElementById('profileToggle');

    if (profileDropdown && profileToggle) {

        profileToggle.addEventListener('click', function (event) {

            event.stopPropagation();

            profileDropdown.classList.toggle('open');

        });

        // Close profile menu when clicking outside
        document.addEventListener('click', function (event) {

            if (!profileDropdown.contains(event.target)) {
                profileDropdown.classList.remove('open');
            }

        });

    }


    // =====================================
    // 5. CLOSE MOBILE SIDEBAR ON OUTSIDE CLICK
    // =====================================

    document.addEventListener('click', function (event) {

        if (!sidebar || window.innerWidth > 992) {
            return;
        }

        if (
            sidebar.classList.contains('show') &&
            !sidebar.contains(event.target) &&
            !sidebarToggle.contains(event.target)
        ) {

            sidebar.classList.remove('show');

        }

    });


    // =====================================
    // 6. HANDLE WINDOW RESIZE
    // =====================================

    window.addEventListener('resize', function () {

        if (!sidebar) {
            return;
        }

        if (window.innerWidth > 992) {
            sidebar.classList.remove('show');
        } else {
            sidebar.classList.remove('collapsed');

            if (mainContent) {
                mainContent.classList.remove('expanded');
            }
        }

    });


    // =====================================
    // 7. CONFIRM DELETE
    // =====================================

    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');

    deleteButtons.forEach(function (button) {

        button.addEventListener('click', function (event) {

            const message = button.getAttribute('data-confirm-delete') ||
                'Are you sure you want to delete this item?';

            if (!confirm(message)) {
                event.preventDefault();
            }

        });

    });


    // =====================================
    // 8. AUTO HIDE ALERT MESSAGES
    // =====================================

    const alerts = document.querySelectorAll('.alert[data-auto-hide]');

    alerts.forEach(function (alert) {

        const duration = parseInt(
            alert.getAttribute('data-auto-hide'),
            10
        ) || 4000;

        setTimeout(function () {

            alert.style.transition = 'opacity 0.4s ease';
            alert.style.opacity = '0';

            setTimeout(function () {
                alert.remove();
            }, 400);

        }, duration);

    });


    // =====================================
    // 9. IMAGE PREVIEW
    // =====================================

    const imageInputs = document.querySelectorAll('[data-image-preview]');

    imageInputs.forEach(function (input) {

        input.addEventListener('change', function () {

            const previewId = input.getAttribute('data-image-preview');
            const preview = document.getElementById(previewId);

            if (!preview || !input.files || !input.files[0]) {
                return;
            }

            const file = input.files[0];

            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file.');
                input.value = '';
                preview.removeAttribute('src');
                return;
            }

            preview.src = URL.createObjectURL(file);

        });

    });


    // =====================================
    // 10. SELECT ALL CHECKBOXES
    // =====================================

    const selectAll = document.getElementById('selectAll');
    const rowCheckboxes = document.querySelectorAll('.row-checkbox');

    if (selectAll) {

        selectAll.addEventListener('change', function () {

            rowCheckboxes.forEach(function (checkbox) {
                checkbox.checked = selectAll.checked;
            });

        });

    }

    rowCheckboxes.forEach(function (checkbox) {

        checkbox.addEventListener('change', function () {

            if (selectAll) {

                const allChecked = Array.from(rowCheckboxes).every(
                    function (item) {
                        return item.checked;
                    }
                );

                selectAll.checked = allChecked;

            }

        });

    });


    // =====================================
    // 11. TOOLTIP INITIALIZATION
    // =====================================

    const tooltipElements = document.querySelectorAll('[data-tooltip]');

    tooltipElements.forEach(function (element) {

        element.setAttribute(
            'title',
            element.getAttribute('data-tooltip')
        );

    });


    console.log('DecorEase Admin Panel Initialized Successfully.');

});
// Preserve Sidebar Scroll Position Between Pages
(function () {

    const sidebarNav = document.querySelector('.sidebar-nav');

    if (!sidebarNav) {
        return;
    }

    const storageKey = 'decorEaseSidebarScroll';

    // Restore previous scroll position
    const savedPosition = sessionStorage.getItem(storageKey);

    if (savedPosition !== null) {
        sidebarNav.scrollTop = parseInt(savedPosition, 10);
    }

    // Save scroll position before navigating
    sidebarNav.addEventListener('click', function (event) {

        const link = event.target.closest('a');

        if (link) {
            sessionStorage.setItem(
                storageKey,
                sidebarNav.scrollTop
            );
        }

    });

})();