<?php

/*
 * GlobeTrek Adventures
 * Administrator Booking Management
 *
 * Allows administrators to oversee all bookings and update
 * their status.
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireRole('admin');

$errors = [];
$successMessage = '';

/*
 * ------------------------------------------------------------
 * UPDATE BOOKING STATUS
 * ------------------------------------------------------------
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update_status'])
) {

    $bookingId = filter_input(
        INPUT_POST,
        'booking_id',
        FILTER_VALIDATE_INT
    );

    $status = $_POST['status'] ?? '';

    $allowedStatuses = [
        'pending',
        'confirmed',
        'cancelled'
    ];


    if (
        $bookingId === false
        || $bookingId === null
        || $bookingId <= 0
    ) {

        $errors[] =
            'Invalid booking selected.';

    } elseif (!in_array($status, $allowedStatuses, true)) {

        $errors[] =
            'Invalid booking status.';

    } else {

        try {

            $statement = $pdo->prepare(
                "UPDATE bookings
                 SET status = :status
                 WHERE id = :id"
            );

            $statement->execute([
                'status' => $status,
                'id' => $bookingId
            ]);

            $successMessage =
                'Booking status updated successfully.';

        } catch (PDOException $exception) {

            error_log(
                'Admin booking status error: '
                . $exception->getMessage()
            );

            $errors[] =
                'Booking status could not be updated.';
        }
    }
}


/*
 * ------------------------------------------------------------
 * LOAD ALL BOOKINGS
 * ------------------------------------------------------------
 */

$bookings = [];

try {

    $statement = $pdo->prepare(
        "SELECT
            b.id,
            b.travel_date,
            b.num_travelers,
            b.customization_notes,
            b.total_price,
            b.status,
            b.created_at,

            u.full_name AS customer_name,
            u.email AS customer_email,
            u.phone AS customer_phone,

            p.title AS package_title,
            p.destination,

            pay.payment_method,
            pay.payment_status,
            pay.paid_at

         FROM bookings b

         INNER JOIN users u
            ON b.user_id = u.id

         INNER JOIN packages p
            ON b.package_id = p.id

         LEFT JOIN payments pay
            ON pay.id = (
                SELECT MAX(pay2.id)
                FROM payments pay2
                WHERE pay2.booking_id = b.id
            )

         ORDER BY b.created_at DESC"
    );

    $statement->execute();

    $bookings = $statement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Admin bookings database error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Bookings could not be loaded.';
}


$pageTitle = 'Admin Booking Management - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<h1>All Customer Bookings</h1>


<?php if ($successMessage !== ''): ?>

    <div class="alert alert-success">
        <?= htmlspecialchars($successMessage) ?>
    </div>

<?php endif; ?>


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


<?php if (empty($bookings)): ?>

    <div class="empty-state">

        <h2>No bookings found</h2>

        <p>
            There are currently no bookings in the system.
        </p>

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
                        <?= htmlspecialchars(
                            $booking['package_title']
                        ) ?>
                    </h2>

                    <p>
                        <strong>Customer:</strong>
                        <?= htmlspecialchars(
                            $booking['customer_name']
                        ) ?>
                    </p>

                    <p>
                        <strong>Email:</strong>
                        <?= htmlspecialchars(
                            $booking['customer_email']
                        ) ?>
                    </p>

                    <p>
                        <strong>Phone:</strong>
                        <?= htmlspecialchars(
                            $booking['customer_phone']
                            ?? 'Not provided'
                        ) ?>
                    </p>

                    <p>
                        <strong>Destination:</strong>
                        <?= htmlspecialchars(
                            $booking['destination']
                        ) ?>
                    </p>

                    <p>
                        <strong>Travel Date:</strong>
                        <?= htmlspecialchars(
                            $booking['travel_date']
                        ) ?>
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
                        <span class="status-badge status-<?= htmlspecialchars($bookingStatus) ?>">
                            <?= htmlspecialchars(ucfirst($bookingStatus)) ?>
                        </span>
                    </p>

                    <p>
                        <strong>Payment:</strong>

                        <?php if ($booking['payment_status']): ?>

                            <?= htmlspecialchars(
                                ucfirst(
                                    $booking['payment_status']
                                )
                            ) ?>

                        <?php else: ?>

                            Not submitted

                        <?php endif; ?>

                    </p>


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


                    <form
                        method="POST"
                        action="manage_bookings.php"
                    >

                        <input
                            type="hidden"
                            name="booking_id"
                            value="<?= (int) $booking['id'] ?>"
                        >


                        <div class="form-group">

                            <label
                                for="status_<?= (int) $booking['id'] ?>"
                            >
                                Booking Status
                            </label>

                            <select
                                id="status_<?= (int) $booking['id'] ?>"
                                name="status"
                                required
                            >

                                <option
                                    value="pending"
                                    <?= $booking['status'] === 'pending'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Pending
                                </option>

                                <option
                                    value="confirmed"
                                    <?= $booking['status'] === 'confirmed'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Confirmed
                                </option>

                                <option
                                    value="cancelled"
                                    <?= $booking['status'] === 'cancelled'
                                        ? 'selected'
                                        : '' ?>
                                >
                                    Cancelled
                                </option>

                            </select>

                        </div>


                        <button
                            type="submit"
                            name="update_status"
                            class="form-button"
                        >
                            Update Status
                        </button>

                    </form>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<?php

require_once '../includes/footer.php';

?>