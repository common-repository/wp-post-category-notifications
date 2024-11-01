<?php
if (!current_user_can('manage_options')) {
		wp_die( __('You do not have sufficient permissions to access this page.') );
}else{

require("wp-post-category-notifications_class.php");
$pCNotifications = new PostCategoryNotifications();
?>
<script type="text/javascript" language="JavaScript">
function wpcn_deleteEntry(rowNumber, category, email){
	
	document.getElementById("loading_"+rowNumber).style.visibility="visible";
	
	var data = {
		'action': 'WPPCNotifications_drop',
		'category': category,
		'email': email
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery(document).ready(function($) {
		$.post(ajaxurl, data, function(response) {
			wpcn_reloadLog();
			$('table#wpcn_table tr#row'+rowNumber).remove();
		});
	});
}

function wpcn_addEntry(){
	var form = document.getElementById('wpcn_form');
	var email = form['email'].value;
	var note = form['note'].value;
	var category = form['category'].value;
	
	document.getElementById("wp-pcn-addLoading").style.visibility="visible";

	document.getElementById('submit-add').disabled = true;
	
	var data = {
		'action': 'WPPCNotifications_add',
		'email': email,
		'note': note,
		'category': category
	};
	
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery(document).ready(function($) {
		$.post(ajaxurl, data, function(response) {
			var deserialisedResponse = JSON.parse(response);
			wpcn_reload_list();
			wpcn_reloadLog();
			document.getElementById('submit-add').disabled = false;
		});
	});
	
}

function wpcn_reload_list(){
	var data = {
		'action': 'WPPCNotifications_get'
	};
	
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery(document).ready(function($) {
		$.post(ajaxurl, data, function(response) {
			var deserialisedResponse = JSON.parse(response);
			
			
			var html = '<tbody><tr><th><?php echo __( 'Kategorie', 'wpcn-plugin' ); ?></th><th><?php echo __( 'E-Mail', 'wpcn-plugin' ); ?></th><th><?php echo __( 'Notizen', 'wpcn-plugin' ); ?></th><th></th></tr>';
			
			for(var i=0; i < deserialisedResponse.length ; i++){
				html += '<tr id="row'+ i +'">'
					+'<td>'+ deserialisedResponse[i].category_name +'</td>'
					+'<td>'+ deserialisedResponse[i].email +'</td>'
					+'<td>'+ deserialisedResponse[i].note +'</td>'
					+'<td><img style="visibility:hidden" id="loading_'+ i +'" height="16" width="16" src="'+ deserialisedResponse[i].loading_image +'">'
					+'<input type="button" value="X" class="button button-secondary" onclick="wpcn_deleteEntry(\''
					+ i +'\', \''+ deserialisedResponse[i].category + '\', \''+ deserialisedResponse[i].email +'\');"/></td></tr>';
			}
			html += '</tbody>';
			$('table#wpcn_table').html(html);	
			document.getElementById("wp-pcn-addLoading").style.visibility="hidden";
		});
	});
}

function wpcn_logOnOff(){
	
	var logOn = false;
	if( document.getElementById("wp-pcn-LogOnOff").checked ){
		logOn = true;
	}
	
	document.getElementById("wp-pcn-logLoading").style.visibility="visible";
	var data = {
		'action': 'WPPCNotifications_logOnOff',
		'logOn': logOn
	};

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery(document).ready(function($) {
		$.post(ajaxurl, data, function(response) {
			var deserialisedResponse = JSON.parse(response);
			document.getElementById("wp-pcn-logLoading").style.visibility="hidden";
			if(deserialisedResponse.log_on == "true"){
				document.getElementById("wp-pcn-LogOnOff-label").innerHTML = "<?php echo __( 'Log On', 'wpcn-plugin' ); ?>";
			}else{
				document.getElementById("wp-pcn-LogOnOff-label").innerHTML = "<?php echo __( 'Log Off', 'wpcn-plugin' ); ?>";
			}
			wpcn_reloadLog();
		});
	});
}
function wpcn_reloadLog(){
	var data = {
		'action': 'WPPCNotifications_reloadLog'
	};
	
	var html = '<td><img style="visibility:visible" id="wp-pcn-addLoading" height="16" width="16" src="'+
				'<?php echo $pCNotifications->getLoadingImage(); ?>" /></td>';
	jQuery('table#wpcn_log').html(html);	

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery(document).ready(function($) {
		$.post(ajaxurl, data, function(response) {
			var deserialisedResponse = JSON.parse(response);
				
			var html = '<tbody><tr><th><?php echo __( 'Datum Uhrzeit', 'wpcn-plugin' ); ?></th><th><?php echo __( 'Bemerkung', 'wpcn-plugin' ); ?></th></tr>';
			html += deserialisedResponse.log;
			html += '</tbody>';
			$('table#wpcn_log').html(html);	
		});
	});
}
function wpcn_clearLog(){
	var data = {
		'action': 'WPPCNotifications_clearLog'
	};
	
	document.getElementById('submit-clear-log').disabled = true;

	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery(document).ready(function($) {
		$.post(ajaxurl, data, function(response) {
			var deserialisedResponse = JSON.parse(response);
			//TODO user feedback
			if(deserialisedResponse.success){
				//reload log
				wpcn_reloadLog();
			}else{
				//TODO show error Message
			}
			document.getElementById('submit-clear-log').disabled = false;
		});
	});
}
wpcn_reload_list();
wpcn_reloadLog();
</script>
<style type="text/css" title="text/css">
/*
Thanks to Craig: http://www.sitepoint.com/css3-toggle-switch/
*/
input.switch:empty
{
	margin-left: -999px;
}
input.switch:empty ~ label
{
	position: relative;
	float: left;
	line-height: 1.6em;
	text-indent: 4em;
	margin: 0.2em 0;
	cursor: pointer;
	-webkit-user-select: none;
	-moz-user-select: none;
	-ms-user-select: none;
	user-select: none;
}
input.switch:empty ~ label:before, 
input.switch:empty ~ label:after
{
	position: absolute;
	display: block;
	top: 0;
	bottom: 0;
	left: 0;
	content: ' ';
	width: 3.6em;
	background-color: #c33;
	border-radius: 0.3em;
	box-shadow: inset 0 0.2em 0 rgba(0,0,0,0.3);
	-webkit-transition: all 100ms ease-in;
	transition: all 100ms ease-in;
}
input.switch:empty ~ label:after
{
	width: 1.4em;
	top: 0.1em;
	bottom: 0.1em;
	margin-left: 0.1em;
	background-color: #fff;
	border-radius: 0.15em;
	box-shadow: inset 0 -0.2em 0 rgba(0,0,0,0.2);
}
input.switch:checked ~ label:before
{
	background-color: #393;
}
input.switch:checked ~ label:after
{
	margin-left: 2em;
}
</style>
<h1>Wp Post Category Notifications</h1>
<?php
//get the post data and save it
if (isset($_POST["submit-add"])){
	if( !empty($_POST["email"]) && !empty($_POST["category"]) ) {
		$pCNotifications->addNotification($_POST['category'], $_POST['email'], $_POST['note']);
	}else{
		if( empty($_POST["email"])){
			echo '<p align="center" style="color:red;">'. __( 'Bitte geben Sie eine gueltige E-Mail Adresse an.', 'wpcn-plugin' ) .'</p>';
		}
	}
}
?>
<form id="wpcn_form">
<p class="submit">
	<?php wp_dropdown_categories(array('hide_empty' => 0, 'name' => 'category', 'hierarchical' => true));?>
	<input name="email" class="regular-text code" id="email" value="" placeholder="markus.lehmann@jf-weser-ems.de" type="email">
	<input name="note" class="regular-text code" id="note" value="" placeholder="Notizen" type="text">
	<img style="visibility:hidden" id="wp-pcn-addLoading" height="16" width="16" src="<?php echo $pCNotifications->getLoadingImage(); ?>" />
	<input name="submit-add" id="submit-add" class="button button-primary" value="Hinzufügen" type="button" onclick="wpcn_addEntry()">
</p>
<?php
if($pCNotifications->getLastPostID() > 0){
	?><p><?php echo __( 'Letzte Benachrichtigung versandt fuer', 'wpcn-plugin' ) ." '". get_the_title($pCNotifications->getLastPostID()); ?>'.</p><?php
}
?>
<table class="form-table" id="wpcn_table">
	<tr>
		<td><img style="visibility:visible" id="wp-pcn-addLoading" height="16" width="16" src="<?php echo $pCNotifications->getLoadingImage(); ?>" /></td>
	</tr>
</table>
<h2>Wp Post Category Notifications Log</h2>
<div>
	<img style="visibility:hidden" id="wp-pcn-logLoading" height="16" width="16" src="<?php echo plugins_url( 'images/loading_1.gif', __FILE__ ); ?>">
	<input name="submit-clear-log" id="submit-clear-log" class="button button-primary" value="Clear Log" type="button" onclick="wpcn_clearLog()">
	<input type="checkbox" id="wp-pcn-LogOnOff" name="wp-pcn-LogOnOff" class="switch" onclick="wpcn_logOnOff()" <?php 
		if( strcmp($pCNotifications->getLogOn(), "true") == 0 ){
			echo "checked=\"checked\"";
		}
	?>/>
	<label id="wp-pcn-LogOnOff-label" for="wp-pcn-LogOnOff"><?php
	echo strcmp($pCNotifications->getLogOn(), "true") == 0 ? __( 'Log On', 'wpcn-plugin' ) : __( 'Log Off', 'wpcn-plugin' );
	?></label>
</div>
<table class="form-table" id="wpcn_log">
	<tr>
		<td><img style="visibility:visible" id="wp-pcn-addLoading" height="16" width="16" src="<?php echo $pCNotifications->getLoadingImage(); ?>" /></td>
	</tr>
</table>
</form>
<?php
}
?>