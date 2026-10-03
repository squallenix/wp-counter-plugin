/**
 * Settings screen helper.
 *
 * Copies the [counter] shortcode to the clipboard.
 */
(function () {
  "use strict";

  var button = document.getElementById("wcp-copy-shortcode");

  var field = document.getElementById("wcp-shortcode-text");

  if (!button || !field) {
    return;
  }

  var originalLabel = button.textContent;

  var copiedLabel = button.getAttribute("data-copied-label") || "Copied!";

  var resetTimer = null;

  /**
   * Select the shortcode field.
   *
   * @return {void}
   */
  function selectField() {
    field.focus();
    field.select();

    if ("function" === typeof field.setSelectionRange) {
      field.setSelectionRange(0, field.value.length);
    }
  }

  /**
   * Update the button temporarily after copying.
   *
   * @param {boolean} success Whether copy succeeded.
   * @return {void}
   */
  function showResult(success) {
    button.textContent = success ? copiedLabel : originalLabel;

    window.clearTimeout(resetTimer);

    resetTimer = window.setTimeout(function () {
      button.textContent = originalLabel;
    }, 2000);
  }

  /**
   * Legacy clipboard fallback.
   *
   * @return {boolean}
   */
  function legacyCopy() {
    selectField();

    try {
      return document.execCommand("copy");
    } catch (error) {
      return false;
    }
  }

  button.addEventListener("click", function () {
    selectField();

    if (
      navigator.clipboard &&
      "function" === typeof navigator.clipboard.writeText
    ) {
      navigator.clipboard
        .writeText(field.value)
        .then(function () {
          showResult(true);
        })
        .catch(function () {
          showResult(legacyCopy());
        });

      return;
    }

    showResult(legacyCopy());
  });
})();
