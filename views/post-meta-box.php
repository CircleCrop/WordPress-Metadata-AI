<?php
/**
 * Post editor meta box template.
 *
 * @var \WP_Post $post
 * @var bool     $dry_run
 * @var string   $generate_url
 * @var string   $redirect_url
 */
?>
<?php wp_nonce_field( 'wmaigen_generate_post', 'wmaigen_generate_post_nonce' ); ?>
<p>
	<?php if ( $dry_run ) : ?>
		<strong><?php esc_html_e( 'Dry Run is enabled.', 'wordpress-metadata-aigen' ); ?></strong>
	<?php else : ?>
	<?php endif; ?>
</p>
<?php if ( '' !== trim( (string) $post->post_excerpt ) ) : ?>
	<p><strong><?php esc_html_e( 'Current excerpt:', 'wordpress-metadata-aigen' ); ?></strong> <?php echo esc_html( $post->post_excerpt ); ?></p>
<?php else : ?>
	<p><?php esc_html_e( 'Current excerpt is empty.', 'wordpress-metadata-aigen' ); ?></p>
<?php endif; ?>
<p>
	<label for="wmaigen-overwrite-existing-post">
		<input id="wmaigen-overwrite-existing-post" type="checkbox" name="wmaigen_overwrite_existing" value="1">
		<?php esc_html_e( 'Overwrite existing excerpt', 'wordpress-metadata-aigen' ); ?>
	</label>
</p>
<p>
	<button
		type="submit"
		class="button button-secondary"
		name="wmaigen_generate_post"
		value="1"
		data-wmaigen-post-generate-button="1"
		data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>"
		data-action-url="<?php echo esc_url( $generate_url ); ?>"
		data-redirect-url="<?php echo esc_url( $redirect_url ); ?>"
		data-pending-text="<?php echo esc_attr__( 'Saving and generating...', 'wordpress-metadata-aigen' ); ?>"
	>
		<?php esc_html_e( 'Save and Generate Description', 'wordpress-metadata-aigen' ); ?>
	</button>
</p>
