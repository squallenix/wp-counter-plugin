<?php
/**
 * [counter] shortcode.
 *
 * @package WP_Counter_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the [counter] shortcode.
 */
class WCP_Shortcode {

	/**
	 * Register shortcode and frontend assets.
	 */
	public function __construct() {
		add_shortcode(
			'counter',
			array( $this, 'render' )
		);

		add_action(
			'wp_enqueue_scripts',
			array( $this, 'register_assets' )
		);
	}

	/**
	 * Register frontend CSS and JS.
	 *
	 * @return void
	 */
	public function register_assets() {

		wp_register_style(
			'wcp-counter',
			WCP_PLUGIN_URL . 'assets/css/counter.css',
			array(),
			WCP_VERSION
		);

		wp_register_script(
			'wcp-counter',
			WCP_PLUGIN_URL . 'assets/js/counter.js',
			array(),
			WCP_VERSION,
			true
		);

		wp_localize_script(
			'wcp-counter',
			'wcpData',
			array(
				'restUrl' => esc_url_raw(
					rest_url(
						WCP_Rest::REST_NAMESPACE . '/counter'
					)
				),
				'i18n'    => array(
					'error' => __(
						'Could not update the counter.',
						'wp-counter-plugin'
					),
					'updated' => __(
						'Count is now %s',
						'wp-counter-plugin'
					),
				),
			)
		);
	}

	/**
	 * Render the counter.
	 *
	 * Examples:
	 *
	 * [counter]
	 * [counter id="likes"]
	 * [counter title="Likes"]
	 * [counter step="5"]
	 * [counter color="#16a34a"]
	 * [counter button="no"]
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {

		if ( is_feed() ) {
			return '';
		}

		$settings = wcp_get_settings();

		/*
		 * Default shortcode values.
		 */
		$atts = shortcode_atts(
			array(
				'title'  => $settings['title'],
				'step'   => $settings['step'],
				'color'  => $settings['color'],
				'button' => $settings['show_button'] ? 'yes' : 'no',
				'id'     => '',
			),
			$atts,
			'counter'
		);

		/*
		 * Title.
		 */
		$title = sanitize_text_field(
			(string) $atts['title']
		);

		if ( '' === $title ) {
			$title = $settings['title'];
		}

		/*
		 * Step.
		 */
		$step = filter_var(
			$atts['step'],
			FILTER_VALIDATE_INT
		);

		if (
			false === $step ||
			$step < 1 ||
			$step > WCP_MAX_STEP
		) {
			$step = $settings['step'];
		}

		/*
		 * Colour.
		 */
		$color = sanitize_hex_color(
			(string) $atts['color']
		);

		if ( ! $color ) {
			$color = $settings['color'];
		}

		/*
		 * Show/hide button.
		 */
		$button_value = strtolower(
			(string) $atts['button']
		);

		$show_button = ! in_array(
			$button_value,
			array(
				'no',
				'false',
				'0',
			),
			true
		);

		/*
		 * Counter ID.
		 */
		$counter_id = wcp_sanitize_counter_id(
			$atts['id']
		);

		/*
		 * Getting the value creates the counter automatically
		 * if it does not exist yet.
		 */
		$value = wcp_get_counter_value(
			$counter_id
		);

		/*
		 * Create token for this counter and step.
		 */
		$token = '';

		if ( $show_button ) {
			$token = wcp_make_token(
				$counter_id,
				$step
			);
		}

		/*
		 * Only load CSS/JS when shortcode is actually used.
		 */
		wp_enqueue_style(
			'wcp-counter'
		);

		wp_enqueue_script(
			'wcp-counter'
		);

		ob_start();
		?>

		<div
			class="wcp-counter"
			data-wcp-id="<?php echo esc_attr( $counter_id ); ?>"
			style="--wcp-color: <?php echo esc_attr( $color ); ?>;"
		>

			<span class="wcp-counter__title">
				<?php echo esc_html( $title ); ?>
			</span>

			<div class="wcp-counter__row">

				<span class="wcp-counter__value">
					<?php
					echo esc_html(
						number_format_i18n( $value )
					);
					?>
				</span>

				<?php if ( $show_button ) : ?>

					<button
						type="button"
						class="wcp-counter__button"
						data-wcp-step="<?php echo esc_attr( $step ); ?>"
						data-wcp-token="<?php echo esc_attr( $token ); ?>"
						aria-label="<?php
						echo esc_attr(
							sprintf(
								__(
									'Increase %s',
									'wp-counter-plugin'
								),
								$title
							)
						);
						?>"
					>
						<span aria-hidden="true">+</span>
					</button>

				<?php endif; ?>

			</div>

			<span
				class="wcp-counter__status"
				role="status"
				aria-live="polite"
			></span>

		</div>

		<?php

		return ob_get_clean();
	}
}