<?php

/*
 * GlobeTrek Adventures
 * Simulated Customer Payment
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';

requireRole('customer');
requireCsrfToken();

$errors = [];
$booking = null;


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $bookingId = filter_input(
        INPUT_POST,
        'booking_id',
        FILTER_VALIDATE_INT
    );

} else {

    $bookingId = filter_input(
        INPUT_GET,
        'booking_id',
        FILTER_VALIDATE_INT
    );
}


if (
    $bookingId === false
    || $bookingId === null
    || $bookingId <= 0
) {

    $errors[] =
        'Invalid booking selected.';
}


if (empty($errors)) {

    try {

        /*
         * Retrieve only the current customer's booking.
         */
        $bookingStatement = $pdo->prepare(
            "SELECT
                b.id,
                b.travel_date,
                b.num_travelers,
                b.customization_notes,
                b.total_price,
                b.status,
                p.title,
                p.destination
             FROM bookings b
             INNER JOIN packages p
                ON b.package_id = p.id
             WHERE b.id = :booking_id
               AND b.user_id = :user_id
             LIMIT 1"
        );

        $bookingStatement->execute([
            'booking_id' => $bookingId,
            'user_id' => $_SESSION['user_id']
        ]);

        $booking = $bookingStatement->fetch();


        if (!$booking) {

            $errors[] =
                'The selected booking could not be found.';

        } elseif ($booking['status'] === 'cancelled') {

            $errors[] =
                'Cancelled bookings cannot be paid for.';

        } elseif ($booking['status'] === 'confirmed') {

            /*
             * A confirmed booking should not be paid for again.
             */
            $existingPaymentStatement = $pdo->prepare(
                "SELECT id
                 FROM payments
                 WHERE booking_id = :booking_id
                   AND payment_status = :payment_status
                 LIMIT 1"
            );

            $existingPaymentStatement->execute([
                'booking_id' => $bookingId,
                'payment_status' => 'completed'
            ]);

            if ($existingPaymentStatement->fetch()) {

                $errors[] =
                    'This booking has already been paid for.';
            }
        }

    } catch (PDOException $exception) {

        error_log(
            'Payment booking lookup error: '
            . $exception->getMessage()
        );

        $errors[] =
            'The booking could not be loaded. '
            . 'Please try again later.';
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {

    $paymentMethod =
        $_POST['payment_method'] ?? '';

    $allowedPaymentMethods = [
        'card',
        'bank_transfer'
    ];


    if (
        !in_array(
            $paymentMethod,
            $allowedPaymentMethods,
            true
        )
    ) {

        $errors[] =
            'Please select a valid payment method.';
    }


    if (empty($errors)) {

        try {

            /*
             * Use a transaction because payment creation and
             * booking confirmation must happen together.
             */
            $pdo->beginTransaction();


            /*
             * Check for a previously completed payment again
             * inside the transaction.
             */
            $existingPaymentStatement = $pdo->prepare(
                "SELECT id
                 FROM payments
                 WHERE booking_id = :booking_id
                   AND payment_status = :payment_status
                 LIMIT 1"
            );

            $existingPaymentStatement->execute([
                'booking_id' => $bookingId,
                'payment_status' => 'completed'
            ]);


            if ($existingPaymentStatement->fetch()) {

                $pdo->rollBack();

                $errors[] =
                    'This booking has already been paid for.';

            } else {

                /*
                 * Create simulated payment.
                 */
                $paymentStatement = $pdo->prepare(
                    "INSERT INTO payments
                        (
                            booking_id,
                            amount,
                            payment_method,
                            payment_status,
                            paid_at
                        )
                     VALUES
                        (
                            :booking_id,
                            :amount,
                            :payment_method,
                            :payment_status,
                            CURRENT_TIMESTAMP
                        )"
                );

                $paymentStatement->execute([
                    'booking_id' =>
                        $bookingId,

                    'amount' =>
                        $booking['total_price'],

                    'payment_method' =>
                        $paymentMethod,

                    'payment_status' =>
                        'completed'
                ]);


                /*
                 * Confirm the booking only when payment succeeds.
                 */
                $updateBookingStatement = $pdo->prepare(
                    "UPDATE bookings
                     SET status = :status
                     WHERE id = :booking_id
                       AND user_id = :user_id
                       AND status <> :cancelled_status"
                );

                $updateBookingStatement->execute([
                    'status' =>
                        'confirmed',

                    'booking_id' =>
                        $bookingId,

                    'user_id' =>
                        $_SESSION['user_id'],

                    'cancelled_status' =>
                        'cancelled'
                ]);


                $pdo->commit();


                header(
                    'Location: my_bookings.php?payment=success'
                );

                exit;
            }

        } catch (PDOException $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Payment processing error: '
                . $exception->getMessage()
            );

            $errors[] =
                'Payment could not be completed. '
                . 'Please try again later.';
        }
    }
}


$pageTitle = 'Payment - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<h1>Payment</h1>


<?php if (!empty($errors)): ?>

    <div class="alert alert-error">

        <h2>Payment Error</h2>

        <ul>

            <?php foreach ($errors as $error): ?>

                <li>
                    <?= htmlspecialchars($error) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>


<?php if ($booking && empty($errors)): ?>

    <section class="package-details">

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
            <strong>Total Amount:</strong>
            Rs.
            <?= number_format(
                (float) $booking['total_price'],
                2
            ) ?>
        </p>


        <h2>Select Payment Method</h2>

        <p>
            This is a simulated payment for coursework purposes.
            No real card or bank information is collected.
        </p>


        <form method="POST" action="payment.php">

            <?= csrfField() ?>

            <input
                type="hidden"
                name="booking_id"
                value="<?= (int) $booking['id'] ?>"
            >


            <div class="form-group">

                <label for="payment_method">
                    Payment Method
                </label>

                <select
                    id="payment_method"
                    name="payment_method"
                    required
                >

                    <option value="">
                        -- Select payment method --
                    </option>

                    <option value="card">
                        Card
                    </option>

                    <option value="bank_transfer">
                        Bank Transfer
                    </option>

                </select>

            </div>


            <button
                type="submit"
                class="form-button"
            >
                Complete Simulated Payment
            </button>

        </form>

    </section>

<?php endif; ?>


<?php

require_once '../includes/footer.php';

?>