<?php
/*
Plugin Name: WP Post Category Notifications
Plugin URI: http://jf-weser-ems.de/wp-post-category-notifications
Description: Sends category specific email notifications whenever a post is published.
Version: 1.0
Author: Markus Alexander Lehmann
Author URI: http://jf-weser-ems.de/ueber-uns/fachbereiche/oeffentlichkeitsarbeit
License: GPL2
*/

//Add the Option Menu
add_action('admin_menu', 'WPPostCategoryNotifications_adminMenuCall');
//Add post publish aktion
add_action( 'publish_post', 'WPPostCategoryNotifications_postPublished', 10, 2 );
add_action("wp_ajax_WPPCNotifications_drop", "WPPostCategoryNotifications_drop");
add_action('wp_ajax_WPPCNotifications_add', 'WPPostCategoryNotifications_add');
add_action('wp_ajax_WPPCNotifications_get', 'WPPostCategoryNotifications_get');
add_action('wp_ajax_WPPCNotifications_logOnOff', 'WPPostCategoryNotifications_logOnOff');
add_action('wp_ajax_WPPCNotifications_clearLog', 'WPPostCategoryNotifications_clearLog');
add_action('wp_ajax_WPPCNotifications_reloadLog', 'WPPostCategoryNotifications_reloadLog');

function wpcn_init() {
 $plugin_dir = basename(dirname(__FILE__));
 load_plugin_textdomain( 'wpcn-plugin', false, $plugin_dir . "/languages/");
}

add_action('plugins_loaded', 'wpcn_init');

function WPPostCategoryNotifications_adminMenuCall() {
	add_options_page('WP Post Category Notifications', 'WP Post Category Notifications', 'manage_options', 'WP_Post_Manager_Options', 'WPPostCategoryNotifications_options');
}

function WPPostCategoryNotifications_options () {

	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	include("wp-post-category-notifications_options.php");	
}

function WPPostCategoryNotifications_postPublished($post_ID, $post){	
	require_once("wp-post-category-notifications_class.php");
	$pCNotifications = new PostCategoryNotifications();
	$pCNotifications->notify($post_ID, $post);
}

function WPPostCategoryNotifications_get(){	
	require_once("wp-post-category-notifications_class.php");
	$pCNotifications = new PostCategoryNotifications();
	
	$responseArray = array();
	
	$loading_image = $pCNotifications->getLoadingImage();
	
	$notifications = $pCNotifications->getNotifications();
	
	foreach($pCNotifications->getNotifications() as $notification){
		$responseArray[] = array( 'category_name' => get_cat_name($notification['category']),
								'category' => $notification['category'],
								'note' => $notification['note'],
								'email' => $notification['email'],
								'loading_image' => $loading_image);
	}
		
	echo json_encode($responseArray);
	
	exit;
}

function WPPostCategoryNotifications_drop(){
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}else{
		require_once("wp-post-category-notifications_class.php");
		$pCNotifications = new PostCategoryNotifications();
		$pCNotifications->dropNotification($_REQUEST["category"], $_REQUEST["email"]);
		echo $_REQUEST["category"]." ".$_REQUEST["email"];
	}
	exit;
}

function WPPostCategoryNotifications_add(){
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}else{
		require_once("wp-post-category-notifications_class.php");
		$pCNotifications = new PostCategoryNotifications();
		$newArrayLength = $pCNotifications->addNotification($_REQUEST["category"], $_REQUEST["email"], $_REQUEST["note"]);
		
		$responseArray = array( 'notification_receiver_count' => $newArrayLength,
								'category_name' => get_cat_name($_REQUEST["category"]),
								'loading_image' => $pCNotifications->getLoadingImage());
		
		 // response
		 echo json_encode( $responseArray );
	}
	exit;
}

function WPPostCategoryNotifications_logOnOff(){
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}else{
		require_once("wp-post-category-notifications_class.php");
		$pCNotifications = new PostCategoryNotifications();
		$pCNotifications->setLogOn($_REQUEST["logOn"]);
		
		 //response
		echo json_encode( array( 'log_on' => $pCNotifications->getLogOn() ));
	}
	exit;
}

function WPPostCategoryNotifications_clearLog(){
	if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}else{
		require_once("wp-post-category-notifications_class.php");
		$pCNotifications = new PostCategoryNotifications();
		$success = $pCNotifications->clearLog();
		
		 //response
		echo json_encode( array( 'success' => $success ));
	}
	exit;
}

function WPPostCategoryNotifications_reloadLog(){if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}else{
		$logFile = file_get_contents(plugin_dir_path( __FILE__ ) . "wp-post-category-notifications_log.php");
		echo json_encode( array( 'log' => $logFile ));
	}
	exit;
}
?>