/**
 * Frontend behaviour for the [counter] shortcode.
 */

(function () {
  "use strict";

  if (typeof window.wcpData === "undefined") {
    return;
  }

  var config = window.wcpData;

  /**
   * Show a message under the counter.
   *
   * @param {HTMLElement} wrapper Counter wrapper.
   * @param {string} message Message text.
   * @param {boolean} isError Whether this is an error.
   */
  function showMessage(wrapper, message, isError) {
    var status = wrapper.querySelector(".wcp-counter__status");

    if (!status) {
      return;
    }

    status.textContent = message || "";

    if (isError) {
      status.classList.add("is-error");
    } else {
      status.classList.remove("is-error");
    }
  }

  /**
   * Update the visible counter value.
   *
   * @param {HTMLElement} wrapper Counter wrapper.
   * @param {string} value New formatted value.
   */
  function updateValue(wrapper, value) {
    var element = wrapper.querySelector(".wcp-counter__value");

    if (!element) {
      return;
    }

    element.textContent = value;

    element.classList.remove("is-updated");

    /*
     * Restart animation.
     */
    void element.offsetWidth;

    element.classList.add("is-updated");
  }

  /**
   * Send increment request.
   *
   * @param {HTMLElement} button Counter button.
   */
  function incrementCounter(button) {
    var wrapper = button.closest(".wcp-counter");

    if (!wrapper || button.disabled) {
      return;
    }

    var id = wrapper.getAttribute("data-wcp-id") || "";

    var amount = parseInt(button.getAttribute("data-wcp-step"), 10);

    var token = button.getAttribute("data-wcp-token") || "";

    if (isNaN(amount) || amount < 1 || !token) {
      showMessage(wrapper, config.i18n.error, true);

      return;
    }

    var body = new URLSearchParams();

    body.append("amount", amount);

    body.append("token", token);

    if (id) {
      body.append("id", id);
    }

    button.disabled = true;

    wrapper.classList.add("is-loading");

    showMessage(wrapper, "", false);

    fetch(config.restUrl, {
      method: "POST",

      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
      },

      body: body.toString(),
    })
      .then(function (response) {
        return response
          .json()
          .catch(function () {
            return {};
          })
          .then(function (data) {
            if (!response.ok) {
              throw new Error(data.message || config.i18n.error);
            }

            return data;
          });
      })

      .then(function (data) {
        updateValue(wrapper, data.formatted);

        showMessage(
          wrapper,
          config.i18n.updated.replace("%s", data.formatted),
          false,
        );

        setTimeout(function () {
          showMessage(wrapper, "", false);
        }, 3000);
      })

      .catch(function (error) {
        showMessage(wrapper, error.message || config.i18n.error, true);
      })

      .finally(function () {
        button.disabled = false;

        wrapper.classList.remove("is-loading");
      });
  }

  /*
   * One click listener works for every counter.
   */
  document.addEventListener("click", function (event) {
    if (!event.target.closest) {
      return;
    }

    var button = event.target.closest(".wcp-counter__button");

    if (button) {
      incrementCounter(button);
    }
  });
})();
