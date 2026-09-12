/*
 * ============================================================
 * GlobeTrek Adventures
 * Shared JavaScript
 * ============================================================
 *
 * This file provides small client-side usability features.
 *
 * Important:
 * Client-side validation improves usability, but PHP remains
 * responsible for server-side validation and security.
 */


/*
 * Wait until the page has loaded.
 */
document.addEventListener('DOMContentLoaded', function () {


    /*
     * --------------------------------------------------------
     * Confirm potentially destructive actions
     * --------------------------------------------------------
     *
     * Buttons/forms can use:
     *
     * data-confirm="Are you sure?"
     */
    const confirmationElements =
        document.querySelectorAll('[data-confirm]');


    confirmationElements.forEach(function (element) {

        element.addEventListener('submit', function (event) {

            const message =
                element.getAttribute('data-confirm');

            if (
                message
                && !window.confirm(message)
            ) {
                event.preventDefault();
            }
        });
    });


    /*
     * --------------------------------------------------------
     * Password confirmation
     * --------------------------------------------------------
     *
     * Used on registration/staff forms when elements with
     * these IDs are present.
     */
    const password =
        document.getElementById('password');

    const confirmPassword =
        document.getElementById('confirm_password');


    if (
        password
        && confirmPassword
    ) {

        confirmPassword.addEventListener(
            'input',
            function () {

                if (
                    confirmPassword.value !== ''
                    && password.value !== confirmPassword.value
                ) {

                    confirmPassword.setCustomValidity(
                        'Passwords do not match.'
                    );

                } else {

                    confirmPassword.setCustomValidity('');
                }
            }
        );
    }


    /*
     * --------------------------------------------------------
     * Automatically focus the first visible form field
     * --------------------------------------------------------
     */
    const firstInput =
        document.querySelector(
            'main input:not([type="hidden"]), main select, main textarea'
        );


    if (firstInput) {
        firstInput.focus();
    }


    /*
     * --------------------------------------------------------
     * Travel date helper
     * --------------------------------------------------------
     *
     * Any date input with id="travel_date" will receive
     * today's date as its minimum value.
     */
    const travelDate =
        document.getElementById('travel_date');


    if (travelDate) {

        const today =
            new Date().toISOString().split('T')[0];

        travelDate.min = today;
    }


    /*
     * --------------------------------------------------------
     * Traveler count / price preview
     * --------------------------------------------------------
     *
     * If a booking form provides:
     *
    * data-package-price="45000"
     *
     * on an element and uses:
     *
     * id="num_travelers"
     *
     * this script can display an estimated total.
     *
     * The real calculation is still performed in PHP.
     */
    const travelersInput =
        document.getElementById('num_travelers');

    const priceElement =
        document.querySelector('[data-package-price]');

    const totalPreview =
        document.getElementById('total_price_preview');


    function updateTotalPreview() {

        if (
            !travelersInput
            || !priceElement
            || !totalPreview
        ) {
            return;
        }


        const price =
            Number(priceElement.dataset.packagePrice);

        const travelers =
            Number(travelersInput.value);


        if (
            Number.isFinite(price)
            && Number.isFinite(travelers)
            && travelers > 0
        ) {

            const total =
                price * travelers;

            totalPreview.textContent =
                'Estimated total: Rs. '
                + total.toLocaleString(
                    'en-LK',
                    {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    }
                );

        } else {

            totalPreview.textContent =
                '';
        }
    }


    if (travelersInput) {

        travelersInput.addEventListener(
            'input',
            updateTotalPreview
        );

        updateTotalPreview();
    }


    /*
     * --------------------------------------------------------
     * Character count for textareas
     * --------------------------------------------------------
     *
     * Textareas with a maxlength attribute get a small
     * character counter.
     */
    const textareas =
        document.querySelectorAll(
            'textarea[maxlength]'
        );


    textareas.forEach(function (textarea) {

        const maxLength =
            Number(
                textarea.getAttribute('maxlength')
            );


        if (!Number.isFinite(maxLength)) {
            return;
        }


        const counter =
            document.createElement('small');

        counter.className =
            'text-muted textarea-counter';


        textarea.insertAdjacentElement(
            'afterend',
            counter
        );


        function updateCounter() {

            const remaining =
                maxLength - textarea.value.length;

            counter.textContent =
                remaining
                + ' characters remaining';
        }


        textarea.addEventListener(
            'input',
            updateCounter
        );


        updateCounter();
    });

});