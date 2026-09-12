<?php

/*
 * GlobeTrek Adventures
 * Administrator Reports
 *
 * Uses SQL aggregate functions to generate:
 * - Revenue summary
 * - Booking summary
 * - Monthly booking statistics
 * - Most-booked package
 * - Customer statistics
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireRole('admin');

$errors = [];

$totalRevenue = 0;
$totalCompletedPayments = 0;
$totalBookings = 0;
$totalCustomers = 0;
$newCustomersThisMonth = 0;

$monthlyBookings = [];
$packagePopularity = [];
$bookingStatusSummary = [];


/*
 * ------------------------------------------------------------
 * Revenue summary
 * ------------------------------------------------------------
 */

try {

    $statement = $pdo->prepare(
        "SELECT
            COALESCE(SUM(amount), 0) AS total_revenue,
            COUNT(*) AS completed_payments
         FROM payments
         WHERE payment_status = :payment_status"
    );

    $statement->execute([
        'payment_status' => 'completed'
    ]);

    $revenueData = $statement->fetch();

    $totalRevenue =
        (float) $revenueData['total_revenue'];

    $totalCompletedPayments =
        (int) $revenueData['completed_payments'];

} catch (PDOException $exception) {

    error_log(
        'Revenue report database error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Revenue report could not be generated.';
}


/*
 * ------------------------------------------------------------
 * Booking summary
 * ------------------------------------------------------------
 */

try {

    $statement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM bookings"
    );

    $statement->execute();

    $totalBookings =
        (int) $statement->fetch()['total'];

} catch (PDOException $exception) {

    error_log(
        'Booking report database error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Booking report could not be generated.';
}


/*
 * ------------------------------------------------------------
 * Customer statistics
 * ------------------------------------------------------------
 */

try {

    $statement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = :role"
    );

    $statement->execute([
        'role' => 'customer'
    ]);

    $totalCustomers =
        (int) $statement->fetch()['total'];


    /*
     * Count new customers created during the current month.
     */
    $monthStatement = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM users
         WHERE role = :role
           AND YEAR(created_at) = YEAR(CURRENT_DATE)
           AND MONTH(created_at) = MONTH(CURRENT_DATE)"
    );

    $monthStatement->execute([
        'role' => 'customer'
    ]);

    $newCustomersThisMonth =
        (int) $monthStatement->fetch()['total'];

} catch (PDOException $exception) {

    error_log(
        'Customer report database error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Customer report could not be generated.';
}


/*
 * ------------------------------------------------------------
 * Bookings per month
 * ------------------------------------------------------------
 */

try {

    $statement = $pdo->prepare(
        "SELECT
            DATE_FORMAT(created_at, '%Y-%m') AS booking_month,
            COUNT(*) AS booking_count
         FROM bookings
         GROUP BY YEAR(created_at), MONTH(created_at)
         ORDER BY YEAR(created_at) DESC,
                  MONTH(created_at) DESC"
    );

    $statement->execute();

    $monthlyBookings =
        $statement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Monthly booking report error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Monthly booking report could not be generated.';
}


/*
 * ------------------------------------------------------------
 * Most-booked packages
 * ------------------------------------------------------------
 */

try {

    $statement = $pdo->prepare(
        "SELECT
            p.title,
            p.destination,
            COUNT(b.id) AS booking_count
         FROM packages p
         LEFT JOIN bookings b
            ON p.id = b.package_id
         GROUP BY
            p.id,
            p.title,
            p.destination
         ORDER BY booking_count DESC,
                  p.title ASC"
    );

    $statement->execute();

    $packagePopularity =
        $statement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Package popularity report error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Package popularity report could not be generated.';
}


/*
 * ------------------------------------------------------------
 * Booking status summary
 * ------------------------------------------------------------
 */

try {

    $statement = $pdo->prepare(
        "SELECT
            status,
            COUNT(*) AS total
         FROM bookings
         GROUP BY status
         ORDER BY total DESC"
    );

    $statement->execute();

    $bookingStatusSummary =
        $statement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Booking status report error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Booking status report could not be generated.';
}


$pageTitle = 'Reports - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<h1>Sales and Customer Reports</h1>


<?php if (!empty($errors)): ?>

    <div class="alert alert-error">

        <h2>Report Warning</h2>

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

    <h2>Revenue Summary</h2>

    <div class="package-grid">

        <article class="package-card">

            <div class="package-card-content">

                <h3>Total Completed Revenue</h3>

                <p class="package-price">
                    Rs.
                    <?= number_format(
                        $totalRevenue,
                        2
                    ) ?>
                </p>

            </div>

        </article>


        <article class="package-card">

            <div class="package-card-content">

                <h3>Completed Payments</h3>

                <p class="package-price">
                    <?= $totalCompletedPayments ?>
                </p>

            </div>

        </article>


        <article class="package-card">

            <div class="package-card-content">

                <h3>Total Bookings</h3>

                <p class="package-price">
                    <?= $totalBookings ?>
                </p>

            </div>

        </article>


        <article class="package-card">

            <div class="package-card-content">

                <h3>Total Customers</h3>

                <p class="package-price">
                    <?= $totalCustomers ?>
                </p>

            </div>

        </article>


        <article class="package-card">

            <div class="package-card-content">

                <h3>New Customers This Month</h3>

                <p class="package-price">
                    <?= $newCustomersThisMonth ?>
                </p>

            </div>

        </article>

    </div>

</section>


<section>

    <h2>Bookings Per Month</h2>

    <?php if (empty($monthlyBookings)): ?>

        <div class="empty-state">

            <p>
                No booking information is available.
            </p>

        </div>

    <?php else: ?>

        <div class="package-grid">

            <?php foreach ($monthlyBookings as $month): ?>

                <article class="package-card">

                    <div class="package-card-content">

                        <h3>
                            <?= htmlspecialchars(
                                $month['booking_month']
                            ) ?>
                        </h3>

                        <p>
                            <strong>Bookings:</strong>
                            <?= (int) $month['booking_count'] ?>
                        </p>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<section>

    <h2>Most-Booked Packages</h2>

    <?php if (empty($packagePopularity)): ?>

        <div class="empty-state">

            <p>
                No package booking information is available.
            </p>

        </div>

    <?php else: ?>

        <div class="package-grid">

            <?php foreach ($packagePopularity as $package): ?>

                <article class="package-card">

                    <div class="package-card-content">

                        <h3>
                            <?= htmlspecialchars(
                                $package['title']
                            ) ?>
                        </h3>

                        <p>
                            <strong>Destination:</strong>
                            <?= htmlspecialchars(
                                $package['destination']
                            ) ?>
                        </p>

                        <p class="package-price">
                            <?= (int) $package['booking_count'] ?>
                            booking(s)
                        </p>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<section>

    <h2>Booking Status Summary</h2>

    <?php if (empty($bookingStatusSummary)): ?>

        <div class="empty-state">

            <p>
                No booking status information is available.
            </p>

        </div>

    <?php else: ?>

        <div class="package-grid">

            <?php foreach ($bookingStatusSummary as $status): ?>

                <article class="package-card">

                    <div class="package-card-content">

                        <h3>
                            <?= htmlspecialchars(
                                ucfirst($status['status'])
                            ) ?>
                        </h3>

                        <p class="package-price">
                            <?= (int) $status['total'] ?>
                        </p>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<?php

require_once '../includes/footer.php';

?>