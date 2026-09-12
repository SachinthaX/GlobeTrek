<?php

/*
 * GlobeTrek Adventures
 * Customer package listing and search.
 */

require_once '../config/db.php';

/*
 * Read search/filter values from the URL.
 *
 * Example:
 * packages.php?destination=Kandy
 */
$destination = trim($_GET['destination'] ?? '');
$minPrice = trim($_GET['min_price'] ?? '');
$maxPrice = trim($_GET['max_price'] ?? '');
$activity = trim($_GET['activity'] ?? '');

$errors = [];
$packages = [];


/*
 * ---------------------------
 * Validate search filters
 * ---------------------------
 */

if ($minPrice !== '') {

    if (!is_numeric($minPrice) || (float) $minPrice < 0) {
        $errors[] = 'Minimum price must be a valid positive number.';
    }
}

if ($maxPrice !== '') {

    if (!is_numeric($maxPrice) || (float) $maxPrice < 0) {
        $errors[] = 'Maximum price must be a valid positive number.';
    }
}

if (
    $minPrice !== '' &&
    $maxPrice !== '' &&
    is_numeric($minPrice) &&
    is_numeric($maxPrice) &&
    (float) $minPrice > (float) $maxPrice
) {
    $errors[] = 'Minimum price cannot be greater than maximum price.';
}


/*
 * ---------------------------
 * Retrieve packages
 * ---------------------------
 */

if (empty($errors)) {

    try {

        /*
         * Start with a query that retrieves active packages.
         */
        $sql = "
            SELECT
                id,
                title,
                destination,
                description,
                price,
                duration_days,
                activities,
                image
            FROM packages
            WHERE status = :status
        ";

        /*
         * Parameters are stored separately from the SQL query.
         */
        $parameters = [
            'status' => 'active'
        ];


        /*
         * Add destination filtering only when the user has
         * entered a destination.
         */
        if ($destination !== '') {

            $sql .= "
                AND destination LIKE :destination
            ";

            $parameters['destination'] = '%' . $destination . '%';
        }


        /*
         * Add minimum price filtering when required.
         */
        if ($minPrice !== '') {

            $sql .= "
                AND price >= :min_price
            ";

            $parameters['min_price'] = (float) $minPrice;
        }


        /*
         * Add maximum price filtering when required.
         */
        if ($maxPrice !== '') {

            $sql .= "
                AND price <= :max_price
            ";

            $parameters['max_price'] = (float) $maxPrice;
        }


        /*
         * Search for the activity in the activities field.
         */
        if ($activity !== '') {

            $sql .= "
                AND activities LIKE :activity
            ";

            $parameters['activity'] = '%' . $activity . '%';
        }


        /*
         * Display the newest packages first.
         */
        $sql .= "
            ORDER BY created_at DESC, id DESC
        ";


        /*
         * Prepare and execute the final SQL query.
         */
        $statement = $pdo->prepare($sql);

        $statement->execute($parameters);

        $packages = $statement->fetchAll();

    } catch (PDOException $exception) {

        /*
         * Store technical details in the server log.
         */
        error_log(
            'Package search database error: '
            . $exception->getMessage()
        );

        $errors[] = 'Packages could not be loaded. Please try again later.';
    }
}

$pageTitle = 'Travel Packages - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<header class="page-header">

    <p class="eyebrow">Explore</p>

    <h1>Travel Packages</h1>

    <p>
        Search our available travel packages by destination,
        price range or activity.
    </p>

</header>


<?php if (!empty($errors)): ?>

    <div class="alert alert-error">

        <h2>Unable to complete your search</h2>

        <ul>
            <?php foreach ($errors as $error): ?>

                <li>
                    <?= htmlspecialchars($error) ?>
                </li>

            <?php endforeach; ?>
        </ul>

    </div>

<?php endif; ?>


<section class="search-box package-search-panel">

    <h2>Search Packages</h2>

    <form method="GET" action="packages.php">

        <div class="search-grid">

            <div class="form-group">

                <label for="destination">
                    Destination
                </label>

                <input
                    type="text"
                    id="destination"
                    name="destination"
                    placeholder="e.g. Kandy"
                    value="<?= htmlspecialchars($destination) ?>"
                >

            </div>


            <div class="form-group">

                <label for="min_price">
                    Minimum Price
                </label>

                <input
                    type="number"
                    id="min_price"
                    name="min_price"
                    min="0"
                    step="0.01"
                    placeholder="e.g. 30000"
                    value="<?= htmlspecialchars($minPrice) ?>"
                >

            </div>


            <div class="form-group">

                <label for="max_price">
                    Maximum Price
                </label>

                <input
                    type="number"
                    id="max_price"
                    name="max_price"
                    min="0"
                    step="0.01"
                    placeholder="e.g. 80000"
                    value="<?= htmlspecialchars($maxPrice) ?>"
                >

            </div>


            <div class="form-group">

                <label for="activity">
                    Activity
                </label>

                <input
                    type="text"
                    id="activity"
                    name="activity"
                    placeholder="e.g. hiking"
                    value="<?= htmlspecialchars($activity) ?>"
                >

            </div>


            <div>

                <button
                    type="submit"
                    class="form-button"
                >
                    Search
                </button>

                <a
                    href="packages.php"
                    class="button button-secondary"
                >
                    Clear Filters
                </a>

            </div>

        </div>

    </form>

</section>


<section class="section-heading package-results">

    <h2>
        Available Packages
    </h2>

    <p>
        <?= count($packages) ?>
        package<?= count($packages) === 1 ? '' : 's' ?>
        matching your search.
    </p>


    <?php if (empty($packages)): ?>

        <div class="empty-state">

            <h3>No packages found</h3>

            <p>
                Try changing your search or filter options.
            </p>

            <a
                href="packages.php"
                class="button"
            >
                Show All Packages
            </a>

        </div>

    <?php else: ?>

        <div class="package-grid">

            <?php foreach ($packages as $package): ?>

                <article class="package-card package-travel-card">

                    <div class="package-card-media">

                        <?php if (!empty($package['image'])): ?>

                            <img
                                src="/globetrek/assets/images/<?= htmlspecialchars($package['image']) ?>"
                                alt="<?= htmlspecialchars($package['title']) ?>"
                                class="package-card-image"
                            >

                        <?php endif; ?>

                    </div>


                    <div class="package-card-content">

                        <p class="package-kicker">
                            <?= htmlspecialchars($package['destination']) ?>
                        </p>

                        <h3>
                            <?= htmlspecialchars($package['title']) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($package['description']) ?>
                        </p>

                        <p class="package-card-duration">
                            <strong>Duration:</strong>
                            <?= (int) $package['duration_days'] ?>
                            day(s)
                        </p>

                        <?php if (!empty($package['activities'])): ?>

                            <p class="package-activity-preview">
                                <strong>Activities:</strong>
                                <?= htmlspecialchars($package['activities']) ?>
                            </p>

                        <?php endif; ?>

                        <p class="package-price package-card-price">
                            <span>From</span>
                            <strong>
                                Rs.
                                <?= number_format((float) $package['price'], 2) ?>
                            </strong>
                        </p>

                        <a
                            href="package_details.php?id=<?= (int) $package['id'] ?>"
                            class="button package-card-cta"
                        >
                            View Details
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<?php

require_once '../includes/footer.php';

?>