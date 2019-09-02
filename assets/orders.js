/**
 * Handles the order form visuals in the admin interface.
 *
 * @package kebabble
 * @author soup-bowl
 */

jQuery( document ).ready(
	function() {
		kebabbleOrderUpdateShowedAreas();

		jQuery( '#cmCheckBox' ).change(
			function(){
				kebabbleOrderUpdateShowedAreas();
			}
		);

		jQuery( '#selCollector' ).change(
			function(){
				kebabbleOrderUpdateShowedAreas();
			}
		);

		jQuery( ".btnAddkorder" ).on(
			'click',
			function(e) {
				e.preventDefault();
				jQuery( "#korder_examplerow" ).clone().appendTo( "#korder_tablelist" ).removeClass( "hidden" ).removeAttr( 'id' );
				reloadRemHooks();
			}
		);

		reloadRemHooks();
	}
);

function reloadRemHooks() {
	jQuery( ".btnRemkorder" ).on(
		'click',
		function(e) {
			e.preventDefault();
			jQuery( this ).closest( 'tr' ).remove();
		}
	);
}

function kebabbleOrderUpdateShowedAreas() {
	var cmState = jQuery( "#cmCheckBox" ).is( ":checked" );
	var crState = parseInt( jQuery( '#selCollector' ).val() );

	if (cmState) {
		jQuery( "#kebabbleCustomMessage" ).show();
		jQuery( "#kebabbleOrder" ).hide();
	} else {
		jQuery( "#kebabbleCustomMessage" ).hide();
		jQuery( "#kebabbleOrder" ).show();
	}

	if (crState === 0) {
		jQuery( "#sctDriver" ).show();
	} else {
		jQuery( "#sctDriver" ).hide();
	}
}
