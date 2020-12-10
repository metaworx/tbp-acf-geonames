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

		acf.add_action('append_field/type=tbp_geoname', initialize_field);
		acf.add_action('append_field/type=tbp_language', initialize_field);
		acf.add_action('ready_field/type=tbp_geoname', initialize_field);
		acf.add_action('ready_field/type=tbp_language', initialize_field);

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

	var TbpRelational = acf.models.RelationshipField.extend({

		select2: false,

		wait: 'load',

		events: {
			'removeField': 'onRemove',
			'keypress [data-filter]': 				'onKeypressFilter',
			'change [data-filter]': 				'onChangeFilter',
			'keyup [data-filter]': 					'onChangeFilter',
			'click .choices-list .acf-rel-item': 	'onClickAdd',
			'click [data-name="remove_item"]': 		'onClickRemove',
		},

		$input: function(){
			return this.$('select');
		},

		$control: function(){
			return this.$('.tbp-acf-' + this.get('type'));
		},

		getAjaxData: function(){

			// load data based on element attributes
			var ajaxData = this.$control().data();
			for( var name in ajaxData ) {
				ajaxData[ name ] = this.get( name );
			}

			// extra
			ajaxData.action = 'acf/fields/' + this.get('type') + '/query';
			ajaxData.field_key = this.get('key');

			// Filter.
			ajaxData = acf.applyFilters(this.get('type') + '_ajax_data', ajaxData, this);

			// return
			return ajaxData;
		},

		onClickAdd: function( e, $el ){

			// vars
			var val = this.val();
			var max = parseInt( this.get('max') );
			var allowReplace = parseInt( this.get('replaceSelected') );

			// can be added?
			if( $el.hasClass('disabled') ) {
				return false;
			}

			if (max === 1 && allowReplace && val.length === 1) {

				// vars
				var $span = this.$listItem('values', val[0]);
				var $a =  $span.find('.acf-icon[data-name="remove_item"]');

				this.onClickRemove( e, $a);

			}

			acf.models.RelationshipField.prototype.onClickAdd.call(this, e, $el);
		},

		initialize: function(){

			// vars
			var $select = this.$input();

			// inherit data
			this.inherit( $select );

			// select2
			if( this.get('ui') ) {

				// populate ajax_data (allowing custom attribute to already exist)
				var ajaxAction = this.get('ajax_action');
				if( !ajaxAction ) {
					ajaxAction = 'acf/fields/' + this.get('type') + '/query';
				}

				// select2
				this.select2 = acf.newSelect2($select, {
					field: this,
					ajax: this.get('ajax'),
					multiple: this.get('multiple'),
					placeholder: this.get('placeholder'),
					allowNull: this.get('allow_null'),
					ajaxAction: ajaxAction,
				});
			}
		},

		onRemove: function(){
			if( this.select2 ) {
				this.select2.destroy();
			}
		}
	});

	var TbpGeoname = TbpRelational.extend({

		type: 'tbp_geoname',

	});

	acf.registerFieldType( TbpGeoname );

	var TbpLanguage = TbpRelational.extend({

		type: 'tbp_language',

	});

	acf.registerFieldType( TbpLanguage );

})(jQuery);
