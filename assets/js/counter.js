/**
 * Frontend behaviour for the [counter] shortcode.
 *
 * One delegated click listener serves any number of counters on the page.
 * The value is updated through the REST endpoint, so no page reload.
 */
( function () {
	'use strict';

	if ( 'undefined' === typeof window.wcpData ) {
		return;
	}

	var config = window.wcpData;

	/**
	 * Paint the value returned by the server and replay the pop animation.
	 *
	 * @param {HTMLElement} wrapper Counter wrapper element.
	 * @param {Object}      data    Response payload.
	 * @return {void}
	 */
	function showValue( wrapper, data ) {
		var value = wrapper.querySelector( '.wcp-counter__value' );

		if ( ! value ) {
			return;
		}

		value.textContent = data.formatted;
		value.classList.remove( 'is-updated' );

		// Force a reflow so the animation restarts on every click.
		void value.offsetWidth;
		value.classList.add( 'is-updated' );
	}

	/**
	 * Show a message in the counter's status line.
	 *
	 * @param {HTMLElement} wrapper Counter wrapper element.
	 * @param {string}      message Message to display.
	 * @return {void}
	 */
	function showMessage( wrapper, message ) {
		var status = wrapper.querySelector( '.wcp-counter__status' );

		if ( status ) {
			status.textContent = message;
		}
	}

	/**
	 * Ask the server to increase the counter.
	 *
	 * @param {HTMLElement} button The clicked "+" button.
	 * @return {void}
	 */
	function increment( button ) {
		var wrapper = button.closest( '.wcp-counter' );

		if ( ! wrapper || button.disabled ) {
			return;
		}

		var amount = parseInt( button.getAttribute( 'data-wcp-step' ), 10 );
		if ( isNaN( amount ) || amount < 1 ) {
			amount = 1;
		}

		var finish = function () {
			button.disabled = false;
			wrapper.classList.remove( 'is-loading' );
		};

		button.disabled = true;
		wrapper.classList.add( 'is-loading' );
		showMessage( wrapper, '' );

		fetch( config.restUrl + '?amount=' + amount, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				Accept: 'application/json',
				'X-WP-Nonce': config.nonce
			}
		} )
			.then( function ( response ) {
				return response.json().then( function ( body ) {
					if ( ! response.ok ) {
						throw new Error(
							body && body.message ? body.message : config.i18n.error
						);
					}

					return body;
				} );
			} )
			.then( function ( data ) {
				showValue( wrapper, data );
			} )
			.catch( function ( error ) {
				showMessage(
					wrapper,
					error && error.message ? error.message : config.i18n.error
				);
			} )
			.then( finish );
	}

	document.addEventListener( 'click', function ( event ) {
		var target = event.target;

		if ( ! target.closest ) {
			return;
		}

		var button = target.closest( '.wcp-counter__button' );
		if ( button ) {
			increment( button );
		}
	} );
} )();
