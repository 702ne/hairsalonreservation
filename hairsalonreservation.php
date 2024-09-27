<?php
/*
Plugin Name: Hair Salon Reservation
Plugin URI: <https://nerdoz.net/>
Description: A reservation system for hair salons allowing customers to book appointments.
Version: 1.0
Author: Young Park
Author URI: <https://nerdoz.net/>
License: GPL2
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
	exit;
}

// Define constants


// Include the functions file
require_once(ABSPATH . 'wp-admin/includes/file.php');
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';
    require_once plugin_dir_path(__FILE__) . 'includes/pages.php';
    require_once plugin_dir_path(__FILE__) . 'includes/client.php';
    require_once plugin_dir_path(__FILE__) . 'includes/email.php';

register_activation_hook(__FILE__, 'hsr_create_tables');
add_action('admin_menu', 'hsr_admin_menu');

// for client
// 예약 내역 단축코드 등록
add_shortcode('hsr_user_reservations', 'hsr_user_reservations');
// 예약 삭제 액션 등록
add_action('admin_post_hsr_delete_user_reservation', 'hsr_delete_user_reservation');

// old
add_shortcode('hsr_reservation_form', 'hsr_reservation_form');
add_shortcode('hsr_reservation_history', 'hsr_reservation_history');

function hsr_enqueue_scripts() {
    wp_enqueue_script('hsr-script', plugins_url('includes/hsr-script.js', __FILE__), array('jquery'), '1.0.0', true);
}
function hsr_enqueue_styles() {
    wp_enqueue_style('hsr-styles', plugins_url('includes/hsr_style.css', __FILE__));
}

add_action('wp_enqueue_scripts', 'hsr_enqueue_scripts');
add_action('wp_enqueue_scripts', 'hsr_enqueue_styles');

add_action('wp_ajax_hsr_get_staff_name', 'hsr_get_staff_name');
add_action('wp_ajax_nopriv_hsr_get_staff_name', 'hsr_get_staff_name');

add_action('wp_ajax_hsr_get_staff_availability', 'hsr_get_staff_availability');
add_action('wp_ajax_nopriv_hsr_get_staff_availability', 'hsr_get_staff_availability');

add_action('wp_ajax_hsr_get_available_staff', 'hsr_get_available_staff');
add_action('wp_ajax_nopriv_hsr_get_available_staff', 'hsr_get_available_staff');

add_action('wp_ajax_hsr_get_available_times', 'hsr_get_available_times');
add_action('wp_ajax_nopriv_hsr_get_available_times', 'hsr_get_available_times');

add_action('show_user_profile', 'hsr_add_phone_field');
add_action('edit_user_profile', 'hsr_add_phone_field');

add_action('personal_options_update', 'hsr_save_phone_field');
add_action('edit_user_profile_update', 'hsr_save_phone_field');

add_action('user_register', 'hsr_save_phone_on_registration');
add_action('register_form', 'hsr_add_phone_field_to_registration');

add_action('wp_ajax_hsr_make_reservation', 'hsr_make_reservation');

add_action('wp_ajax_hsr_ajax_test', 'hsr_ajax_test');
add_action('wp_ajax_nopriv_hsr_ajax_test', 'hsr_ajax_test');