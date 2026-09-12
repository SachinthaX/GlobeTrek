<?php

/*
 * GlobeTrek Adventures
 * Individual package details page.
 */

require_once '../config/db.php';

$package = null;
$accommodations = [];
$transportation = [];
$errors = [];


/*
 * ---------------------------
 * Validate package ID
 * ---------------------------
 */

$packageId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if ($packageId === false || $packageId === null || $packageId <= 0) {

    $errors[] = 'Invalid package selected.';

} else {

    try {

        /*
         * Retrieve the selected active package.
         */
        $packageStatement = $pdo->prepare(
            "SELECT
                id,
                title,
                destination,
                description,
                price,
                duration_days,
                activities,
                image
             FROM packages
             WHERE id = :id
               AND status = :status
             LIMIT 1"
        );

        $packageStatement->execute([
            'id' => $packageId,
            'status' => 'active'
        ]);

        $package = $packageStatement->fetch();


        if (!$package) {

            $errors[] = 'The selected package could not be found.';

        } else {

            /*
             * Retrieve accommodation options for the package.
             */
            $accommodationStatement = $pdo->prepare(
                "SELECT
                    id,
                    hotel_name,
                    location,
                    price_per_night,
                    rating
                 FROM accommodations
                 WHERE package_id = :package_id
                 ORDER BY rating DESC"
            );

            $accommodationStatement->execute([
                'package_id' => $packageId
            ]);

            $accommodations = $accommodationStatement->fetchAll();


            /*
             * Retrieve transportation options for the package.
             */
            $transportationStatement = $pdo->prepare(
                "SELECT
                    id,
                    type,
                    provider,
                    price
                 FROM transportation
                 WHERE package_id = :package_id
                 ORDER BY price ASC"
            );

            $transportationStatement->execute([
                'package_id' => $packageId
            ]);

            $transportation = $transportationStatement->fetchAll();
        }

    } catch (PDOException $exception) {

        /*
         * Store technical information in the log.
         */
        error_log(
            'Package details database error: '
            . $exception->getMessage()
        );

        $errors[] = 'Package details could not be loaded. Please try again later.';
    }
}


$pageTitle = 'Package Details - GlobeTrek Adventures';

require_once '../includes/header.php';

?>


<?php if (!empty($errors)): ?>

    <div class="alert alert-error">

        <h2>Package Error</h2>

        <ul>

            <?php foreach ($errors as $error): ?>

                <li>
                    <?= htmlspecialchars($error) ?>
                </li>

            <?php endforeach; ?>

        </ul>

        <a
            href="packages.php"
            class="button"
        >
            Back to Packages
        </a>

    </div>


<?php elseif ($package): ?>

    <article class="package-details package-details-page">

        <?php if (!empty($package['image'])): ?>

            <img
                src="/globetrek/assets/images/<?= htmlspecialchars($package['image']) ?>"
                alt="<?= htmlspecialchars($package['title']) ?>"
                class="package-details-image"
            >

        <?php endif; ?>


        <p class="package-kicker">
            <?= htmlspecialchars($package['destination']) ?>
        </p>

        <h1>
            <?= htmlspecialchars($package['title']) ?>
        </h1>


        <div class="package-meta">

            <div class="meta-item">

                <strong>Destination</strong>

                <br>

                <?= htmlspecialchars($package['destination']) ?>

            </div>


            <div class="meta-item">

                <strong>Duration</strong>

                <br>

                <?= (int) $package['duration_days'] ?>
                day(s)

            </div>


            <div class="meta-item">

                <strong>Price</strong>

                <br>

                Rs.
                <?= number_format((float) $package['price'], 2) ?>

                per traveler

            </div>

        </div>


        <section class="detail-section">

            <h2>Overview</h2>

            <p>
                <?= nl2br(htmlspecialchars($package['description'])) ?>
            </p>

        </section>


        <section class="detail-section">

            <h2>Activities</h2>

            <?php
            /*
             * Activities are stored as comma-separated text
             * in the current database design.
             */
            $activityList = array_filter(
                array_map(
                    'trim',
                    explode(',', $package['activities'] ?? '')
                )
            );
            ?>

            <?php if (!empty($activityList)): ?>

                <ul class="activities-list">

                    <?php foreach ($activityList as $activityItem): ?>

                        <li>
                            <?= htmlspecialchars($activityItem) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            <?php else: ?>

                <p>
                    No activities have been specified yet.
                </p>

            <?php endif; ?>

        </section>


        <section class="detail-section">

            <h2>Accommodation</h2>

            <?php if (!empty($accommodations)): ?>

                <?php foreach ($accommodations as $accommodation): ?>

                    <div class="meta-item">

                        <h3>
                            <?= htmlspecialchars($accommodation['hotel_name']) ?>
                        </h3>

                        <p>
                            <strong>Location:</strong>
                            <?= htmlspecialchars($accommodation['location']) ?>
                        </p>

                        <p>
                            <strong>Price per night:</strong>
                            Rs.
                            <?= number_format(
                                (float) $accommodation['price_per_night'],
                                2
                            ) ?>
                        </p>

                        <p>
                            <strong>Rating:</strong>
                            <?= htmlspecialchars($accommodation['rating']) ?>
                            / 5
                        </p>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p>
                    No accommodation information is available.
                </p>

            <?php endif; ?>

        </section>


        <section class="detail-section">

            <h2>Transportation</h2>

            <?php if (!empty($transportation)): ?>

                <?php foreach ($transportation as $transport): ?>

                    <div class="meta-item">

                        <h3>
                            <?= htmlspecialchars($transport['type']) ?>
                        </h3>

                        <p>
                            <strong>Provider:</strong>
                            <?= htmlspecialchars($transport['provider']) ?>
                        </p>

                        <p>
                            <strong>Price:</strong>
                            Rs.
                            <?= number_format(
                                (float) $transport['price'],
                                2
                            ) ?>
                        </p>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p>
                    No transportation information is available.
                </p>

            <?php endif; ?>

        </section>


        <section class="detail-section detail-cta">

            <h2>Ready to Book?</h2>

            <?php if (isset($_SESSION['user_id'])): ?>

                <?php if ($_SESSION['role'] === 'customer'): ?>

                    <a
                        href="booking.php?package_id=<?= (int) $package['id'] ?>"
                        class="button"
                    >
                        Book This Package
                    </a>

                <?php else: ?>

                    <p>
                        This booking option is available to customer
                        accounts.
                    </p>

                <?php endif; ?>

            <?php else: ?>

                <p>
                    Please log in or create a customer account before
                    booking.
                </p>

                <a
                    href="login.php"
                    class="button"
                >
                    Login to Book
                </a>

            <?php endif; ?>

        </section>


    </article>

<?php endif; ?>


<?php

require_once '../includes/footer.php';

?>