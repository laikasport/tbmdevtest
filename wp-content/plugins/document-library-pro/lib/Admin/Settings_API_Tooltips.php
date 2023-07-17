<?php
namespace Barn2\DLP_Lib\Admin;

use Barn2\DLP_Lib\Registerable,
    Barn2\DLP_Lib\Plugin\Plugin;

/**
 * Registers the tooltip assets
 *
 * @package   Barn2\barn2-lib
 * @author    Barn2 Plugins <support@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 * @version   1.0
 */
class Settings_API_Tooltips implements Registerable {
    private $plugin;

    public function __construct( Plugin $plugin ) {
        $this->plugin    = $plugin;
        $this->plugin_id = $plugin->get_id();
    }

    public function register() {
        add_action( 'admin_enqueue_scripts', [ $this, 'load_scripts' ] );
    }


    public function load_scripts() {
        if ( ! wp_script_is( 'barn2-tiptip', 'registered' ) ) {
            wp_register_script(
                'barn2-tiptip',
                plugins_url( 'lib/assets/js/jquery-tiptip/jquery.tipTip.min.js', $this->plugin->get_file() ),
                array( 'jquery' ),
                $this->plugin->get_version(),
                true
            );
        }
    }

}
