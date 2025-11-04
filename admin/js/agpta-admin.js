/*
 * File: agpta-admin.js
 *
 * @author Gabriel Castillo <gabriel@gabrielcastillo.net>
 * Copyright (c) 2025.
 */

(function ($, document, window) {
	'use strict';

	// Contact form Message handler.
	const formController = {
		init: function () {
			formController.cacheDom();
			formController.bindEvents();
		},
		cacheDom: function () {
			this.$body      = $( 'body' );
			this.$formTable = this.$body.find( '#form-table' );
			this.$loader    = this.$body.find( '#loader' );
		},

		bindEvents: function () {
			this.$formTable.find( 'tr' ).on( 'click', this.handleMessageData.bind( this ) );
		},

		handleMessageData: function (e) {
			e.preventDefault();
			formController.showLoader();

			// Get the clicked row.
			let $targetRow = $( e.target ).closest( 'tr' ); // Make sure we get the closest <tr>.

			// Now, get the 'data-details' from the correct <td> in that row.
			let formId = $targetRow.find( 'td[data-id]' ).data( 'id' );

			$.ajax(
				{
					url: ajax_params.ajaxurl,
					type: 'POST',
					data: {
						action: 'get_form_message_ajax_call',
						formId: formId,
						nonce: ajax_params.nonce,
					},
					success: function ( response ) {
						formController.hideLoader();
						if ( response.success ) {
							let data = response.data.data;

							$( $targetRow ).find( 'td.record_status' ).html( data.status );

							let messageData = `
										< p > < strong > Name: < / strong > ${data.name} < / strong > < / p >
										< p > < strong > Email: < / strong > ${data.email} < / strong > < / p >
										< strong > Message: < / strong > ${data.message}
										`;
							$( '#dialog-content' ).html( messageData );

							$( '#dialog' ).dialog(
								{
									modal: true,
									width: 600,
									draggable: false,
								}
							);
						} else {
							console.error( 'Error fetching the message.' );
						}
					},

					error: function ( response ) {
						console.error( "AJAX error:", response.responseText );
						alert( "There was an error while fetching the message. Please try again." );
					}
				}
			);
		},

		showLoader: function () {
			$( formController.$loader ).fadeIn();
		},

		hideLoader: function () {
			$( formController.$loader ).fadeOut();
		}
	};
	const wishlistController = {
		init: function () {
			wishlistController.cacheDom();
			wishlistController.bindEvents();
		},

		cacheDom: function () {
			this.$body 		= $( 'body' );
			this.$deleteBtn = this.$body.find( '#delete-wishlist' );
		},

		bindEvents: function () {
			this.$deleteBtn.on( 'click', this.handleDeleteRequest.bind( this ) );
		},

		handleDeleteRequest: function (e) {
			e.preventDefault();

			let wishlistId = $( e.target ).data( 'id' );

			if ( typeof(wishlistId) !== 'number'  ) {
				alert( 'Wishlist ID must be a valid number.' );
			}

			if ( confirm( 'Are you sure you want to delete this wishlist?' ) ) {
				$.ajax(
					{
						url: agpta_ajax_params.ajaxurl,
						type: 'POST',
						data: {
							action: 'agpta_wishlist_delete',
							nonce: agpta_ajax_params.ajaxnonce,
							id: wishlistId,
						},
						success: function ( response ) {

							if ( response.success == true ) {
								window.location.href = '/wp-admin/admin.php?page=agpta-wishlists';
							}
						},
						error: function ( response ) {
							let html = '<div class="notice notice-error is-dismissible"><p>' + response.responseJSON.data.message + '</p>';
							$( '#wpbody-content' ).prepend( html );
							jQuery( document ).on(
								'click',
								'.is-dismissible',
								function () {
									jQuery( this ).closest( '.notice' ).remove();
								}
							);
						}
					}
				);
			}
		},
	};

	$( document ).ready(
		function () {
			formController.init();
			wishlistController.init();
		}
	);
})( jQuery, document, window );
