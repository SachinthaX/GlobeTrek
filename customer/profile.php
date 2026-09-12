<?php

/*
 * GlobeTrek Adventures
 * Customer Profile
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/csrf.php';

requireRole('customer');
requireCsrfToken();

$errors = [];
$successMessage = '';
$profile = null;

$fullName = '';
$email = '';
$phone = '';

$userId = $_SESSION['user_id'];


/*
 * Load the current customer's profile.
 */
try {

    $profileStatement = $pdo->prepare(
        "SELECT
            id,
            full_name,
            email,
            phone
         FROM users
         WHERE id = :id
           AND role = 'customer'
         LIMIT 1"
    );

    $profileStatement->execute([
        'id' => $userId
    ]);

    $profile = $profileStatement->fetch();

    if ($profile) {

        $fullName = $profile['full_name'];
        $email = $profile['email'];
        $phone = $profile['phone'] ?? '';

    } else {

        $errors[] =
            'Your customer profile could not be found.';
    }

} catch (PDOException $exception) {

    error_log(
        'Customer profile lookup error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Your profile could not be loaded. Please try again later.';
}


/*
 * Validate and save profile changes.
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && $profile
) {

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');


    if ($fullName === '') {

        $errors[] = 'Full name is required.';

    } elseif (strlen($fullName) > 100) {

        $errors[] =
            'Full name cannot exceed 100 characters.';
    }


    if ($email === '') {

        $errors[] = 'Email address is required.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] =
            'Please enter a valid email address.';

    } elseif (strlen($email) > 150) {

        $errors[] =
            'Email address cannot exceed 150 characters.';
    }


    if ($phone !== '') {

        if (strlen($phone) > 20) {

            $errors[] =
                'Phone number cannot exceed 20 characters.';

        } elseif (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {

            $errors[] =
                'Phone number contains invalid characters.';
        }
    }


    if (empty($errors)) {

        try {

            $emailCheckStatement = $pdo->prepare(
                "SELECT id
                 FROM users
                 WHERE email = :email
                   AND id <> :id
                 LIMIT 1"
            );

            $emailCheckStatement->execute([
                'email' => $email,
                'id' => $userId
            ]);


            if ($emailCheckStatement->fetch()) {

                $errors[] =
                    'That email address is already being used.';

            } else {

                $updateStatement = $pdo->prepare(
                    "UPDATE users
                     SET
                        full_name = :full_name,
                        email = :email,
                        phone = :phone
                     WHERE id = :id"
                );

                $updateStatement->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'phone' => $phone !== '' ? $phone : null,
                    'id' => $userId
                ]);

                $_SESSION['full_name'] = $fullName;
                $_SESSION['email'] = $email;

                $profile['full_name'] = $fullName;
                $profile['email'] = $email;
                $profile['phone'] = $phone;

                $successMessage =
                    'Profile updated successfully.';
            }

        } catch (PDOException $exception) {

            error_log(
                'Customer profile update error: '
                . $exception->getMessage()
            );

            $errors[] =
                'Your profile could not be updated. Please try again later.';
        }
    }
}

$profileInitials = '';

if ($profile) {

    $nameParts = preg_split(
        '/\s+/',
        trim($fullName),
        -1,
        PREG_SPLIT_NO_EMPTY
    );

    foreach (array_slice($nameParts, 0, 2) as $namePart) {
        $profileInitials .= strtoupper(substr($namePart, 0, 1));
    }
}


$pageTitle = 'My Profile - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<header class="page-header profile-page-header">
    <p class="eyebrow">My account</p>
    <h1>Profile</h1>
    <p>Manage your personal information and account details.</p>
</header>


<?php if ($successMessage !== ''): ?>

    <div class="alert alert-success">

        <?= htmlspecialchars($successMessage) ?>

    </div>

<?php endif; ?>


<?php if (!empty($errors)): ?>

    <div class="alert alert-error">

        <h2>Please correct the following:</h2>

        <ul>

            <?php foreach ($errors as $error): ?>

                <li>
                    <?= htmlspecialchars($error) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>


<?php if ($profile): ?>

    <div class="profile-layout">

        <aside class="profile-summary">

            <div class="profile-avatar" aria-hidden="true">
                <?= htmlspecialchars($profileInitials) ?>
            </div>

            <div class="profile-info">

                <p class="eyebrow">GlobeTrek traveller</p>

                <h2>
                    <?= htmlspecialchars($fullName) ?>
                </h2>

                <p class="profile-email">
                    <?= htmlspecialchars($email) ?>
                </p>

            </div>

            <div class="profile-account-note">
                <span>Account</span>
                <strong>Customer account</strong>
            </div>

        </aside>

        <section class="package-details profile-form profile-section">

            <div class="profile-section-heading">
                <p class="eyebrow">Your details</p>
                <h2>Personal Information</h2>
                <p>Keep your contact details up to date for smoother travel planning.</p>
            </div>

            <form method="POST" action="profile.php">

            <?= csrfField() ?>

            <div class="form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    maxlength="100"
                    autocomplete="name"
                    required
                    value="<?= htmlspecialchars($fullName) ?>"
                >

            </div>


            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    autocomplete="email"
                    required
                    value="<?= htmlspecialchars($email) ?>"
                >

            </div>


            <div class="form-group">

                <label for="phone">
                    Phone Number
                </label>

                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    maxlength="20"
                    autocomplete="tel"
                    value="<?= htmlspecialchars($phone) ?>"
                >

            </div>


            <button
                type="submit"
                class="form-button"
            >
                Save Changes
            </button>

            </form>

        </section>

    </div>

<?php endif; ?>


<?php

require_once '../includes/footer.php';

?>