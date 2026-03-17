<?php
/**
 * Term edit screen generator controls.
 *
 * @var \WP_Term $term
 * @var bool     $dry_run
 */
?>
<tr class="form-field">
	<th scope="row"><?php esc_html_e( 'AI Description Generator', 'wordpress-metadata-aigen' ); ?></th>
	<td>
		<?php wp_nonce_field( 'wmaigen_generate_term', 'wmaigen_generate_term_nonce' ); ?>
		<p>
			<?php if ( $dry_run ) : ?>
				<strong><?php esc_html_e( 'Dry Run is enabled.', 'wordpress-metadata-aigen' ); ?></strong>
				<?php esc_html_e( 'The generated description will be previewed in an admin notice and not written to the term description field.', 'wordpress-metadata-aigen' ); ?>
			<?php else : ?>
				<?php esc_html_e( 'Generate a description and write it to the native taxonomy description field.', 'wordpress-metadata-aigen' ); ?>
			<?php endif; ?>
		</p>
		<?php if ( '' !== trim( (string) $term->description ) ) : ?>
			<p><strong><?php esc_html_e( 'Current description:', 'wordpress-metadata-aigen' ); ?></strong> <?php echo esc_html( $term->description ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'Current description is empty.', 'wordpress-metadata-aigen' ); ?></p>
		<?php endif; ?>
		<p>
			<label for="wmaigen-overwrite-existing-term">
				<input id="wmaigen-overwrite-existing-term" type="checkbox" name="wmaigen_overwrite_existing" value="1">
				<?php esc_html_e( 'Overwrite existing description', 'wordpress-metadata-aigen' ); ?>
			</label>
		</p>
		<p>
			<button type="submit" class="button button-secondary" name="wmaigen_generate_term" value="1">
				<?php esc_html_e( 'Save and Generate Description', 'wordpress-metadata-aigen' ); ?>
			</button>
		</p>
		<p class="description">
			<?php esc_html_e( 'This button submits the normal term edit form first, then runs generation. Any other term changes on the screen will also be saved.', 'wordpress-metadata-aigen' ); ?>
		</p>
	</td>
</tr>
