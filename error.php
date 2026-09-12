<?php

/*
 * GlobeTrek Adventures
 * Central Application Error Page
 *
 * This page provides a user-friendly destination for application
 * errors such as unauthorized access, missing pages and unexpected
 * application problems.
 *
 * Technical error details should never be displayed to visitors.
 */

$errorCode = filter_input(
    INPUT_GET,
    'code',
    FILTER_VALIDATE_INT
);

$errorMessage = 'Something went wrong. Please try again later.';

switch ($errorCode) {

    case 401:
        $errorTitle = 'Login Required';
        $errorMessage =
            'You must log in before accessing this page.';
        break;

    case 403:
        $errorTitle = 'Access Denied';
        $errorMessage =
            'You do not have permission to access this page.';
        break;

    case 404:
        $errorTitle = 'Page Not Found';
        $errorMessage =
            'The page you requested could not be found.';
        break;

    case 500:
        $errorTitle = 'Application Error';
        $errorMessage =
            'The application encountered an unexpected problem. '
            . 'Please try again later.';
        break;

    default:
        $errorTitle = 'Something Went Wrong';
        break;
}

$pageTitle = $errorTitle . ' - GlobeTrek Adventures';

require_once __DIR__ . '/includes/header.php';

?>

<section class="empty-state error-state">

    <p class="eyebrow">GlobeTrek Adventures</p>

    <h1>
        <?= htmlspecialchars($errorTitle) ?>
    </h1>

    <p>
        <?= htmlspecialchars($errorMessage) ?>
    </p>

    <p>
        <a
            href="/globetrek/index.php"
            class="button"
        >
            Return to Home
        </a>
    </p>

    <p>
        <a
            href="/globetrek/customer/packages.php"
            class="button"
        >
            Browse Packages
        </a>
    </p>

</section>

<?php

require_once __DIR__ . '/includes/footer.php';

?>