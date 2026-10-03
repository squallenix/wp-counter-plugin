<?php
/**
 * Admin settings page.
 *
 * @package WP_Counter_Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles the plugin admin page.
 */
class WCP_Admin {

	/**
	 * Settings page slug.
	 */
	const PAGE_SLUG = 'wp-counter-plugin';

	/**
	 * Settings group.
	 */
	const OPTION_GROUP = 'wcp_counter_group';

	/**
	 * Register admin hooks.
	 */
	public function __construct() {

		add_action(
			'admin_menu',
			array( $this, 'add_menu_page' )
		);

		add_action(
			'admin_init',
			array( $this, 'register_settings' )
		);

		add_action(
			'admin_enqueue_scripts',
			array( $this, 'enqueue_assets' )
		);

		add_action(
			'admin_post_wcp_reset_all',
			array( $this, 'reset_all' )
		);

		add_action(
			'admin_post_wcp_reset_single',
			array( $this, 'reset_single' )
		);
	}

	/**
	 * Add Settings → WP Counter.
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
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {

		register_setting(
			self::OPTION_GROUP,
			WCP_SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array(
					$this,
					'sanitize_settings',
				),
				'default'           => wcp_get_default_settings(),
			)
		);

		add_settings_section(
			'wcp_main_section',
			__( 'Counter Settings', 'wp-counter-plugin' ),
			'__return_false',
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
			__( 'Starting Value', 'wp-counter-plugin' ),
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
			__( 'Accent Colour', 'wp-counter-plugin' ),
			array( $this, 'render_color_field' ),
			self::PAGE_SLUG,
			'wcp_main_section'
		);

		add_settings_field(
			'wcp_show_button',
			__( 'Show Button', 'wp-counter-plugin' ),
			array( $this, 'render_button_field' ),
			self::PAGE_SLUG,
			'wcp_main_section'
		);
	}

	/**
	 * Validate settings.
	 *
	 * @param mixed $input Raw form input.
	 * @return array
	 */
	public function sanitize_settings( $input ) {

		$input = is_array( $input )
			? $input
			: array();

		$old    = wcp_get_settings();
		$output = $old;

		/*
		 * Title.
		 */
		$title = isset( $input['title'] )
			? sanitize_text_field( $input['title'] )
			: '';

		if ( '' !== $title ) {
			$output['title'] = $title;
		}

		/*
		 * Starting value.
		 */
		if ( isset( $input['starting_value'] ) ) {

			$value = filter_var(
				$input['starting_value'],
				FILTER_VALIDATE_INT
			);

			if (
				false !== $value &&
				$value >= WCP_MIN_VALUE &&
				$value <= WCP_MAX_VALUE
			) {
				$output['starting_value'] = $value;
			}
		}

		/*
		 * Step.
		 */
		if ( isset( $input['step'] ) ) {

			$step = filter_var(
				$input['step'],
				FILTER_VALIDATE_INT
			);

			if (
				false !== $step &&
				$step >= 1 &&
				$step <= WCP_MAX_STEP
			) {
				$output['step'] = $step;
			}
		}

		/*
		 * Colour.
		 */
		if ( isset( $input['color'] ) ) {

			$color = sanitize_hex_color(
				$input['color']
			);

			if ( $color ) {
				$output['color'] = $color;
			}
		}

		/*
		 * Checkbox.
		 */
		$output['show_button'] = ! empty(
			$input['show_button']
		);

		return $output;
	}

	/**
	 * Title input.
	 *
	 * @return void
	 */
	public function render_title_field() {

		$settings = wcp_get_settings();

		printf(
			'<input
				type="text"
				class="regular-text"
				name="%1$s[title]"
				value="%2$s"
			/>',
			esc_attr( WCP_SETTINGS_OPTION ),
			esc_attr( $settings['title'] )
		);
	}

	/**
	 * Starting value input.
	 *
	 * @return void
	 */
	public function render_starting_value_field() {

		$settings = wcp_get_settings();

		printf(
			'<input
				type="number"
				name="%1$s[starting_value]"
				value="%2$d"
				min="%3$d"
				max="%4$d"
			/>',
			esc_attr( WCP_SETTINGS_OPTION ),
			intval( $settings['starting_value'] ),
			WCP_MIN_VALUE,
			WCP_MAX_VALUE
		);
	}

	/**
	 * Step input.
	 *
	 * @return void
	 */
	public function render_step_field() {

		$settings = wcp_get_settings();

		printf(
			'<input
				type="number"
				name="%1$s[step]"
				value="%2$d"
				min="1"
				max="%3$d"
			/>',
			esc_attr( WCP_SETTINGS_OPTION ),
			intval( $settings['step'] ),
			WCP_MAX_STEP
		);
	}

	/**
	 * Colour input.
	 *
	 * @return void
	 */
	public function render_color_field() {

		$settings = wcp_get_settings();

		printf(
			'<input
				type="text"
				class="wcp-color-picker"
				name="%1$s[color]"
				value="%2$s"
			/>',
			esc_attr( WCP_SETTINGS_OPTION ),
			esc_attr( $settings['color'] )
		);
	}

	/**
	 * Show button checkbox.
	 *
	 * @return void
	 */
	public function render_button_field() {

		$settings = wcp_get_settings();

		printf(
			'<label>
				<input
					type="checkbox"
					name="%1$s[show_button]"
					value="1"
					%2$s
				/>
				%3$s
			</label>',
			esc_attr( WCP_SETTINGS_OPTION ),
			checked(
				$settings['show_button'],
				true,
				false
			),
			esc_html__(
				'Show the + button',
				'wp-counter-plugin'
			)
		);
	}

	/**
	 * Load admin assets.
	 *
	 * @param string $hook Current admin page.
	 * @return void
	 */
	public function enqueue_assets( $hook ) {

		if (
			'settings_page_' . self::PAGE_SLUG !== $hook
		) {
			return;
		}

		wp_enqueue_script( 'wp-color-picker' );
wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'wcp-admin',
			WCP_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			WCP_VERSION,
			true
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_page() {

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings  = wcp_get_settings();
		$named_ids = wcp_get_counter_ids();

		?>

		<div class="wrap">

			<h1>
				<?php
				echo esc_html(
					get_admin_page_title()
				);
				?>
			</h1>

			<?php settings_errors(); ?>

			<form
				method="post"
				action="options.php"
			>

				<?php
				settings_fields(
					self::OPTION_GROUP
				);

				do_settings_sections(
					self::PAGE_SLUG
				);

				submit_button();
				?>

			</form>

			<hr>

			<h2>
				<?php
					esc_html_e(
						'Counters',
						'wp-counter-plugin'
					);
				?>
			</h2>

			<table
				class="widefat striped"
				style="max-width:700px"
			>

				<thead>
					<tr>
						<th>Shortcode</th>
						<th>Value</th>
						<th>Action</th>
					</tr>
				</thead>

				<tbody>

					<?php
					$this->render_counter_row(
						''
					);
					?>

					<?php foreach ( $named_ids as $id ) : ?>

						<?php
						$this->render_counter_row(
							$id
						);
						?>

					<?php endforeach; ?>

				</tbody>

			</table>

			<p>

				<form
					method="post"
					action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				>

					<input
						type="hidden"
						name="action"
						value="wcp_reset_all"
					/>

					<?php
					wp_nonce_field(
						'wcp_reset_all'
					);
					?>

					<?php
					submit_button(
						__(
							'Reset All Counters',
							'wp-counter-plugin'
						),
						'secondary',
						'submit',
						false
					);
					?>

				</form>

			</p>

			<hr>

			<h2>
				<?php
					esc_html_e(
						'Shortcode',
						'wp-counter-plugin'
					);
				?>
			</h2>

			<p>

				<input
					type="text"
					id="wcp-shortcode-text"
					class="regular-text code"
					value="[counter]"
					readonly
				/>

				<button
					type="button"
					id="wcp-copy-shortcode"
					class="button"
					data-copied-label="<?php
						esc_attr_e(
							'Copied!',
							'wp-counter-plugin'
						);
					?>"
				>
					<?php
						esc_html_e(
							'Copy Shortcode',
							'wp-counter-plugin'
						);
					?>
				</button>

			</p>

			<p>
				<code>
					[counter id="likes" title="Likes" step="1" color="#2563eb"]
				</code>
			</p>

		</div>

		<?php
	}

	/**
	 * Render one counter table row.
	 *
	 * @param string $id Counter ID.
	 * @return void
	 */
	private function render_counter_row( $id ) {

		$id    = wcp_sanitize_counter_id( $id );
		$value = wcp_get_counter_value( $id );

		$shortcode = '' === $id
			? '[counter]'
			: '[counter id="' . $id . '"]';

		?>

		<tr>

			<td>
				<code>
					<?php echo esc_html( $shortcode ); ?>
				</code>
			</td>

			<td>
				<?php
				echo esc_html(
					number_format_i18n(
						$value
					)
				);
				?>
			</td>

			<td>

				<form
					method="post"
					action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
				>

					<input
						type="hidden"
						name="action"
						value="wcp_reset_single"
					/>

					<input
						type="hidden"
						name="counter_id"
						value="<?php echo esc_attr( $id ); ?>"
					/>

					<?php
					wp_nonce_field(
						'wcp_reset_single_' . $id
					);
					?>

					<button
						type="submit"
						class="button button-small"
					>
						<?php
							esc_html_e(
								'Reset',
								'wp-counter-plugin'
							);
						?>
					</button>

				</form>

			</td>

		</tr>

		<?php
	}

	/**
	 * Reset all counters.
	 *
	 * @return void
	 */
	public function reset_all() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__(
					'Permission denied.',
					'wp-counter-plugin'
				)
			);
		}

		check_admin_referer(
			'wcp_reset_all'
		);

		wcp_reset_all_counters();

		$this->redirect_back();
	}

	/**
	 * Reset one counter.
	 *
	 * @return void
	 */
	public function reset_single() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die(
				esc_html__(
					'Permission denied.',
					'wp-counter-plugin'
				)
			);
		}

		$id = isset( $_POST['counter_id'] )
			? wcp_sanitize_counter_id(
				wp_unslash(
					$_POST['counter_id']
				)
			)
			: '';

		check_admin_referer(
			'wcp_reset_single_' . $id
		);

		if ( wcp_counter_exists( $id ) ) {

			wcp_set_counter_value(
				wcp_get_settings()['starting_value'],
				$id
			);
		}

		$this->redirect_back();
	}

	/**
	 * Return to settings page.
	 *
	 * @return void
	 */
	private function redirect_back() {

		wp_safe_redirect(
			add_query_arg(
				'page',
				self::PAGE_SLUG,
				admin_url(
					'options-general.php'
				)
			)
		);

		exit;
	}
}