<?php
/**
 * Shared admin log table.
 *
 * @var array<int,array<string,mixed>> $logs
 * @var \WMAIGEN\Support\TimeFormatter $time_formatter
 */
?>
<?php if ( empty( $logs ) ) : ?>
	<p><?php esc_html_e( 'No logs have been recorded yet.', 'wordpress-metadata-aigen' ); ?></p>
<?php else : ?>
	<table class="widefat striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Time', 'wordpress-metadata-aigen' ); ?></th>
				<th><?php esc_html_e( 'Type', 'wordpress-metadata-aigen' ); ?></th>
				<th><?php esc_html_e( 'ID', 'wordpress-metadata-aigen' ); ?></th>
				<th><?php esc_html_e( 'Name', 'wordpress-metadata-aigen' ); ?></th>
				<th><?php esc_html_e( 'Action', 'wordpress-metadata-aigen' ); ?></th>
				<th><?php esc_html_e( 'Result', 'wordpress-metadata-aigen' ); ?></th>
				<th><?php esc_html_e( 'Message', 'wordpress-metadata-aigen' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $logs as $log ) : ?>
				<tr>
					<td><?php echo esc_html( $time_formatter->format( (int) $log['timestamp'] ) ); ?></td>
					<td><?php echo esc_html( trim( (string) $log['object_kind'] . ' / ' . (string) $log['object_subtype'], ' /' ) ); ?></td>
					<td><?php echo esc_html( (string) $log['object_id'] ); ?></td>
					<td><?php echo esc_html( (string) $log['object_name'] ); ?></td>
					<td><?php echo esc_html( (string) $log['action'] ); ?></td>
					<td><?php echo esc_html( (string) $log['result'] ); ?></td>
					<td><?php echo esc_html( (string) $log['message'] ); ?></td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
