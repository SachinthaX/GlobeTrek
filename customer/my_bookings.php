<?php

/*
 * GlobeTrek Adventures
 * Customer Booking Management
 *
 * Displays bookings belonging to the currently logged-in customer.
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireRole('customer');

$bookings = [];
$errors = [];
$successMessage = '';


/*
 * Display a message after successful payment.
 */
if (
    isset($_GET['payment'])
    && $_GET['payment'] === 'success'
) {

    $successMessage = 'Payment completed successfully. Your booking has been confirmed.';
}


/*
 * ------------------------------------------------------------
 * Load current customer's bookings
 * ------------------------------------------------------------
 */

try {

    $bookingStatement = $pdo->prepare(
        "SELECT
            b.id,
            b.travel_date,
            b.num_travelers,
            b.customization_notes,
            b.total_price,
            b.status,
            b.created_at,
            p.title,
            p.destination,
            pay.payment_status,
            pay.payment_method,
            pay.paid_at
         FROM bookings b
         INNER JOIN packages p
            ON b.package_id = p.id
         LEFT JOIN payments pay
            ON pay.id = (
                SELECT MAX(pay2.id)
                FROM payments pay2
                WHERE pay2.booking_id = b.id
            )
         WHERE b.user_id = :user_id
         ORDER BY b.created_at DESC"
    );

    $bookingStatement->execute([
        'user_id' => $_SESSION['user_id']
    ]);

    $bookings = $bookingStatement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'My bookings database error: '
        . $exception->getMessage()
    );

    $errors[] = 'Your bookings could not be loaded. Please try again later.';
}

$totalTrips = count($bookings);
$upcomingTrips = 0;
$confirmedTrips = 0;
$pendingTrips = 0;

foreach ($bookings as $bookingSummary) {

    if (
        $bookingSummary['travel_date'] >= date('Y-m-d')
        && $bookingSummary['status'] !== 'cancelled'
    ) {
        $upcomingTrips++;
    }

    if ($bookingSummary['status'] === 'confirmed') {
        $confirmedTrips++;
    }

    if ($bookingSummary['status'] === 'pending') {
        $pendingTrips++;
    }
}


$pageTitle = 'My Bookings - GlobeTrek Adventures';

require_once '../includes/header.php';

?>


<header class="bookings-intro">

    <p class="eyebrow">My Trips</p>

    <h1>My Bookings</h1>

    <p>
        Keep track of your adventures, payments, and travel plans.
    </p>

</header>


<?php if ($successMessage !== ''): ?>

    <div class="alert alert-success">

        <?= htmlspecialchars($successMessage) ?>

    </div>

<?php endif; ?>


<?php if (!empty($errors)): ?>

    <div class="alert alert-error">

        <h2>Error</h2>

        <ul>

            <?php foreach ($errors as $error): ?>

                <li>
                    <?= htmlspecialchars($error) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>


<?php if (!empty($bookings)): ?>

    <section
        class="booking-summary"
        aria-label="Booking summary"
    >

        <article class="booking-summary-card">
            <span>Total Trips</span>
            <strong><?= $totalTrips ?></strong>
        </article>

        <article class="booking-summary-card">
            <span>Upcoming</span>
            <strong><?= $upcomingTrips ?></strong>
        </article>

        <article class="booking-summary-card">
            <span>Confirmed</span>
            <strong><?= $confirmedTrips ?></strong>
        </article>

        <article class="booking-summary-card">
            <span>Pending</span>
            <strong><?= $pendingTrips ?></strong>
        </article>

    </section>

<?php endif; ?>


<?php if (empty($bookings)): ?>

    <div class="empty-state">

        <h2>No trips booked yet</h2>

        <p>
            Your next Sri Lankan adventure is waiting for you.
        </p>

        <a
            href="packages.php"
            class="button"
        >
            Browse Packages
        </a>

    </div>

<?php else: ?>

    <div class="booking-list">

        <?php foreach ($bookings as $booking): ?>

            <?php
            $bookingStatus = in_array(
                $booking['status'],
                ['pending', 'confirmed', 'cancelled'],
                true
            )
                ? $booking['status']
                : 'pending';
            ?>

            <?php
            $travelDateObject = DateTime::createFromFormat(
                'Y-m-d',
                $booking['travel_date']
            );

            $paymentStatus = $booking['payment_status'] ?? '';
            $paymentStatusClass = in_array(
                $paymentStatus,
                ['completed', 'pending', 'cancelled'],
                true
            )
                ? $paymentStatus
                : 'inactive';
            ?>

            <article class="booking-card">

                <div class="booking-card-header">

                    <div>

                        <p class="booking-kicker">
                            <?= htmlspecialchars($booking['destination']) ?>
                        </p>

                        <h2>
                            <?= htmlspecialchars($booking['title']) ?>
                        </h2>

                    </div>

                    <span class="booking-id">
                        Booking #<?= (int) $booking['id'] ?>
                    </span>

                </div>

                <div class="booking-card-body">

                    <div class="booking-date-panel">

                        <span class="booking-date-day">
                            <?= $travelDateObject
                                ? $travelDateObject->format('d')
                                : htmlspecialchars($booking['travel_date']) ?>
                        </span>

                        <?php if ($travelDateObject): ?>

                            <span>
                                <?= htmlspecialchars(
                                    strtoupper($travelDateObject->format('M'))
                                ) ?>
                            </span>

                            <span>
                                <?= $travelDateObject->format('Y') ?>
                            </span>

                        <?php endif; ?>

                    </div>

                    <div class="booking-details-grid">

                        <div class="booking-detail">
                            <span>Travel date</span>
                            <strong><?= htmlspecialchars($booking['travel_date']) ?></strong>
                        </div>

                        <div class="booking-detail">
                            <span>Travelers</span>
                            <strong><?= (int) $booking['num_travelers'] ?></strong>
                        </div>

                        <div class="booking-detail">
                            <span>Booking status</span>
                            <span class="status-badge status-<?= htmlspecialchars($bookingStatus) ?>">
                                <?= htmlspecialchars(ucfirst($bookingStatus)) ?>
                            </span>
                        </div>

                        <div class="booking-detail">
                            <span>Payment</span>

                            <?php if ($paymentStatus !== ''): ?>

                                <span class="status-badge status-<?= htmlspecialchars($paymentStatusClass) ?>">
                                    <?= htmlspecialchars(ucfirst($paymentStatus)) ?>
                                </span>

                            <?php else: ?>

                                <span class="status-badge status-inactive">
                                    Not submitted
                                </span>

                            <?php endif; ?>

                        </div>

                    </div>

                </div>

                <div class="booking-card-footer">

                    <div>

                        <?php if ($booking['payment_method']): ?>

                            <p class="booking-payment-method">
                                Payment method:
                                <strong>
                                    <?= htmlspecialchars(
                                        str_replace(
                                            '_',
                                            ' ',
                                            ucwords($booking['payment_method'])
                                        )
                                    ) ?>
                                </strong>
                            </p>

                        <?php endif; ?>

                        <?php if (!empty($booking['customization_notes'])): ?>

                            <div class="booking-notes">
                                <strong>Trip Notes</strong>
                                <p>
                                    <?= nl2br(
                                        htmlspecialchars(
                                            $booking['customization_notes']
                                        )
                                    ) ?>
                                </p>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="booking-total">
                        <span>Total price</span>
                        <strong>
                            Rs.
                            <?= number_format(
                                (float) $booking['total_price'],
                                2
                            ) ?>
                        </strong>

                        <?php if ($booking['status'] === 'pending'): ?>

                            <a
                                href="payment.php?booking_id=<?= (int) $booking['id'] ?>"
                                class="button"
                            >
                                Complete Payment
                            </a>

                        <?php endif; ?>

                    </div>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<?php

require_once '../includes/footer.php';

?>