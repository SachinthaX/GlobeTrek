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


$pageTitle = 'My Bookings - GlobeTrek Adventures';

require_once '../includes/header.php';

?>


<h1>My Bookings</h1>


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

    <div class="package-grid">

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

            <article class="package-card">

                <div class="package-card-content">

                    <h2>
                        <?= htmlspecialchars($booking['title']) ?>
                    </h2>

                    <p>
                        <strong>Destination:</strong>
                        <?= htmlspecialchars($booking['destination']) ?>
                    </p>

                    <p>
                        <strong>Travel Date:</strong>
                        <?= htmlspecialchars($booking['travel_date']) ?>
                    </p>

                    <p>
                        <strong>Travelers:</strong>
                        <?= (int) $booking['num_travelers'] ?>
                    </p>

                    <p>
                        <strong>Total Price:</strong>
                        Rs.
                        <?= number_format(
                            (float) $booking['total_price'],
                            2
                        ) ?>
                    </p>

                    <p>

                        <strong>Booking Status:</strong>

                        <span
                            class="status-badge status-<?= htmlspecialchars($bookingStatus) ?>"
                        >
                            <?= htmlspecialchars(ucfirst($bookingStatus)) ?>
                        </span>

                    </p>


                    <?php if ($booking['payment_status']): ?>

                        <p>
                            <strong>Payment Status:</strong>
                            <?= htmlspecialchars(
                                ucfirst($booking['payment_status'])
                            ) ?>
                        </p>

                    <?php else: ?>

                        <p>
                            <strong>Payment Status:</strong>
                            Not submitted
                        </p>

                    <?php endif; ?>


                    <?php if ($booking['payment_method']): ?>

                        <p>
                            <strong>Payment Method:</strong>
                            <?= htmlspecialchars(
                                str_replace(
                                    '_',
                                    ' ',
                                    ucwords($booking['payment_method'])
                                )
                            ) ?>
                        </p>

                    <?php endif; ?>


                    <?php if (!empty($booking['customization_notes'])): ?>

                        <p>
                            <strong>Customization Notes:</strong>
                        </p>

                        <p>
                            <?= nl2br(
                                htmlspecialchars(
                                    $booking['customization_notes']
                                )
                            ) ?>
                        </p>

                    <?php endif; ?>


                    <?php if ($booking['status'] === 'pending'): ?>

                        <a
                            href="payment.php?booking_id=<?= (int) $booking['id'] ?>"
                            class="button"
                        >
                            Complete Payment
                        </a>

                    <?php endif; ?>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<?php

require_once '../includes/footer.php';

?>