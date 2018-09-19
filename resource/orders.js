jQuery(document).ready(function() {
	kebabbleOrderUpdateShowedAreas();
	
	jQuery('#cmCheckBox').change(function(){
		kebabbleOrderUpdateShowedAreas();
	});
	
    jQuery(".btnAddkorder").on('click', function(e) {
    	e.preventDefault();
		jQuery( "#korder_examplerow" ).clone().appendTo( "#korder_tablelist" ).removeClass( "hidden" ).removeAttr('id');
		reloadRemHooks();
    });
    
    reloadRemHooks();
});

function reloadRemHooks() {
	jQuery(".btnRemkorder").on('click', function(e) {
    	e.preventDefault();
    	jQuery(this).closest('tr').remove();
    });
}

function kebabbleOrderUpdateShowedAreas() {
	var cmState = jQuery("#cmCheckBox").is(":checked");
	
	if (cmState) {
		jQuery("#kebabbleCustomMessage").show();
		jQuery("#kebabbleOrder").hide();
	} else {
		jQuery("#kebabbleCustomMessage").hide();
		jQuery("#kebabbleOrder").show();
	}
}