jQuery(document).ready(function($) {
    jQuery("#uc_post_type_id").on("change", function() {
        var request = jQuery.ajax({
            url: "admin-ajax.php",
            method: "POST",
            data: { action: 'uc_load_posts', post_type: jQuery("#uc_post_type_id").val(), post_id: jQuery("#post_id").val() }
        });

        request.done(function(msg) {
            jQuery("#uc_post_code_id, #uc_exclude_post_code_id").children('option:not(:first)').remove();
            jQuery("#uc_post_code_id, #uc_exclude_post_code_id").append(msg);
        });
    });

    jQuery('#zone-code select').select2({ tags: true });
});