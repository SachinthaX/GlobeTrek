<?php

/*
 * GlobeTrek Adventures
 * Customer / Guest Contact Query
 */

require_once '../config/db.php';
require_once '../includes/csrf.php';

requireCsrfToken();

$errors = [];
$successMessage = '';

$subject = '';
$message = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $subject =
        trim($_POST['subject'] ?? '');

    $message =
        trim($_POST['message'] ?? '');


    /*
     * Subject validation.
     */
    if ($subject === '') {

        $errors[] =
            'Subject is required.';

    } elseif (strlen($subject) < 3) {

        $errors[] =
            'Subject must contain at least 3 characters.';

    } elseif (strlen($subject) > 150) {

        $errors[] =
            'Subject cannot exceed 150 characters.';
    }


    /*
     * Message validation.
     */
    if ($message === '') {

        $errors[] =
            'Message is required.';

    } elseif (strlen($message) < 10) {

        $errors[] =
            'Message must contain at least 10 characters.';

    } elseif (strlen($message) > 5000) {

        $errors[] =
            'Message cannot exceed 5000 characters.';
    }


    if (empty($errors)) {

        try {

            /*
             * Attach the query to the logged-in user when possible.
             * Guest queries remain NULL.
             */
            $userId =
                $_SESSION['user_id'] ?? null;


            $queryStatement = $pdo->prepare(
                "INSERT INTO queries
                    (
                        user_id,
                        subject,
                        message,
                        status
                    )
                 VALUES
                    (
                        :user_id,
                        :subject,
                        :message,
                        :status
                    )"
            );

            $queryStatement->execute([
                'user_id' =>
                    $userId,

                'subject' =>
                    $subject,

                'message' =>
                    $message,

                'status' =>
                    'open'
            ]);


            $successMessage =
                'Your query has been submitted successfully. '
                . 'Our travel management team will review it.';


            $subject = '';
            $message = '';

        } catch (PDOException $exception) {

            error_log(
                'Contact query database error: '
                . $exception->getMessage()
            );

            $errors[] =
                'Your query could not be submitted. '
                . 'Please try again later.';
        }
    }
}


$pageTitle = 'Contact Us - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<h1>We are here to help</h1>

<p class="page-lead">
    Have a question about a package, booking, transportation,
    accommodation or another travel arrangement?
    Send us a message.
</p>


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


<section class="package-details">

    <form
        method="POST"
        action="contact.php"
    >

        <?= csrfField() ?>

        <div class="form-group">

            <label for="subject">
                Subject
            </label>

            <input
                type="text"
                id="subject"
                name="subject"
                maxlength="150"
                required
                value="<?= htmlspecialchars($subject) ?>"
            >

        </div>


        <div class="form-group">

            <label for="message">
                Your Message
            </label>

            <textarea
                id="message"
                name="message"
                rows="8"
                maxlength="5000"
                required
            ><?= htmlspecialchars($message) ?></textarea>

        </div>


        <button
            type="submit"
            class="form-button"
        >
            Submit Query
        </button>

    </form>

</section>


<?php

require_once '../includes/footer.php';

?>