
<?php
require_once __DIR__ . '/layout-start.php';

// Fixed Admin Information
$admin = [
    'user_id' => 'Demo Admin',
    'full_name' => $_SESSION['user_name'] ?? 'Admin',
    'email' => $_SESSION['user_email'] ?? 'admin@gmail.com',
    'phone' => 'Not Set',
    'status' => 'active'
];

?>

<style>
.settings-grid {
    display: grid;
    grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr);
    gap: 18px;
    align-items: start;
}

.settings-card {
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 11px;
    padding: 22px;
    margin-bottom: 18px;
}

.settings-card h3 {
    font-size: 15px;
    margin: 0;
}

.settings-description {
    color: var(--muted);
    font-size: 11px;
    line-height: 1.6;
    margin: 7px 0 20px;
}

.settings-info {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid var(--border);
    font-size: 11px;
}

.settings-info:last-child {
    border-bottom: 0;
}

.settings-info span:first-child {
    color: var(--muted);
}

.settings-info strong {
    color: var(--text);
    text-align: right;
    overflow-wrap: anywhere;
}

.settings-icon {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    display: grid;
    place-items: center;
    background: #d01fe626;
    color: #e84bf2;
    font-size: 18px;
    margin-bottom: 13px;
}

@media(max-width:850px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="page-heading">
    <div>
        <h1>Settings</h1>
        <p>Manage your administrator profile and account information.</p>
    </div>
    <span class="date-label">⚙ Account Settings</span>
</section>

<div class="settings-grid">

    <div>

        <!-- Admin Profile -->
        <section class="settings-card">

            <div class="settings-icon">♙</div>

            <h3>Admin Profile</h3>

            <p class="settings-description">
                Administrator information for this demonstration project.
            </p>

            <div class="settings-info">
                <span>Full Name</span>
                <strong><?= h($admin['full_name']) ?></strong>
            </div>

            <div class="settings-info">
                <span>Email Address</span>
                <strong><?= h($admin['email']) ?></strong>
            </div>

            <div class="settings-info">
                <span>Phone Number</span>
                <strong><?= h($admin['phone']) ?></strong>
            </div>

        </section>

        <!-- Login Credentials -->
        <section class="settings-card">

            <div class="settings-icon">🔒</div>

            <h3>Login Credentials</h3>

            <p class="settings-description">
                This project uses fixed administrator credentials
                for demonstration purposes.
            </p>

            <div class="settings-info">
                <span>Login Email</span>
                <strong>admin@gmail.com</strong>
            </div>

            <div class="settings-info">
                <span>Password</span>
                <strong>Configured in Login File</strong>
            </div>

            <div class="settings-info">
                <span>Authentication</span>
                <strong>Fixed Admin Login</strong>
            </div>

        </section>

    </div>

    <div>

        <!-- Account Information -->
        <section class="settings-card">

            <div class="settings-icon">ℹ</div>

            <h3>Account Information</h3>

            <p class="settings-description">
                A quick overview of your administrator account.
            </p>

            <div class="settings-info">
                <span>Account ID</span>
                <strong>Demo Admin</strong>
            </div>

            <div class="settings-info">
                <span>Account Type</span>
                <strong>Administrator</strong>
            </div>

            <div class="settings-info">
                <span>Account Status</span>
                <strong>
                    <span class="status active">Active</span>
                </strong>
            </div>

            <div class="settings-info">
                <span>Database</span>
                <strong>event_decoration_db</strong>
            </div>

            <div class="settings-info">
                <span>Database Engine</span>
                <strong>MySQL / InnoDB</strong>
            </div>

        </section>

        <!-- Security Tips -->
        <section class="settings-card">

            <div class="settings-icon">🛡</div>

            <h3>Security Tips</h3>

            <p class="settings-description">
                Keep your administrator access secure.
            </p>

            <ul style="color:#c9bacf;font-size:11px;line-height:2;padding-left:18px;margin-bottom:0">

                <li>Do not share your administrator password.</li>

                <li>Always log out after using the admin panel.</li>

                <li>Use the correct login credentials.</li>

                <li>Do not leave your account open on shared computers.</li>

            </ul>

        </section>

    </div>

</div>

<?php require __DIR__ . '/layout-end.php'; ?>
