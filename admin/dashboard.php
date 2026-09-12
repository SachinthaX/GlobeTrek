<?php

/*
 * GlobeTrek Adventures
 * Administrator Dashboard
 *
 * This page provides administrators with a summary of the
 * current system state and links to administrative functions.
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';

/*
 * Only administrators are allowed to access this page.
 */
requireRole('admin');

$errors = [];

$totalCustomers = 0;
$totalStaff = 0;
$totalPackages = 0;
$totalBookings = 0;
$totalRevenue = 0;


/*
 * ------------------------------------------------------------
 * Retrieve dashboard statistics
 * ------------------------------------------------------------
 */

try {

    /*
     * Count customers.
     */
    $customerStatement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = :role"
    );

    $customerStatement->execute([
        'role' => 'customer'
    ]);

    $totalCustomers =
        (int) $customerStatement->fetch()['total'];


    /*
     * Count staff members.
     */
    $staffStatement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = :role"
    );

    $staffStatement->execute([
        'role' => 'staff'
    ]);

    $totalStaff =
        (int) $staffStatement->fetch()['total'];


    /*
     * Count packages.
     */
    $packageStatement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM packages"
    );

    $packageStatement->execute();

    $totalPackages =
        (int) $packageStatement->fetch()['total'];


    /*
     * Count bookings.
     */
    $bookingStatement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM bookings"
    );

    $bookingStatement->execute();

    $totalBookings =
        (int) $bookingStatement->fetch()['total'];


    /*
     * Calculate revenue only from completed payments.
     *
     * Cancelled or pending payments should not be treated
     * as confirmed revenue.
     */
    $revenueStatement = $pdo->prepare(
        "SELECT COALESCE(SUM(amount), 0) AS total
         FROM payments
         WHERE payment_status = :payment_status"
    );

    $revenueStatement->execute([
        'payment_status' => 'completed'
    ]);

    $totalRevenue =
        (float) $revenueStatement->fetch()['total'];


} catch (PDOException $exception) {

    error_log(
        'Admin dashboard database error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Dashboard information could not be loaded.';
}


$pageTitle = 'Admin Dashboard - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<h1>Administrator Dashboard</h1>

<p>
    Welcome,
    <strong>
        <?= htmlspecialchars($_SESSION['full_name']) ?>
    </strong>.
</p>


<?php if (!empty($errors)): ?>

    <div class="alert alert-error">

        <ul>

            <?php foreach ($errors as $error): ?>

                <li>
                    <?= htmlspecialchars($error) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>


<section>

    <h2>System Overview</h2>

    <div class="dashboard-grid">

        <article class="dashboard-card">

            <h3>Total Customers</h3>

            <p class="dashboard-number">
                <?= $totalCustomers ?>
            </p>

        </article>


        <article class="dashboard-card">

            <h3>Total Staff</h3>

            <p class="dashboard-number">
                <?= $totalStaff ?>
            </p>

        </article>


        <article class="dashboard-card">

            <h3>Total Packages</h3>

            <p class="dashboard-number">
                <?= $totalPackages ?>
            </p>

        </article>


        <article class="dashboard-card">

            <h3>Total Bookings</h3>

            <p class="dashboard-number">
                <?= $totalBookings ?>
            </p>

        </article>


        <article class="dashboard-card">

            <h3>Completed Revenue</h3>

            <p class="dashboard-number">
                Rs.
                <?= number_format($totalRevenue, 2) ?>
            </p>

        </article>

    </div>

</section>


<section>

    <h2>Administrative Functions</h2>

    <div class="package-grid">

        <article class="package-card">

            <div class="package-card-content">

                <h3>Manage Staff</h3>

                <p>
                    Create, edit and remove staff accounts.
                </p>

                <a
                    href="manage_staff.php"
                    class="button"
                >
                    Manage Staff
                </a>

            </div>

        </article>


        <article class="package-card">

            <div class="package-card-content">

                <h3>Manage Bookings</h3>

                <p>
                    Oversee all customer bookings and their
                    current status.
                </p>

                <a
                    href="manage_bookings.php"
                    class="button"
                >
                    Manage Bookings
                </a>

            </div>

        </article>


        <article class="package-card">

            <div class="package-card-content">

                <h3>Reports</h3>

                <p>
                    Review sales and customer statistics using
                    database aggregate queries.
                </p>

                <a
                    href="reports.php"
                    class="button"
                >
                    View Reports
                </a>

            </div>

        </article>

    </div>

</section>


<?php

require_once '../includes/footer.php';

?>