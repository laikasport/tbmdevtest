( function() {
	'use strict';

	/** globals FrmQuizzesHelpers, frmDom, wp */

	const frmhelpers = window.FrmQuizzesHelpers;
	const { __ } = wp.i18n;
	const { span } = frmDom;

	// Object for scored quiz action.
	const FrmQuizzesFormAction = {
		initModal: function( selector ) {
			const el = document.querySelector( selector );
			if ( ! el ) {
				return;
			}

			const open = () => {
				el.setAttribute( 'data-frm-modal-active', 'true' );
				document.body.setAttribute( 'data-frm-modal-opening', 'true' );
			};

			const close = () => {
				el.removeAttribute( 'data-frm-modal-active' );
				document.body.removeAttribute( 'data-frm-modal-opening' );
			};

			const onDismiss = event => {
				event.preventDefault();
				close();
			};

			if ( ! el.getAttribute( 'data-frm-modal-handled' ) ) {
				el.querySelectorAll( '[data-frm-modal-dismiss]' ).forEach( dismissEl => {
					dismissEl.addEventListener( 'click', onDismiss );
				});

				el.querySelector( '.frm_quizzes_modal__overlay' ).addEventListener( 'click', event => close() );

				el.setAttribute( 'data-frm-modal-handled', 'true' );
			}

			open();
		},

		initModalSettings: function() {
			const onClickOpenModal = () => {
				this.initModal( '#frm_quizzes_scored_settings_modal' );
				setVariables( document.getElementById( 'frm_quizzes_scored_settings_modal' ) );
			};

			const setVariables = popup => {
				const contentHeight = popup.querySelector( '.frm_quizzes_modal__content' ).getBoundingClientRect().height;
				popup.style.setProperty( '--checkboxes-list-max-height', Math.floor( contentHeight / 2 ) + 'px' );
			};

			frmhelpers.frmOn( document, 'click', '#frm_quizzes_edit_quiz', onClickOpenModal );
		},

		toggleField: function() {
			const onToggle = event => {
				const fieldEl  = event.target.closest( '.frm_quizzes_scored_field' );
				const cssClass = 'frm_quizzes_scored_field--disabled';
				fieldEl.classList.toggle( cssClass, ! event.target.checked );
			};

			frmhelpers.frmOn( document, 'change', '.frm_quizzes_scored_field .frm_toggle input', onToggle );
		},

		toggleScoreManually: function() {
			const onToggle = event => {
				const fieldEl = event.target.closest( '.frm_quizzes_scored_field' );
				fieldEl.classList.toggle( 'frm_quizzes_scored_field--score_manually', event.target.checked );
			};

			frmhelpers.frmOn( document, 'change', '.frm_quizzes_admin_score_manually', onToggle );
		},

		toggleAdvScoring: function() {
			const onToggle = event => {
				const fieldEl = event.target.closest( '.frm_quizzes_scored_field' );
				const cssClass = 'frm_quizzes_scored_field--adv_scoring';
				if ( event.target.checked ) {
					fieldEl.classList.add( cssClass );
				} else {
					fieldEl.classList.remove( cssClass );
				}
			};

			frmhelpers.frmOn( document, 'change', '.frm_quizzes_admin_adv_scoring', onToggle );
		},

		multiCheckboxes: function() {
			const rootSelector = '.frm_quizzes_multi_checkboxes';

			const onFocus = event => {
				event.target.closest( '#frm_quizzes_scored_settings' ).querySelectorAll( '.frm_focus' ).forEach( el => {
					// Close other dropdowns.
					el.classList.remove( 'frm_focus' );
				});

				const rootEl = event.target.closest( rootSelector );

				event.target.classList.add( 'frm_focus' );

				changeDropdownPosition( rootEl );
			};

			const changeDropdownPosition = rootEl => {
				const dropdownRect   = rootEl.querySelector( 'ul' ).getBoundingClientRect();
				const parentRect     = rootEl.closest( '.frm_quizzes_modal__content' ).getBoundingClientRect();
				const changeToTop    = dropdownRect.y + dropdownRect.height > parentRect.y + parentRect.height;
				const changeToBottom = dropdownRect.y < parentRect.y;
				const className      = 'frm_quizzes_multi_checkboxes--dropdown_top';

				if ( changeToTop && ! rootEl.classList.contains( className ) ) {
					rootEl.classList.add( className );
				} else if ( changeToBottom && rootEl.classList.contains( className ) ) {
					rootEl.classList.remove( className );
				}
			};

			const onChangeCheckbox = event => {
				const rootEl = event.target.closest( rootSelector );
				const ul     = rootEl.querySelector( 'ul' );
				const btn    = rootEl.querySelector( 'button' );
				const placeholder = btn.querySelector( '.frm_quizzes_placeholder' );

				let buttonTexts = '';

				ul.querySelectorAll( 'input[type="checkbox"]' ).forEach( input => {
					if ( input.checked ) {
						buttonTexts += '<span>' + input.parentElement.querySelector( 'span' ).innerHTML + '</span>';
					}
				});

				btn.innerHTML = buttonTexts;
				btn.appendChild( placeholder );
			};

			let ticking = false;
			const onScroll = event => {
				if ( ticking ) {
					return;
				}

				window.requestAnimationFrame( () => {
					const activeDropdown = document.querySelector( '.frm_quizzes_multi_checkboxes .frm_focus' );
					if ( activeDropdown ) {
						changeDropdownPosition( activeDropdown.parentNode );
					}

					ticking = false;
				});

				ticking = true;
			};

			frmhelpers.frmOn( document, 'change', rootSelector + ' input[type="checkbox"]', onChangeCheckbox );
			frmhelpers.frmOn( document, 'focusin', rootSelector + ' button', onFocus );

			// Change position of dropdown on scroll.
			frmhelpers.frmOn( document, 'scroll', '.frm_quizzes_modal__content', onScroll, true );
			frmhelpers.frmOn( document, 'scroll', '.frm_quizzes_multi_checkboxes ul', e => e.stopPropagation(), false );

			// Close the dropdown when clicking outside.
			frmhelpers.frmOn( document, 'click', rootSelector, event => event.stopPropagation(), true );
			document.addEventListener( 'click', event => {
				document.querySelectorAll( rootSelector + ' button' ).forEach( btn => {
					btn.classList.remove( 'frm_focus' );
				});
			});
		},

		tagsInput: function() {
			const rootSelector = '.frm_quizzes_admin_tags_input';

			const appendTag = ( tag, name, wrapper ) => {
				const input = document.createElement( 'input' );
				input.type = 'hidden';
				input.name = name;
				input.value = tag;

				const span = document.createElement( 'span' );
				span.appendChild( document.createTextNode( tag ) );
				span.appendChild( input );

				wrapper.appendChild( span );
			};

			const onInput = event => {
				if ( 'insertText' !== event.inputType || ',' !== event.data ) {
					return;
				}

				const spans = event.target.closest( rootSelector ).querySelector( ':scope > span' );
				const tag   = event.target.value.replace( ',', '' );
				const name  = event.target.getAttribute( 'data-name' );
				appendTag( tag, name, spans );
				event.target.value = '';
			};

			const onKeyDown = event => {
				if ( 'Backspace' !== event.key || event.target.value ) {
					return;
				}

				const spans    = event.target.closest( rootSelector ).querySelector( ':scope > span' );
				const lastSpan = spans.querySelector( 'span:last-child' );
				if ( lastSpan ) {
					lastSpan.remove();
				}
			};

			// Handle comma key.
			frmhelpers.frmOn( document, 'input', rootSelector + ' input', onInput );

			// Handle backspace key.
			frmhelpers.frmOn( document, 'keydown', rootSelector + ' input', onKeyDown );
		},

		saveScoredSettings: function() {
			const onClickSaveSettings = event => {
				document.getElementById( 'frm_quizzes_scored_setting_values' ).innerHTML = document.getElementById( 'frm_quizzes_scored_settings_modal' ).innerHTML;
			};
			frmhelpers.frmOn( document, 'click', '#frm_quizzes_save_score_weights', onClickSaveSettings );
		},

		setManualScore: function() {
			const editingClass = 'frm_quizzes_manual_score--editing';

			const onClick = event => {
				const btn = event.target;
				const input = btn.parentNode.querySelector( 'input' );
				const score = input.value;
				const fieldId = btn.getAttribute( 'data-field-id' );
				const entryId = btn.getAttribute( 'data-entry-id' );
				const nonce = FrmQuizzesAdminL10n.ajaxNonce;

				input.setAttribute( 'disabled', 'disabled' );
				btn.setAttribute( 'disabled', 'disabled' );

				wp.ajax.send( 'frm_quizzes_set_manual_score', {
					type: 'POST',
					data: {
						_ajax_nonce: nonce,
						field_id: fieldId,
						entry_id: entryId,
						score: score
					},
					success: response => {
						const wrapper = btn.closest( '.frm_quizzes_manual_score' );

						if ( response.score_text ) {
							wrapper.querySelector( '.frm_quizzes_manual_score__view span' ).innerText = response.score_text;
						}

						if ( response.total_score ) {
							document.getElementById( 'frm_quizzes_total_score' ).innerText = response.total_score;
						}

						wrapper.classList.remove( editingClass );
						input.removeAttribute( 'disabled' );
						btn.removeAttribute( 'disabled' );
					},
					error: response => {
						console.error( response );
					}
				});
			};

			const onClickEdit = event => {
				event.preventDefault();
				event.target.closest( '.frm_quizzes_manual_score' ).classList.add( editingClass );
			};

			const onClickCancel = event => {
				event.preventDefault();
				event.target.closest( '.frm_quizzes_manual_score' ).classList.remove( editingClass );
			};

			frmhelpers.frmOn( document, 'click', '.frm_quizzes_save_manual_score', onClick );
			frmhelpers.frmOn( document, 'click', '.frm_quizzes_edit_manual_score', onClickEdit );
			frmhelpers.frmOn( document, 'click', '.frm_quizzes_cancel_edit_manual_score', onClickCancel );
		},

		init: function() {
			this.initModalSettings();
			this.toggleField();
			this.toggleScoreManually();
			this.toggleAdvScoring();
			this.multiCheckboxes();
			this.tagsInput();
			this.saveScoredSettings();
			this.setManualScore();
		}
	};

	function initializeQuizOutcomes() {
		function checkIfQuizOutcomeLoaded( event ) {
			const settings = event.target.closest( '.frm_form_action_settings' );
			if ( settings && settings.classList.contains( 'frm_single_quiz_outcome_settings' ) ) {
				onQuizOutcomeLoaded( settings );
			}
		}

		function onQuizOutcomeLoaded( settings ) {
			changeConditionalLogicMarkup( settings );

			const wysiwyg = settings.querySelector( '.wp-editor-area' );
			if ( wysiwyg ) {
				initWysiwyg( wysiwyg );
			}

			const actionKey = parseInt( settings.getAttribute( 'data-actionkey' ) );
			const idInput   = settings.querySelector( '[name="frm_quiz_outcome_action[' + actionKey + '][ID]"]' );
			if ( ! idInput ) {
				return;
			}

			const actionIsNew = '' === idInput.value;
			if ( ! actionIsNew ) {
				return;
			}

			setNewOutcomeActionName( settings );
		}

		function initWysiwyg( wysiwyg ) {
			if ( 'undefined' === typeof frmDom.wysiwyg ) {
				return;
			}
			frmDom.wysiwyg.init(
				wysiwyg, { height: 160, addFocusEvents: true }
			);
		}

		function changeConditionalLogicMarkup( actionSettings ) {
			const title = actionSettings.querySelector( 'h3' );
			if ( title ) {
				title.textContent = __( 'Outcome Conditions', 'formidable-quizzes' );

				if ( ! title.parentNode.querySelector( '.howto' ) ) {
					title.parentNode.insertBefore(
						span({
							className: 'howto',
							text: __( 'The outcome with the highest number of matches will show after submit.', 'frm-quizzes' )
						}),
						title.nextSibling
					);
				}
			}

			const inlineSelect = actionSettings.querySelector( '.frm-inline-select' );
			if ( inlineSelect ) {
				inlineSelect.remove();
			}

			const addButton = actionSettings.querySelector( '.frm_add_logic_link .frm_add_logic_row' );
			if ( addButton ) {
				replaceTextNodeChild( addButton, __( 'Add Outcome Conditions', 'frm-quizzes' ) );
			}
		}

		function replaceTextNodeChild( parent, textContent ) {
			Array.from( parent.childNodes ).forEach(
				child => {
					if ( '#text' === child.nodeName && '' !== child.textContent.trim() ) {
						child.textContent = textContent;
					}
				}
			);
		}

		function handleOutcomeClickEvents( event ) {
			if ( event.target.classList.contains( 'frm-quizzes-add-outcome' ) ) {
				addNewOutcome();
			}
		}

		function addNewOutcome() {
			const actionTrigger = getOutcomeActionTrigger();
			if ( actionTrigger ) {
				// Trigger the "Quiz Outcome" form action type from the actions list.
				actionTrigger.click();
			}
		}

		function setNewOutcomeActionName( newAction ) {
			const actionKey  = parseInt( newAction.getAttribute( 'data-actionkey' ) );
			const titleInput = newAction.querySelector( '[name="frm_quiz_outcome_action[' + actionKey + '][post_title]"]' );
			if ( ! titleInput ) {
				return;
			}

			const actionTriggerText = document.querySelector( '.frm_quiz_outcome_action' ).textContent.trim();
			const substring         = titleInput.value.replace( actionTriggerText, '' ).trim();

			if ( substring.length && ( isNaN( substring ) || isNaN( parseFloat( substring ) ) ) ) {
				// The action name is not using the default "Quiz Outcome" name so avoid auto-numbering.
				return;
			}

			const numberOfOutcomes  = document.querySelectorAll( '.frm_single_quiz_outcome_settings' ).length;
			const newActionTitle    = actionTriggerText + ' ' + numberOfOutcomes;
			titleInput.value        = newActionTitle;

			// Replace the action's title on duplicate.
			replaceTextNodeChild( newAction.querySelector( '.widget-title h4' ), newActionTitle );
		}

		jQuery( document ).on( 'frm-action-loaded', checkIfQuizOutcomeLoaded );
		document.addEventListener( 'click', handleOutcomeClickEvents );
		wp.hooks.addAction( 'frm_after_duplicate_action', 'formidable', setNewOutcomeActionName );
	}

	function initializeQuizActionLimits() {
		function filterActionAtLimit( atLimit, { type }) {
			if ( atLimit || -1 === [ 'quiz', 'quiz_outcome' ].indexOf( type ) ) {
				return atLimit;
			}
			return quizTypeAtLimit( type );
		}

		function quizTypeAtLimit( type ) {
			if ( 'quiz' === type ) {
				return null !== document.querySelector( '.frm_single_quiz_outcome_settings' );
			}
			// 'quiz_outcome' === type
			return null !== document.querySelector( '.frm_single_quiz_settings' );
		}

		function afterActionRemoved({ type }) {
			if ( -1 === [ 'quiz', 'quiz_outcome' ].indexOf( type ) ) {
				return;
			}

			if ( 'quiz' === type ) {
				enableAction( getOutcomeActionTrigger() );
				return;
			}

			// 'quiz_outcome' === type
			if ( ! document.querySelector( '.frm_single_quiz_outcome_settings' ) ) {
				enableAction( getScoredActionTrigger() );
			}
		}

		function getScoredActionTrigger() {
			return document.querySelector( '.frm_quiz_action' );
		}

		function disableActionsOnActionLoaded( event ) {
			const settings = event.target.closest( '.frm_form_action_settings' );
			if ( ! settings ) {
				return;
			}

			if ( settings.classList.contains( 'frm_single_quiz_outcome_settings' ) ) {
				disableScoredQuizzesOnQuizOutcomeLoaded();
				return;
			}

			if ( settings.classList.contains( 'frm_single_quiz_settings' ) ) {
				disableOutcomesOnScoredQuizLoaded();
			}
		}

		function disableScoredQuizzesOnQuizOutcomeLoaded() {
			const scoredQuizActionTrigger = document.querySelector( '.frm_quiz_action' );
			if ( scoredQuizActionTrigger ) {
				disableAction( scoredQuizActionTrigger );
			}
		}

		function disableOutcomesOnScoredQuizLoaded() {
			const outcomeActionTrigger = getOutcomeActionTrigger();
			if ( outcomeActionTrigger ) {
				disableAction( outcomeActionTrigger );
			}
		}

		function enableAction( trigger ) {
			trigger.classList.remove( 'frm_inactive_action', 'frm_already_used' );
			trigger.classList.add( 'frm_active_action' );
		}

		function disableAction( trigger ) {
			trigger.classList.remove( 'frm_active_action' );
			trigger.classList.add( 'frm_inactive_action', 'frm_already_used' );
		}

		wp.hooks.addFilter( 'frm_action_at_limit', 'formidable', filterActionAtLimit );
		wp.hooks.addAction( 'frm_after_action_removed', 'formidable', afterActionRemoved );
		jQuery( document ).on( 'frm-action-loaded', disableActionsOnActionLoaded );
	}

	function getOutcomeActionTrigger() {
		return document.querySelector( '.frm_quiz_outcome_action' );
	}

	// Initialize everything.
	initializeQuizActionLimits(); // Restrict scored quizzes and outcomes from both existing in a single form.
	FrmQuizzesFormAction.init(); // Scored Quizzes.
	initializeQuizOutcomes(); // Quiz Outcomes.
}() );
