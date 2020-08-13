<?php
$auto_delete_settings = get_option(
	'awsm_jobs_auto_remove_applications',
	array(
		'enable_auto_delete' => '',
		'count'              => '',
		'period'             => '',
		'force_delete'       => '',
	)
);

$options = array(
	'days'   => __( 'Day(s)', 'auto-delete-wp-job-openings' ),
	'months' => __( 'Months(s)', 'auto-delete-wp-job-openings' ),
	'years'  => __( 'Years(s)', 'auto-delete-wp-job-openings' ),
);

?>
<div class="awsm-add-on-general-settings-section">
	<div class="awsm-add-on-general-settings-container" >
	<label for="awsm-jobs-enable-auto-delete">
		<input type="checkbox" name="awsm_jobs_auto_remove_applications[enable_auto_delete]" value="enable" <?php checked( $auto_delete_settings['enable_auto_delete'], 'enable', true ); ?> class="awsm-check-toggle-control" id="awsm-jobs-enable-auto-delete" data-toggle="true" data-toggle-target="#awsm_auto_remove_apps">
		<?php echo esc_html__( 'Enable auto delete applications', 'auto-delete-wp-job-openings' ); ?></label>
	</div>
	<div id="awsm_auto_remove_apps" class="<?php echo $auto_delete_settings['enable_auto_delete'] && $auto_delete_settings['enable_auto_delete'] === 'enable' ? ' show' : 'awsm-hide'; ?>">
		<br />
		<fieldset>
			<ul class="awsm-list-inline">
				<li>
					<label for="">
					<?php echo esc_html__( 'After', 'auto-delete-wp-job-openings' ); ?>
						<input type="text" class="small-text" name="awsm_jobs_auto_remove_applications[count]" value="<?php echo esc_attr( $auto_delete_settings['count'] ); ?>" id="" class="" data-toggle-target="">
					</label>
				</li>
				<li>
				<label for="">
					<?php
					$period = $auto_delete_settings['period'];
					echo "<select name='awsm_jobs_auto_remove_applications[period]'>";
					foreach ( $options as $key  => $key_label ) {
						$selected = '';
						if ( $period === $key ) {
							$selected = ' selected';
						}
						printf( '<option value="%1$s"%3$s>%2$s</option>', esc_attr( $key ), esc_html( $key_label ), esc_attr( $selected ) );
					}
					echo '</select>';
					?>
					</label>
				</li>
			</ul>
			<label for="awsm-jobs-enable-force-delete">
				<input type="checkbox" name="awsm_jobs_auto_remove_applications[force_delete]" value="enable" <?php checked( $auto_delete_settings['force_delete'], 'enable', true ); ?> class="awsm-check-toggle-control" id="awsm-jobs-enable-force-delete">
				<?php echo esc_html__( 'Enable force delete', 'auto-delete-wp-job-openings' ); ?></label>
		</fieldset>
	</div>
</div>
