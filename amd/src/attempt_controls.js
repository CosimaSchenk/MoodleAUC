/**
 * JavaScript for the auc_responses behaviour.
 *
 * @module qbehaviour_auc_responses/attempt_controls
 * @copyright 2024
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Run callback when DOM is ready.
 *
 * @param {Function} fn
 */
const onDomReady = (fn) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fn, {once: true});
    } else {
        fn();
    }
};

/**
 * Disable radio buttons whose value matches any of the previous responses
 * and disable the quiz "Next" button (if present).
 *
 * NOTE: This intentionally mimics the original behaviour:
 * - It queries the whole document for matching radio buttons (not scoped).
 * - It disables the element with id "mod_quiz-next-nav" if present.
 *
 * @param {string[]} previousResponses
 * @param {boolean} disableNext
 */
export const init = (previousResponses, disableNext) => {
    onDomReady(() => {
        const responses = Array.isArray(previousResponses) ? previousResponses : [];

        responses.forEach((response) => {
            if (response === null || response === undefined) {
                return;
            }

            // Keep behaviour identical: find first matching radio in the whole document.
            const value = String(response);

            // Use CSS.escape when available to avoid selector breakage.
            const escapedValue = (window.CSS && typeof window.CSS.escape === 'function')
                ? window.CSS.escape(value)
                : value.replace(/["\\]/g, '\\$&');

            const radio = document.querySelector(`input[type="radio"][value="${escapedValue}"]`);
            if (radio) {
                radio.checked = false;
                radio.disabled = true;
            }
        });

        if (disableNext) {
            const nextButton = document.getElementById('mod_quiz-next-nav');
            if (nextButton) {
                nextButton.setAttribute('disabled', 'disabled');
            }
        }
    });
};