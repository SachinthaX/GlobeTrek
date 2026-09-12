<?php

/*
 * GlobeTrek Adventures
 * Customer Booking
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireRole('customer');

$errors = [];
$package = null;

$travelDate = '';
$numTravelers = 1;
$customizationNotes = '';

/*
 * Get package ID.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $packageId = filter_input(
        INPUT_POST,
        'package_id',
        FILTER_VALIDATE_INT
    );

} else {

    $packageId = filter_input(
        INPUT_GET,
        'package_id',
        FILTER_VALIDATE_INT
    );
}


if (
    $packageId === false
    || $packageId === null
    || $packageId <= 0
) {

    $errors[] =
        'Invalid travel package selected.';
}


/*
 * Load the active package.
 */
if (empty($errors)) {

    try {

        $statement = $pdo->prepare(
            "SELECT
                id,
                title,
                destination,
                price,
                duration_days
             FROM packages
             WHERE id = :id
               AND status = :status
             LIMIT 1"
        );

        $statement->execute([
            'id' => $packageId,
            'status' => 'active'
        ]);

        $package = $statement->fetch();


        if (!$package) {

            $errors[] =
                'The selected package is no longer available.';
        }

    } catch (PDOException $exception) {

        error_log(
            'Booking package lookup error: '
            . $exception->getMessage()
        );

        $errors[] =
            'The package could not be loaded. '
            . 'Please try again later.';
    }
}


/*
 * Process booking form.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {

    $travelDate =
        trim($_POST['travel_date'] ?? '');

    $numTravelers = filter_input(
        INPUT_POST,
        'num_travelers',
        FILTER_VALIDATE_INT
    );

    $customizationNotes =
        trim($_POST['customization_notes'] ?? '');


    /*
     * Validate travel date.
     */
    if ($travelDate === '') {

        $errors[] =
            'Travel date is required.';

    } else {

        $dateObject = DateTime::createFromFormat(
            'Y-m-d',
            $travelDate
        );

        $dateErrors = DateTime::getLastErrors();

        $hasDateErrors =
            is_array($dateErrors)
            && (
                $dateErrors['warning_count'] > 0
                || $dateErrors['error_count'] > 0
            );


        if (
            !$dateObject
            || $hasDateErrors
            || $dateObject->format('Y-m-d') !== $travelDate
        ) {

            $errors[] =
                'Please enter a valid travel date.';

        } elseif ($travelDate < date('Y-m-d')) {

            $errors[] =
                'Travel date cannot be in the past.';
        }
    }


    /*
     * Validate traveler count.
     */
    if (
        $numTravelers === false
        || $numTravelers === null
    ) {

        $errors[] =
            'Please enter a valid number of travelers.';

    } elseif (
        $numTravelers < 1
        || $numTravelers > 20
    ) {

        $errors[] =
            'Number of travelers must be between 1 and 20.';
    }


    /*
     * Validate customization notes.
     */
    if (strlen($customizationNotes) > 2000) {

        $errors[] =
            'Customization notes cannot exceed 2000 characters.';
    }


    if (empty($errors)) {

        try {

            /*
             * Re-read the package immediately before calculating
             * the price. This ensures the calculation uses the
             * current active database value.
             */
            $currentPackageStatement = $pdo->prepare(
                "SELECT
                    id,
                    price
                 FROM packages
                 WHERE id = :id
                   AND status = :status
                 LIMIT 1"
            );

            $currentPackageStatement->execute([
                'id' => $packageId,
                'status' => 'active'
            ]);

            $currentPackage =
                $currentPackageStatement->fetch();


            if (!$currentPackage) {

                $errors[] =
                    'The selected package is no longer available.';

            } else {

                /*
                 * Calculate price on the server.
                 */
                $totalPrice =
                    (float) $currentPackage['price']
                    * $numTravelers;


                $bookingStatement = $pdo->prepare(
                    "INSERT INTO bookings
                        (
                            user_id,
                            package_id,
                            travel_date,
                            num_travelers,
                            customization_notes,
                            total_price,
                            status
                        )
                     VALUES
                        (
                            :user_id,
                            :package_id,
                            :travel_date,
                            :num_travelers,
                            :customization_notes,
                            :total_price,
                            :status
                        )"
                );

                $bookingStatement->execute([
                    'user_id' =>
                        $_SESSION['user_id'],

                    'package_id' =>
                        $packageId,

                    'travel_date' =>
                        $travelDate,

                    'num_travelers' =>
                        $numTravelers,

                    'customization_notes' =>
                        $customizationNotes !== ''
                            ? $customizationNotes
                            : null,

                    'total_price' =>
                        $totalPrice,

                    'status' =>
                        'pending'
                ]);


                $bookingId =
                    $pdo->lastInsertId();


                header(
                    'Location: payment.php?booking_id='
                    . urlencode($bookingId)
                );

                exit;
            }

        } catch (PDOException $exception) {

            error_log(
                'Booking creation database error: '
                . $exception->getMessage()
            );

            $errors[] =
                'Your booking could not be created. '
                . 'Please try again later.';
        }
    }
}


$pageTitle = 'Book Package - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<h1>Book Your Travel Package</h1>


<?php if (!empty($errors)): ?>

    <div class="alert alert-error">

        <h2>Booking Error</h2>

        <ul>

            <?php foreach ($errors as $error): ?>

                <li>
                    <?= htmlspecialchars($error) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>


<?php if ($package): ?>

    <section class="package-details">

        <h2>
            <?= htmlspecialchars($package['title']) ?>
        </h2>

        <p>
            <strong>Destination:</strong>
            <?= htmlspecialchars($package['destination']) ?>
        </p>

        <p>
            <strong>Duration:</strong>
            <?= (int) $package['duration_days'] ?>
            day(s)
        </p>

        <p
            data-package-price="<?= htmlspecialchars(
                $package['price']
            ) ?>"
        >
            <strong>Price per traveler:</strong>
            Rs.
            <?= number_format(
                (float) $package['price'],
                2
            ) ?>
        </p>

        <p
            id="total_price_preview"
            class="package-price"
            aria-live="polite"
        >
        </p>


        <form method="POST" action="booking.php">

            <input
                type="hidden"
                name="package_id"
                value="<?= (int) $package['id'] ?>"
            >


            <div class="form-group">

                <label for="travel_date">
                    Travel Date
                </label>

                <input
                    type="date"
                    id="travel_date"
                    name="travel_date"
                    min="<?= date('Y-m-d') ?>"
                    value="<?= htmlspecialchars($travelDate) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="num_travelers">
                    Number of Travelers
                </label>

                <input
                    type="number"
                    id="num_travelers"
                    name="num_travelers"
                    min="1"
                    max="20"
                    value="<?= htmlspecialchars(
                        (string) $numTravelers
                    ) ?>"
                    required
                >

            </div>


            <div class="form-group">

                <label for="customization_notes">
                    Customization Notes
                </label>

                <textarea
                    id="customization_notes"
                    name="customization_notes"
                    rows="6"
                    maxlength="2000"
                ><?= htmlspecialchars($customizationNotes) ?></textarea>

            </div>


            <button
                type="submit"
                class="form-button"
            >
                Continue to Payment
            </button>

        </form>

    </section>

<?php endif; ?>


<?php

require_once '../includes/footer.php';

?>