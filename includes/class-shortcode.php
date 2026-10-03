<?php
/**
 * [counter] shortcode and its frontend assets.
 *
 * @package WP_Counter_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the shortcode, renders the markup and loads CSS/JS.
 */
class WCP_Shortcode {

	/**
	 * Hook the frontend behaviour.
	 */
	public function __construct() {
		add_shortcode( 'counter', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Load the stylesheet and script used by the counter.
	 *
	 * The files are tiny and the shortcode can also come from a widget or a
	 * page builder, so they are loaded on every frontend request instead of
	 * guessing where the shortcode might be.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		wp_enqueue_style(
			'wcp-counter',
			WCP_PLUGIN_URL . 'assets/css/counter.css',
			array(),
			WCP_VERSION
		);

		wp_enqueue_script(
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
				'restUrl' => esc_url_raw( rest_url( WCP_Rest::REST_NAMESPACE . '/counter' ) ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'i18n'    => array(
					'error' => __( 'Could not update the counter. Please try again.', 'wp-counter-plugin' ),
				),
			)
		);
	}

	/**
	 * Render the counter.
	 *
	 * Supported attributes: title, step, color, button.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string HTML output (empty string when the tag is unknown).
	 */
	public function render( $atts ) {
		$settings = wcp_get_settings();

		$atts = shortcode_atts(
			array(
				'title'  => $settings['title'],
				'step'   => $settings['step'],
				'color'  => $settings['color'],
				'button' => $settings['show_button'] ? 'yes' : 'no',
			),
			$atts,
			'counter'
		);

		$title = sanitize_text_field( (string) $atts['title'] );
		if ( '' === $title ) {
			$title = $settings['title'];
		}

		$step = intval( $atts['step'] );
		if ( $step < 1 ) {
			$step = $settings['step'];
		}

		$color = sanitize_hex_color( (string) $atts['color'] );
		if ( ! $color ) {
			$color = $settings['color'];
		}

		$show_button = ! in_array(
			strtolower( (string) $atts['button'] ),
			array( 'no', 'false', '0' ),
			true
		);

		$value = wcp_get_counter_value();

		ob_start();
		?>
		<div class="wcp-counter" style="--wcp-color: <?php echo esc_attr( $color ); ?>;" data-wcp-counter>
			<span class="wcp-counter__title"><?php echo esc_html( $title ); ?></span>

			<div class="wcp-counter__row">
				<span class="wcp-counter__value"><?php echo esc_html( number_format_i18n( $value ) ); ?></span>

				<?php if ( $show_button ) : ?>
					<button
						type="button"
						class="wcp-counter__button"
						data-wcp-step="<?php echo esc_attr( $step ); ?>"
						aria-label="<?php echo esc_attr( $title ); ?>"
					>
						<span aria-hidden="true">+</span>
					</button>
				<?php endif; ?>
			</div>

			<span class="wcp-counter__status" role="status" aria-live="polite"></span>
		</div>
		<?php
		return ob_get_clean();
	}
}
