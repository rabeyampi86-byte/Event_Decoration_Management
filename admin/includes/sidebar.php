
<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<aside class="sidebar" id="sidebar">

    <!-- Logo -->
    <div class="sidebar-brand">
        <div class="brand-icon">
            <i class="fas fa-gem"></i>
        </div>
        <div class="brand-text">
            <h2>DecorEase</h2>
            <span>Admin Panel</span>
        </div>
    </div>

    <!-- Sidebar Navigation -->
    <nav class="sidebar-nav">

        <!-- Main Menu -->
        <div class="nav-section">
            <span class="nav-section-title">MAIN MENU</span>

            <a href="admin_dashboard.php"
               class="nav-link <?php echo ($currentPage == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Website Management -->
        <div class="nav-section">
            <span class="nav-section-title">WEBSITE MANAGEMENT</span>

            <button class="nav-dropdown" type="button">
                <span>
                    <i class="fas fa-globe"></i>
                    <span>Website Content</span>
                </span>
                <i class="fas fa-chevron-down arrow"></i>
            </button>

            <div class="nav-submenu">
                <a href="pages.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Pages</span>
                </a>
                <a href="page_sections.php">
                    <i class="fas fa-layer-group"></i>
                    <span>Page Sections</span>
                </a>
                <a href="banners.php">
                    <i class="fas fa-images"></i>
                    <span>Banners</span>
                </a>
                <a href="navigation.php">
                    <i class="fas fa-bars"></i>
                    <span>Navigation Menu</span>
                </a>
                <a href="media.php">
                    <i class="fas fa-photo-video"></i>
                    <span>Media Library</span>
                </a>
                <a href="site-settings.php">Site Settings</a>
            </div>

            <a href="team_members.php"
   class="nav-link <?php echo (
       $currentPage == 'team_members.php' ||
       $currentPage == 'add_team_member.php' ||
       $currentPage == 'view_team_member.php' ||
       $currentPage == 'edit_team_member.php' ||
       $currentPage == 'delete_team_member.php'
   ) ? 'active' : ''; ?>">

    <i class="fas fa-users"></i>
    <span>Team Members</span>
</a>

            <a href="gallery_albums.php" class="nav-link">
    <i class="fas fa-folder-open"></i>
    <span>Gallery Albums</span>
</a>

<a href="gallery_images.php" class="nav-link">
    <i class="fas fa-images"></i>
    <span>Gallery Images</span>
</a>

            <a href="testimonials.php" class="nav-link">
                <i class="fas fa-comment-dots"></i>
                <span>Testimonials</span>
            </a>
        </div>

        <!-- Event Management -->
        <div class="nav-section">
            <span class="nav-section-title">EVENT MANAGEMENT</span>

            <button class="nav-dropdown" type="button">
                <span>
                    <i class="fas fa-calendar-alt"></i>
                    <span>Events & Packages</span>
                </span>
                <i class="fas fa-chevron-down arrow"></i>
            </button>

            <div class="nav-submenu">
                <a href="event_categories.php"
                    class="nav-link <?php echo ($currentPage == 'event_categories.php') ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i>
                    <span>Event Categories</span>
                </a>
                <a href="packages.php"
                    class="nav-link <?php echo ($currentPage == 'packages.php' || $currentPage == 'add_package.php' || $currentPage == 'edit_package.php') ? 'active' : ''; ?>">
                    <i class="fas fa-box-open"></i>
                    <span>Packages</span>
                </a>
                <a href="package_details.php"
                    class="nav-link <?php echo (
                    $currentPage == 'package_details.php' ||
                    $currentPage == 'add_package_details.php' ||
                    $currentPage == 'edit_package_details.php'
                    ) ? 'active' : ''; ?>">

                    <i class="fas fa-file-alt"></i>
                    <span>Package Details</span>
                </a>
                <a href="venues.php"
                    class="nav-link <?php echo (
                    $currentPage == 'venues.php' ||
                    $currentPage == 'add_venue.php' ||
                    $currentPage == 'edit_venue.php'
                    ) ? 'active' : ''; ?>">

                    <i class="fas fa-map-marker-alt"></i>
                    <span>Event Venues</span>
                </a>
            </div>

            <button class="nav-dropdown" type="button">
                <span>
                    <i class="fas fa-palette"></i>
                    <span>Decoration Resources</span>
                </span>
                <i class="fas fa-chevron-down arrow"></i>
            </button>

            <div class="nav-submenu">
                <a href="decoration_areas.php">
                    <i class="fas fa-map-marked-alt"></i>
                    <span>Decoration Areas</span>
                </a>
                <a href="flowers.php">
                    <i class="fas fa-spa"></i>
                    <span>Flowers</span>
                </a>
                <a href="flower_designs.php">
                    <i class="fas fa-palette"></i>
                    <span>Flower Designs</span>
                </a>
                <a href="lighting_equipment.php">
                    <i class="fas fa-lightbulb"></i>
                    <span>Lighting Equipment</span>
                </a>
                <a href="food_menu_items.php">
                    <i class="fas fa-utensils"></i>
                    <span>Food Menu</span>
                </a>
                <a href="facilities.php">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Facilities</span>
                </a>
                <a href="inventory.php">
                    <i class="fas fa-boxes-stacked"></i>
                    <span>Inventory</span>
                </a>
                <a href="vendors.php">
                    <i class="fas fa-truck-field"></i>
                    <span>Vendors</span>
                </a>
            </div>
        </div>

        <!-- Business Management -->
        <div class="nav-section">
            <span class="nav-section-title">BUSINESS MANAGEMENT</span>

            <a href="users.php"
                class="nav-link <?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i>
                <span>Users</span>
            </a>

            <a href="bookings.php"
               class="nav-link <?php echo in_array($currentPage, ['booking.php', 'bookings.php', 'booking_form.php', 'booking_details.php']) ? 'active' : ''; ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Bookings</span>
            </a>

            <a href="payments.php" class="nav-link">
                <i class="fas fa-wallet"></i>
                <span>Payments</span>
            </a>
            <a href="payment-receipt.php" class="nav-link">
                <i class="fas fa-file-invoice-dollar"></i>
                <span>Payment Receipt</span>
            </a>
            <a href="invoices.php" class="nav-link">
                <i class="fas fa-file-invoice"></i>
                <span>Invoices</span>
            </a>

            <a href="reviews.php" class="nav-link">
                <i class="fas fa-star"></i>
                <span>Reviews</span>
            </a>

            <a href="contact_messages.php" class="nav-link">
                <i class="fas fa-envelope"></i>
                <span>Contact Messages</span>
            </a>
        </div>

        <!-- Team & Operations -->
        <div class="nav-section">
            <span class="nav-section-title">TEAM & OPERATIONS</span>

            <a href="employees.php"
                class="nav-link <?php echo ($currentPage == 'employees.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-tie"></i>
                <span>Employees</span>
            </a>

            
<a href="team_roles.php"
   class="nav-link <?php echo (
       $currentPage == 'team_roles.php' ||
       $currentPage == 'add_team_role.php' ||
       $currentPage == 'view_team_role.php' ||
       $currentPage == 'edit_team_role.php' ||
       $currentPage == 'delete_team_role.php'
   ) ? 'active' : ''; ?>">

    <i class="fas fa-user-tag"></i>
    <span>Team Roles</span>
</a>


            
<a href="event_tasks.php"
   class="nav-link <?php echo (
       $currentPage == 'event_tasks.php' ||
       $currentPage == 'add_event_task.php' ||
       $currentPage == 'view_event_task.php' ||
       $currentPage == 'edit_event_task.php' ||
       $currentPage == 'delete_event_task.php'
   ) ? 'active' : ''; ?>">

    <i class="fas fa-tasks"></i>
    <span>Event Tasks</span>
</a>


           <a href="task_assignments.php"
   class="nav-link <?php echo (
       $currentPage == 'task_assignments.php' ||
       $currentPage == 'add_task_assignment.php' ||
       $currentPage == 'view_task_assignment.php' ||
       $currentPage == 'edit_task_assignment.php' ||
       $currentPage == 'delete_task_assignment.php'
   ) ? 'active' : ''; ?>">
    <i class="fas fa-user-check"></i>
    <span>Task Assignments</span>
</a>
<a href="task_updates.php"
   class="nav-link <?php echo (
       $currentPage == 'task_updates.php' ||
       $currentPage == 'add_task_update.php' ||
       $currentPage == 'view_task_update.php' ||
       $currentPage == 'edit_task_update.php' ||
       $currentPage == 'delete_task_update.php'
   ) ? 'active' : ''; ?>">
    <i class="fas fa-clipboard-check"></i>
    <span>Task Updates</span>
</a>

        <!-- Reports & System -->
        <div class="nav-section">
            <span class="nav-section-title">REPORTS & SYSTEM</span>

    <a href="reports.php" class="nav-link">
        <i class="fas fa-chart-line"></i>
        <span>Business Overview</span>
    </a>

    <a href="booking-progress.php" class="nav-link">
        <i class="fas fa-tasks"></i>
        <span>Booking Progress</span>
    </a>

    <a href="package-performance.php" class="nav-link">
        <i class="fas fa-box"></i>
        <span>Package Performance</span>
    </a>

    <a href="income-reports.php" class="nav-link">
        <i class="fas fa-wallet"></i>
        <span>Income & Payments</span>
    </a>

    <a href="expense-profit.php" class="nav-link">
        <i class="fas fa-calculator"></i>
        <span>Expense & Profit</span>
    </a>

            <a href="notifications.php" class="nav-link">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>

            <a href="activity_logs.php" class="nav-link">
                <i class="fas fa-history"></i>
                <span>Activity Logs</span>
            </a>
        </div>

    </nav>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
        <div class="sidebar-footer-content">
            <i class="fas fa-shield-alt"></i>
            <div>
                <strong>DecorEase Admin</strong>
                <small>Management System</small>
            </div>
        </div>
    </div>

</aside>
