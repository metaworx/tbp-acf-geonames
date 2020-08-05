(function($){
	
	
	/**
	*  initialize_field
	*
	*  This function will initialize the $field.
	*
	*  @date	30/11/17
	*  @since	5.6.5
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function initialize_field( $field ) {
		
		console.debug($field);
		console.debug('done initialize_field');
		
	}
	
	
	if( typeof acf.add_action !== 'undefined' ) {
	
		/*
		*  ready & append (ACF5)
		*
		*  These two events are called when a field element is ready for initizliation.
		*  - ready: on page load similar to $(document).ready()
		*  - append: on new DOM elements appended via repeater field or other AJAX calls
		*
		*  @param	n/a
		*  @return	n/a
		*/

		acf.add_action('ready_field/type=geoname', initialize_field);
		acf.add_action('append_field/type=geoname', initialize_field);
		
		
	} else {
		
		/*
		*  acf/setup_fields (ACF4)
		*
		*  These single event is called when a field element is ready for initizliation.
		*
		*  @param	event		an event object. This can be ignored
		*  @param	element		An element which contains the new HTML
		*  @return	n/a
		*/

		window.alert('acf/setup_fields');
		$(document).on('acf/setup_fields', function(e, postbox){
			
			// find all relevant fields
			$(postbox).find('.field[data-field_type="geoname"]').each(function(){
				
				// initialize
				initialize_field( $(this) );
				
			});
		
		});
	
	}

})(jQuery);

(function($, undefined){

	var Field = acf.models.RelationshipField.extend({

		type: 'geoname',

		events: {
			'keypress [data-filter]': 				'onKeypressFilter',
			'change [data-filter]': 				'onChangeFilter',
			'keyup [data-filter]': 					'onChangeFilter',
			'click .choices-list .acf-rel-item': 	'onClickAdd',
			'click [data-name="remove_item"]': 		'onClickRemove',
		},

		$control: function(){
			return this.$('.acf-geoname');
		},

		getAjaxData: function(){

			// load data based on element attributes
			var ajaxData = this.$control().data();
			for( var name in ajaxData ) {
				ajaxData[ name ] = this.get( name );
			}

			// extra
			ajaxData.action = 'acf/fields/geoname/query';
			ajaxData.field_key = this.get('key');

			// Filter.
			ajaxData = acf.applyFilters( 'geoname_ajax_data', ajaxData, this );

			// return
			return ajaxData;
		}

	});

	acf.registerFieldType( Field );

})(jQuery);

