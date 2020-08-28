<?php
$adl_settings = self::get_general_settings();

$options = array(
	'days'   => __( 'Day(s)', 'auto-delete-wp-job-openings' ),
	'months' => __( 'Month(s)', 'auto-delete-wp-job-openings' ),
	'years'  => __( 'Year(s)', 'auto-delete-wp-job-openings' ),
);

?>
<div class="awsm-add-on-general-settings-section">
	<div class="awsm-add-on-general-settings-container" >
	<label for="awsm-jobs-enable-auto-delete">
		<input type="checkbox" name="awsm_jobs_adl_general_settings[enable_auto_delete]" value="enable" <?php checked( $adl_settings['enable_auto_delete'], 'enable', true ); ?> class="awsm-check-toggle-control" id="awsm-jobs-enable-auto-delete" data-toggle="true" data-toggle-target="#awsm_auto_remove_apps">
		<?php echo esc_html__( 'Enable auto delete applications', 'auto-delete-wp-job-openings' ); ?></label>
	</div>
	<div id="awsm_auto_remove_apps" class="<?php echo $adl_settings['enable_auto_delete'] && $adl_settings['enable_auto_delete'] === 'enable' ? ' show' : 'awsm-hide'; ?>">
		<br />
		<fieldset>
			<ul class="awsm-list-inline">
				<li>
					<label for="">
					<?php echo esc_html__( 'After', 'auto-delete-wp-job-openings' ); ?>
						<input type="number" class="small-text" name="awsm_jobs_adl_general_settings[count]" min="1" value="<?php echo esc_attr( $adl_settings['count'] ); ?>" style="margin-top: 0;">
					</label>
				</li>
				<li>
				<label for="">
					<?php
					$period = $adl_settings['period'];
					echo "<select name='awsm_jobs_adl_general_settings[period]'>";
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
				<input type="checkbox" name="awsm_jobs_adl_general_settings[force_delete]" value="enable" <?php checked( $adl_settings['force_delete'], 'enable', true ); ?> class="awsm-check-toggle-control" id="awsm-jobs-enable-force-delete">
				<?php echo esc_html__( 'Enable force delete', 'auto-delete-wp-job-openings' ); ?>
			</label>
			<p class="description" style="margin:0;"><?php esc_html_e( 'Whether to force delete applications or move it to trash.', 'auto-delete-wp-job-openings' ); ?></p><br>
		</fieldset>
	</div>
</div>
