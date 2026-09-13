<?php

/*
 * GlobeTrek Adventures
 * User Login
 */

require_once '../config/db.php';
require_once '../includes/csrf.php';

requireCsrfToken();

$errors = [];

$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    /*
     * Validate email.
     */
    if ($email === '') {

        $errors[] =
            'Email address is required.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] =
            'Please enter a valid email address.';

    } elseif (strlen($email) > 150) {

        $errors[] =
            'Email address is too long.';
    }

    /*
     * Validate password.
     */
    if ($password === '') {

        $errors[] =
            'Password is required.';
    }

    if (empty($errors)) {

        try {

            /*
             * Retrieve the user by email.
             */
            $statement = $pdo->prepare(
                "SELECT
                    id,
                    full_name,
                    email,
                    password_hash,
                    role,
                    phone
                 FROM users
                 WHERE email = :email
                 LIMIT 1"
            );

            $statement->execute([
                'email' => $email
            ]);

            $user = $statement->fetch();

            /*
             * Verify password against stored hash.
             */
            if (
                $user
                && password_verify(
                    $password,
                    $user['password_hash']
                )
            ) {

                /*
                 * Regenerate the session ID after successful login.
                 */
                session_regenerate_id(true);

                $_SESSION['user_id'] =
                    $user['id'];

                $_SESSION['full_name'] =
                    $user['full_name'];

                $_SESSION['email'] =
                    $user['email'];

                $_SESSION['role'] =
                    $user['role'];

                /*
                 * Redirect according to role.
                 */
                switch ($user['role']) {

                    case 'customer':

                        header(
                            'Location: /globetrek/customer/packages.php'
                        );

                        break;

                    case 'staff':

                        header(
                            'Location: /globetrek/staff/dashboard.php'
                        );

                        break;

                    case 'admin':

                        header(
                            'Location: /globetrek/admin/dashboard.php'
                        );

                        break;

                    default:

                        session_unset();
                        session_destroy();

                        header(
                            'Location: /globetrek/error.php?code=500'
                        );

                        exit;
                }

                exit;

            } else {

                /*
                 * Generic message for security.
                 */
                $errors[] =
                    'Invalid email address or password.';
            }

        } catch (PDOException $exception) {

            error_log(
                'Login database error: '
                . $exception->getMessage()
            );

            $errors[] =
                'Login could not be completed. '
                . 'Please try again later.';
        }
    }
}

$pageTitle = 'Login - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<section class="auth-page">

    <div class="auth-layout">

        <!-- Left visual panel -->
        <aside class="auth-visual">

            <img
                src="/globetrek/assets/images/kandy-cultural.jpg"
                alt="Sri Lankan travel destination"
                class="auth-visual-image"
            >

            <div class="auth-visual-overlay"></div>

            <div class="auth-visual-content">

                <p class="auth-eyebrow">
                    EXPLORE SRI LANKA
                </p>

                <h1>
                    Your next adventure starts here.
                </h1>

                <p>
                    Discover unforgettable destinations,
                    curated travel packages and experiences
                    across Sri Lanka with GlobeTrek Adventures.
                </p>

            </div>

        </aside>


        <!-- Login panel -->
        <section class="auth-panel">

            <div class="auth-panel-inner">

                <p class="auth-eyebrow auth-eyebrow-dark">
                    WELCOME BACK
                </p>

                <h2>
                    Sign in to GlobeTrek
                </h2>

                <p class="auth-intro">
                    Access your bookings, profile and travel plans.
                </p>


                <?php if (!empty($errors)): ?>

                    <div class="alert alert-error auth-alert">

                        <h3>
                            Login Error
                        </h3>

                        <ul>

                            <?php foreach ($errors as $error): ?>

                                <li>
                                    <?= htmlspecialchars($error) ?>
                                </li>

                            <?php endforeach; ?>

                        </ul>

                    </div>

                <?php endif; ?>


                <form
                    method="POST"
                    action="login.php"
                    class="auth-form"
                >

                    <?= csrfField() ?>


                    <div class="form-group">

                        <label for="email">
                            Email Address
                        </label>

                        <input
                            type="email"
                            id="email"
                            name="email"
                            maxlength="150"
                            required
                            autocomplete="email"
                            value="<?= htmlspecialchars($email) ?>"
                            placeholder="you@example.com"
                        >

                    </div>


                    <div class="form-group">

                        <label for="password">
                            Password
                        </label>

                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        >

                    </div>


                    <button
                        type="submit"
                        class="form-button auth-submit"
                    >
                        Sign In
                    </button>

                </form>


                <div class="auth-footer-text">

                    <span>
                        Don't have an account?
                    </span>

                    <a href="register.php">
                        Create an account
                    </a>

                </div>

            </div>

        </section>

    </div>

</section>


<?php

require_once '../includes/footer.php';

?>