( function() {
	'use strict';

	const frmhelpers = window.FrmQuizzesHelpers = {
		/**
		 * Acts like jQuery( document ).on( 'event', 'selector', function() {} ).
		 *
		 * @param {Object}         el        HTML element.
		 * @param {String}         eventName Event name.
		 * @param {String}         selector  Selector.
		 * @param {Function}       handler   Event handler.
		 * @param {Boolean|Object} options   The options passed to addEventListener().
		 */
		frmOn: function( el, eventName, selector, handler, options ) {
			el.addEventListener( eventName, event => {
				for ( let target = event.target; target && target !== this && 'function' === typeof target.matches; target = target.parentNode ) {
					if ( target.matches( selector ) ) {
						handler.call( target, event );
						break;
					}
				}
			}, 'undefined' === typeof options ? false : options );
		}
	};

	const FrmQuizzesAdmin = {
		migrate: function() {
			const nonce = FrmQuizzesAdminL10n.ajaxNonce;

			const setMigrating = notice => {
				notice.querySelector( 'p' ).innerText = FrmQuizzesAdminL10n.migrating;
			};

			const setMigrateSuccess = notice => {
				notice.querySelector( 'p' ).innerText = FrmQuizzesAdminL10n.migrateSuccess;
				notice.classList.remove( 'notice-error' );
				notice.classList.add( 'notice-success' );
			};

			const setMigrateError = notice => {
				notice.querySelector( 'p' ).innerText = FrmQuizzesAdminL10n.migrateFailed;
			};

			const onClick = event => {
				const notice = event.target.closest( '.notice' );

				setMigrating( notice );

				wp.ajax.send( 'frm_quizzes_migrate', {
					type: 'POST',
					data: {
						_ajax_nonce: nonce
					},
					success: response => {
						setMigrateSuccess( notice );
					},
					error: response => {
						setMigrateError( notice );
					}
				});
			};

			frmhelpers.frmOn( document, 'click', '#frm-quizzes-migrate', onClick );
		},

		init: function() {
			this.migrate();
		}
	};

	FrmQuizzesAdmin.init();
}() );
