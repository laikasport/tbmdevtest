<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'You are not allowed to access this file directly.' );
}

class FrmQuizzesSettings {

	/**
	 * @var stdClass|null
	 */
	public $settings;

	/**
	 * @var string
	 */
	protected $setting_name = 'frm_quizzes_settings';

	public function __construct() {
		$this->set_default_options();
	}

	public function default_options() {
		return array(
			'grading_scale' => array(
				array(
					'grade' => 'A',
					'start' => '90',
					'end'   => '100',
				),
				array(
					'grade' => 'B',
					'start' => '80',
					'end'   => '89.9',
				),
				array(
					'grade' => 'C',
					'start' => '70',
					'end'   => '79.9',
				),
				array(
					'grade' => 'D',
					'start' => '60',
					'end'   => '69.9',
				),
				array(
					'grade' => 'F',
					'start' => '0',
					'end'   => '59.9',
				),
			),
		);
	}

	public function set_default_options( $settings = false ) {
		$default_settings = $this->default_options();

		if ( ! $settings ) {
			$settings = $this->get_options();
		} elseif ( true === $settings ) {
			$settings = new stdClass();
		}

		if ( ! isset( $this->settings ) ) {
			$this->settings = new stdClass();
		}

		foreach ( $default_settings as $setting => $default ) {
			if ( is_object( $settings ) && isset( $settings->{$setting} ) ) {
				$this->settings->{$setting} = $settings->{$setting};
			}

			if ( ! isset( $this->settings->{$setting} ) ) {
				$this->settings->{$setting} = $default;
			}
		}
	}

	public function get_options() {
		$settings = get_option( $this->setting_name );

		if ( ! is_object( $settings ) ) {
			if ( $settings ) { // workaround for W3 total cache conflict.
				$settings = unserialize( serialize( $settings ) );
			} else {
				$settings = $this->set_default_options( true );
				$this->store();
			}
		} else {
			$this->set_default_options( $settings );
		}

		return $this->settings;
	}

	public function update( $params ) {
		$settings = $this->default_options();

		foreach ( $settings as $setting => $default ) {
			if ( isset( $params[ 'frm_quizzes_' . $setting ] ) ) {
				$this->settings->{$setting} = $params[ 'frm_quizzes_' . $setting ];
			}
			unset( $setting, $default );
		}
	}

	public function store() {
		// Save the posted value in the database.
		update_option( $this->setting_name, $this->settings );
	}

}
