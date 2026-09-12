<?php

/*
 * GlobeTrek Adventures
 * Staff Customer Query Management
 */

require_once '../config/db.php';
require_once '../includes/auth_check.php';

requireRole('staff');

$errors = [];
$successMessage = '';


/*
 * ------------------------------------------------------------
 * RESPOND TO QUERY
 * ------------------------------------------------------------
 */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['respond_query'])
) {

    $queryId = filter_input(
        INPUT_POST,
        'query_id',
        FILTER_VALIDATE_INT
    );

    $staffResponse = trim(
        $_POST['staff_response'] ?? ''
    );


    /*
     * Validate query ID.
     */
    if (
        $queryId === false
        || $queryId === null
        || $queryId <= 0
    ) {

        $errors[] = 'Invalid query selected.';
    }


    /*
     * Validate response.
     */
    if ($staffResponse === '') {

        $errors[] =
            'A response is required before answering a query.';

    } elseif (strlen($staffResponse) > 5000) {

        $errors[] =
            'Response cannot exceed 5000 characters.';
    }


    /*
     * Save the response.
     */
    if (empty($errors)) {

        try {

            $statement = $pdo->prepare(
                "UPDATE queries
                 SET
                    staff_response = :staff_response,
                    status = :status
                 WHERE id = :id"
            );

            $statement->execute([
                'staff_response' => $staffResponse,
                'status' => 'answered',
                'id' => $queryId
            ]);

            $successMessage =
                'Customer query answered successfully.';

        } catch (PDOException $exception) {

            error_log(
                'Customer query response error: '
                . $exception->getMessage()
            );

            $errors[] =
                'The query response could not be saved.';
        }
    }
}


/*
 * ------------------------------------------------------------
 * LOAD CUSTOMER QUERIES
 * ------------------------------------------------------------
 */

$queries = [];

try {

    $statement = $pdo->prepare(
        "SELECT
            q.id,
            q.subject,
            q.message,
            q.status,
            q.staff_response,
            q.created_at,

            u.full_name,
            u.email

         FROM queries q

         LEFT JOIN users u
            ON q.user_id = u.id

         ORDER BY
            CASE
                WHEN q.status = 'open' THEN 0
                ELSE 1
            END,
            q.created_at DESC"
    );

    $statement->execute();

    $queries = $statement->fetchAll();

} catch (PDOException $exception) {

    error_log(
        'Customer queries database error: '
        . $exception->getMessage()
    );

    $errors[] =
        'Customer queries could not be loaded.';
}


$pageTitle = 'Customer Queries - GlobeTrek Adventures';

require_once '../includes/header.php';

?>

<h1>Customer Queries</h1>


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


<?php if (empty($queries)): ?>

    <div class="empty-state">

        <h2>No customer queries</h2>

        <p>
            There are currently no submitted queries.
        </p>

    </div>

<?php else: ?>

    <div class="package-grid">

        <?php foreach ($queries as $query): ?>

            <article class="package-card">

                <div class="package-card-content">

                    <h2>
                        <?= htmlspecialchars(
                            $query['subject']
                        ) ?>
                    </h2>


                    <p>
                        <strong>Customer:</strong>

                        <?php if ($query['full_name']): ?>

                            <?= htmlspecialchars(
                                $query['full_name']
                            ) ?>

                            -
                            <?= htmlspecialchars(
                                $query['email']
                            ) ?>

                        <?php else: ?>

                            Guest User

                        <?php endif; ?>

                    </p>


                    <p>
                        <strong>Date:</strong>
                        <?= htmlspecialchars(
                            $query['created_at']
                        ) ?>
                    </p>


                    <p>
                        <strong>Status:</strong>
                        <?= htmlspecialchars(
                            ucfirst($query['status'])
                        ) ?>
                    </p>


                    <h3>Customer Message</h3>

                    <p>
                        <?= nl2br(
                            htmlspecialchars(
                                $query['message']
                            )
                        ) ?>
                    </p>


                    <h3>Staff Response</h3>


                    <?php if ($query['status'] === 'answered'): ?>

                        <p>
                            <?= nl2br(
                                htmlspecialchars(
                                    $query['staff_response']
                                )
                            ) ?>
                        </p>

                        <hr>

                        <h3>
                            Update Response
                        </h3>

                    <?php endif; ?>


                    <form
                        method="POST"
                        action="queries.php"
                    >

                        <input
                            type="hidden"
                            name="query_id"
                            value="<?= (int) $query['id'] ?>"
                        >


                        <div class="form-group">

                            <label
                                for="response_<?= (int) $query['id'] ?>"
                            >
                                Response
                            </label>

                            <textarea
                                id="response_<?= (int) $query['id'] ?>"
                                name="staff_response"
                                rows="5"
                                maxlength="5000"
                                required
                            ><?= htmlspecialchars(
                                $query['staff_response'] ?? ''
                            ) ?></textarea>

                        </div>


                        <button
                            type="submit"
                            name="respond_query"
                            class="form-button"
                        >
                            Save Response
                        </button>

                    </form>

                </div>

            </article>

        <?php endforeach; ?>

    </div>

<?php endif; ?>


<?php

require_once '../includes/footer.php';

?>