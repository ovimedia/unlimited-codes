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