<?php
/*
Plugin Name: Unlimited Codes 
Description: Plugin that allows include diferent types of codes in your Wordpress.
Author: Ovi García - ovimedia.es
Author URI: http://www.ovimedia.es/
Text Domain: unlimited-codes
Version: 0.4
Plugin URI: http://www.ovimedia.es/
*/


if ( ! class_exists( 'unlimited_codes' ) ) 
{
	class unlimited_codes 
    {
        public $codes = "";
        
        function __construct() 
        {   
            add_action( 'init', array( $this, 'uc_load_languages') );
            add_action( 'init', array( $this, 'uc_init_taxonomy') );
            add_action( 'admin_print_scripts', array( $this, 'uc_admin_js_css') );
            add_action( 'add_meta_boxes', array( $this, 'uc_init_metabox') ); 
            add_action( 'save_post', array( $this, 'uc_save_data_codes') );
            add_filter( 'the_content', array( $this, 'uc_load_body') );
            add_action( 'wp_footer', array( $this, 'uc_load_footer') );
            add_action( 'wp_head', array( $this, 'uc_load_head') ); 
            add_action( 'woocommerce_after_single_product', array( $this,'uc_load_after_product'), 100 );
            add_action( 'woocommerce_before_single_product', array( $this,'uc_load_before_product'), 0 );
            
            
            $args = array(
                        'sort_order' => 'asc',
                        'sort_column' => 'post_title',
                        'numberposts'      =>   -1,
                        'post_type' => "code",
                        'post_status' => 'publish'
                     ); 

            $this->codes = get_posts($args); 
        }

        public function uc_load_languages() 
        {
            load_plugin_textdomain( 'unlimited-codes', false, '/'.basename( dirname( __FILE__ ) ) . '/languages/' ); 
        }

        public function uc_init_taxonomy()
        {    
            $labels = array(
            'name' => translate( 'Codes', 'unlimited-codes' ),
                'singular_name' => translate( 'Codes', 'unlimited-codes' ),
                'add_new' =>  translate( 'Add code', 'unlimited-codes' ),
                'add_new_item' => translate( 'Add new code', 'unlimited-codes' ),
                'edit_item' => translate( 'Edit codes', 'unlimited-codes' ),
                'new_item' => translate( 'New code', 'unlimited-codes' ),
                'view_item' => translate( 'Show code', 'unlimited-codes' ),
                'search_items' => translate( 'Search codes', 'unlimited-codes' ),
                'not_found' =>  translate( 'No codes found', 'unlimited-codes' ),
                'not_found_in_trash' => translate( 'No codes found in trash', 'unlimited-codes' ),
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
                'menu_position' => 50,
                'menu_icon' => 'dashicons-editor-code',
                'supports' => array( 'title', 'editor')
            );

            register_post_type( 'code', $args );
        }

        public function uc_admin_js_css() 
        {
            if(get_post_type(get_the_ID()) == "code")
            {
                wp_register_style( 'custom_codes_admin_css', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/css/style.css', false, '1.0.0' );

                wp_enqueue_style( 'custom_codes_admin_css' );

                 wp_register_style( 'codes_select2_css', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/css/select2.min.css', false, '1.0.0' );

                wp_enqueue_style( 'codes_select2_css' );

                wp_enqueue_script( 'codes_script', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/js/scripts.js', array('jquery') );

                wp_enqueue_script( 'codes_select2', 'https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.3/js/select2.min.js', array('jquery') );
            }
        }

        public function uc_init_metabox()
        {
            add_meta_box( 'zone-code', translate( 'Code options', 'unlimited-codes' ), 
                         array( $this, 'uc_meta_options'), 'code', 'side', 'default' );
        }

        public function uc_meta_options( $post )
        {
            global $wpdb;

            ?>
          
            <div class="meta_div_codes">
              <p><label for="uc_post_type_id"><?php echo translate( 'Post type:', 'unlimited-codes' ) ?> </label></p>
              <p><select id="uc_post_type_id" name="uc_post_type_id">
                    <option value="all"><?php echo translate( 'All types', 'unlimited-codes' ) ?></option>
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

                <p>
                    <label for="uc_post_code_id"><?php echo translate( 'Load into:', 'unlimited-codes' ) ?> </label>
                </p>
                <p>
                   <select  multiple="multiple" id="uc_post_code_id" name="uc_post_code_id[]">
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

                        $values = get_post_meta( get_the_ID(), 'uc_post_code_id');

                        echo '<option value="0" ';
                        
                        if(in_array(0, $values[0]))
                                 echo ' selected="selected" ';
                        
                        echo '>'.translate( 'All', 'unlimited-codes' ).'</option>';

                        foreach($posts as $post)
                        {
                            echo '<option ';

                             if(in_array($post->ID, $values[0]))
                                 echo ' selected="selected" ';

                            echo ' value="'.$post->ID.'">'.$post->post_title.'</option>';
                        } 
                     }
                    else
                        echo '<option selected="selected" value="0" >'.translate( 'All', 'unlimited-codes' ).'</option>';

                    ?>

                </select></p>

                <p>
                    <label for="location_code_page"><?php echo translate( 'Post zone:', 'unlimited-codes' ) ?> </label></p>
                <p>
                   <select id="location_code_page" name="location_code_page">
                    <option 
                    <?php if(get_post_meta( get_the_ID(), 'location_code_page', true) == "head")
                    { echo " selected='selected' "; } ?> value="head"><?php echo translate( 'Head', 'unlimited-codes' ) ?></option>
                    <option 
                    <?php if(get_post_meta( get_the_ID(), 'location_code_page', true) == "before_content")
                    { echo " selected='selected' "; } ?>
                    value="before_content"><?php echo translate( 'Before content', 'unlimited-codes' ) ?></option>
                    <option 
                    <?php if(get_post_meta( get_the_ID(), 'location_code_page', true) == "after_content")
                    { echo " selected='selected' "; } ?> value="after_content"><?php echo translate( 'After content', 'unlimited-codes' ) ?></option>
                    <option 
                    <?php if(get_post_meta( get_the_ID(), 'location_code_page', true) == "footer")
                    { echo " selected='selected' "; } ?> value="footer"><?php echo translate( 'Footer', 'unlimited-codes' ) ?></option>
                    </select>
                </p>

                <input type="hidden" id="url_base" value="<?php echo WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/'; ?>" />
                <input type="hidden" id="post_id" value="<?php echo get_the_ID(); ?>" />

                </div>
            <?php 
        }

        public function uc_save_data_codes( $post_id )
        {
            if ( "code" != get_post_type($post_id)) return;

            if( isset( $_REQUEST['uc_post_type_id'] ) )
                update_post_meta( $post_id, 'uc_post_type_id',  $_REQUEST['uc_post_type_id'] );

            if( isset( $_REQUEST['uc_post_code_id'] ) )
                update_post_meta( $post_id, 'uc_post_code_id', $_REQUEST['uc_post_code_id'] );

            if( isset( $_REQUEST['location_code_page'] ) )
                update_post_meta( $post_id, 'location_code_page', $_REQUEST['location_code_page'] );
        }

        public function uc_load_body($content) 
        {
            return $this->unlimited_codes("before_content") . $content . $this->unlimited_codes("after_content");
        } 

        public function uc_load_footer() 
        {
            echo do_shortcode($this->unlimited_codes("footer"));
        }

        public function uc_load_head() 
        {
            echo $this->unlimited_codes("head");
        }
        
        public function uc_load_before_product()
        {
            echo do_shortcode($this->unlimited_codes("before_content"));
        }
        
        public function uc_load_after_product()
        {
            echo do_shortcode($this->unlimited_codes("after_content"));
        }

        public function unlimited_codes($zone)
        {
            $result = "";

            foreach($this->codes as $code)
            {
                $post_type = get_post_meta( $code->ID, 'uc_post_type_id',  true );
                $post_id = get_post_meta( $code->ID, 'uc_post_code_id');
                $post_location = get_post_meta( $code->ID, 'location_code_page', true );

                if($post_type == "all" || $post_type == get_post_type(get_the_id()))
                    if( $post_location == $zone)
                        if(in_array(get_the_id(), $post_id[0]) || in_array(0, $post_id[0]))
                            $result .= $code->post_content;
            }	

            return stripslashes($result);
        }
    }
}

$GLOBALS['unlimited_codes'] = new unlimited_codes();   
    
?>