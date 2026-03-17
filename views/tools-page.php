<?php
/**
 * Batch tools page template.
 *
 * @var array<string,mixed>|null            $batch_state
 * @var bool                                $dry_run
 * @var array<string,string>                $filter_options
 * @var \WMAIGEN\Support\ObjectTypeRegistry $object_type_registry
 * @var array<int,array<string,mixed>>      $logs
 * @var \WMAIGEN\Support\TimeFormatter      $time_formatter
 */
$current_filter = is_array( $batch_state ) && isset( $batch_state['filter'] ) ? (string) $batch_state['filter'] : 'all';
$current_limit  = is_array( $batch_state ) && isset( $batch_state['limit'] ) ? (int) $batch_state['limit'] : 10;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Metadata AI Batch Tools', 'wordpress-metadata-aigen' ); ?></h1>
	<p><?php esc_html_e( 'Scan supported objects whose description fields are empty, preview the candidates, then run one synchronous batch.', 'wordpress-metadata-aigen' ); ?></p>
	<?php if ( $dry_run ) : ?>
		<div class="notice notice-info inline"><p><?php esc_html_e( 'Dry Run is currently enabled in plugin settings. Batch execution will generate previews and logs, but it will not write descriptions yet.', 'wordpress-metadata-aigen' ); ?></p></div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wmaigen_scan_batch">
		<?php wp_nonce_field( 'wmaigen_scan_batch' ); ?>
		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="wmaigen-object-filter"><?php esc_html_e( 'Object Type', 'wordpress-metadata-aigen' ); ?></label></th>
					<td>
						<select id="wmaigen-object-filter" name="object_filter">
							<?php foreach ( $filter_options as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current_filter, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wmaigen-limit"><?php esc_html_e( 'Limit', 'wordpress-metadata-aigen' ); ?></label></th>
					<td>
						<input id="wmaigen-limit" name="limit" type="number" min="1" max="100" class="small-text" value="<?php echo esc_attr( (string) $current_limit ); ?>">
						<p class="description"><?php esc_html_e( 'The scan preview and the subsequent batch run use the same limit.', 'wordpress-metadata-aigen' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		<?php submit_button( __( 'Scan Empty Descriptions', 'wordpress-metadata-aigen' ), 'primary', 'submit', false ); ?>
	</form>

	<?php if ( is_array( $batch_state ) ) : ?>
		<h2><?php esc_html_e( 'Current Preview', 'wordpress-metadata-aigen' ); ?></h2>
		<?php if ( empty( $batch_state['candidates'] ) ) : ?>
			<p><?php esc_html_e( 'The last scan did not find any eligible objects.', 'wordpress-metadata-aigen' ); ?></p>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Type', 'wordpress-metadata-aigen' ); ?></th>
						<th><?php esc_html_e( 'ID', 'wordpress-metadata-aigen' ); ?></th>
						<th><?php esc_html_e( 'Name', 'wordpress-metadata-aigen' ); ?></th>
						<th><?php esc_html_e( 'Edit', 'wordpress-metadata-aigen' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $batch_state['candidates'] as $candidate ) : ?>
						<tr>
							<td><?php echo esc_html( $object_type_registry->get_label( (string) $candidate['kind'], (string) $candidate['subtype'] ) ); ?></td>
							<td><?php echo esc_html( (string) $candidate['id'] ); ?></td>
							<td><?php echo esc_html( (string) $candidate['title'] ); ?></td>
							<td>
								<?php if ( ! empty( $candidate['edit_link'] ) ) : ?>
									<a href="<?php echo esc_url( (string) $candidate['edit_link'] ); ?>"><?php esc_html_e( 'Open', 'wordpress-metadata-aigen' ); ?></a>
								<?php else : ?>
									&mdash;
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="margin-top: 16px;">
				<input type="hidden" name="action" value="wmaigen_run_batch">
				<?php wp_nonce_field( 'wmaigen_run_batch' ); ?>
				<?php submit_button( __( 'Run Batch Generation', 'wordpress-metadata-aigen' ), 'secondary', 'submit', false ); ?>
			</form>
		<?php endif; ?>
	<?php endif; ?>

	<h2><?php esc_html_e( 'Recent Logs', 'wordpress-metadata-aigen' ); ?></h2>
	<?php include WMAIGEN_VIEWS_DIR . 'partials/log-table.php'; ?>
</div>
