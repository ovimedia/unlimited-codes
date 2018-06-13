<?php
/*
Plugin Name: Unlimited Codes 
Description: Plugin that allows include different code types in your Wordpress.
Author: Ovi García - ovimedia.es
Author URI: http://www.ovimedia.es/
Text Domain: unlimited-codes
Version: 1.8
Plugin URI: https://github.com/ovimedia/unlimited-codes
*/

if ( ! defined( 'ABSPATH' ) ) exit; 

if ( ! class_exists( 'unlimited_codes' ) ) 
{
	class unlimited_codes 
    {        
        function __construct() 
        {   
            add_action( 'init', array( $this, 'uc_load_languages') );
            add_action( 'init', array( $this, 'uc_init_taxonomy') );
            add_action( 'admin_print_scripts', array( $this, 'uc_admin_js_css') );
            add_action( 'add_meta_boxes', array( $this, 'uc_init_metabox') ); 
            add_action( 'save_post', array( $this, 'uc_save_data_codes') );
            add_action( 'wp_footer', array( $this, 'uc_load_footer'), 200 );
            add_action( 'wp_head', array( $this, 'uc_load_head') ); 
            add_action( 'woocommerce_after_single_product', array( $this,'uc_load_after_product'), 100 );
            add_action( 'woocommerce_before_single_product', array( $this,'uc_load_before_product'), 0 );
            add_action( 'woocommerce_after_shop_loop', array( $this,'uc_load_after_product'), 100 );
            add_action( 'woocommerce_before_shop_loop', array( $this,'uc_load_before_product'), 0 );
    
            add_action( 'wp_ajax_uc_load_posts', array( $this, 'uc_load_posts') );
            
            add_filter( 'the_content', array( $this, 'uc_load_body') );
            add_filter( 'plugin_action_links_'.plugin_basename( plugin_dir_path( __FILE__ ) . 'unlimited_codes.php'), array( $this, 'uc_plugin_settings_link' ) );
            
                    
            add_filter( 'manage_edit-code_columns', array( $this, 'uc_edit_code_columns' )) ;
            add_action( 'manage_code_posts_custom_column', array( $this, 'uc_manage_code_columns'), 10, 2 );
            
            add_action( 'template_redirect', array( $this, 'uc_redirect_post') );   
            add_shortcode( 'uc_shortcode', array( $this, 'uc_load_shortcode'));
            add_shortcode( 'uc_post_title', array( $this, 'uc_load_post_title_shortcode'));
            add_shortcode( 'uc_post_taxonomy_terms', array( $this, 'uc_load_post_taxonomy_terms_shortcode'));

            add_action( 'vc_before_init',  array( $this, 'uc_vc_code_shortcode') );
            add_action( 'vc_before_init',  array( $this, 'uc_vc_post_title') );
            add_action( 'vc_before_init',  array( $this, 'uc_vc_post_taxonomy') );
        }

        public function uc_redirect_post() 
        {
            if ( is_single() && 'code' ==  get_query_var('post_type') ) 
            {
                wp_redirect( get_home_url(), 301 );
                exit;
            }
        }
        
        public function uc_load_languages() 
        {
            load_plugin_textdomain( 'unlimited-codes', false, '/'.basename( dirname( __FILE__ ) ) . '/languages/' ); 
        }

        public function uc_init_taxonomy()
        {    
            $labels = array(
                'name' => translate( 'Unlimited Codes', 'unlimited-codes' ),
                'singular_name' => translate( 'Unlimited Code', 'unlimited-codes' ),
                'add_new' =>  translate( 'Add code', 'unlimited-codes' ),
                'add_new_item' => translate( 'Add new code', 'unlimited-codes' ),
                'edit_item' => translate( 'Edit code', 'unlimited-codes' ),
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
                'menu_icon' => WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/img/ufc_icon.png',
                'supports' => array( 'title', 'editor', 'revisions')
            );

            register_post_type( 'code', $args );
        }


        public function uc_edit_code_columns( $columns ) {

            $columns = array(
                'cb' => '<input type="checkbox" />',
                'title' => translate( 'Code', 'unlimited-codes' ),
                'postype' => translate( 'Post type:', 'unlimited-codes' ),
                'loadinto' => translate( 'Load into:', 'unlimited-codes' ),
                'excludein' => translate( 'Exclude in:', 'unlimited-codes' ),
                'pagelocation' =>  translate( 'Page location:', 'unlimited-codes' ),
                'order' =>  translate( 'Order:', 'unlimited-codes' ),
                'shortcode' => translate( 'Shortcode', 'unlimited-codes' ),
                'date' => __( 'Date' )
            );
            
            return $columns;

        }
        
        public function uc_manage_code_columns( $column, $post_id ) 
        {
            switch( $column ) 
            {         
                case 'postype':

                    $values = get_post_meta( $post_id, 'uc_post_type_id', true) ; 
                    
                    $column_values = "";

                    for ($x = 0; $x < count($values); $x++)
                    {
                        $column_values .= translate( ucfirst(  $values[$x]), 'unlimited-codes' ).", ";
                    }

                    echo substr($column_values, 0, -2); 

                break;    
                    
                case 'loadinto':
                    
                    $values = get_post_meta( $post_id, 'uc_post_code_id', true);
                    
                    $column_values = "";
                    
                    foreach ($values as $value)
                    {
                        if($value == -1)
                        {
                            $column_values .= translate( 'All', 'unlimited-codes' ).", ";
                        }
                        else  
                        {
                            $post = get_post($value);
                            $column_values .= translate( ucfirst(  $post->post_title ), 'unlimited-codes' ).", ";
                        }
                    }

                    echo substr($column_values, 0, -2); 
  

                break;    
                    
                case 'excludein':
                    
                    $values = get_post_meta( $post_id, 'uc_exclude_post_code_id', true);

                    $column_values = "";
                    
                    foreach ($values as $value)
                    {
                        $post = get_post($value);
                        
                        if($post->ID == $post_id)
                            $column_values .= "-, ";
                        else  
                            $column_values .= translate( ucfirst(  $post->post_title ), 'unlimited-codes' ).", ";
                    }

                    echo substr($column_values, 0, -2); 
  

                break; 
                      
                case 'pagelocation':

                    echo translate( ucfirst(str_replace("_", " ", get_post_meta( $post_id, 'uc_location_code_page', true))), 'unlimited-codes' ) ;

                break;
                    
                case 'shortcode':

                    echo '[uc_shortcode id="'.$post_id.'"]';

                    break;
                    
                case 'order':

                    echo get_post_meta( $post_id, 'uc_order_code', true) ;

                    break;    
                    

                default :
                    break;
            }
        }
        
        public function uc_load_shortcode( $atts ) 
        {
            $code = get_post($atts['id']); 
            
            return do_shortcode($this->uc_check_shortcode($code->post_content, $atts['id']));
        }

        public function uc_load_post_title_shortcode( $atts ) 
        {
            return "<".$atts['tag']." class='".$atts['class']."' style='text-align: ".$atts['align']."; 
            padding: ".$atts["padding"]."' >".get_the_title()."</".$atts['tag'].">";
        }

        public function uc_load_post_taxonomy_terms_shortcode( $atts ) 
        {
            $terms = wp_get_post_terms(get_the_ID(), $atts['taxonomy']);

            $result = "";

            foreach($terms as $term)
            {
                if($atts["link"] == "yes")
                    $result .= "<a href='".get_term_link($term->term_id)."' >";

                $result .= $term->name;

                if($atts["link"] == "yes")
                    $result .= "</a>";

                $result .= " / ";
            }           
            
            return "<p class='".$atts['class']."'>".substr($result, 0, -3)."</p>";
        }
      
           
        public function uc_check_shortcode($code, $id)
        {
            if(strpos($code, '[uc_shortcode id="'.$id.'"]') > 0)
                return translate( 'Can not load code shortcode inside it.', 'unlimited-codes' );
                
            return $code;
        }

        public function uc_admin_js_css() 
        {
            if(get_post_type(get_the_ID()) == "code")
            {
                wp_register_style( 'custom_codes_admin_css', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/css/style.css', false, '1.0.0' );

                wp_enqueue_style( 'custom_codes_admin_css' );

                wp_register_style( 'codes_select2_css', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/css/select2.min.css', false, '1.0.0' );

                wp_enqueue_style( 'codes_select2_css' );

                wp_enqueue_script( 'codes_script', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/js/scripts.js', array('jquery') );

                wp_enqueue_script( 'codes_select2', WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/js/select2.min.js', array('jquery') );
            }
        }

        public function uc_init_metabox()
        {
            add_meta_box( 'zone-code', translate( 'Code options', 'unlimited-codes' ), 
                         array( $this, 'uc_meta_options'), 'code', 'side', 'default' );
        }
        
        public function uc_get_posts($post_types, $post_id)
        {
              $args = array(
                'orderby' => 'title',
                'order' => 'asc',
                'numberposts' => -1,
                'post_type' => $post_types,
                'post_status' => 'publish'
             ); 
            
            $types = get_post_meta( $post_id, 'uc_post_type_id', true);
            
            if( !in_array("all", $types) && isset($types))
                $args['post_type'] = $types;
            
            return get_posts($args); 
        }

        public function uc_meta_options( $post )
        {
            global $wpdb;
            
            $types = get_post_meta( get_the_ID(), 'uc_post_type_id', true);

            ?>
            <div class="meta_div_codes">         
                <p>
                    <label for="uc_post_type_id">
                        <?php echo translate( 'Post type:', 'unlimited-codes' ) ?>
                    </label>
                </p>
                <p>
                    <select multiple="multiple"  id="uc_post_type_id" name="uc_post_type_id[]">
                        <option value="all" <?php if(in_array("all", $types)) echo ' selected="selected" '; ?> >
                            <?php echo translate( 'All', 'unlimited-codes' ) ?>
                        </option>
                        <?php

                            $results = $wpdb->get_results( 'SELECT DISTINCT post_type FROM '.$wpdb->prefix.'posts WHERE post_status like "publish" and post_type <> "code" and post_type <> "nav_menu_item" and post_type <> "wpcf7_contact_form" order by 1 asc'  );
            
                            $post_types = array();

                            foreach ( $results as $row )
                            {
                                $post_types[] = $row->post_type;
                                
                                echo '<option ';

                                if( in_array($row->post_type, $types) )
                                    echo ' selected="selected" ';

                                echo ' value="'.$row->post_type.'">'.ucfirst ($row->post_type).'</option>';
                            } 

                        ?>
                    </select>
                </p>
                <p>
                    <label for="uc_post_code_id">
                        <?php echo translate( 'Load into:', 'unlimited-codes' ) ?>
                    </label>
                </p>
                <p>
                    <select multiple="multiple" id="uc_post_code_id" name="uc_post_code_id[]">
                        <?php

                            $posts = $this->uc_get_posts($post_types,  get_the_ID());
            
                            $values = get_post_meta( get_the_ID(), 'uc_post_code_id', true);

                            echo '<option value="-1" ';

                            if(in_array(-1, $values))
                                echo ' selected="selected" ';

                            echo '>'.translate( 'All', 'unlimited-codes' ).'</option>';

                            foreach($posts as $post)
                            {
                                echo '<option ';

                                 if(in_array($post->ID, $values))
                                     echo ' selected="selected" ';

                                echo ' value="'.$post->ID.'">'.$post->post_title.'</option>';
                            } 

                            ?>
                    </select>
                </p>
                <p>
                    <label for="uc_exclude_post_code_id">
                        <?php echo translate( 'Exclude in:', 'unlimited-codes' ) ?>
                    </label>
                </p>
                <p>
                    <select multiple="multiple" id="uc_exclude_post_code_id" name="uc_exclude_post_code_id[]">
                        <?php

                            $values = get_post_meta( get_the_ID(), 'uc_exclude_post_code_id', true);

                            foreach($posts as $post)
                            {
                                echo '<option ';

                                 if(in_array($post->ID, $values))
                                     echo ' selected="selected" ';

                                echo ' value="'.$post->ID.'">'.$post->post_title.'</option>';
                            } 
                             
                            ?>
                    </select>
                </p>
                <p>
                    <label for="uc_location_code_page">
                        <?php echo translate( 'Page location:', 'unlimited-codes' ) ?>
                    </label>
                </p>
                <p>
                    <select id="uc_location_code_page" name="uc_location_code_page">
                    <option <?php if(get_post_meta( get_the_ID(), 'uc_location_code_page', true) =="neither" ) { echo " selected='selected' "; } ?> value="neither">
                            <?php echo translate( 'Neither', 'unlimited-codes' ) ?>
                        </option>
                        <option <?php if(get_post_meta( get_the_ID(), 'uc_location_code_page', true) =="head" ) { echo " selected='selected' "; } ?> value="head">
                            <?php echo translate( 'Head', 'unlimited-codes' ) ?>
                        </option>
                        <option <?php if(get_post_meta( get_the_ID(), 'uc_location_code_page', true) =="before_content" ) { echo " selected='selected' "; } ?> value="before_content">
                            <?php echo translate( 'Before content', 'unlimited-codes' ) ?>
                        </option>
                        <option <?php if(get_post_meta( get_the_ID(), 'uc_location_code_page', true) =="after_content" ) { echo " selected='selected' "; } ?> value="after_content">
                            <?php echo translate( 'After content', 'unlimited-codes' ) ?>
                        </option>
                        <option <?php if(get_post_meta( get_the_ID(), 'uc_location_code_page', true) =="footer" ) { echo " selected='selected' "; } ?> value="footer">
                            <?php echo translate( 'Footer', 'unlimited-codes' ) ?>
                        </option>
                    </select>
                </p>
                <p>
                    <label for="uc_order_code">
                        <?php echo translate( 'Order:', 'unlimited-codes' ) ?>
                    </label>
                </p>
                <p>
                    <input type="number" value="<?php if(get_post_meta( get_the_ID(), 'uc_order_code', true) == "") echo "0"; else echo get_post_meta( get_the_ID(), 'uc_order_code', true) ; ?>" placeholder="<?php echo translate( 'Order:', 'unlimited-codes' ) ?>" name="uc_order_code" id="uc_order_code" />
                </p>
                
                
                <?php
                                
                    if ( function_exists('icl_object_id') ) 
                    {
                       ?>
                     <p>
                        <label for="uc_wpml_languages_load">
                            <?php echo translate( 'Apply in following languages:', 'unlimited-codes' ) ?>
                        </label>
                    </p>
                    <p>
                        <select multiple="multiple" id="uc_wpml_languages_load" name="uc_wpml_languages_load[]">
                            <?php

                                $languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=translated_name&order=desc' );

                                if ( !empty( $languages ) ) 
                                {
                                    $values = get_post_meta( get_the_ID(), 'uc_wpml_languages_load', true);

                                    echo '<option value="all" ';

                                    if(in_array("all", $values) || empty($values))
                                        echo ' selected="selected" ';

                                    echo '>'.translate( 'All', 'unlimited-codes' ).'</option>';

                                    foreach( $languages as $language ) 
                                    {
                                        echo '<option ';

                                         if(in_array($language["code"], $values))
                                             echo ' selected="selected" ';

                                        echo ' value="'.$language["code"].'">'.$language["translated_name"].'</option>';
                                    }
                                }

                                ?>
                        </select>
                    </p>
                
                <?php } ?>
                        
                 <p>
                    <label for="uc_shortcode">
                        <?php echo translate( 'Code shortcode:', 'unlimited-codes' ) ?>
                    </label>
                </p>
                <p>
                   <input type="text" readonly value='<?php echo '[uc_shortcode id="'.get_the_ID().'"]'; ?>' id="uc_shortcode" name="uc_shortcode" />
                </p>
                  <input type="hidden" value="ok" id="uc_validate_data" name="uc_validate_data" />
            </div>
        <?php 
        }

        public function uc_save_data_codes( $post_id )
        {
            if ( "code" != get_post_type($post_id) || current_user_can("administrator") != 1 || !isset($_REQUEST['uc_validate_data'])) return;

            $post_type_ids = $post_code_ids = $exclude_post_code_ids = array();

            $validate_uc_post_type_id = $validate_uc_post_code_id = $validate_exclude_post_code_ids = $validate_uc_wpml_languages_load = true;

            foreach( $_REQUEST['uc_post_type_id'] as $type)
            {
                if(wp_check_invalid_utf8( $type, true ) != "")
                    $post_type_ids[] = sanitize_text_field($type);
                else
                    $validate_uc_post_type_id = false;
            }

            foreach( $_REQUEST['uc_post_code_id'] as $id)
            {
                if(intval($id))
                    $post_code_ids[] = intval($id);
                else
                    $validate_uc_post_code_id = false;
            }

            foreach( $_REQUEST['uc_exclude_post_code_id'] as $id)
            {
                if(intval($id))
                    $exclude_post_code_ids[] = intval($id);
                else
                    $validate_exclude_post_code_ids = false;
            }

            foreach( $_REQUEST['uc_wpml_languages_load'] as $id)
            {
                if(wp_check_invalid_utf8( $id, true ) != "")
                    $uc_wpml_languages_load[] = sanitize_text_field($id);
                else
                    $validate_uc_wpml_languages_load = false;
            }

            if($validate_uc_post_type_id )
                update_post_meta( $post_id, 'uc_post_type_id', $post_type_ids);

            if($validate_uc_post_code_id)
                update_post_meta( $post_id, 'uc_post_code_id', $post_code_ids);
            
            if($validate_exclude_post_code_ids)
                update_post_meta( $post_id, 'uc_exclude_post_code_id', $exclude_post_code_ids);

            if(wp_check_invalid_utf8( $_REQUEST['uc_location_code_page'], true ) != "")
                update_post_meta( $post_id, 'uc_location_code_page', sanitize_text_field($_REQUEST['uc_location_code_page']) );
            
            update_post_meta( $post_id, 'uc_order_code', intval( $_REQUEST['uc_order_code'] ));

            if($validate_uc_wpml_languages_load)
                update_post_meta( $post_id, 'uc_wpml_languages_load', $uc_wpml_languages_load);
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

            $args = array(
                'numberposts' =>   -1,
                'post_type' => "code",
                'post_status' => 'publish',
                'meta_key'   => 'uc_order_code',
                'orderby'    => 'meta_value_num',
                'order'      => 'ASC',
                'meta_query' => array(
                    array(
                        'key' => 'uc_location_code_page',
                        'value'  =>  $zone,
                        'compare' => 'IN'
                    )
                )
            ); 

            $codes = get_posts($args); 

            foreach($codes as $code)
            {
                $post_type = get_post_meta( $code->ID, 'uc_post_type_id', true );
                $post_id = get_post_meta( $code->ID, 'uc_post_code_id', true);
                $exclude_post_id = get_post_meta( $code->ID, 'uc_exclude_post_code_id', true); 
                $post_location = get_post_meta( $code->ID, 'uc_location_code_page', true );
                
                if($this->check_wpml_languages($code->ID))
                    if(in_array("all", $post_type) || in_array(get_post_type(get_the_id()), $post_type))
                            if(in_array(get_the_id(), $post_id) || in_array(-1, $post_id) && !in_array(get_the_id(), $exclude_post_id ))
                                $result .= $code->post_content;
            }	

            $pos = 0;

            $total = substr_count($result, 'css=".', $pos);
            
            if($total > 0)
            {
                $result .= "<style>";

                for($x=0; $x < $total; $x++)
                {
                    $pos = strpos($result, 'css=".', $pos);
                    
                    $result .= substr($result, $pos + 5, strpos( $result, "}", $pos) - $pos - 4);

                    $pos++;
                }

                $result .= get_post_meta( $code->ID, "_wpb_post_custom_css", true );

                $result .= "</style>";
            }

            return $this->uc_check_shortcode($result, $code->ID);
        }
        
        public function check_wpml_languages($code_id)
        {
            if ( function_exists('icl_object_id') )  
            {
                $wpml_languages = get_post_meta( $code_id, 'uc_wpml_languages_load', true );
                
                if(in_array("all", $wpml_languages) || in_array(ICL_LANGUAGE_CODE, $wpml_languages) )
                    return true;
                else
                    return false;
            }
            
            return true; 
        }
        
        public function uc_plugin_settings_link( $links ) 
        { 
            $settings_link = '<a href="'.admin_url().'/edit.php?post_type=code">'.translate( 'Codes', 'unlimited-codes' ).'</a>';
            array_unshift( $links, $settings_link ); 
            return $links; 
        }

        public function uc_load_posts()
        {
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

            exit();

        }

        
        public function uc_vc_code_shortcode() 
        {
            $values = array();

            $args = array(
                'numberposts' =>   -1,
                'post_type' => "code",
                'post_status' => 'publish',
                'meta_key'   => 'uc_order_code',
                'orderby'    => 'meta_value_num',
                'order'      => 'ASC'
            ); 

            $codes = get_posts($args); 

            foreach($codes as $code)
            {
                $values[$code->post_title] = $code->ID;
            }

            vc_map( array(
                "name" => translate( "Unlimited Code", "unlimited-codes" ),
                "base" => "uc_shortcode",
                "class" => "",
                "icon" => WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/img/ufc_icon.png',
                "category" => translate( "Unlimited Codes", "unlimited-codes"),
                'admin_enqueue_js' => array(get_template_directory_uri().'/vc_extend/bartag.js'),
                'admin_enqueue_css' => array(get_template_directory_uri().'/vc_extend/bartag.css'),
                "params" => array(
                    array(
                        "type" => "dropdown",
                        "holder" => "div",
                        "class" => "",
                        "heading" => translate( "Unlimited Code:", "unlimited-codes" ),
                        "param_name" => "id",
                        "value" => $values,
                        "description" => translate( "Select a unlimited code.", "unlimited-codes" )
                    )
                )
            ) );
        }

        public function uc_vc_post_title() 
        {
            vc_map( array(
                "name" => translate( "Post title", "unlimited-codes" ),
                "base" => "uc_post_title",
                "class" => "",
                "icon" => WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/img/ufc_icon.png',
                "category" => translate( "Unlimited Codes", "unlimited-codes"),
                'admin_enqueue_js' => array(get_template_directory_uri().'/vc_extend/bartag.js'),
                'admin_enqueue_css' => array(get_template_directory_uri().'/vc_extend/bartag.css'),
                "params" => array(              
                    array(
                        "type" => "dropdown",
                        "holder" => "div",
                        "class" => "",
                        "heading" => translate( "HTML tag:", "unlimited-codes" ),
                        "param_name" => "tag",
                        "value" => array(
                            "P" => "p",
                            "H1" => "h1",
                            "H2" => "h2",
                            "H3" => "h3",
                            "H4" => "h4",
                            "H5" => "h5",
                            "H6" => "h6"
                        ),
                        "description" => translate( "Select a HTML tag.", "unlimited-codes" )
                    ),
                    array(
                        "type" => "dropdown",
                        "holder" => "div",
                        "class" => "",
                        "heading" => translate( "Text align:", "unlimited-codes" ),
                        "param_name" => "align",
                        "value" => array(
                            "Left" => "left",
                            "Center" => "center",
                            "Right" => "right"
                        ),
                        "description" => translate( "Select a text align.", "unlimited-codes" )
                    ),
                    array(
                        "type" => "textfield",
                        "holder" => "div",
                        "class" => "",
                        "heading" => translate( "Padding for title:", "unlimited-codes" ),
                        "param_name" => "padding",
                        "description" => translate( "Select a padding in px or %.", "unlimited-codes" )
                    ),
                    array(
                        "type" => "textfield",
                        "holder" => "div",
                        "class" => "",
                        "heading" => translate( "CSS Class:", "unlimited-codes" ),
                        "param_name" => "class",
                        "description" => translate( "Select a CSS class.", "unlimited-codes" )
                    ),
                )
            ) );
        }

        public function uc_vc_post_taxonomy() 
        {
            $values = array();

            $taxonomies = get_taxonomies();

            foreach ($taxonomies as $tax)
            {
                $values[$tax] = $tax;
            }

            vc_map( array(
                "name" => translate( "Post taxonomy terms", "unlimited-codes" ),
                "base" => "uc_post_taxonomy_terms",
                "class" => "",
                "icon" => WP_PLUGIN_URL. '/'.basename( dirname( __FILE__ ) ).'/img/ufc_icon.png',
                "category" => translate( "Unlimited Codes", "unlimited-codes"),
                'admin_enqueue_js' => array(get_template_directory_uri().'/vc_extend/bartag.js'),
                'admin_enqueue_css' => array(get_template_directory_uri().'/vc_extend/bartag.css'),
                "params" => array(              
                    array(
                        "type" => "dropdown",
                        "holder" => "div",
                        "class" => "",
                        "value" => $values,
                        "heading" => translate( "Taxonomy post:", "unlimited-codes" ),
                        "param_name" => "taxonomy",
                        "description" => translate( "Type the taxonomy to show the terms.", "unlimited-codes" )
                    ),
                    array(
                        "type" => "dropdown",
                        "holder" => "div",
                        "class" => "",
                        "heading" => translate( "Link term:", "unlimited-codes" ),
                        "param_name" => "link",
                        "value" => array(
                            "No" => "no",
                            "Yes" => "yes"  
                        ),
                        "description" => translate( "Add a link for every term.", "unlimited-codes" )
                    ),
                    array(
                        "type" => "textfield",
                        "holder" => "div",
                        "class" => "",
                        "heading" => translate( "CSS Class:", "unlimited-codes" ),
                        "param_name" => "class",
                        "description" => translate( "Select a CSS class.", "unlimited-codes" )
                    ),
                )
            ) );
        }
    }
}

$GLOBALS['unlimited_codes'] = new unlimited_codes();   
    
?>
