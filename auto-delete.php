<?php
/**
 * Add-on for WP Job Openings for automatically deleting applications based on the specified time.
 *
 * @package wp-job-openings
 */

/**
 * Plugin Name: Auto Delete Applications - Add-on for WP Job Openings
 * Plugin URI: https://wpjobopenings.com/
 * Description: This is an add-on for WP Job Openings Plugins, which will let you delete the received applications periodically.
 * Author: AWSM Innovations
 * Author URI: https://awsm.in/
 * Version: 1.0.0
 * License: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text domain: auto-delete-wp-job-openings
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin Constants
if ( ! defined( 'AWSM_JOBS_MAIN_PLUGIN' ) ) {
	define( 'AWSM_JOBS_MAIN_PLUGIN', 'wp-job-openings/wp-job-openings.php' );
}

if ( ! defined( 'AWSM_JOBS_MAIN_REQ_VERSION' ) ) {
	define( 'AWSM_JOBS_MAIN_REQ_VERSION', '1.4' );
}

if ( ! defined( 'AWSM_JOBS_PRO_PLUGIN_BASENAME' ) ) {
	define( 'AWSM_JOBS_PRO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

class AWSM_Job_Openings_Auto_Delete_Addon {
	private static $instance = null;

	public function __construct() {
		$this->cpath = untrailingslashit( plugin_dir_path( __FILE__ ) );
		add_action( 'admin_init', array( $this, 'handle_plugin_activation_add_on' ) );
		add_action( 'awsm_check_for_old_applications', array( $this, 'handle_old_applications' ) );
		add_action( 'before_delete_post', array( $this, 'remove_attachments' ) );
		add_action( 'admin_init', array( $this, 'register_auto_delete_settings' ) );
		add_filter( 'awsm_jobs_general_settings_fields', array( $this, 'awsm_jobs_general_settings_fields') );
	}

	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function activate() {
		$this->cron_job();
		$this->register_auto_delete_settings();
	}

	public function deactivate() {
		$this->clear_cron_jobs();
	}

	public function cron_job() {
		if ( ! wp_next_scheduled( 'awsm_check_for_old_applications' ) ) {
			wp_schedule_event( time(), 'daily', 'awsm_check_for_old_applications' );
		}
	}

	public function clear_cron_jobs() {
		wp_clear_scheduled_hook( 'awsm_check_for_old_applications' );
	}

	public function handle_plugin_activation_add_on() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_inactive( AWSM_JOBS_MAIN_PLUGIN ) || ! class_exists( 'AWSM_Job_Openings' ) ) {
			add_action(
				'admin_notices',
				function() {
					$this->admin_notices();
				}
			);
			deactivate_plugins( AWSM_JOBS_PRO_PLUGIN_BASENAME );
		}

		if ( defined( 'AWSM_JOBS_PLUGIN_VERSION' ) ) {
			if ( version_compare( AWSM_JOBS_PLUGIN_VERSION, AWSM_JOBS_MAIN_REQ_VERSION, '<' ) ) {
				add_action(
					'admin_notices',
					function() {
						$this->admin_notices( false );
					}
				);
				deactivate_plugins( AWSM_JOBS_PRO_PLUGIN_BASENAME );
			}
		}
	}

	public function get_main_plugin_activation_link( $is_update = false ) {
		$content = $link_action = $action_url = $link_class = ''; // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments.Found

		if ( ! $is_update ) {
			// when plugin is not active.
			$link_action = esc_html__( 'Activate', 'wp-job-openings' );
			$action_url  = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . AWSM_JOBS_MAIN_PLUGIN ), 'activate-plugin_' . AWSM_JOBS_MAIN_PLUGIN );
			$link_class  = ' activate-now';

			// when plugin is not installed.
			$plugin_arr       = explode( '/', esc_html( AWSM_JOBS_MAIN_PLUGIN ) );
			$plugin_slug      = $plugin_arr[0];
			$installed_plugin = get_plugins( '/' . $plugin_slug );
			if ( empty( $installed_plugin ) ) {
				if ( get_filesystem_method( array(), WP_PLUGIN_DIR ) === 'direct' ) {
					$link_action = esc_html__( 'Install', 'wp-job-openings' );
					$action_url  = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=' . $plugin_slug ), 'install-plugin_' . $plugin_slug );
					$link_class  = ' install-now';
				}
			}
		} else {
			// when plugin needs an update.
			$link_action = esc_html__( 'Update', 'wp-job-openings' );
			$action_url  = wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . AWSM_JOBS_MAIN_PLUGIN ), 'upgrade-plugin_' . AWSM_JOBS_MAIN_PLUGIN );
			$link_class  = ' update-now';
		}

		if ( ! empty( $link_action ) && ! empty( $action_url ) && ! empty( $link_class ) ) {
			$content = sprintf( '<a href="%2$s" class="button button-small%3$s">%1$s</a>', esc_html( $link_action ), esc_url( $action_url ), esc_attr( $link_class ) );
		}
		return $content;
	}

	public function admin_notices( $is_default = true, $req_plugin_version = AWSM_JOBS_MAIN_REQ_VERSION ) { ?>
		<div class="updated error">
				<p>
					<?php
						$req_plugin = sprintf( '<strong>"%s"</strong>', esc_html__( 'WP Job Openings', 'wp-job-openings' ) );
						$plugin     = sprintf( '<strong>"%s"</strong>', esc_html__( 'Auto Delete Applications - Add-on for WP Job Openings', 'wp-job-openings' ) );
					if ( $is_default ) {
						/* translators: %1$s: main plugin, %2$s: current plugin, %3$s: plugin activation link, %4$s: line break */
						printf( esc_html__( 'The plugin %2$s needs the plugin %1$s active. %4$s Please %3$s %1$s', 'wp-job-openings' ), $req_plugin, $plugin, $this->get_main_plugin_activation_link(), '<br />' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					} else {
						/* translators: %1$s: main plugin, %2$s: current plugin, %3$s: minimum required version of the main plugin, %4$s: plugin updation link */
						printf( esc_html__( '%2$s plugin requires %1$s version %3$s. Please %4$s %1$s plugin to the latest version.', 'wp-job-openings' ), $req_plugin, $plugin, esc_html( $req_plugin_version ), $this->get_main_plugin_activation_link( true ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</p>
			</div>
		<?php
	}

	public function awsm_jobs_general_settings_fields( $settings_fields ) {
		ob_start();
		include $this->cpath . '/inc/remove-applications.php';
		$auto_delete_content = ob_get_clean();
		$settings_fields['default'][] =
			array(
				'name'        => 'awsm_jobs_auto_remove_applications',
				'label'       => __( 'Auto delete applications ', 'wp-job-openings' ),
				'type'        => 'raw',
				'value'       => $auto_delete_content,
				'description' => __( 'CAUTION: Checking this option will permanently delete applications after the selected time period from the date of application. (For example, if you configure the option for 6 months, all the applications you have received before 6 months will be deleted immediately and every application that completes 6 months will be deleted from next day on wards automatically).', 'wp-job-openings'),
			);
		return $settings_fields;
	}

	private function settings() {
		$settings = array(
			'general'         => array(
				array(
					'option_name' => 'awsm_jobs_auto_remove_applications',
					'callback'    => array( $this, 'auto_delete_handler' ),
				),
			),
		);
		return $settings;
	}

	public function auto_delete_handler( $auto_delete_options ) {
		$options = array(
			'enable_auto_delete'  => '',
			'count'               => '',
			'period'              => '',
			'force_delete'        => '',
		);

		if ( ! empty( $auto_delete_options ) && is_array( $auto_delete_options ) ) {
				$options['enable_auto_delete'] = isset( $auto_delete_options['enable_auto_delete'] ) ? sanitize_text_field( $auto_delete_options['enable_auto_delete'] ) : '';
				$options['count']              = isset( $auto_delete_options['count'] ) ? sanitize_text_field( $auto_delete_options['count'] ) : '';
				$options['period']             = isset( $auto_delete_options['period'] ) ? $auto_delete_options['period'] : '';
				$options['force_delete']       = isset( $auto_delete_options['force_delete'] ) ? sanitize_text_field( $auto_delete_options['force_delete'] ) : '';

				if ( current_user_can( 'delete_applications' ) ) {
					$this->delete_applications( $options );
				}
		}
		return $options;
	}

	public function delete_applications( $options ) {
		$count                = $options['count'];
		$period               = $options['period'];
		$force_delete         = $options['force_delete'];
		$before               = $count .' '. $period .' ago';
		$args    = array(
			'fields'         => 'ids',
			'post_type'      => 'awsm_job_application',
			'post_status'    => array( 'publish', 'private', 'trash', 'progress', 'shortlist', 'reject', 'select' ),
			'posts_per_page' => -1,
			'date_query'     => array(
				array(
					'column' => 'post_date_gmt',
					'before' => $before,
				),
			),
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				if( $force_delete === 'enable' ) {
					wp_delete_post( get_the_ID(), true );
				} else {
					wp_trash_post( get_the_ID() );
				}
			}
		}
	}

	public function register_auto_delete_settings() {
		$settings = $this->settings();
		foreach ( $settings as $group => $settings_args ) {
			foreach ( $settings_args as $setting_args ) {
				register_setting( 'awsm-jobs-' . $group . '-settings', $setting_args['option_name'], isset( $setting_args['callback'] ) ? $setting_args['callback'] : 'sanitize_text_field' );
			}
		}
	}

	public function handle_old_applications() {
		$options              = get_option( 'awsm_jobs_auto_remove_applications' );
		$enable_auto_delete   = ! empty( $options['enable_auto_delete']) ? $options['enable_auto_delete'] : '';
		if( $enable_auto_delete === 'enable' ) {
			$this->delete_applications( $options );
		}
	}

	public function remove_attachments( $post_id ) {
		if ( get_post_type( $post_id ) === 'awsm_job_application' ) {
			$attachments = get_attached_media( '', $post_id );
			if ( ! empty( $attachments ) ) {
				foreach ( $attachments as $attachment ) {
					wp_delete_attachment( $attachment->ID, true );
				}
			}
		}
	}
}

$auto_delete_addon = AWSM_Job_Openings_Auto_Delete_Addon::init();

// activation
register_activation_hook( __FILE__, array( $auto_delete_addon, 'activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $auto_delete_addon, 'deactivate' ) );
