<?php
class PostCategoryNotifications{
    public $notifications_receiver;
    public $logOn; //is logging activated?
    public $lastPostID; //prevents sending more than one e-mail if a post gets published

    public function __construct(){
        if(get_option('wp-post-category-notifications') != "" ){
			//extrat array
			$this->notifications_receiver = unserialize(get_option('wp-post-category-notifications'));
			$this->logOn = get_option('wp-post-category-notifications-logOn',false);
			$this->lastPostID = get_option('wp-post-category-notifications-lastPostID');
			
			if($this->lastPostID == ""){
				$this->lastPostID = 0;
			}
			
			//prevent errors
			if(gettype($this->notifications_receiver) != "array"){
				$this->notifications_receiver = array();	
			}
		}else{
			//initiate variables
			$this->notifications_receiver = array();	
		}	
    }
	
	function dropNotification($category, $email){
		$tmp =  array();	
		foreach ($this->notifications_receiver as $elements)
		{
			if($category != $elements['category'] || $email != $elements['email'])
			{			
				array_push($tmp, array(
							category => $elements['category'],
							email => $elements['email'],
							note => $elements['note']
							));
			}else{			
				$this->log_write( __( 'Benachrichtigung geloescht fuer', 'wpcn-plugin' ) ." ".  $elements['email'] ." : ". get_cat_name($elements['category']) .".", "blue");
			}
		}
		update_option('wp-post-category-notifications', serialize($tmp));
	}
	
	function addNotification($category, $email, $note){
		$newArrayLength = array_push($this->notifications_receiver, array(
							'category' => $category,
							'email' => $email,
							'note' => $note
							));
		$this->log_write(__( 'Benachrichtigung hinzugefuegt fuer', 'wpcn-plugin' ) ." ". $email ." : ". get_cat_name($category) .".", "blue");
		update_option('wp-post-category-notifications', serialize($this->notifications_receiver));
		return $newArrayLength;
	}
	
	function getLogOn(){
		return $this->logOn;
	}
	
	function setLogOn($status){
		$this->log_write(strcmp($status, "true") == 0 ? __( "Logging eingeschaltet.", 'wpcn-plugin' ) : __( "Logging ausgeschaltet.", 'wpcn-plugin' ), "red");
		
		$this->logOn = $status;
		
		$this->log_write(strcmp($status, "true") == 0 ? __( "Logging eingeschaltet.", 'wpcn-plugin' ) : __( "Logging ausgeschaltet.", 'wpcn-plugin' ), "red");
		update_option('wp-post-category-notifications-logOn', $status);
	}
	
	function setLastPostID($lastPostID){
		$this->lastPostID = $lastPostID;
		update_option('wp-post-category-notifications-lastPostID', $lastPostID);
	}
	
	function getLastPostID(){
		return $this->lastPostID;
	}
	
	function getNotifications(){
		return $this->notifications_receiver;
	}
	
	function notify($post_ID, $post){	
		$mailto = array();		
		
		//prevent sending notifications twice
		if( $post->ID > $this->lastPostID ){
			
			$cat_stack = array();
			
			foreach (get_the_category($post->ID) as $category) {
			
				//push the current category
				array_push($cat_stack, $category->term_id);
			
				$category_parents = get_category_parents( $category, FALSE, "," );
				$category_parents_array = explode(",",$category_parents);
								
				foreach($category_parents_array as $cattitle){
					$category = get_term_by('name', $cattitle, 'category');
					//push all parents of the category
					array_push($cat_stack, $category->term_id);
				}
			}
			
			//we only need categorys once
			$cat_stack = array_unique($cat_stack);
			
			foreach ($cat_stack as $category) {
				//for all categorys
				foreach ($this->getNotifications() as $elements) {	
					//for all email-clients
					if($elements[category] == $category){
						array_push($mailto, $elements[email]);
					}				
				}
			}
			//we only have to send one mail per client
			$mailto = array_unique($mailto);
			
			if(count($mailto) > 0){
				$this->setLastPostID($post->ID);
				
				$subject = utf8_decode( __( "Neuer Beitrag auf", 'wpcn-plugin' ) ." ".get_site_url().": ".$post->post_title );
				
				//built e-mail
				$headers   = array();
				$headers[] = "Content-type: text/html; charset=iso-8859-1";
				$headers[] = "From: ". get_option('blogname') ." <". get_option('admin_email') .">";
				$headers[] = "Reply-To: ". get_option('blogname') ." <". get_option('admin_email') .">";
				$headers[] = "Subject: ". $subject;
				$headers[] = "X-Mailer: PHP/".phpversion();
				
				$body = '<html>
						<head>
						<title>'.$subject.'</title>
						<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
						</head>
						<body>'.
						__( "Titel", 'wpcn-plugin' ). ": ". utf8_decode( $post->post_title ) ."<br>".
						__( "Veroeffentlicht durch", 'wpcn-plugin' ) .": ". get_userdata($post->post_author)->user_login ."<br>". //get author username
						__( "Veroeffentlicht am", 'wpcn-plugin' ) .": ". $post->post_date ."<br>";
						
				//get the categories and link them categories
				$categories = get_the_category($post->ID);
				$output = '';
				if($categories){
					foreach($categories as $category) {
						$output .= '<a href="'.get_category_link( $category->term_id ).'" title="' 
						. esc_attr( sprintf( __( "View all posts in %s" ), $category->name ) ) 
						.'">'. utf8_decode( $category->cat_name ) .'</a>'.$separator;
					}
				}
				$body .= __( "Kategorien", 'wpcn-plugin' ). ": ". trim($output, ' ') ."<br>";
						
				$content = utf8_decode( apply_filters("the_content", $post->post_content) );  //get the content
				$body .= "__________________<br>".
						__( "Inhalt", 'wpcn-plugin' ). ": ". $content;
						
				$body .= "<br>__________________<br>".
						__( "Dies ist eine automatisierte Benachrichtigung von", 'wpcn-plugin' ). " <a href='".get_site_url().
						"' title='".get_option('blogname'). "'>".get_option('blogname'). "</a>.<br>".
						__( "Falls Sie keine weiteren Benachrichtigung erhalten möchten, wenden Sie sich bitte an einen Administrator", 'wpcn-plugin' ) .": ".
						"<a href=\"mailto:".get_option('admin_email')."\">".get_option('admin_email')."</a><br>".
						'</body>
						</html>';
										
				//attach all media				
				$attachments = array( );
				if ( $images = get_posts(array(
					'post_parent' => $post_ID,
					'post_type' => 'attachment',
					'numberposts' => -1,
					'post_mime_type' => 'image',)))
				{
					foreach( $images as $image ) {
						
						$attachmentimageURL=wp_get_attachment_image_src( $image->ID, full );
						$attachmentimage = split("/",$attachmentimageURL[0]);
						$attachmentimage =  wp_upload_dir()['path'] . "/" . $attachmentimage[count($attachmentimage)-1];
						
						array_push($attachments, $attachmentimage);
					}
				}
				foreach ($mailto as $to) {
					wp_mail($to, $subject, $body, $headers, $attachments );
				}
				if(sizeof($mailto) > 0 && $this->logOn){
					$this->log_write(__( "E-Mail", 'wpcn-plugin' )." ".$subject." ID: ". $post_ID ." ".__( "gesendet an", 'wpcn-plugin' ).": ". implode("','",$mailto), "green");
				}
			}
		}
	}
	
	function log_write( $text, $color )
	{
		$isLogOn = $this->logOn;
		if($isLogOn == 'true'){
			$time = date("d.m.Y H:i");
				 
			$file = fopen(plugin_dir_path( __FILE__ ) . "wp-post-category-notifications_log.php","a");
			
			if( $color == "")
			{
				fputs($file,"
				<tr>
					<td>".$time."</td>
					<td>".$text."</td>
				</tr>");
			}
			else{
				fputs($file,"
				<tr>
					<td style=\"color:".$color."\">".$time."</td>
					<td style=\"color:".$color."\">".$text."</td>
				</tr>");
			}
		}
	}
	
	function clearLog(){
		return copy(plugin_dir_path( __FILE__ ) . "wp-post-category-notifications_log_template.php",
			 plugin_dir_path( __FILE__ ) . "wp-post-category-notifications_log.php");
	}
	
	function getLoadingImage(){
		return plugins_url( 'images/loading_1.gif', __FILE__ );
	}
}
?>