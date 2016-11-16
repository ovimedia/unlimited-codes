<?php
/*
Plugin Name: Unlimited Codes 
Description: Plugin that allows include diferent types of codes in your Wordpress.
Author: Ovi García - ovimedia.es
Author URI: http://www.ovimedia.es/
Text Domain: unlimited-codes
Version: 0.1
Plugin URI: http://www.ovimedia.es/
*/

function init_unlimted_codes()
{    
    $labels = array(
	'name' => _x( 'Códigos', 'post type general name' ),
        'singular_name' => _x( 'Códigos', 'post type singular name' ),
        'add_new' => _x( 'Añadir nuevo', 'code' ),
        'add_new_item' => __( 'Añadir nuevo código' ),
        'edit_item' => __( 'Editar código' ),
        'new_item' => __( 'Nuevo código' ),
        'view_item' => __( 'Ver código' ),
        'search_items' => __( 'Buscar códigos' ),
        'not_found' =>  __( 'No se han encontrado códigos' ),
        'not_found_in_trash' => __( 'No se han encontrado códigos en la papelera' ),
        'parent_item_colon' => ''
    );
 
    $args = array( 'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'query_var' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'menu_position' => 70,
        'menu_icon' => 'dashicons-editor-code',
        'supports' => array( 'title', 'editor' )
    );
 
    register_post_type( 'code', $args );
}

add_action( 'init', 'init_unlimted_codes' );


function unlimited_codes_admin_styles() 
{
    if(get_post_type(get_the_ID()) == "code")
    {
        wp_register_style( 'custom_codes_admin_css', WP_PLUGIN_URL. '/unlimited-codes-master/css/style.css', false, '1.0.0' );
        wp_enqueue_style( 'custom_codes_admin_css' );

        wp_enqueue_script( 'codes_script', WP_PLUGIN_URL. '/unlimited-codes-master/js/scripts.js', array('jquery') );
    }
}

add_action( 'admin_print_scripts', 'unlimited_codes_admin_styles' );

add_action( 'add_meta_boxes', 'unlimited_codes_metabox' );

function unlimited_codes_metabox()
{
	add_meta_box( 'zone-code', 'Opciones del código: ', 'unlimited_codes_meta_options', 'code', 'side', 'default' );
}

function unlimited_codes_meta_options( $post )
{
    global $wpdb;
    
    ?>
   <div class="meta_div_codes">
      <p><label for="uc_post_type_id">Tipo de post: </label></p>
      <p><select id="uc_post_type_id" name="uc_post_type_id">
            <option value="all"> --- Toda la web ---</option>
            <?php

            $results = $wpdb->get_results( 'SELECT DISTINCT post_type FROM '.$wpdb->prefix.'posts WHERE post_status like "publish" order by 1 asc'  );

            foreach ( $results as $row )
            {
                echo '<option ';

                if(get_post_meta( get_the_ID(), 'uc_post_type_id', true) == $row->post_type)
                    echo ' selected="selected" ';

                echo ' value="'.$row->post_type.'">'.ucfirst ($row->post_type).'</option>';
            } 

        ?>
        </select></p>

        <p><label for="uc_post_code_id">Cargar en: </label></p>
        <p><select id="uc_post_code_id" name="uc_post_code_id">
           <option value="0" > --- Mostrar en Todos---</option>
            <?php


            if(get_post_meta( get_the_ID(), 'uc_post_type_id', true) != "")
            {
                $args = array(
                    'sort_order' => 'asc',
                    'sort_column' => 'post_title',
                    'numberposts'      =>   -1,
                    'post_type' => get_post_meta( get_the_ID(), 'uc_post_type_id', true),
                    'post_status' => 'publish'
                 ); 

                $posts = get_posts($args); 

                foreach($posts as $post)
                {
                    echo '<option ';

                     if(get_post_meta( get_the_ID(), 'uc_post_code_id', true) == $post->ID)
                         echo ' selected="selected" ';

                    echo ' value="'.$post->ID.'">'.$post->post_title.'</option>';
                } 
             }

            ?>

        </select></p>

        <p><label for="location_code_page">Zona de la página: </label></p>
        <p><select id="location_code_page" name="location_code_page">
            <option value="-1"> --- Cargar código en ---</option>
            <option 
            <?php if(get_post_meta( get_the_ID(), 'location_code_page', true) == "head")
            { echo " selected='selected' "; } ?> value="head">Head</option>
            <option 
            <?php if(get_post_meta( get_the_ID(), 'location_code_page', true) == "before_content")
            { echo " selected='selected' "; } ?>
            value="before_content">Before Content</option>
            <option 
            <?php if(get_post_meta( get_the_ID(), 'location_code_page', true) == "after_content")
            { echo " selected='selected' "; } ?> value="after_content">After Content</option>
            <option 
            <?php if(get_post_meta( get_the_ID(), 'location_code_page', true) == "footer")
            { echo " selected='selected' "; } ?> value="footer">Footer</option>
        </select></p>

        <input type="hidden" id="url_base" value="<?php echo WP_PLUGIN_URL. '/unlimited-codes-master/'; ?>" />
        <input type="hidden" id="post_id" value="<?php echo get_the_ID(); ?>" />
    
        </div>
    <?php 
}


function uc_save_data_codes( $post_id )
{
    if ( "code" != get_post_type($post_id)) return;
    
    if( isset( $_REQUEST['uc_post_type_id'] ) )
        update_post_meta( $post_id, 'uc_post_type_id',  $_REQUEST['uc_post_type_id'] );
 
    if( isset( $_REQUEST['uc_post_code_id'] ) )
        update_post_meta( $post_id, 'uc_post_code_id', $_REQUEST['uc_post_code_id'] );
 
	if( isset( $_REQUEST['location_code_page'] ) )
        update_post_meta( $post_id, 'location_code_page', $_REQUEST['location_code_page'] );
}

add_action( 'save_post', 'uc_save_data_codes' );

function insert_codes_body($content) 
{
	$content = unlimited_codes("before_content") . $content . unlimited_codes("after_content");
	
	return $content;
}

add_filter( 'the_content', 'insert_codes_body' );


function insert_codes_footer() 
{
	echo unlimited_codes("footer");
}

add_action( 'wp_footer', 'insert_codes_footer');


function insert_codes_head() 
{
	echo unlimited_codes("head");
}

add_action( 'wp_head', 'insert_codes_head' );


function unlimited_codes($zone)
{
	$result = "";
	
	$original = array("]]&gt");
	$changed = array("]]>");
		
    $args = array(
                'sort_order' => 'asc',
                'sort_column' => 'post_title',
                'numberposts'      =>   -1,
                'post_type' => "code",
                'post_status' => 'publish'
             ); 

    $codes = get_posts($args); 
		
	foreach($codes as $code)
	{
        $post_type = get_post_meta( $code->ID, 'uc_post_type_id',  true );
        $post_id = get_post_meta( $code->ID, 'uc_post_code_id', true );
        $post_location = get_post_meta( $code->ID, 'location_code_page', true );
         
		if($post_type == "all" || $post_type == get_post_type(get_the_id()))
			if( $post_location == $zone)
				if(get_the_id() == $post_id || $post_id  == 0)
					$result .= $code->post_content;
	}	
	
	return str_replace($original, $changed, do_shortcode(stripslashes ($result)));
}

?>