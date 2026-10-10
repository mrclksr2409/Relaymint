/**
 * Relaymint admin scripts.
 *
 * @package Relaymint
 */
( function () {
	'use strict';

	var i18n = window.relaymintAdmin || {};

	/**
	 * Clone the first element of a <template>.
	 *
	 * @param {string} id Template ID.
	 * @return {Element|null}
	 */
	function fromTemplate( id ) {
		var tpl = document.getElementById( id );
		if ( ! tpl ) {
			return null;
		}
		return tpl.content.firstElementChild.cloneNode( true );
	}

	/* Confirmation for destructive links: handled by WP-Backend UI (data-wpb-confirm). */

	/* Toggle dependent settings. */
	function initToggles() {
		document.querySelectorAll( '.relaymint-toggle' ).forEach( function ( toggle ) {
			var update = function () {
				document.querySelectorAll( toggle.dataset.target ).forEach( function ( el ) {
					el.hidden = ! toggle.checked;
				} );
			};
			toggle.addEventListener( 'change', update );
			update();
		} );
	}

	/* Connection form: mailer sections, port suggestions and auth/auto TLS rows. */
	function initConnectionForms() {
		document.querySelectorAll( '.relaymint-connection-form' ).forEach( function ( form ) {
			var updateMailer = function () {
				var checked = form.querySelector( 'input[name="connection[mailer]"]:checked' );
				var mailer = checked ? checked.value : 'smtp';
				form.querySelectorAll( '.relaymint-mailer-section' ).forEach( function ( section ) {
					section.hidden = section.dataset.mailer !== mailer;
				} );
			};
			form.querySelectorAll( 'input[name="connection[mailer]"]' ).forEach( function ( radio ) {
				radio.addEventListener( 'change', updateMailer );
			} );
			updateMailer();

			var updateMsAuth = function () {
				var checked = form.querySelector( 'input[name="connection[ms_auth]"]:checked' );
				form.querySelectorAll( '.relaymint-ms-delegated-row' ).forEach( function ( row ) {
					row.hidden = ! checked || 'delegated' !== checked.value;
				} );
			};
			form.querySelectorAll( 'input[name="connection[ms_auth]"]' ).forEach( function ( radio ) {
				radio.addEventListener( 'change', updateMsAuth );
			} );
			updateMsAuth();

			var port = form.querySelector( 'input[name="connection[port]"]' );
			var auth = form.querySelector( '.relaymint-auth-toggle' );
			var autotlsRow = form.querySelector( '.relaymint-autotls-row' );
			var defaultPorts = [ '25', '465', '587' ];

			var updateEncryption = function () {
				var checked = form.querySelector( 'input[name="connection[encryption]"]:checked' );
				if ( autotlsRow ) {
					autotlsRow.hidden = ! checked || 'none' !== checked.value;
				}
			};

			form.querySelectorAll( 'input[name="connection[encryption]"]' ).forEach( function ( radio ) {
				radio.addEventListener( 'change', function () {
					// Only adjust the port if it still holds a standard value.
					if ( port && ! port.disabled && ( '' === port.value || -1 !== defaultPorts.indexOf( port.value ) ) ) {
						port.value = radio.dataset.port;
					}
					updateEncryption();
				} );
			} );
			updateEncryption();

			if ( auth ) {
				var updateAuth = function () {
					form.querySelectorAll( '.relaymint-auth-row' ).forEach( function ( row ) {
						row.hidden = ! auth.checked;
					} );
				};
				auth.addEventListener( 'change', updateAuth );
				updateAuth();
			}
		} );
	}

	/* Smart Routing rule builder. */
	function initRouting() {
		var form = document.getElementById( 'relaymint-routing-form' );
		if ( ! form ) {
			return;
		}
		var routes = form.querySelector( '.relaymint-routes' );

		form.addEventListener( 'click', function ( event ) {
			var button = event.target.closest( 'button' );
			if ( ! button ) {
				return;
			}
			var route = button.closest( '.relaymint-route' );
			var group = button.closest( '.relaymint-group' );

			if ( button.classList.contains( 'relaymint-add-route' ) ) {
				routes.appendChild( fromTemplate( 'relaymint-tpl-route' ) );
			} else if ( button.classList.contains( 'relaymint-remove-route' ) ) {
				if ( window.confirm( i18n.confirmDelete || 'Are you sure?' ) ) {
					route.remove();
				}
			} else if ( button.classList.contains( 'relaymint-add-group' ) ) {
				route.querySelector( '.relaymint-groups' ).appendChild( fromTemplate( 'relaymint-tpl-group' ) );
			} else if ( button.classList.contains( 'relaymint-add-condition' ) ) {
				group.querySelector( '.relaymint-conditions' ).appendChild( fromTemplate( 'relaymint-tpl-condition' ) );
			} else if ( button.classList.contains( 'relaymint-remove-condition' ) ) {
				button.closest( '.relaymint-condition' ).remove();
				if ( ! group.querySelector( '.relaymint-condition' ) ) {
					group.remove();
				}
			} else if ( button.classList.contains( 'relaymint-move-up' ) && route.previousElementSibling ) {
				routes.insertBefore( route, route.previousElementSibling );
			} else if ( button.classList.contains( 'relaymint-move-down' ) && route.nextElementSibling ) {
				routes.insertBefore( route.nextElementSibling, route );
			} else {
				return;
			}
			event.preventDefault();
		} );

		// Give every field its indexed name right before submitting.
		form.addEventListener( 'submit', function () {
			routes.querySelectorAll( '.relaymint-route' ).forEach( function ( route, r ) {
				var prefix = 'routes[' + r + ']';
				route.querySelectorAll( '.relaymint-route-header [data-name]' ).forEach( function ( field ) {
					field.name = prefix + '[' + field.dataset.name + ']';
				} );
				route.querySelectorAll( '.relaymint-group' ).forEach( function ( group, g ) {
					group.querySelectorAll( '.relaymint-condition' ).forEach( function ( condition, c ) {
						condition.querySelectorAll( '[data-name]' ).forEach( function ( field ) {
							field.name = prefix + '[groups][' + g + '][' + c + '][' + field.dataset.name + ']';
						} );
					} );
				} );
			} );
		} );
	}

	document.addEventListener( 'DOMContentLoaded', function () {
		initToggles();
		initConnectionForms();
		initRouting();
	} );
}() );
