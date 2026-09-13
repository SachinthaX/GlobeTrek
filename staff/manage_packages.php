<?php

/*
 * GlobeTrek Adventures
 * Staff Package Management
 *
 * Staff can:
 * - View packages
 * - Add packages
 * - Edit packages
 * - Delete packages
 * - Manage one accommodation record
 * - Manage one transportation record
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';

requireRole('staff');
requireCsrfToken();

$errors = [];
$successMessage = '';

$editPackage = null;
$editAccommodation = null;
$editTransportation = null;


/*
 * ------------------------------------------------------------
 * DELETE PACKAGE
 * ------------------------------------------------------------
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_package'])
) {

    $packageId = filter_input(
        INPUT_POST,
        'package_id',
        FILTER_VALIDATE_INT
    );

    if (
        $packageId === false
        || $packageId === null
        || $packageId <= 0
    ) {

        $errors[] = 'Invalid package selected for deletion.';

    } else {

        try {

            /*
             * Check whether the package has bookings.
             *
             * Historical booking information should not be
             * destroyed accidentally.
             */
            $bookingCheck = $pdo->prepare(
                "SELECT COUNT(*) AS total
                 FROM bookings
                 WHERE package_id = :package_id"
            );

            $bookingCheck->execute([
                'package_id' => $packageId
            ]);

            $bookingCount =
                (int) $bookingCheck->fetch()['total'];


            if ($bookingCount > 0) {

                $errors[] =
                    'This package cannot be deleted because it has existing bookings. '
                    . 'Set it to inactive instead.';

            } else {

                /*
                 * Delete related accommodation and transportation
                 * records before deleting the package.
                 */
                $pdo->beginTransaction();


                $deleteAccommodation = $pdo->prepare(
                    "DELETE FROM accommodations
                     WHERE package_id = :package_id"
                );

                $deleteAccommodation->execute([
                    'package_id' => $packageId
                ]);


                $deleteTransportation = $pdo->prepare(
                    "DELETE FROM transportation
                     WHERE package_id = :package_id"
                );

                $deleteTransportation->execute([
                    'package_id' => $packageId
                ]);


                $deletePackage = $pdo->prepare(
                    "DELETE FROM packages
                     WHERE id = :package_id"
                );

                $deletePackage->execute([
                    'package_id' => $packageId
                ]);


                $pdo->commit();

                $successMessage =
                    'Package deleted successfully.';
            }

        } catch (PDOException $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Package deletion error: '
                . $exception->getMessage()
            );

            $errors[] =
                'The package could not be deleted.';
        }
    }
}


/*
 * ------------------------------------------------------------
 * ADD OR UPDATE PACKAGE
 * ------------------------------------------------------------
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['save_package'])
) {

    $packageId = filter_input(
        INPUT_POST,
        'package_id',
        FILTER_VALIDATE_INT
    );

    $title = trim($_POST['title'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $durationDays = filter_input(
        INPUT_POST,
        'duration_days',
        FILTER_VALIDATE_INT
    );
    $activities = trim($_POST['activities'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $status = $_POST['status'] ?? 'active';


    /*
     * Accommodation fields.
     */
    $hotelName = trim($_POST['hotel_name'] ?? '');
    $hotelLocation = trim($_POST['hotel_location'] ?? '');
    $hotelPrice = trim($_POST['price_per_night'] ?? '');
    $hotelRating = trim($_POST['rating'] ?? '');


    /*
     * Transportation fields.
     */
    $transportType = trim($_POST['transport_type'] ?? '');
    $transportProvider = trim($_POST['transport_provider'] ?? '');
    $transportPrice = trim($_POST['transport_price'] ?? '');


    /*
     * --------------------------------------------------------
     * Validation
     * --------------------------------------------------------
     */

    if ($title === '') {
        $errors[] = 'Package title is required.';
    } elseif (strlen($title) > 150) {
        $errors[] =
            'Package title cannot exceed 150 characters.';
    }


    if ($destination === '') {
        $errors[] = 'Destination is required.';
    } elseif (strlen($destination) > 100) {
        $errors[] =
            'Destination cannot exceed 100 characters.';
    }


    if ($description === '') {
        $errors[] = 'Package description is required.';
    }


    if (
        $price === ''
        || !is_numeric($price)
        || (float) $price <= 0
    ) {

        $errors[] =
            'Package price must be greater than zero.';
    }


    if (
        $durationDays === false
        || $durationDays === null
        || $durationDays <= 0
    ) {

        $errors[] =
            'Duration must be a positive whole number.';
    }


    if (!in_array($status, ['active', 'inactive'], true)) {

        $errors[] = 'Invalid package status.';
    }


    /*
     * Accommodation is optional.
     * If entered, validate its individual fields.
     */
    if ($hotelName !== '') {

        if ($hotelLocation === '') {
            $errors[] =
                'Accommodation location is required when a hotel is provided.';
        }

        if (
            $hotelPrice === ''
            || !is_numeric($hotelPrice)
            || (float) $hotelPrice < 0
        ) {
            $errors[] =
                'Accommodation price must be zero or greater.';
        }

        if (
            $hotelRating !== ''
            && (
                !is_numeric($hotelRating)
                || (float) $hotelRating < 0
                || (float) $hotelRating > 5
            )
        ) {
            $errors[] =
                'Accommodation rating must be between 0 and 5.';
        }
    }


    /*
     * Transportation is optional.
     * If entered, validate its fields.
     */
    if ($transportType !== '') {

        if ($transportProvider === '') {
            $errors[] =
                'Transportation provider is required.';
        }

        if (
            $transportPrice === ''
            || !is_numeric($transportPrice)
            || (float) $transportPrice < 0
        ) {
            $errors[] =
                'Transportation price must be zero or greater.';
        }
    }


    /*
     * --------------------------------------------------------
     * Save package
     * --------------------------------------------------------
     */

    if (empty($errors)) {

        try {

            /*
             * Use a transaction because the operation can update:
             *
             * 1. packages
             * 2. accommodations
             * 3. transportation
             *
             * Either all changes succeed or none are saved.
             */
            $pdo->beginTransaction();


            if (
                $packageId !== false
                && $packageId !== null
                && $packageId > 0
            ) {

                /*
                 * UPDATE existing package.
                 */
                $packageStatement = $pdo->prepare(
                    "UPDATE packages
                     SET
                        title = :title,
                        destination = :destination,
                        description = :description,
                        price = :price,
                        duration_days = :duration_days,
                        activities = :activities,
                        image = :image,
                        status = :status
                     WHERE id = :id
                       AND created_by = :created_by"
                );

                $packageStatement->execute([
                    'title' => $title,
                    'destination' => $destination,
                    'description' => $description,
                    'price' => (float) $price,
                    'duration_days' => $durationDays,
                    'activities' => $activities,
                    'image' => $image !== '' ? $image : null,
                    'status' => $status,
                    'id' => $packageId,
                    'created_by' => $_SESSION['user_id']
                ]);

            } else {

                /*
                 * INSERT new package.
                 */
                $packageStatement = $pdo->prepare(
                    "INSERT INTO packages
                        (
                            title,
                            destination,
                            description,
                            price,
                            duration_days,
                            activities,
                            image,
                            created_by,
                            status
                        )
                     VALUES
                        (
                            :title,
                            :destination,
                            :description,
                            :price,
                            :duration_days,
                            :activities,
                            :image,
                            :created_by,
                            :status
                        )"
                );

                $packageStatement->execute([
                    'title' => $title,
                    'destination' => $destination,
                    'description' => $description,
                    'price' => (float) $price,
                    'duration_days' => $durationDays,
                    'activities' => $activities,
                    'image' => $image !== '' ? $image : null,
                    'created_by' => $_SESSION['user_id'],
                    'status' => $status
                ]);

                $packageId = $pdo->lastInsertId();
            }


            /*
             * ------------------------------------------------
             * Accommodation
             * ------------------------------------------------
             */

            $deleteAccommodation = $pdo->prepare(
                "DELETE FROM accommodations
                 WHERE package_id = :package_id"
            );

            $deleteAccommodation->execute([
                'package_id' => $packageId
            ]);


            if ($hotelName !== '') {

                $accommodationStatement = $pdo->prepare(
                    "INSERT INTO accommodations
                        (
                            package_id,
                            hotel_name,
                            location,
                            price_per_night,
                            rating
                        )
                     VALUES
                        (
                            :package_id,
                            :hotel_name,
                            :location,
                            :price_per_night,
                            :rating
                        )"
                );

                $accommodationStatement->execute([
                    'package_id' => $packageId,
                    'hotel_name' => $hotelName,
                    'location' => $hotelLocation,
                    'price_per_night' => (float) $hotelPrice,
                    'rating' => $hotelRating !== ''
                        ? (float) $hotelRating
                        : null
                ]);
            }


            /*
             * ------------------------------------------------
             * Transportation
             * ------------------------------------------------
             */

            $deleteTransportation = $pdo->prepare(
                "DELETE FROM transportation
                 WHERE package_id = :package_id"
            );

            $deleteTransportation->execute([
                'package_id' => $packageId
            ]);


            if ($transportType !== '') {

                $transportationStatement = $pdo->prepare(
                    "INSERT INTO transportation
                        (
                            package_id,
                            type,
                            provider,
                            price
                        )
                     VALUES
                        (
                            :package_id,
                            :type,
                            :provider,
                            :price
                        )"
                );

                $transportationStatement->execute([
                    'package_id' => $packageId,
                    'type' => $transportType,
                    'provider' => $transportProvider,
                    'price' => (float) $transportPrice
                ]);
            }


            $pdo->commit();

            $successMessage =
                $packageId
                ? 'Package saved successfully.'
                : 'Package created successfully.';

        } catch (PDOException $exception) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                'Package save database error: '
                . $exception->getMessage()
            );

            $errors[] =
                'The package could not be saved. Please try again.';
        }
    }
}


/*
 * ------------------------------------------------------------
 * LOAD PACKAGE FOR EDITING
 * ------------------------------------------------------------
 */

if (
    isset($_GET['edit'])
    && filter_var($_GET['edit'], FILTER_VALIDATE_INT)
) {

    $editId = (int) $_GET['edit'];

    try {

        $editPackageStatement = $pdo->prepare(
            "SELECT
                id,
                title,
                destination,
                description,
                price,
                duration_days,
                activities,
                image,
                status
             FROM packages
             WHERE id = :id
             LIMIT 1"
        );

        $editPackageStatement->execute([
            'id' => $editId
        ]);

        $editPackage = $editPackageStatement->fetch();


        if ($editPackage) {

            $accommodationStatement = $pdo->prepare(
                "SELECT
                    hotel_name,
                    location,
                    price_per_night,
                    rating
                 FROM accommodations
                 WHERE package_id = :package_id
                 ORDER BY id ASC
                 LIMIT 1"
            );

            $accommodationStatement->execute([
                'package_id' => $editId
            ]);

            $editAccommodation =
                $accommodationStatement->fetch();


            $transportationStatement = $pdo->prepare(
                "SELECT
                    type,
                    provider,
                    price
                 FROM transportation
                 WHERE package_id = :package_id
                 ORDER BY id ASC
                 LIMIT 1"
            );

            $transportationStatement->execute([
                'package_id' => $editId
            ]);

            $editTransportation =
                $transportationStatement->fetch();

        } else {

            $errors[] =
                'The selected package could not be found.';
        }

    } catch (PDOException $exception) {

        error_log(
            'Package edit lookup error: '
            . $exception->getMessage()
        );

        $errors[] =
            'Package information could not be loaded.';
    }
}


/*
 * ------------------------------------------------------------
 * LOAD ALL PACKAGES
 * ------------------------------------------------------------
 */

$packages = [];

try {

    $packageListStatement = $pdo->prepare(
        "SELECT
            p.id,
            p.title,
            p.destination,
            p.price,
            p.duration_days,
            p.status,
            p.created_at,
            u.full_name AS creator_name
         FROM packages p
         INNER JOIN users u
            ON p.created_by = u.id
         ORDER BY p.created_at DESC, p.id DESC"
    );

    $packageListStatement->execute();

    $packages = $packageListStatement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Package list database error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Package list could not be loaded.';
}


$pageTitle = 'Manage Packages - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<header class="page-header workspace-header">
    <p class="eyebrow">Staff operations</p>
    <h1>Manage Travel Packages</h1>
</header>


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


<section class="package-details form-section">

    <h2>
        <?= $editPackage
            ? 'Edit Package'
            : 'Add New Package' ?>
    </h2>


    <form method="POST" action="manage_packages.php">

        <?= csrfField() ?>

        <?php if ($editPackage): ?>

            <input
                type="hidden"
                name="package_id"
                value="<?= (int) $editPackage['id'] ?>"
            >

        <?php endif; ?>


        <div class="form-group">

            <label for="title">
                Package Title
            </label>

            <input
                type="text"
                id="title"
                name="title"
                maxlength="150"
                required
                value="<?= htmlspecialchars(
                    $editPackage['title'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="destination">
                Destination
            </label>

            <input
                type="text"
                id="destination"
                name="destination"
                maxlength="100"
                required
                value="<?= htmlspecialchars(
                    $editPackage['destination'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="description">
                Description
            </label>

            <textarea
                id="description"
                name="description"
                rows="6"
                required
            ><?= htmlspecialchars(
                $editPackage['description'] ?? ''
            ) ?></textarea>

        </div>


        <div class="form-group">

            <label for="price">
                Price per Traveler
            </label>

            <input
                type="number"
                id="price"
                name="price"
                min="0"
                step="0.01"
                required
                value="<?= htmlspecialchars(
                    $editPackage['price'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="duration_days">
                Duration in Days
            </label>

            <input
                type="number"
                id="duration_days"
                name="duration_days"
                min="1"
                required
                value="<?= htmlspecialchars(
                    $editPackage['duration_days'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="activities">
                Activities
            </label>

            <textarea
                id="activities"
                name="activities"
                rows="4"
                placeholder="Example: Hiking, sightseeing, cultural tour"
            ><?= htmlspecialchars(
                $editPackage['activities'] ?? ''
            ) ?></textarea>

        </div>


        <div class="form-group">

            <label for="image">
                Image File Name
            </label>

            <input
                type="text"
                id="image"
                name="image"
                maxlength="255"
                placeholder="Example: ella-adventure.jpg"
                value="<?= htmlspecialchars(
                    $editPackage['image'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="status">
                Package Status
            </label>

            <select
                id="status"
                name="status"
                required
            >

                <option
                    value="active"
                    <?= (($editPackage['status'] ?? 'active') === 'active')
                        ? 'selected'
                        : '' ?>
                >
                    Active
                </option>

                <option
                    value="inactive"
                    <?= (($editPackage['status'] ?? '') === 'inactive')
                        ? 'selected'
                        : '' ?>
                >
                    Inactive
                </option>

            </select>

        </div>


        <h3>Accommodation</h3>

        <p>
            These fields are optional.
        </p>


        <div class="form-group">

            <label for="hotel_name">
                Hotel Name
            </label>

            <input
                type="text"
                id="hotel_name"
                name="hotel_name"
                maxlength="150"
                value="<?= htmlspecialchars(
                    $editAccommodation['hotel_name'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="hotel_location">
                Hotel Location
            </label>

            <input
                type="text"
                id="hotel_location"
                name="hotel_location"
                maxlength="150"
                value="<?= htmlspecialchars(
                    $editAccommodation['location'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="price_per_night">
                Price Per Night
            </label>

            <input
                type="number"
                id="price_per_night"
                name="price_per_night"
                min="0"
                step="0.01"
                value="<?= htmlspecialchars(
                    $editAccommodation['price_per_night'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="rating">
                Hotel Rating
            </label>

            <input
                type="number"
                id="rating"
                name="rating"
                min="0"
                max="5"
                step="0.1"
                value="<?= htmlspecialchars(
                    $editAccommodation['rating'] ?? ''
                ) ?>"
            >

        </div>


        <h3>Transportation</h3>


        <div class="form-group">

            <label for="transport_type">
                Transportation Type
            </label>

            <input
                type="text"
                id="transport_type"
                name="transport_type"
                maxlength="50"
                placeholder="Example: Van"
                value="<?= htmlspecialchars(
                    $editTransportation['type'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="transport_provider">
                Provider
            </label>

            <input
                type="text"
                id="transport_provider"
                name="transport_provider"
                maxlength="100"
                value="<?= htmlspecialchars(
                    $editTransportation['provider'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="transport_price">
                Transportation Price
            </label>

            <input
                type="number"
                id="transport_price"
                name="transport_price"
                min="0"
                step="0.01"
                value="<?= htmlspecialchars(
                    $editTransportation['price'] ?? ''
                ) ?>"
            >

        </div>


        <button
            type="submit"
            name="save_package"
            class="form-button"
        >
            <?= $editPackage
                ? 'Update Package'
                : 'Add Package' ?>
        </button>


        <?php if ($editPackage): ?>

            <a
                href="manage_packages.php"
                class="button"
            >
                Cancel Edit
            </a>

        <?php endif; ?>

    </form>

</section>


<section class="content-section">

    <h2>Existing Packages</h2>


    <?php if (empty($packages)): ?>

        <div class="empty-state">
            <p>No packages are available.</p>
        </div>

    <?php else: ?>

        <div class="package-grid">

            <?php foreach ($packages as $package): ?>

                <?php
                $packageStatus = in_array(
                    $package['status'],
                    ['active', 'inactive'],
                    true
                )
                    ? $package['status']
                    : 'inactive';
                ?>

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

                        <p>
                            <strong>Price:</strong>
                            Rs.
                            <?= number_format(
                                (float) $package['price'],
                                2
                            ) ?>
                        </p>

                        <p>
                            <strong>Duration:</strong>
                            <?= (int) $package['duration_days'] ?>
                            days
                        </p>

                        <p>
                            <strong>Status:</strong>
                            <span class="status-badge status-<?= htmlspecialchars($packageStatus) ?>">
                                <?= htmlspecialchars(ucfirst($packageStatus)) ?>
                            </span>
                        </p>

                        <p>
                            <strong>Created by:</strong>
                            <?= htmlspecialchars(
                                $package['creator_name']
                            ) ?>
                        </p>


                        <div class="package-card-actions">

                            <a
                                href="manage_packages.php?edit=<?= (int) $package['id'] ?>"
                                class="button"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="manage_packages.php"
                                data-confirm="Are you sure you want to delete this package?"
                            >

                            <?= csrfField() ?>

                            <input
                                type="hidden"
                                name="package_id"
                                value="<?= (int) $package['id'] ?>"
                            >

                            <button
                                type="submit"
                                name="delete_package"
                                class="form-button"
                            >
                                Delete
                            </button>

                        </form>
                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<?php

require_once '../includes/footer.php';

?>