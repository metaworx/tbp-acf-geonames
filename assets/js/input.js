( function ( $ ) {

	/**
	 *  initialize_field
	 *
	 *  This function will initialize the $field.
	 *
	 *  @date    30/11/17
	 *  @since    5.6.5
	 *
	 *  @param    n/a
	 *  @return    n/a
	 */

	function initialize_field( $field ) {
	}


	if ( typeof acf.add_action !== 'undefined' ) {

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

		acf.add_action( 'append_field/type=tbp_geoname', initialize_field );
		acf.add_action( 'append_field/type=tbp_language', initialize_field );
		acf.add_action( 'append_field/type=tbp_relationship', initialize_field );
		acf.add_action( 'ready_field/type=tbp_geoname', initialize_field );
		acf.add_action( 'ready_field/type=tbp_language', initialize_field );
		acf.add_action( 'ready_field/type=tbp_relationship', initialize_field );

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

		$( document ).on( 'acf/setup_fields', function ( e, postbox ) {

			// find all relevant fields
			$( postbox ).find( '.field[data-field_type="geoname"]' ).each( function () {

				// initialize
				initialize_field( $( this ) );

			} );

		} );

	}

} )( jQuery );

( function ( $ ) {

	var TbpRelational = acf.models.RelationshipField.extend( {

		select2: false,

		wait: 'load',

		events: {},

		$control: function () {
			return this.$( '.tbp-acf-' + this.get( 'type' ) );
		},

		getAjaxData: function () {

			// load data based on element attributes
			var ajaxData = this.$control().data();
			for ( var name in ajaxData ) {
				ajaxData[ name ] = this.get( name );
			}

			// extra
			ajaxData.action = 'acf/fields/' + this.get( 'type' ) + '/query';
			ajaxData.field_key = this.get( 'key' );

			// Filter.
			ajaxData = acf.applyFilters( this.get( 'type' ) + '_ajax_data', ajaxData, this );

			// return
			return ajaxData;
		},

		onClickAdd: function ( e, $el ) {

			// can be added?
			if ( $el.hasClass( 'disabled' ) ) {
				return false;
			}

			// vars
			var val = this.val();
			var max = parseInt( this.get( 'max' ) );
			var allowReplace = parseInt( this.get( 'replaceSelected' ) );

			if ( max === 1 && allowReplace && val.length === 1 ) {

				// vars
				var $span = this.$listItem( 'values', val[ 0 ] );
				var $a = $span.find( '.acf-icon[data-name="remove_item"]' );

				this.onClickRemove( e, $a );
			}

			acf.models.RelationshipField.prototype.onClickAdd.call( this, e, $el );
		},

		initialize: function () {

			switch ( this.data.layout ) {

				case 'select':

					//console.log( 'select field' );

					this.events = acf.models.SelectField.prototype.events
					this.$input = acf.models.SelectField.prototype.$input;

					acf.models.SelectField.prototype.initialize.call( this );

					break


				case 'list':

					//console.log( 'list field' );

					this.events = acf.models.RelationshipField.prototype.events
					this.getValue = acf.models.RelationshipField.prototype.getValue

					acf.models.RelationshipField.prototype.initialize.call( this );

					break
			}

			//console.log( this )
			//console.log( 'end field' );
		},

		onRemove: acf.models.SelectField.prototype.onRemove,

		onDuplicate: acf.models.SelectField.prototype.onDuplicate

	} );

	var TbpGeoname = TbpRelational.extend( {

		type: 'tbp_geoname',

	} );

	acf.registerFieldType( TbpGeoname );

	var TbpLanguage = TbpRelational.extend( {

		type: 'tbp_language',

	} );

	acf.registerFieldType( TbpLanguage );

	var TbpRelationship = TbpRelational.extend( {

		type: 'tbp_relationship',

	} );

	acf.registerFieldType( TbpRelationship );

} )( jQuery );
