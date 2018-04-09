jQuery(document).ready(function() {
	kebabbleOrderUpdateShowedAreas();
	
	jQuery('#cmCheckBox').change(function(){
		kebabbleOrderUpdateShowedAreas();
	});
});

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