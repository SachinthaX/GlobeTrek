<?php

/*
 * GlobeTrek Adventures
 * User Login
 */

require_once '../config/db.php';

session_start();

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
                 * Use a generic message rather than revealing whether
                 * the email exists in the system.
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

<h1>GlobeTrek Adventures Login</h1>


<?php if (!empty($errors)): ?>

    <div class="alert alert-error">

        <h2>Login Error</h2>

        <ul>

            <?php foreach ($errors as $error): ?>

                <li>
                    <?= htmlspecialchars($error) ?>
                </li>

            <?php endforeach; ?>

        </ul>

    </div>

<?php endif; ?>


<form method="POST" action="login.php">

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

        <label for="password">
            Password
        </label>

        <input
            type="password"
            id="password"
            name="password"
            required
        >

    </div>


    <button
        type="submit"
        class="form-button"
    >
        Login
    </button>

</form>


<p>
    Don't have an account?
    <a href="register.php">
        Create an account
    </a>
</p>


<?php

require_once '../includes/footer.php';

?>