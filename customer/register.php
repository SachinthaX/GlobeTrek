<?php

/*
 * GlobeTrek Adventures
 * Customer Registration
 */

require_once '../config/db.php';
require_once '../includes/csrf.php';

requireCsrfToken();

$errors = [];
$successMessage = '';

$fullName = '';
$email = '';
$phone = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';


    /*
     * --------------------------------------------------------
     * Full name validation
     * --------------------------------------------------------
     */

    if ($fullName === '') {

        $errors[] = 'Full name is required.';

    } elseif (strlen($fullName) > 100) {

        $errors[] =
            'Full name cannot exceed 100 characters.';
    }


    /*
     * --------------------------------------------------------
     * Email validation
     * --------------------------------------------------------
     */

    if ($email === '') {

        $errors[] = 'Email address is required.';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $errors[] =
            'Please enter a valid email address.';

    } elseif (strlen($email) > 150) {

        $errors[] =
            'Email address cannot exceed 150 characters.';
    }


    /*
     * --------------------------------------------------------
     * Phone validation
     * --------------------------------------------------------
     *
     * Allows digits, spaces, plus, hyphens and parentheses.
     */

    if ($phone !== '') {

        if (strlen($phone) > 20) {

            $errors[] =
                'Phone number cannot exceed 20 characters.';

        } elseif (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {

            $errors[] =
                'Phone number contains invalid characters.';
        }
    }


    /*
     * --------------------------------------------------------
     * Password validation
     * --------------------------------------------------------
     */

    if ($password === '') {

        $errors[] = 'Password is required.';

    } elseif (strlen($password) < 8) {

        $errors[] =
            'Password must contain at least 8 characters.';
    }


    if ($confirmPassword === '') {

        $errors[] =
            'Please confirm your password.';

    } elseif ($password !== $confirmPassword) {

        $errors[] =
            'Passwords do not match.';
    }


    /*
     * --------------------------------------------------------
     * Create account
     * --------------------------------------------------------
     */

    if (empty($errors)) {

        try {

            /*
             * Check whether the email is already registered.
             */
            $checkStatement = $pdo->prepare(
                "SELECT id
                 FROM users
                 WHERE email = :email
                 LIMIT 1"
            );

            $checkStatement->execute([
                'email' => $email
            ]);

            if ($checkStatement->fetch()) {

                $errors[] =
                    'An account with this email address already exists.';

            } else {

                /*
                 * Hash the password before storing it.
                 */
                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


                /*
                 * Public registration always creates customers.
                 */
                $insertStatement = $pdo->prepare(
                    "INSERT INTO users
                        (
                            full_name,
                            email,
                            password_hash,
                            role,
                            phone
                        )
                     VALUES
                        (
                            :full_name,
                            :email,
                            :password_hash,
                            :role,
                            :phone
                        )"
                );

                $insertStatement->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'role' => 'customer',
                    'phone' => $phone !== ''
                        ? $phone
                        : null
                ]);


                $successMessage =
                    'Registration successful! You can now log in.';

                $fullName = '';
                $email = '';
                $phone = '';
            }

        } catch (PDOException $exception) {

            error_log(
                'Registration database error: '
                . $exception->getMessage()
            );

            $errors[] =
                'Registration could not be completed. '
                . 'Please try again later.';
        }
    }
}

$pageTitle = 'Customer Registration - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<h1>Create a GlobeTrek Account</h1>


<?php if ($successMessage !== ''): ?>

    <div class="alert alert-success">

        <p>
            <?= htmlspecialchars($successMessage) ?>
        </p>

        <p>
            <a href="login.php">
                Go to Login
            </a>
        </p>

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


<form method="POST" action="register.php">

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
            value="<?= htmlspecialchars($phone) ?>"
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
            minlength="8"
            required
        >

    </div>


    <div class="form-group">

        <label for="confirm_password">
            Confirm Password
        </label>

        <input
            type="password"
            id="confirm_password"
            name="confirm_password"
            minlength="8"
            required
        >

    </div>


    <button
        type="submit"
        class="form-button"
    >
        Create Account
    </button>

</form>


<p>
    Already have an account?
    <a href="login.php">
        Login here
    </a>
</p>


<?php

require_once '../includes/footer.php';

?>