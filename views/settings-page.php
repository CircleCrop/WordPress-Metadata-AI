<?php
/**
 * Settings page template.
 *
 * @var array<string,mixed>                 $settings
 * @var array<int,array<string,mixed>>      $logs
 * @var \WMAIGEN\Support\TimeFormatter      $time_formatter
 */
?>
<div class="wrap">
	<h1><?php esc_html_e( 'WordPress Metadata AI', 'wordpress-metadata-aigen' ); ?></h1>
	<p><?php esc_html_e( 'Configure the OpenAI-compatible API, tune prompts, and inspect recent generation logs.', 'wordpress-metadata-aigen' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wmaigen_save_settings">
		<?php wp_nonce_field( 'wmaigen_save_settings' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="wmaigen-base-url"><?php esc_html_e( 'Base URL', 'wordpress-metadata-aigen' ); ?></label></th>
					<td>
						<input id="wmaigen-base-url" name="wmaigen_settings[base_url]" type="url" class="regular-text" value="<?php echo esc_attr( (string) $settings['base_url'] ); ?>">
						<p class="description"><?php esc_html_e( 'Enter the API root URL, for example https://api.openai.com/v1. The plugin appends /chat/completions automatically.', 'wordpress-metadata-aigen' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wmaigen-api-key"><?php esc_html_e( 'API Key', 'wordpress-metadata-aigen' ); ?></label></th>
					<td>
						<input id="wmaigen-api-key" name="wmaigen_settings[api_key]" type="password" class="regular-text" value="<?php echo esc_attr( (string) $settings['api_key'] ); ?>" autocomplete="off">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wmaigen-model"><?php esc_html_e( 'Model', 'wordpress-metadata-aigen' ); ?></label></th>
					<td>
						<input id="wmaigen-model" name="wmaigen_settings[model]" type="text" class="regular-text" value="<?php echo esc_attr( (string) $settings['model'] ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wmaigen-timeout"><?php esc_html_e( 'Timeout (seconds)', 'wordpress-metadata-aigen' ); ?></label></th>
					<td>
						<input id="wmaigen-timeout" name="wmaigen_settings[timeout]" type="number" min="5" max="120" class="small-text" value="<?php echo esc_attr( (string) $settings['timeout'] ); ?>">
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Dry Run', 'wordpress-metadata-aigen' ); ?></th>
					<td>
						<input name="wmaigen_settings[dry_run]" type="hidden" value="0">
						<label for="wmaigen-dry-run">
							<input id="wmaigen-dry-run" name="wmaigen_settings[dry_run]" type="checkbox" value="1" <?php checked( ! empty( $settings['dry_run'] ) ); ?>>
							<?php esc_html_e( 'Generate descriptions without writing them to WordPress fields.', 'wordpress-metadata-aigen' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wmaigen-prompt-post"><?php esc_html_e( 'System Prompt: Post / Page / CPT', 'wordpress-metadata-aigen' ); ?></label></th>
					<td>
						<textarea id="wmaigen-prompt-post" name="wmaigen_settings[prompt_post_like]" rows="8" class="large-text code"><?php echo esc_textarea( (string) $settings['prompt_post_like'] ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="wmaigen-prompt-term"><?php esc_html_e( 'System Prompt: Category / Tag', 'wordpress-metadata-aigen' ); ?></label></th>
					<td>
						<textarea id="wmaigen-prompt-term" name="wmaigen_settings[prompt_term_like]" rows="8" class="large-text code"><?php echo esc_textarea( (string) $settings['prompt_term_like'] ); ?></textarea>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( __( 'Save Settings', 'wordpress-metadata-aigen' ) ); ?>
	</form>

	<hr>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="wmaigen_test_connection">
		<?php wp_nonce_field( 'wmaigen_test_connection' ); ?>
		<?php submit_button( __( 'Test API Connection', 'wordpress-metadata-aigen' ), 'secondary', 'submit', false ); ?>
	</form>

	<h2><?php esc_html_e( 'Recent Logs', 'wordpress-metadata-aigen' ); ?></h2>
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
</div>
