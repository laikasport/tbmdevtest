<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

class FrmQuizzesRandom {

	protected $form_id = 0;

	protected $entry_id = 0;

	protected $is_field_object = false;

	protected $add_ids_input = false;

	protected $field_order = array();

	/**
	 * Set up the random options.
	 *
	 * @param array     $fields A list of all form fields.
	 * @param array|int $args   The arguments.
	 */
	public function __construct( $fields, $args ) {
		$this->init_form_id( $args );
		$this->init_entry_id( $args );
		$this->is_field_object = is_object( reset( $fields ) );
	}

	/**
	 * Fills changes fields order args.
	 *
	 * @param array|int $args The arguments.
	 */
	protected function init_form_id( $args ) {
		if ( is_numeric( $args ) ) {
			$this->form_id = $args;
		} elseif ( isset( $args['form_id'] ) ) {
			$this->form_id = $args['form_id'];
		} elseif ( isset( $args['form'] ) ) {
			$this->form_id = $args['form']->id;
		}
	}

	/**
	 * Gets entry ID from args.
	 *
	 * @since 2.0
	 *
	 * @param array $args
	 *
	 * @return void
	 */
	protected function init_entry_id( $args ) {
		if ( $this->entry_id ) {
			return;
		}

		if ( ! empty( $args['entry'] ) ) {
			$this->entry_id = $args['entry']->id;
		} elseif ( FrmAppHelper::is_admin_page( 'formidable-entries' ) && ! empty( $_GET['id'] ) ) {
			$this->entry_id = intval( $_GET['id'] );
		}
	}

	/**
	 * Gets field ids input name.
	 *
	 * @return string
	 */
	protected function get_field_ids_input_name() {
		return 'frm_quizzes_field_ids';
	}

	/**
	 * Changes the fields order.
	 *
	 * @since 2.0
	 *
	 * @param array $fields Fields list.
	 *
	 * @return array
	 */
	public function change_fields_order( $fields ) {
		$quiz_action = FrmQuizzesFormActionHelper::get_quiz_action_from_form( $this->form_id, true );
		if ( ! $quiz_action ) {
			return $fields;
		}

		// Do not randomize the edit or show entry page.
		$skip_on_page = FrmAppHelper::is_admin_page( 'formidable-entries' ) && in_array( FrmAppHelper::simple_get( 'frm_action' ), array( 'show', 'edit' ) );
		if ( $skip_on_page ) {
			return $fields;
		}

		if ( ! empty( $quiz_action->post_content['random_options'] ) ) {
			$this->sort_fields_options_randomly( $fields );
		}

		if ( empty( $quiz_action->post_content['random_questions'] ) ) {
			return $fields;
		}

		$this->add_ids_input = true;

		return $this->get_randomized_fields( $fields );
	}

	protected function sort_fields_options_randomly( &$fields ) {
		foreach ( $fields as &$field ) {
			if ( $this->is_field_object ) {
				if ( empty( $field->options ) || ! is_array( $field->options ) ) {
					continue;
				}
				$field->options = $this->get_randomized_options( $field->options );
			} else {
				if ( empty( $field['options'] ) || ! is_array( $field['options'] ) ) {
					continue;
				}
				$field['options'] = $this->get_randomized_options( $field['options'] );
			}
		}
	}

	/**
	 * Randomize the options in a field. This assumes the
	 * keys are numeric.
	 *
	 * @param array $options
	 *
	 * @since 2.0
	 */
	protected function get_randomized_options( $options ) {
		$first     = reset( $options );
		$first_key = key( $options );
		$has_blank = false;

		// Remove the blank option to add back later.
		if ( $first && $first === '' || ( isset( $first['value'] ) && $first['value'] === '' ) ) {
			$has_blank = true;
			unset( $options[ $first_key ] );
		}

		shuffle( $options );

		if ( $has_blank ) {
			// Include the blank option first.
			array_unshift( $options, $first );
		}

		return $options;
	}

	/**
	 * Gets randomize fields.
	 *
	 * @since 2.0.0
	 *
	 * @param array $fields  Fields array.
	 *
	 * @return array
	 */
	protected function get_randomized_fields( $fields ) {
		$new_field_ids = $this->get_randomized_field_ids();

		if ( false !== $new_field_ids && count( $new_field_ids ) === count( $fields ) ) {
			$new_fields = $this->sort_fields_using_ids( $fields, $new_field_ids );
		} else {
			$paged_fields = $this->split_fields_into_paged( $fields );
			if ( 1 === count( $paged_fields ) ) {
				$new_fields = $this->sort_fields_randomly( $fields );
			} else {
				$new_fields = $this->sort_split_fields_randomly( $paged_fields );
			}
		}

		if ( $this->add_ids_input ) {
			$this->field_order = wp_list_pluck( $new_fields, 'id' );
			add_action( 'frm_entry_form', array( &$this, 'add_ids_input_field' ) );
		}

		return $new_fields;
	}

	protected function sort_split_fields_randomly( $paged_fields ) {
		$new_fields = array();
		foreach ( $paged_fields as $page_data ) {
			if ( count( $page_data['fields'] ) < 2 ) {
				$new_fields += $page_data['fields'];
			} else {
				$new_fields += $this->sort_fields_randomly( $page_data['fields'] );
			}

			if ( ! empty( $page_data['end_field'] ) ) {
				$new_fields[ $page_data['end_index'] ] = $page_data['end_field'];
			}
		}

		return $new_fields;
	}

	protected function split_fields_into_paged( $fields ) {
		$paged_fields = array();
		$current_page = 1;
		foreach ( $fields as $index => $field ) {
			if ( ! isset( $paged_fields[ $current_page ] ) ) {
				$paged_fields[ $current_page ] = array(
					'fields' => array(),
				);
			}

			if ( 'break' === FrmField::get_field_type( $field ) ) {
				$paged_fields[ $current_page ]['end_index'] = $index;
				$paged_fields[ $current_page ]['end_field'] = $field;
				$current_page++;
			} else {
				$paged_fields[ $current_page ]['fields'][ $index ] = $field;
			}

			unset( $field );
		}

		return $paged_fields;
	}

	/**
	 * Sorts fields using field IDs.
	 *
	 * @param array $fields Fields list.
	 * @param array $ids    Fields IDs.
	 *
	 * @return array
	 */
	protected function sort_fields_using_ids( $fields, $ids ) {
		$new_fields = array();

		foreach ( $ids as $id ) {
			foreach ( $fields as $k => $field ) {
				$field_id = is_object( $field ) ? $field->id : $field['id'];
				if ( (int) $field_id === (int) $id ) {
					$new_fields[] = $field;
					unset( $fields[ $k ], $field );
				}
			}
		}

		if ( ! empty( $fields ) ) {
			// Just in case there are fields left, or the ids were tampered with.
			$new_fields += $fields;
			unset( $fields );
		}

		return $new_fields;
	}

	/**
	 * Sorts fields randomly.
	 *
	 * @param array $fields Fields list.
	 * @return array
	 */
	protected function sort_fields_randomly( $fields ) {
		$new_fields = $fields;
		$ordering   = $this->get_list_of_fields_to_reorder( $new_fields );

		// Swap positions that should be shuffled.
		$this->swap_field_order( $new_fields, $fields, $ordering );

		// Keep section fields with the section.
		$this->maybe_add_child_fields( $new_fields, $ordering['sections'] );

		return $new_fields;
	}

	/**
	 * Loop through the list of fields and get the positions of every field that should move.
	 * This also removes the child fields and adds them into an array to add back later.
	 *
	 * @param array $fields The list of fields that will be reordered.
	 *
	 * @return array
	 */
	protected function get_list_of_fields_to_reorder( &$fields ) {
		$reorder_me = array(); // A list of fields that will be shuffled.
		$to_switch  = array(); // A list of field positions that are up for grabs.
		$sections   = array(); // A nested array with fields inside each section.
		$in_section = array(); // A list of positions that are in sections.

		foreach ( $fields as $k => $field ) {
			$field_id   = $this->get_field_data( $field, 'id' );
			$section_id = FrmField::get_option( $field, 'in_section' );
			if ( FrmField::is_field_type( $field, 'divider' ) ) {
				$sections[ $field_id ] = array();
			} elseif ( FrmField::is_field_type( $field, 'end_divider' ) ) {
				// Keep the end section with the other child fields.
				end( $sections );
				$section_id = key( $sections );
			}

			if ( $section_id ) {
				$sections[ $section_id ][] = $field;
				// Remove the fields in a section, so the ordering stays correct.
				unset( $fields[ $k ] );

				$in_section[] = $k;
				$to_switch[]  = $k;
				continue;
			}

			if ( in_array( FrmField::get_field_type( $field ), $this->get_not_randomized_field_types(), true ) ) {
				continue;
			}

			$to_switch[]  = $k;
			$reorder_me[] = array(
				'order' => $k,
				'id'    => $field_id,
			);
		}

		return compact( 'reorder_me', 'to_switch', 'sections', 'in_section' );
	}

	/**
	 * Shuffle the list of field ids, then replace each available slot with a different field.
	 *
	 * @param array $new_fields The reordered list of fields.
	 * @param array $fields     The original list of fields.
	 * @param array $ordering   The info about what to shuffle and what's in sections.
	 *
	 * @return void
	 */
	protected function swap_field_order( &$new_fields, $fields, $ordering ) {
		$reorder_me = $ordering['reorder_me'];

		// Randomize the field ids.
		shuffle( $reorder_me );

		foreach ( $ordering['to_switch'] as $order ) {
			if ( in_array( $order, $ordering['in_section'], true ) ) {
				// Fields in a section will be handled later.
				continue;
			}

			reset( $reorder_me );
			$reorder_key      = key( $reorder_me );
			$reorder_info     = $reorder_me[ $reorder_key ];
			$replace_with     = $fields[ $reorder_info['order'] ];
			unset( $reorder_me[ $reorder_key ] );

			// Replace each field slot with a random one.
			$new_fields[ $order ] = $replace_with;
		}
	}

	/**
	 * Maybe add sub fields with each section field.
	 *
	 * @param array $fields   The reordered list of fields.
	 * @param array $sections All the sub fields in a section.
	 *
	 * @return void
	 */
	protected function maybe_add_child_fields( &$fields, $sections ) {
		if ( ! empty( $sections ) ) {
			// Reset keys before array_splice does.
			$fields = array_values( $fields );

			$this->add_child_fields( $fields, $sections );
		}
	}

	/**
	 * Insert sub fields into the fields array, right after the section heading field.
	 *
	 * @param array $fields   The reordered list of fields.
	 * @param array $sections All the sub fields in a section.
	 *
	 * @return void
	 */
	protected function add_child_fields( &$fields, $sections ) {
		foreach ( $sections as $section_id => $sub ) {
			foreach ( $fields as $k => $f ) {
				if ( (int) $this->get_field_data( $f, 'id' ) !== $section_id ) {
					continue;
				}

				$slice = ( (int) $k ) + 1;
				array_splice( $fields, $slice, 0, $sub );
				break;
			}
		}
	}

	/**
	 * Generates the field IDs field. This field is used to store the order of fields.
	 *
	 * @since 2.0
	 *
	 * @param object $form The current form getting displayed.
	 */
	public function add_ids_input_field( $form ) {
		if ( $form->id !== $this->form_id ) {
			// Only add the quiz field on the right form.
			return;
		}

		$name  = $this->get_field_ids_input_name();
		$value = $name . '_' . implode( ',', $this->field_order );
		echo '<input type="hidden" name="item_meta[' . esc_attr( $name ) . ']" id="field_' . esc_attr( $name ) . '" value="' . esc_attr( $value ) . '"/>';
	}

	/**
	 * Gets field types should not be randomized.
	 *
	 * @since 2.0.0
	 *
	 * @return array
	 */
	protected function get_not_randomized_field_types() {
		return apply_filters( 'frm_quizzes_not_randomized_field_types', array( 'break', 'summary', 'hidden' ) );
	}

	/**
	 * Gets field IDs from the arguments.
	 *
	 * @since 2.0.0
	 *
	 * @return array|false
	 */
	protected function get_randomized_field_ids() {
		if ( $this->entry_id ) {
			return $this->get_field_ids_from_entry();
		}

		return $this->get_field_ids_from_request();
	}

	/**
	 * Gets field IDs of entry. Used in case questions are randomized.
	 *
	 * @since 2.0.0
	 *
	 * @return array|false
	 */
	protected function get_field_ids_from_entry() {
		$ids = FrmQuizzesManualHelper::get_custom_value_from_entry(
			$this->entry_id,
			$this->get_field_ids_input_name()
		);

		if ( ! $ids ) {
			return false;
		}

		return explode( ',', $ids );
	}

	/**
	 * Gets field IDs from request.
	 *
	 * @return array|false
	 */
	protected function get_field_ids_from_request() {
		$name      = $this->get_field_ids_input_name();
		$item_meta = FrmAppHelper::get_post_param( 'item_meta' );
		if ( ! isset( $item_meta[ $name ] ) ) {
			return false;
		}

		$ids = sanitize_text_field( wp_unslash( $item_meta[ $name ] ) );
		$ids = str_replace( $name . '_', '', $ids );

		return $ids ? explode( ',', $ids ) : array();
	}

	/**
	 * Gets field data.
	 *
	 * @param array|object $field Field data.
	 * @param string       $key   Data key.
	 * @return mixed
	 */
	protected function get_field_data( $field, $key ) {
		return is_object( $field ) ? $field->$key : $field[ $key ];
	}
}
