/**
 * Settings screen helper: copies the [counter] shortcode to the clipboard.
 */
( function () {
	'use strict';

	var button = document.getElementById( 'wcp-copy-shortcode' );
	var field = document.getElementById( 'wcp-shortcode-text' );

	if ( ! button || ! field ) {
		return;
	}

	var originalLabel = button.textContent;
	var copiedLabel = button.getAttribute( 'data-copied-label' ) || 'Copied!';
	var resetTimer;

	/**
	 * Select the field so older browsers can still copy with a shortcut.
	 *
	 * @return {void}
	 */
	function selectField() {
		field.focus();
		field.select();
		field.setSelectionRange( 0, field.value.length );
	}

	/**
	 * Confirm the copy on the button for a couple of seconds.
	 *
	 * @param {boolean} ok Whether copying worked.
	 * @return {void}
	 */
	function confirmCopy( ok ) {
		button.textContent = ok ? copiedLabel : originalLabel;
		window.clearTimeout( resetTimer );
		resetTimer = window.setTimeout( function () {
			button.textContent = originalLabel;
		}, 2000 );
	}

	button.addEventListener( 'click', function () {
		selectField();

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard
				.writeText( field.value )
				.then( function () {
					confirmCopy( true );
				} )
				.catch( function () {
					confirmCopy( legacyCopy() );
				} );
			return;
		}

		confirmCopy( legacyCopy() );
	} );

	/**
	 * Fallback for browsers without the async clipboard API.
	 *
	 * @return {boolean} Whether the text was copied.
	 */
	function legacyCopy() {
		selectField();

		try {
			return document.execCommand( 'copy' );
		} catch ( error ) {
			return false;
		}
	}
} )();
