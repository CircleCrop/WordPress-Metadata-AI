( function ( wp ) {
	'use strict';

	function createField( doc, name, value ) {
		var input = doc.createElement( 'input' );
		input.type = 'hidden';
		input.name = name;
		input.value = value;
		return input;
	}

	function submitGenerateRequest( button ) {
		var doc = window.document;
		var nonceField = doc.querySelector( '[name="wmaigen_generate_post_nonce"]' );
		var overwriteField = doc.querySelector( '#wmaigen-overwrite-existing-post' );
		var actionUrl = button.getAttribute( 'data-action-url' );
		var postId = button.getAttribute( 'data-post-id' );
		var redirectUrl = button.getAttribute( 'data-redirect-url' ) || window.location.href;

		if ( ! nonceField || ! actionUrl || ! postId ) {
			return;
		}

		var form = doc.createElement( 'form' );
		form.method = 'post';
		form.action = actionUrl;
		form.style.display = 'none';

		form.appendChild( createField( doc, 'action', 'wmaigen_generate_post' ) );
		form.appendChild( createField( doc, 'post_id', postId ) );
		form.appendChild( createField( doc, 'redirect_to', redirectUrl ) );
		form.appendChild( createField( doc, 'wmaigen_generate_post_nonce', nonceField.value ) );

		if ( overwriteField && overwriteField.checked ) {
			form.appendChild( createField( doc, 'wmaigen_overwrite_existing', '1' ) );
		}

		doc.body.appendChild( form );
		form.submit();
	}

	function init() {
		if ( ! document.body.classList.contains( 'block-editor-page' ) ) {
			return;
		}

		document.addEventListener( 'click', function ( event ) {
			var editorSelect;
			var editorDispatch;
			var wasDirty;
			var originalText;
			var button = event.target.closest( '[data-wmaigen-post-generate-button]' );

			if ( ! button ) {
				return;
			}

			event.preventDefault();

			if ( ! wp || ! wp.data || typeof wp.data.select !== 'function' || typeof wp.data.dispatch !== 'function' ) {
				submitGenerateRequest( button );
				return;
			}

			editorSelect = wp.data.select( 'core/editor' );
			editorDispatch = wp.data.dispatch( 'core/editor' );
			wasDirty =
				editorSelect &&
				typeof editorSelect.isEditedPostDirty === 'function' &&
				editorSelect.isEditedPostDirty();
			originalText = button.textContent;

			button.disabled = true;
			button.textContent = button.getAttribute( 'data-pending-text' ) || originalText;

			if ( ! wasDirty ) {
				submitGenerateRequest( button );
				return;
			}

			Promise.resolve( editorDispatch.savePost() )
				.then( function () {
					if (
						editorSelect &&
						typeof editorSelect.didPostSaveRequestSucceed === 'function' &&
						! editorSelect.didPostSaveRequestSucceed()
					) {
						button.disabled = false;
						button.textContent = originalText;
						return;
					}

					submitGenerateRequest( button );
				} )
				.catch( function () {
					button.disabled = false;
					button.textContent = originalText;
				} );
		} );
	}

	if ( wp && typeof wp.domReady === 'function' ) {
		wp.domReady( init );
		return;
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
		return;
	}

	init();
}( window.wp ) );
