<?php

/*
 * GlobeTrek Adventures
 * Administrator Staff Management
 *
 * Administrators can:
 * - Add staff accounts
 * - Edit staff accounts
 * - Change staff passwords
 * - Delete staff accounts
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireRole('admin');

$errors = [];
$successMessage = '';

$editStaff = null;


/*
 * ------------------------------------------------------------
 * DELETE STAFF
 * ------------------------------------------------------------
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['delete_staff'])
) {

    $staffId = filter_input(
        INPUT_POST,
        'staff_id',
        FILTER_VALIDATE_INT
    );


    if (
        $staffId === false
        || $staffId === null
        || $staffId <= 0
    ) {

        $errors[] = 'Invalid staff account selected.';

    } elseif ($staffId == $_SESSION['user_id']) {

        /*
         * Prevent the logged-in administrator from deleting
         * their own account.
         */
        $errors[] =
            'You cannot delete your own administrator account.';

    } else {

        try {

            /*
             * Check that this account is actually a staff account.
             */
            $checkStatement = $pdo->prepare(
                "SELECT id
                 FROM users
                 WHERE id = :id
                   AND role = :role
                 LIMIT 1"
            );

            $checkStatement->execute([
                'id' => $staffId,
                'role' => 'staff'
            ]);

            $staff = $checkStatement->fetch();


            if (!$staff) {

                $errors[] =
                    'The selected staff account could not be found.';

            } else {

                /*
                 * Deleting a staff member who created packages
                 * would violate the packages.created_by foreign key.
                 *
                 * Therefore, check for packages before deletion.
                 */
                $packageCheck = $pdo->prepare(
                    "SELECT COUNT(*) AS total
                     FROM packages
                     WHERE created_by = :created_by"
                );

                $packageCheck->execute([
                    'created_by' => $staffId
                ]);

                $packageCount =
                    (int) $packageCheck->fetch()['total'];


                if ($packageCount > 0) {

                    $errors[] =
                        'This staff member cannot be deleted because '
                        . 'they have created packages. Please remove or '
                        . 'reassign those packages first.';

                } else {

                    $deleteStatement = $pdo->prepare(
                        "DELETE FROM users
                         WHERE id = :id
                           AND role = :role"
                    );

                    $deleteStatement->execute([
                        'id' => $staffId,
                        'role' => 'staff'
                    ]);

                    $successMessage =
                        'Staff account deleted successfully.';
                }
            }

        } catch (PDOException $exception) {

            error_log(
                'Staff deletion database error: '
                . $exception->getMessage()
            );

            $errors[] =
                'The staff account could not be deleted.';
        }
    }
}


/*
 * ------------------------------------------------------------
 * ADD / UPDATE STAFF
 * ------------------------------------------------------------
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['save_staff'])
) {

    $staffId = filter_input(
        INPUT_POST,
        'staff_id',
        FILTER_VALIDATE_INT
    );

    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';


    /*
     * Validate full name.
     */
    if ($fullName === '') {

        $errors[] = 'Full name is required.';

    } elseif (strlen($fullName) > 100) {

        $errors[] =
            'Full name cannot exceed 100 characters.';
    }


    /*
     * Validate email.
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
     * Validate phone.
     */
    if ($phone !== '' && strlen($phone) > 20) {

        $errors[] =
            'Phone number cannot exceed 20 characters.';
    }


    /*
     * A password is required when creating a new account.
     *
     * When editing an existing account, an empty password means
     * "keep the current password".
     */
    $isEditing =
        $staffId !== false
        && $staffId !== null
        && $staffId > 0;


    if (!$isEditing && $password === '') {

        $errors[] =
            'A password is required for a new staff account.';

    } elseif ($password !== '' && strlen($password) < 8) {

        $errors[] =
            'Password must contain at least 8 characters.';
    }


    /*
     * --------------------------------------------------------
     * Save staff account
     * --------------------------------------------------------
     */

    if (empty($errors)) {

        try {

            /*
             * Check whether another user already has this email.
             *
             * This prevents duplicate emails while still allowing
             * an existing staff member to keep their own email.
             */
            if ($isEditing) {

                $emailCheck = $pdo->prepare(
                    "SELECT id
                     FROM users
                     WHERE email = :email
                       AND id <> :id
                     LIMIT 1"
                );

                $emailCheck->execute([
                    'email' => $email,
                    'id' => $staffId
                ]);

            } else {

                $emailCheck = $pdo->prepare(
                    "SELECT id
                     FROM users
                     WHERE email = :email
                     LIMIT 1"
                );

                $emailCheck->execute([
                    'email' => $email
                ]);
            }


            if ($emailCheck->fetch()) {

                $errors[] =
                    'That email address is already being used.';

            } else {

                if ($isEditing) {

                    /*
                     * Update staff details.
                     */
                    $updateStatement = $pdo->prepare(
                        "UPDATE users
                         SET
                            full_name = :full_name,
                            email = :email,
                            phone = :phone
                         WHERE id = :id
                           AND role = :role"
                    );

                    $updateStatement->execute([
                        'full_name' => $fullName,
                        'email' => $email,
                        'phone' => $phone !== ''
                            ? $phone
                            : null,
                        'id' => $staffId,
                        'role' => 'staff'
                    ]);


                    /*
                     * Change the password only if the administrator
                     * entered a new password.
                     */
                    if ($password !== '') {

                        $passwordHash = password_hash(
                            $password,
                            PASSWORD_DEFAULT
                        );

                        $passwordStatement = $pdo->prepare(
                            "UPDATE users
                             SET password_hash = :password_hash
                             WHERE id = :id
                               AND role = :role"
                        );

                        $passwordStatement->execute([
                            'password_hash' => $passwordHash,
                            'id' => $staffId,
                            'role' => 'staff'
                        ]);
                    }


                    $successMessage =
                        'Staff account updated successfully.';

                } else {

                    /*
                     * Create new staff account.
                     */
                    $passwordHash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

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
                        'role' => 'staff',
                        'phone' => $phone !== ''
                            ? $phone
                            : null
                    ]);

                    $successMessage =
                        'New staff account created successfully.';
                }
            }

        } catch (PDOException $exception) {

            error_log(
                'Staff save database error: '
                . $exception->getMessage()
            );

            $errors[] =
                'The staff account could not be saved.';
        }
    }
}


/*
 * ------------------------------------------------------------
 * LOAD STAFF FOR EDITING
 * ------------------------------------------------------------
 */

if (
    isset($_GET['edit'])
    && filter_var($_GET['edit'], FILTER_VALIDATE_INT)
) {

    $editId = (int) $_GET['edit'];

    try {

        $statement = $pdo->prepare(
            "SELECT
                id,
                full_name,
                email,
                phone
             FROM users
             WHERE id = :id
               AND role = :role
             LIMIT 1"
        );

        $statement->execute([
            'id' => $editId,
            'role' => 'staff'
        ]);

        $editStaff = $statement->fetch();


        if (!$editStaff) {

            $errors[] =
                'The selected staff account could not be found.';
        }

    } catch (PDOException $exception) {

        error_log(
            'Staff edit lookup error: '
            . $exception->getMessage()
        );

        $errors[] =
            'Staff information could not be loaded.';
    }
}


/*
 * ------------------------------------------------------------
 * LOAD STAFF LIST
 * ------------------------------------------------------------
 */

$staffMembers = [];

try {

    $statement = $pdo->prepare(
        "SELECT
            id,
            full_name,
            email,
            phone,
            created_at
         FROM users
         WHERE role = :role
         ORDER BY created_at DESC, id DESC"
    );

    $statement->execute([
        'role' => 'staff'
    ]);

    $staffMembers = $statement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Staff list database error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Staff accounts could not be loaded.';
}


$pageTitle = 'Manage Staff - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<h1>Manage Staff Accounts</h1>


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


<section class="package-details">

    <h2>
        <?= $editStaff
            ? 'Edit Staff Account'
            : 'Add Staff Account' ?>
    </h2>


    <form
        method="POST"
        action="manage_staff.php"
    >

        <?php if ($editStaff): ?>

            <input
                type="hidden"
                name="staff_id"
                value="<?= (int) $editStaff['id'] ?>"
            >

        <?php endif; ?>


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
                value="<?= htmlspecialchars(
                    $editStaff['full_name'] ?? ''
                ) ?>"
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
                value="<?= htmlspecialchars(
                    $editStaff['email'] ?? ''
                ) ?>"
            >

        </div>


        <div class="form-group">

            <label for="phone">
                Phone
            </label>

            <input
                type="tel"
                id="phone"
                name="phone"
                maxlength="20"
                value="<?= htmlspecialchars(
                    $editStaff['phone'] ?? ''
                ) ?>"
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
                <?= $editStaff ? '' : 'required' ?>
            >

            <?php if ($editStaff): ?>

                <small>
                    Leave blank to keep the current password.
                </small>

            <?php endif; ?>

        </div>


        <button
            type="submit"
            name="save_staff"
            class="form-button"
        >
            <?= $editStaff
                ? 'Update Staff'
                : 'Create Staff' ?>
        </button>


        <?php if ($editStaff): ?>

            <a
                href="manage_staff.php"
                class="button"
            >
                Cancel Edit
            </a>

        <?php endif; ?>

    </form>

</section>


<section>

    <h2>Existing Staff Accounts</h2>


    <?php if (empty($staffMembers)): ?>

        <div class="empty-state">

            <p>
                No staff accounts found.
            </p>

        </div>

    <?php else: ?>

        <div class="package-grid">

            <?php foreach ($staffMembers as $staff): ?>

                <article class="package-card">

                    <div class="package-card-content">

                        <h3>
                            <?= htmlspecialchars(
                                $staff['full_name']
                            ) ?>
                        </h3>

                        <p>
                            <strong>Email:</strong>
                            <?= htmlspecialchars(
                                $staff['email']
                            ) ?>
                        </p>

                        <p>
                            <strong>Phone:</strong>
                            <?= htmlspecialchars(
                                $staff['phone'] ?? 'Not provided'
                            ) ?>
                        </p>

                        <p>
                            <strong>Created:</strong>
                            <?= htmlspecialchars(
                                $staff['created_at']
                            ) ?>
                        </p>


                        <a
                            href="manage_staff.php?edit=<?= (int) $staff['id'] ?>"
                            class="button"
                        >
                            Edit
                        </a>


                        <form
                            method="POST"
                            action="manage_staff.php"
                            style="display:inline;"
                            data-confirm="Are you sure you want to delete this staff account?"
                        >

                            <input
                                type="hidden"
                                name="staff_id"
                                value="<?= (int) $staff['id'] ?>"
                            >

                            <button
                                type="submit"
                                name="delete_staff"
                                class="form-button"
                            >
                                Delete
                            </button>

                        </form>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</section>


<?php

require_once '../includes/footer.php';

?>