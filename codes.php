<?php

require_once( '../../../wp-load.php' );

if(isset($_REQUEST["post_type"]))
{
	if ( ! function_exists( 'get_posts' ) ) 
     require_once '../../../wp-includes/post.php';
    
    $args = array(
        'orderby' => 'title',
        'order' => 'asc',
        'numberposts' => -1,
        'post_type' => $_REQUEST["post_type"], 
		'post_status' => 'publish'
    ); 
 
    if(in_array( "all", $_REQUEST["post_type"]))
    {
        global $wpdb;
        
        $results = $wpdb->get_results( 'SELECT DISTINCT post_type FROM '.$wpdb->prefix.'posts WHERE post_status like "publish" and post_type <> "code" and post_type <> "nav_menu_item" and post_type <> "wpcf7_contact_form" order by 1 asc'  );
        
        $post_types = array();
        
        foreach($results as $value)
            $post_types[] = $value->post_type;
        
        $args['post_type'] = $post_types;
    }
        
	$posts = get_posts($args); 

	foreach($posts as $post)
	{
		echo '<option ';
        
         if(get_post_meta( $_REQUEST["post_id"], 'uc_post_code_id', true) == $post->ID)
             echo ' selected="selected" ';
        
        echo ' value="'.$post->ID.'">'.$post->post_title.'</option>';
	} 
}

?>