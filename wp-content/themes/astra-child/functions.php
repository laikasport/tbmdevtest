<?php
/**
 * Astra Child Theme functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Astra Child
 * @since 1.0.0
 */

/**
 * Define Constants
 */
define( 'CHILD_THEME_ASTRA_CHILD_VERSION', '1.0.0' );

/**
 * Enqueue styles
 */
function child_enqueue_styles() {

	wp_enqueue_style( 'astra-child-theme-css', get_stylesheet_directory_uri() . '/style.css', array('astra-theme-css'), CHILD_THEME_ASTRA_CHILD_VERSION, 'all' );

}

add_action( 'wp_enqueue_scripts', 'child_enqueue_styles', 15 );



/**
 * CHANGE PRIMARY MENU FOR LOGGED IN USERS
 */
function my_wp_nav_menu_args( $args = '' ) {

if( $args['theme_location'] == 'primary' || $args['theme_location'] == 'mobile_menu' ){
    if( is_user_logged_in() ) { 
        $args['menu'] = '20';
    } else { 
        $args['menu'] = '33';
    }
}
    return $args;
}
add_filter( 'wp_nav_menu_args', 'my_wp_nav_menu_args' );

/**
 * CHANGE SECONDARY MENU FOR LOGGED IN USERS
 */
function custom_wp_nav_menu_args( $args = '' ) { 

  if( $args['theme_location'] == 'secondary_menu' ) {
      if( is_user_logged_in() ) { 
        $args['menu'] = '21';
    } else { 
        $args['menu'] = '34';
    }
}
  return $args;
}
add_filter( 'wp_nav_menu_args', 'custom_wp_nav_menu_args' );


/** CHANGE HOMEPAGE FOR LOGGED IN USERS
     * If a user is logged in, tell WordPress to use 'page' on front page of the site
     * @param string $value
     * @return string
     */
    function fn_set_page_as_front_for_loggedin_user( $value ) {
        if ( is_user_logged_in() ) {
            $value = 'page';
            //page is set as front page
        }
        return $value;
    }
    add_filter( 'pre_option_show_on_front', 'fn_set_page_as_front_for_loggedin_user' );

    /**
     * If user is not logged in, set our static page to act as home page
     * @param $value
     * @return int
     */
    function fn_set_context_based_page_on_front( $value ) {

        if( ! is_user_logged_in() ) {
            return $value;
        }

        //for logged in user, use page id
        return 105;
        //change with your own page id.
    }
  add_filter( 'pre_option_page_on_front', 'fn_set_context_based_page_on_front' );