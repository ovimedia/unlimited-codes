jQuery(document).ready(function($)
{		
    jQuery("#uc_post_type_id").on("change", function()
    {	
        load_data();
    });
	
    function load_data()
    {
         var request = jQuery.ajax({
              url: jQuery( "#url_base").val() + "codes.php", 
              method: "POST",
              data: { post_type :  jQuery("#uc_post_type_id").val(), post_id:  jQuery("#post_id").val() }	
        });

        request.done(function( msg ) 
        {	
            jQuery("#uc_post_code_id, #uc_exclude_post_code_id").children('option:not(:first)').remove();
            jQuery("#uc_post_code_id, #uc_exclude_post_code_id").append(msg);
        });
    }
    
    
    jQuery('#content-tmce').click(function() {

        jQuery('.CodeMirror').css("display", "none");
    }); 

    jQuery('#content-html').click(function() {

        jQuery('.CodeMirror').css("display", "block");

    }); 


    $('#zone-code select').select2({tags: true});
}); 