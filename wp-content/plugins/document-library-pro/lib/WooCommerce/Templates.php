<?php
namespace Barn2\DLP_Lib\WooCommerce;

use Barn2\DLP_Lib\Template_Loader;

/**
 * A WooCommerce template loader.
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 */
class Templates implements Template_Loader {

    private $template_path;
    private $default_path;

    public function __construct( $theme_dir = '', $default_path = '' ) {
        $this->template_path = $theme_dir ? \trailingslashit( \WC()->template_path() . $theme_dir ) : '';
        $this->default_path  = $default_path ? \trailingslashit( $default_path ) : '';
    }

    public function get_template( $template_name, array $args = [] ) {
        return \wc_get_template_html( $template_name, $args, $this->get_template_path(), $this->get_default_path() );
    }

    public function load_template( $template_name, array $args = [] ) {
        \wc_get_template( $template_name, $args, $this->get_template_path(), $this->get_default_path() );
    }

    public function get_template_path() {
        return $this->template_path;
    }

    public function get_default_path() {
        return $this->default_path;
    }

}
