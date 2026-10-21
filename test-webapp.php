<?php
/*
|--------------------------------------------------------------------------
| DATABASE CONNECTION
|--------------------------------------------------------------------------
*/
mysqli_report(MYSQLI_REPORT_OFF);

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "birthday_wishnote";

$conn = new mysqli($servername, $username, $password, $dbname);

/*
|--------------------------------------------------------------------------
| INITIALIZE VARIABLES
|--------------------------------------------------------------------------
*/
$successMessage = "";
$errorMessage   = "";

$firstName  = "";
$lastName   = "";
$govPosition = "";
$location   = "";

/*
|--------------------------------------------------------------------------
| HELPER FUNCTIONS
|--------------------------------------------------------------------------
*/
function escapeHTML($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES | ENT_SUBSTITUTE,
        "UTF-8"
    );
}

function characterLength($value)
{
    return function_exists("mb_strlen")
        ? mb_strlen($value, "UTF-8")
        : strlen($value);
}

/*
|--------------------------------------------------------------------------
| HANDLE POST REQUEST
|--------------------------------------------------------------------------
*/
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Capture all four submitted values.
    $firstName   = trim((string) ($_POST["FirstName"] ?? ""));
    $lastName    = trim((string) ($_POST["LastName"] ?? ""));
    $govPosition = trim((string) ($_POST["GovPosition"] ?? ""));
    $location    = trim((string) ($_POST["Location"] ?? ""));

    /*
     * Server-side validation supplements the HTML required
     * and maxlength attributes.
     */
    $validationErrors = [];

    if ($firstName === "") {
        $validationErrors[] = "Target's First Name is required.";
    } elseif (characterLength($firstName) > 30) {
        $validationErrors[] = "First Name must not exceed 30 characters.";
    }

    if ($lastName === "") {
        $validationErrors[] = "Target's Last Name is required.";
    } elseif (characterLength($lastName) > 30) {
        $validationErrors[] = "Last Name must not exceed 30 characters.";
    }

    if ($govPosition === "") {
        $validationErrors[] = "Target's Government Position is required.";
    } elseif (characterLength($govPosition) > 20) {
        $validationErrors[] = "Government Position must not exceed 20 characters.";
    }

    if (characterLength($location) > 40) {
        $validationErrors[] = "Location must not exceed 40 characters.";
    }

    if (!empty($validationErrors)) {

        $errorMessage = implode(" ", $validationErrors);

    } elseif ($conn->connect_error) {

        // Avoid displaying sensitive database connection details.
        $errorMessage = "Unable to connect to the database. Please try again.";

    } else {

        /*
        |--------------------------------------------------------------------------
        | INTENTIONAL DATABASE INSERTION FLAW
        |--------------------------------------------------------------------------
        | The Location value is captured above and displayed in the
        | front-end verification modal. However, Location is deliberately
        | OMITTED from both the INSERT column list and bind_param().
        |
        | Only FirstName, LastName, and GovPosition are inserted.
        |--------------------------------------------------------------------------
        */

        $sql = "INSERT INTO main_corrupt_politicians
                    (FirstName, LastName, GovPosition)
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);

        if ($stmt === false) {

            // Fallback error if the SQL statement cannot be prepared.
            $errorMessage = "Database error: Unable to prepare the insertion query.";

        } else {

            // Intentionally bind only three values; Location is omitted.
            $stmt->bind_param(
                "sss",
                $firstName,
                $lastName,
                $govPosition
            );

            if ($stmt->execute()) {

                $successMessage = "Success! The wish note has been sent.";

                // Clear the fields after a successful submission.
                $firstName   = "";
                $lastName    = "";
                $govPosition = "";
                $location    = "";

            } else {

                $errorMessage = "The wish note could not be sent. Please try again.";
            }

            $stmt->close();
        }
    }
}

// Close the database connection after processing the request.
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Kara David's Wish Note</title>

    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-blue-dark: #1d4ed8;
            --light-blue: #eff6ff;
            --background: #f3f6fb;
            --white: #ffffff;
            --text: #1f2937;
            --muted: #6b7280;
            --border: #dce3ed;
            --success-bg: #ecfdf3;
            --success-text: #166534;
            --success-border: #bbf7d0;
            --error-bg: #fef2f2;
            --error-text: #b91c1c;
            --error-border: #fecaca;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            padding: 32px 16px;
            display: flex;
            justify-content: center;
            align-items: center;
            background: var(--background);
            color: var(--text);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.6;
        }

        .page-container {
            width: 100%;
            max-width: 540px;
            margin: auto;
        }

        .form-card {
            width: 100%;
            padding: 34px;
            background: var(--white);
            border-radius: 14px;
            box-shadow: 0 10px 35px rgba(31, 41, 55, 0.09);
        }

        .heading {
            margin-bottom: 28px;
            text-align: center;
        }

        .heading h2 {
            margin: 0 0 8px;
            color: var(--primary-blue);
            font-size: 27px;
            font-weight: 700;
            letter-spacing: -0.6px;
        }

        .heading p {
            margin: 0;
            color: var(--muted);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 21px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 600;
            color: var(--text);
        }

        .required-mark {
            color: #dc2626;
        }

        .form-group input {
            display: block;
            width: 100%;
            min-height: 46px;
            padding: 12px 14px;
            border: 1px solid var(--border);
            border-radius: 8px;
            outline: none;
            background: var(--white);
            color: var(--text);
            font: inherit;
            font-size: 14px;
            transition:
                border-color 0.2s ease,
                box-shadow 0.2s ease;
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .form-group input:focus {
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .field-hint {
            display: block;
            margin-top: 6px;
            color: var(--muted);
            font-size: 12px;
        }

        .submit-button,
        .modal-button {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            min-height: 46px;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition:
                background-color 0.2s ease,
                transform 0.2s ease;
        }

        .submit-button {
            width: 100%;
            margin-top: 5px;
            background: var(--primary-blue);
            color: var(--white);
        }

        .submit-button:hover {
            background: var(--primary-blue-dark);
        }

        .submit-button:active,
        .modal-button:active {
            transform: scale(0.99);
        }

        .alert {
            margin-bottom: 22px;
            padding: 14px 16px;
            border: 1px solid;
            border-radius: 8px;
            font-size: 14px;
            overflow-wrap: anywhere;
        }

        .success-message {
            color: var(--success-text);
            background: var(--success-bg);
            border-color: var(--success-border);
        }

        .error-message {
            color: var(--error-text);
            background: var(--error-bg);
            border-color: var(--error-border);
        }

        .form-footer {
            margin-top: 22px;
            text-align: center;
            color: var(--muted);
            font-size: 12px;
        }

        /* ---------------- MODAL OVERLAY ---------------- */

        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
            padding: 18px;
            background: rgba(15, 23, 42, 0.58);
            backdrop-filter: blur(3px);
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-card {
            width: 100%;
            max-width: 460px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 28px;
            background: var(--white);
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
            animation: modalAppear 0.2s ease-out;
        }

        @keyframes modalAppear {
            from {
                opacity: 0;
                transform: translateY(10px) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .modal-card h3 {
            margin: 0 0 8px;
            color: var(--primary-blue);
            font-size: 22px;
        }

        .modal-description {
            margin: 0 0 22px;
            color: var(--muted);
            font-size: 14px;
        }

        .verification-list {
            margin: 0 0 24px;
            padding: 0;
            border: 1px solid var(--border);
            border-radius: 9px;
            overflow: hidden;
        }

        .verification-row {
            display: grid;
            grid-template-columns: 43% 57%;
            gap: 10px;
            padding: 13px 14px;
            border-bottom: 1px solid var(--border);
        }

        .verification-row:last-child {
            border-bottom: none;
        }

        .verification-label {
            color: var(--muted);
            font-size: 13px;
            font-weight: 600;
        }

        .verification-value {
            min-width: 0;
            color: var(--text);
            font-size: 14px;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .modal-actions button {
            flex: 1;
        }

        .cancel-button {
            color: var(--text);
            background: #eef2f7;
        }

        .cancel-button:hover {
            background: #e2e8f0;
        }

        .confirm-button {
            color: var(--white);
            background: var(--primary-blue);
        }

        .confirm-button:hover {
            background: var(--primary-blue-dark);
        }

        button:focus-visible {
            outline: 3px solid rgba(37, 99, 235, 0.35);
            outline-offset: 3px;
        }

        @media (max-width: 480px) {
            body {
                align-items: flex-start;
                padding: 20px 12px;
            }

            .form-card {
                padding: 24px 20px;
            }

            .heading h2 {
                font-size: 24px;
            }

            .modal-card {
                padding: 22px 18px;
            }

            .modal-actions {
                flex-direction: column-reverse;
            }

            .modal-actions button {
                width: 100%;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>

<body>

    <main class="page-container">

        <section class="form-card">

            <header class="heading">
                <h2>Kara David's Wish Note</h2>
                <p>Enter the information below and verify it before sending.</p>
            </header>

            <!-- SUCCESS MESSAGE -->
            <?php if ($successMessage !== ""): ?>
                <div
                    class="alert success-message"
                    role="status"
                    aria-live="polite"
                >
                    <?= escapeHTML($successMessage) ?>
                </div>
            <?php endif; ?>

            <!-- ERROR MESSAGE -->
            <?php if ($errorMessage !== ""): ?>
                <div
                    class="alert error-message"
                    role="alert"
                >
                    <?= escapeHTML($errorMessage) ?>
                </div>
            <?php endif; ?>

            <form
                id="wishNoteForm"
                method="POST"
                action=""
            >

                <div class="form-group">
                    <label for="FirstName">
                        Target's First Name
                        <span class="required-mark">*</span>
                    </label>

                    <input
                        type="text"
                        id="FirstName"
                        name="FirstName"
                        maxlength="30"
                        value="<?= escapeHTML($firstName) ?>"
                        placeholder="Enter first name"
                        autocomplete="given-name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="LastName">
                        Target's Last Name
                        <span class="required-mark">*</span>
                    </label>

                    <input
                        type="text"
                        id="LastName"
                        name="LastName"
                        maxlength="30"
                        value="<?= escapeHTML($lastName) ?>"
                        placeholder="Enter last name"
                        autocomplete="family-name"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="GovPosition">
                        Target's Government Position
                        <span class="required-mark">*</span>
                    </label>

                    <input
                        type="text"
                        id="GovPosition"
                        name="GovPosition"
                        maxlength="20"
                        value="<?= escapeHTML($govPosition) ?>"
                        placeholder="Enter government position"
                        required
                    >

                    <span class="field-hint">
                        Maximum of 20 characters.
                    </span>
                </div>

                <div class="form-group">
                    <label for="Location">
                        Target's Location
                    </label>

                    <input
                        type="text"
                        id="Location"
                        name="Location"
                        maxlength="40"
                        value="<?= escapeHTML($location) ?>"
                        placeholder="Enter location (optional)"
                        autocomplete="address-level2"
                    >

                    <span class="field-hint">
                        Optional. Maximum of 40 characters.
                    </span>
                </div>

                <button
                    type="submit"
                    class="submit-button"
                >
                    Verify Before Sending
                </button>

            </form>

            <footer class="form-footer">
                Fields marked with <span class="required-mark">*</span> are required.
            </footer>

        </section>

    </main>

    <!-- VERIFICATION MODAL -->
    <div
        class="modal-overlay"
        id="verificationModal"
        role="presentation"
        aria-hidden="true"
    >

        <section
            class="modal-card"
            role="dialog"
            aria-modal="true"
            aria-labelledby="modalTitle"
            aria-describedby="modalDescription"
        >

            <h3 id="modalTitle">Verify Target Data</h3>

            <p class="modal-description" id="modalDescription">
                Please review the information below before confirming your submission.
            </p>

            <div class="verification-list">

                <div class="verification-row">
                    <span class="verification-label">
                        Target's First Name
                    </span>

                    <span
                        class="verification-value"
                        id="reviewFirstName"
                    ></span>
                </div>

                <div class="verification-row">
                    <span class="verification-label">
                        Target's Last Name
                    </span>

                    <span
                        class="verification-value"
                        id="reviewLastName"
                    ></span>
                </div>

                <div class="verification-row">
                    <span class="verification-label">
                        Target's Government Position
                    </span>

                    <span
                        class="verification-value"
                        id="reviewGovPosition"
                    ></span>
                </div>

                <div class="verification-row">
                    <span class="verification-label">
                        Target's Location
                    </span>

                    <span
                        class="verification-value"
                        id="reviewLocation"
                    ></span>
                </div>

            </div>

            <div class="modal-actions">

                <button
                    type="button"
                    class="modal-button cancel-button"
                    id="cancelButton"
                >
                    Cancel
                </button>

                <button
                    type="button"
                    class="modal-button confirm-button"
                    id="confirmButton"
                >
                    Confirm &amp; Send
                </button>

            </div>

        </section>

    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {

            const form = document.getElementById("wishNoteForm");
            const modal = document.getElementById("verificationModal");

            const cancelButton = document.getElementById("cancelButton");
            const confirmButton = document.getElementById("confirmButton");

            const firstNameInput = document.getElementById("FirstName");
            const lastNameInput = document.getElementById("LastName");
            const govPositionInput = document.getElementById("GovPosition");
            const locationInput = document.getElementById("Location");

            let previouslyFocusedElement = null;

            /*
             * Intercept the normal form submission.
             * Display the verification modal instead of sending immediately.
             */
            form.addEventListener("submit", function (e) {

                e.preventDefault();

                // Ensure the form is valid before showing the modal.
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                // Remember which element had focus before opening the modal.
                previouslyFocusedElement = document.activeElement;

                // Copy field values into the modal using textContent.
                // textContent prevents entered HTML from being interpreted as markup.
                document.getElementById("reviewFirstName").textContent =
                    firstNameInput.value || "(Not provided)";

                document.getElementById("reviewLastName").textContent =
                    lastNameInput.value || "(Not provided)";

                document.getElementById("reviewGovPosition").textContent =
                    govPositionInput.value || "(Not provided)";

                document.getElementById("reviewLocation").textContent =
                    locationInput.value.trim() || "(Not provided)";

                // Show the centered modal overlay.
                modal.classList.add("active");
                modal.setAttribute("aria-hidden", "false");

                document.body.style.overflow = "hidden";

                confirmButton.focus();
            });

            /*
             * Close the modal without submitting the form.
             */
            function closeModal() {

                modal.classList.remove("active");
                modal.setAttribute("aria-hidden", "true");

                document.body.style.overflow = "";

                if (previouslyFocusedElement) {
                    previouslyFocusedElement.focus();
                }
            }

            cancelButton.addEventListener("click", function () {
                closeModal();
            });

            /*
             * CONFIRM & SEND
             *
             * form.submit() deliberately bypasses the submit event listener,
             * preventing the verification modal from reopening.
             */
            confirmButton.addEventListener("click", function () {

                confirmButton.disabled = true;
                confirmButton.textContent = "Sending...";

                // Submit the form directly using POST.
                form.submit();
            });

            /*
             * Allow the user to close the modal using the Escape key.
             */
            document.addEventListener("keydown", function (e) {

                if (
                    e.key === "Escape" &&
                    modal.classList.contains("active")
                ) {
                    closeModal();
                }
            });

        });
    </script>

</body>
</html>