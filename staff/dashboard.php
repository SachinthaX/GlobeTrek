<?php

/*
 * GlobeTrek Adventures
 * Staff Dashboard
 *
 * Provides staff members with a quick overview of:
 * - Active travel packages
 * - Total bookings
 * - Pending bookings
 * - Open customer queries
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';

/*
 * Only authenticated staff members can access this page.
 */
requireRole('staff');

$activePackages = 0;
$totalBookings = 0;
$pendingBookings = 0;
$openQueries = 0;
$errors = [];

/*
 * Retrieve dashboard summary information.
 *
 * Each query is kept simple and uses prepared statements.
 */
try {

    $packageStatement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM packages
         WHERE status = :status"
    );

    $packageStatement->execute([
        'status' => 'active'
    ]);

    $activePackages = (int) $packageStatement->fetch()['total'];


    $bookingStatement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM bookings"
    );

    $bookingStatement->execute();

    $totalBookings = (int) $bookingStatement->fetch()['total'];


    $pendingBookingStatement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM bookings
         WHERE status = :status"
    );

    $pendingBookingStatement->execute([
        'status' => 'pending'
    ]);

    $pendingBookings =
        (int) $pendingBookingStatement->fetch()['total'];


    $queryStatement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM queries
         WHERE status = :status"
    );

    $queryStatement->execute([
        'status' => 'open'
    ]);

    $openQueries = (int) $queryStatement->fetch()['total'];

} catch (PDOException $exception) {

    error_log(
        'Staff dashboard database error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Dashboard information could not be loaded.';
}


$pageTitle = 'Staff Dashboard - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<header class="page-header workspace-header">
    <p class="eyebrow">Operations workspace</p>
    <h1>Staff Dashboard</h1>
</header>

<p>
    Welcome,
    <strong>
        <?= htmlspecialchars($_SESSION['full_name']) ?>
    </strong>.
</p>


<?php if (!empty($errors)): ?>

    <div class="alert alert-error">

        <?php foreach ($errors as $error): ?>

            <p>
                <?= htmlspecialchars($error) ?>
            </p>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<section class="content-section">

    <h2>System Overview</h2>

    <div class="dashboard-grid">

        <article class="dashboard-card">

            <h3>Active Packages</h3>

            <p class="dashboard-number">
                <?= $activePackages ?>
            </p>

        </article>


        <article class="dashboard-card">

            <h3>Total Bookings</h3>

            <p class="dashboard-number">
                <?= $totalBookings ?>
            </p>

        </article>


        <article class="dashboard-card">

            <h3>Pending Bookings</h3>

            <p class="dashboard-number">
                <?= $pendingBookings ?>
            </p>

        </article>


        <article class="dashboard-card">

            <h3>Open Queries</h3>

            <p class="dashboard-number">
                <?= $openQueries ?>
            </p>

        </article>

    </div>

</section>


<section class="content-section">

    <h2>Staff Actions</h2>

    <div class="package-grid">

        <article class="package-card">

            <div class="package-card-content">

                <h3>Manage Packages</h3>

                <p>
                    Add, edit and remove travel packages,
                    accommodation and transportation information.
                </p>

                <a
                    href="manage_packages.php"
                    class="button"
                >
                    Manage Packages
                </a>

            </div>

        </article>


        <article class="package-card">

            <div class="package-card-content">

                <h3>Manage Bookings</h3>

                <p>
                    View customer bookings and update their
                    booking status.
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

                <h3>Customer Queries</h3>

                <p>
                    Review customer questions and send responses.
                </p>

                <a
                    href="queries.php"
                    class="button"
                >
                    View Queries
                </a>

            </div>

        </article>

    </div>

</section>


<?php

require_once '../includes/footer.php';

?>