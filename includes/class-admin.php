<?php
/**
 * Admin settings page (Settings API).
 *
 * @package WP_Counter_Plugin
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the "WP Counter" settings screen under Settings → WP Counter.
 */
class WCP_Admin {

	/** Slug of the settings page. */
	const PAGE_SLUG = 'wp-counter-plugin';

	/** Settings API option group. */
	const OPTION_GROUP = 'wcp_counter_group';

	/**
	 * Hook the admin behaviour.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_wcp_reset_counter', array( $this, 'handle_reset' ) );
		add_action( 'admin_notices', array( $this, 'render_reset_notice' ) );
	}

	/**
	 * Add the settings page under the Settings menu.
	 *
	 * @return void
	 */
	public function add_menu_page() {
		add_options_page(
			__( 'WP Counter', 'wp-counter-plugin' ),
			__( 'WP Counter', 'wp-counter-plugin' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the option, the section and the fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			WCP_SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => wcp_get_default_settings(),
			)
		);

		add_settings_section(
			'wcp_main_section',
			__( 'Counter settings', 'wp-counter-plugin' ),
			array( $this, 'render_section' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'wcp_title',
			__( 'Title', 'wp-counter-plugin' ),
			array( $this, 'render_title_field' ),
			self::PAGE_SLUG,
			'wcp_main_section'
		);

		add_settings_field(
			'wcp_starting_value',
			__( 'Starting value', 'wp-counter-plugin' ),
			array( $this, 'render_starting_value_field' ),
			self::PAGE_SLUG,
			'wcp_main_section'
		);

		add_settings_field(
			'wcp_step',
			__( 'Step', 'wp-counter-plugin' ),
			array( $this, 'render_step_field' ),
			self::PAGE_SLUG,
			'wcp_main_section'
		);

		add_settings_field(
			'wcp_color',
			__( 'Accent colour', 'wp-counter-plugin' ),
			array( $this, 'render_color_field' ),
			self::PAGE_SLUG,
			'wcp_main_section'
		);

		add_settings_field(
			'wcp_show_button',
			__( 'Show the "+" button', 'wp-counter-plugin' ),
			array( $this, 'render_button_field' ),
			self::PAGE_SLUG,
			'wcp_main_section'
		);
	}

	/**
	 * Validate and clean everything the admin submits.
	 *
	 * Invalid values never silently disappear: an error message is attached to
	 * the field and the previously saved value is kept instead.
	 *
	 * @param array $input Raw input from the form.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$existing = wcp_get_settings();
		$output   = $existing;

		// Title: plain text, never empty.
		$title = isset( $input['title'] ) && is_string( $input['title'] )
			? sanitize_text_field( $input['title'] )
			: '';
		if ( '' === $title ) {
			add_settings_error(
				'wcp_title',
				'wcp_invalid_title',
				__( 'The title cannot be empty, the previous title was kept.', 'wp-counter-plugin' ),
				'error'
			);
		} else {
			$output['title'] = $title;
		}

		// Starting value: integer within a sane range.
		if ( isset( $input['starting_value'] ) ) {
			$starting_value = intval( $input['starting_value'] );
			if ( $starting_value < -999999 || $starting_value > 999999 ) {
				add_settings_error(
					'wcp_starting_value',
					'wcp_invalid_starting_value',
					__( 'The starting value must be between -999999 and 999999.', 'wp-counter-plugin' ),
					'error'
				);
			} else {
				$output['starting_value'] = $starting_value;
			}
		}

		// Step: a whole number of 1 or more. Zero/negative is rejected.
		if ( isset( $input['step'] ) ) {
			$step = intval( $input['step'] );
			if ( $step < 1 ) {
				add_settings_error(
					'wcp_step',
					'wcp_invalid_step',
					__( 'The step must be a whole number of 1 or more. The previous step was kept.', 'wp-counter-plugin' ),
					'error'
				);
			} else {
				$output['step'] = $step;
			}
		}

		// Colour: valid hex colour only.
		if ( isset( $input['color'] ) ) {
			$color = is_string( $input['color'] ) ? sanitize_hex_color( $input['color'] ) : null;
			if ( $color ) {
				$output['color'] = $color;
			} else {
				add_settings_error(
					'wcp_color',
					'wcp_invalid_color',
					__( 'Please choose a valid colour.', 'wp-counter-plugin' ),
					'error'
				);
			}
		}

		// Checkbox: absent means unchecked.
		$output['show_button'] = ! empty( $input['show_button'] );

		// A new starting value means a fresh counter: apply it right away.
		if ( $output['starting_value'] !== $existing['starting_value'] ) {
			wcp_set_counter_value( $output['starting_value'] );
		}

		return $output;
	}

	/**
	 * Section intro text.
	 *
	 * @return void
	 */
	public function render_section() {
		echo '<p>' . esc_html__( 'These settings control every [counter] shortcode that does not override them with an attribute.', 'wp-counter-plugin' ) . '</p>';
	}

	/**
	 * Title field.
	 *
	 * @return void
	 */
	public function render_title_field() {
		$settings = wcp_get_settings();
		printf(
			'<input type="text" class="regular-text" id="wcp_title" name="%1$s[title]" value="%2$s" placeholder="%3$s" /> <p class="description">%4$s</p>',
			esc_attr( WCP_SETTINGS_OPTION ),
			esc_attr( $settings['title'] ),
			esc_attr__( 'e.g. Visitor Counter', 'wp-counter-plugin' ),
			esc_html__( 'Shown above the number on the frontend.', 'wp-counter-plugin' )
		);
	}

	/**
	 * Starting value field.
	 *
	 * @return void
	 */
	public function render_starting_value_field() {
		$settings = wcp_get_settings();
		printf(
			'<input type="number" step="1" class="small-text" id="wcp_starting_value" name="%1$s[starting_value]" value="%2$d" /> <p class="description">%3$s</p>',
			esc_attr( WCP_SETTINGS_OPTION ),
			intval( $settings['starting_value'] ),
			esc_html__( 'Changing this resets the counter to the new value immediately.', 'wp-counter-plugin' )
		);
	}

	/**
	 * Step field.
	 *
	 * @return void
	 */
	public function render_step_field() {
		$settings = wcp_get_settings();
		printf(
			'<input type="number" step="1" min="1" class="small-text" id="wcp_step" name="%1$s[step]" value="%2$d" /> <p class="description">%3$s</p>',
			esc_attr( WCP_SETTINGS_OPTION ),
			intval( $settings['step'] ),
			esc_html__( 'How much the counter increases on every click. Must be 1 or more.', 'wp-counter-plugin' )
		);
	}

	/**
	 * Colour picker field.
	 *
	 * @return void
	 */
	public function render_color_field() {
		$settings = wcp_get_settings();
		printf(
			'<input type="text" class="wcp-color-picker" id="wcp_color" name="%1$s[color]" value="%2$s" data-default-color="%3$s" /> <p class="description">%4$s</p>',
			esc_attr( WCP_SETTINGS_OPTION ),
			esc_attr( $settings['color'] ),
			esc_attr( wcp_get_default_settings()['color'] ),
			esc_html__( 'Used for the button and the counter border.', 'wp-counter-plugin' )
		);
	}

	/**
	 * Show/hide button checkbox.
	 *
	 * @return void
	 */
	public function render_button_field() {
		$settings = wcp_get_settings();
		printf(
			'<label for="wcp_show_button"><input type="checkbox" id="wcp_show_button" name="%1$s[show_button]" value="1" %2$s /> %3$s</label>',
			esc_attr( WCP_SETTINGS_OPTION ),
			checked( $settings['show_button'], true, false ),
			esc_html__( 'Visitors can click "+" to increase the counter.', 'wp-counter-plugin' )
		);
	}

	/**
	 * Enqueue the colour picker and the small admin script on our page only.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_color_picker();

		wp_enqueue_script(
			'wcp-admin',
			WCP_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			WCP_VERSION,
			true
		);
	}

	/**
	 * Render the settings screen.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to access this page.', 'wp-counter-plugin' ) );
		}

		$settings      = wcp_get_settings();
		$current_value = wcp_get_counter_value();
		$example       = sprintf(
			'[counter title="%1$s" step="%2$d" color="%3$s"]',
			$settings['title'],
			$settings['step'],
			$settings['color']
		);
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php settings_errors(); ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>

			<h2><?php esc_html_e( 'Current counter value', 'wp-counter-plugin' ); ?></h2>
			<p>
				<strong><?php echo esc_html( number_format_i18n( $current_value ) ); ?></strong>
				&mdash;
				<?php esc_html_e( 'the number visitors see on the frontend.', 'wp-counter-plugin' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="wcp_reset_counter" />
				<?php wp_nonce_field( 'wcp_reset_counter' ); ?>
				<?php submit_button( __( 'Reset counter to starting value', 'wp-counter-plugin' ), 'secondary', 'submit', false ); ?>
			</form>

			<h2><?php esc_html_e( 'Use the shortcode', 'wp-counter-plugin' ); ?></h2>
			<p><?php esc_html_e( 'Paste this into any post, page or text widget:', 'wp-counter-plugin' ); ?></p>

			<p>
				<input
					type="text"
					id="wcp-shortcode-text"
					class="regular-text code"
					readonly="readonly"
					value="[counter]"
				/>
				<button
					type="button"
					class="button"
					id="wcp-copy-shortcode"
					data-copied-label="<?php esc_attr_e( 'Copied!', 'wp-counter-plugin' ); ?>"
				>
					<?php esc_html_e( 'Copy shortcode', 'wp-counter-plugin' ); ?>
				</button>
			</p>

			<p><?php esc_html_e( 'Optional attributes override the settings above:', 'wp-counter-plugin' ); ?></p>
			<p><code><?php echo esc_html( $example ); ?></code></p>

			<table class="widefat striped" style="max-width:760px">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Attribute', 'wp-counter-plugin' ); ?></th>
						<th><?php esc_html_e( 'Description', 'wp-counter-plugin' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>title</code></td>
						<td><?php esc_html_e( 'Heading shown above the number. Defaults to the settings title.', 'wp-counter-plugin' ); ?></td>
					</tr>
					<tr>
						<td><code>step</code></td>
						<td><?php esc_html_e( 'Amount added on each click. Defaults to the settings step.', 'wp-counter-plugin' ); ?></td>
					</tr>
					<tr>
						<td><code>color</code></td>
						<td><?php esc_html_e( 'Accent colour, e.g. #16a34a. Defaults to the settings colour.', 'wp-counter-plugin' ); ?></td>
					</tr>
					<tr>
						<td><code>button</code></td>
						<td><?php esc_html_e( 'Show or hide the "+" button: [counter button="no"].', 'wp-counter-plugin' ); ?></td>
					</tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'How it works', 'wp-counter-plugin' ); ?></h2>
			<ol>
				<li><?php esc_html_e( 'Set the title, starting value, step and colour above, then press Save Changes.', 'wp-counter-plugin' ); ?></li>
				<li><?php esc_html_e( 'Add [counter] to any post or page.', 'wp-counter-plugin' ); ?></li>
				<li><?php esc_html_e( 'Visitors press "+" and the value increases by the step without reloading the page.', 'wp-counter-plugin' ); ?></li>
				<li><?php esc_html_e( 'Use the reset button above to go back to the starting value at any time.', 'wp-counter-plugin' ); ?></li>
			</ol>
		</div>
		<?php
	}

	/**
	 * Reset the counter to the starting value (nonce + capability checked).
	 *
	 * @return void
	 */
	public function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to reset the counter.', 'wp-counter-plugin' ), '', 403 );
		}

		check_admin_referer( 'wcp_reset_counter' );

		wcp_set_counter_value( wcp_get_settings()['starting_value'] );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => self::PAGE_SLUG,
					'wcp-reset' => '1',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Confirmation notice after a reset.
	 *
	 * @return void
	 */
	public function render_reset_notice() {
		if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		if ( ! isset( $_GET['wcp-reset'] ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'Counter reset to the starting value.', 'wp-counter-plugin' )
		);
	}
}
