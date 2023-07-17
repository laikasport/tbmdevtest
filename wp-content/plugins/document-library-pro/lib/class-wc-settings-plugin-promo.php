<?php

use Barn2\DLP_Lib\Registerable;

if ( ! class_exists( 'WC_Barn2_Plugin_Promo' ) ) {

    /**
     * Provides functions to add the plugin promo to the plugin settings page in the WordPress admin.
     *
     * @package   Barn2\barn2-lib
     * @author    Barn2 Plugins <info@barn2.com>
     * @license   GPL-3.0
     * @copyright Barn2 Media Ltd
     * @version   1.3.3
     */
    class WC_Barn2_Plugin_Promo implements Registerable {

        private $plugin_id;
        private $plugin_file;
        private $section_slug;
        private $settings_page;

        public function __construct( $plugin_id, $plugin_file, $section_slug, $settings_page = false ) {
            $this->plugin_id     = $plugin_id;
            $this->plugin_file   = $plugin_file;
            $this->section_slug  = $section_slug;
            $this->settings_page = $settings_page;
        }

        public function register() {
            if ( $this->settings_page ) {
                add_filter( 'woocommerce_get_settings_' . $this->section_slug, array( $this, 'add_promo' ), 11, 1 );
            } else {
                add_filter( 'woocommerce_get_settings_products', array( $this, 'add_product_section_promo' ), 11, 2 );
            }

            add_action( 'admin_enqueue_scripts', array( $this, 'load_styles' ) );
        }

        public function add_product_section_promo( $settings, $current_section ) {
            // Check we're on the correct settings section
            if ( $this->section_slug !== $current_section ) {
                return $settings;
            }
            return $this->add_promo( $settings );
        }

        public function get_promo_content() {
            if ( ( $promo_content = get_transient( 'barn2_plugin_promo_' . $this->plugin_id ) ) === false ) {
                $promo_response = wp_remote_get( 'https://barn2.com/wp-json/barn2/v2/pluginpromo/' . $this->plugin_id . '?_=' . date('mdY') );

                if ( wp_remote_retrieve_response_code( $promo_response ) != 200 ) {
                    return;
                }

                $promo_content = json_decode( wp_remote_retrieve_body( $promo_response ), true );

                set_transient( 'barn2_plugin_promo_' . $this->plugin_id, $promo_content, DAY_IN_SECONDS );
            }

            if ( empty( $promo_content ) || is_array( $promo_content ) ) {
                return;
            }

            return $promo_content;
        }

        public function add_promo( $settings = array() ) {
            $promo_content = $this->get_promo_content();

            if ( empty( $promo_content ) ) {
                return $settings;
            }

            $settings[0]['class'] = $settings[0]['class'] . ' promo';

            $plugin_settings = array(
                array(
                    'id'    => 'barn2_plugin_promo',
                    'type'  => 'settings_start',
                    'class' => 'barn2-plugin-promo'
                )
            );

            $plugin_settings[] = array(
                'id'   => 'barn2_plugin_promo_content',
                'type' => 'plugin_promo',
                'content' => $promo_content
            );

            $plugin_settings[] = array(
                'id'   => 'barn2_plugin_promo',
                'type' => 'settings_end'
            );

            return array_merge( $settings, $plugin_settings );
        }

        public function load_styles() {
            wp_enqueue_style( 'barn2-promo', plugins_url( 'lib/assets/css/admin/plugin-promo.min.css', $this->plugin_file ) );
        }

        public function render_promo() {
            $promo_content = $this->get_promo_content();

            if ( !empty( $promo_content ) ) {
                return '<div id="barn2_plugin_promo" class="barn2-plugin-promo"><div id="barn2_plugin_promo_content">' . $promo_content . '</div></div>';
            }

            return;
        }
    }
}