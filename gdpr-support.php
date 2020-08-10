<?php
/**
 * Custom Add-on of WP Job Openings Plugin providing GDPR Support.
 *
 * @package wp-job-openings
 */

/**
 * Plugin Name: GDPR Support for WP Job Openings
 * Plugin URI: https://wordpress.org/plugins/wp-job-openings/
 * Description: Custom Add-on of WP Job Openings Plugin providing GDPR Support. This can automatically remove submitted applications / documents after a certain amount of time.
 * Author: AWSM Innovations
 * Author URI: https://awsm.in/
 * Version: 1.0.0
 * Licence: GPLv2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AWSM_Job_Openings_GDPR_Addon {
	private static $instance = null;

	public function __construct() {
		$this->cpath = untrailingslashit( plugin_dir_path( __FILE__ ) );
		add_action( 'awsm_check_for_old_applications', array( $this, 'delete_old_applications' ) );
		add_action( 'before_delete_post', array( $this, 'remove_attachments' ) );
		add_action( 'admin_init', array( $this, 'register_gdpr_settings' ) );
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
		$this->register_gdpr_settings();
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

	public function awsm_jobs_general_settings_fields( $settings_fields ) {
		ob_start();
		include $this->cpath . '/inc/remove-applications.php';
		$auto_delete_content = ob_get_clean();
		$settings_fields['default'][] = 
			array(
				'name'          => 'awsm_jobs_auto_remove_applications',
				'label'         => __( 'Auto remove applications ', 'wp-job-openings' ),
				'type'          => 'raw',
				'value'         => $auto_delete_content,
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
			$options['force_delete'] = isset( $auto_delete_options['force_delete'] ) ? sanitize_text_field( $auto_delete_options['force_delete'] ) : '';
		}
		return $options;
	}
	public function register_gdpr_settings() {
		$settings = $this->settings();
		foreach ( $settings as $group => $settings_args ) {
			foreach ( $settings_args as $setting_args ) {
				register_setting( 'awsm-jobs-' . $group . '-settings', $setting_args['option_name'], isset( $setting_args['callback'] ) ? $setting_args['callback'] : 'sanitize_text_field' );
			}
		}
	}

	public function delete_old_applications() {
		$auto_delete_settings = get_option( 'awsm_jobs_auto_remove_applications' );
		$enable_auto_delete   = $auto_delete_settings['enable_auto_delete'];
		$count                = $auto_delete_settings['count'];
		$period               = $auto_delete_settings['period'];
		$force_delete         = $auto_delete_settings['force_delete'];
		$before               = $count .' '. $period .' ago';
		if( $enable_auto_delete === 'enable' ) {
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
					if($force_delete === 'enable' ) {
						wp_delete_post( get_the_ID(), true );
					} else {
						wp_trash_post( get_the_ID() );
					}
				}
			}
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

$gdpr_addon = AWSM_Job_Openings_GDPR_Addon::init();

// activation
register_activation_hook( __FILE__, array( $gdpr_addon, 'activate' ) );

// deactivation
register_deactivation_hook( __FILE__, array( $gdpr_addon, 'deactivate' ) );
