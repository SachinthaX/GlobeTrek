<?php

/*
 * GlobeTrek Adventures
 * Shared navigation bar.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>

<header class="site-header">

    <div class="container">

        <div class="brand">

            <a href="/globetrek/index.php">
                GlobeTrek Adventures
            </a>

        </div>


        <nav
            class="main-navigation"
            aria-label="Main navigation"
        >

            <a href="/globetrek/index.php">
                Home
            </a>

            <a href="/globetrek/customer/packages.php">
                Packages
            </a>

            <a href="/globetrek/customer/contact.php">
                Contact
            </a>


            <?php if (isset($_SESSION['user_id'])): ?>

                <span class="nav-welcome">

                    Welcome,
                    <?= htmlspecialchars(
                        $_SESSION['full_name']
                    ) ?>

                </span>


                <?php if ($_SESSION['role'] === 'customer'): ?>

                    <a href="/globetrek/customer/my_bookings.php">
                        My Bookings
                    </a>

                    <a href="/globetrek/customer/profile.php">
                        Profile
                    </a>

                <?php elseif ($_SESSION['role'] === 'staff'): ?>

                    <a href="/globetrek/staff/dashboard.php">
                        Staff Dashboard
                    </a>

                <?php elseif ($_SESSION['role'] === 'admin'): ?>

                    <a href="/globetrek/admin/dashboard.php">
                        Admin Dashboard
                    </a>

                <?php endif; ?>


                <a href="/globetrek/logout.php">
                    Logout
                </a>


            <?php else: ?>

                <a href="/globetrek/customer/login.php">
                    Login
                </a>

                <a href="/globetrek/customer/register.php">
                    Register
                </a>

            <?php endif; ?>

        </nav>

    </div>

</header>