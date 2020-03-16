/* global CodeMirror, fusionAllElements */
/* eslint no-shadow: 0 */
/* eslint no-empty: 0 */
var FusionPageBuilder = FusionPageBuilder || {};

( function( $ ) {
	var widgets = [];
	var widget;

	$( document ).ready( function() {

		FusionPageBuilder.BaseWidgetSettingsView = FusionPageBuilder.ElementSettingsView.extend( {

			/**
			 * Formats a widget name
			 * @param {String} className
			 * @param {String} name
			 */
			formatName: function ( className, name ) {
				var prefix = className.toLowerCase() + '__';

				try {
					prefix += name.match( /\[(.*?)\]/g ).slice( -1 )[ 0 ].replace( /\[|(\])/g, '' );
				} catch ( e ) {}

				return prefix;
			},

			/**
			 * Maps a widget to the FusionWpWidget view
			 * @param {Object} widget
			 * @param {String} className
			 */
			mapToFusionWpWidget: function( invalidWidgets, widget, className ) {
				var $form,
					selectors,
					fields,
					getKeyValues,
					incorrectFields,
					self;

				self   = this;
				fields = {};
				$form  = $( '<form></form>' ).html( widget.form );
				selectors   = [
					'fieldset',
					'input',
					'select',
					'textarea'
				].join( ',' );

				getKeyValues = function ( element, keys ) {
					var result = {};
					_.each( keys, function( key ) {
					result[ key ] = element[ key ];
					} );
					return result;
				};

				$form.find( selectors ).not( '[type="button"]' ).each( function() {
					var field, name;

					field = getKeyValues(
						this,
						[ 'id', 'className', 'name', 'tagName', 'type', 'value', 'placeholder', 'options', 'checked' ]
					);

					name = self.formatName( className, field.name );

					// Map options for select elements
					field.options = _.map( field.options, function( option ) {
						return getKeyValues( option, [ 'value', 'text', 'selected' ] );
					} );

					// adjust elelment options to fusion options according it's type
					switch ( field.tagName ) {
						case 'INPUT': {
							if ( [ 'text', 'number' ].includes( field.type ) ) {
								field.type = 'textfield';
							} else if ( 'checkbox' === field.type ) {
								field.type = 'radio_button_set';
								field.value = {
									'on': 'On',
									'': 'Off'
								};
								// eslint-disable-next-line dot-notation
								field.default = field.checked ? 'on' : '';
							}
							break;
						}
						case 'SELECT': {
							field.type = 'select';
							field.value = _.reduce( field.options, function( options, option ) {
								if ( option.selected ) {
									field[ 'default' ] = option.value;
								}
								options[ option.value ] = option.text;
								return options;
							}, {} );
							break;
						}

						case 'TEXTAREA': {
							field.type = 'textarea';
							break;
						}

						default:
							break;
					}

					// update common properties
					if ( field.id ) {
						// Temporal fix for menu widget typo
						field.id = 'widget-menu-widget--menu_Link_color' === field.id ? field.id.toLowerCase() : field.id;
						field.heading = $form.find( 'label[for="' + field.id + '"]' ).text() || field.placeholder;
					}

					field.param_name 	= name;
					// assing same ajax callback
					field.callback		= {};
					field.callback[ 'function' ] = 'fusion_get_widget_markup';
					field.callback.ajax     = true;

					if ( 'hidden' === field.type ) {
						delete field.dependency;
						field.hidden = true;
					}

					fields[ name ] = field;
				} );

				incorrectFields = _.find( fields, function ( field ) {
					return ! field.heading || !field.name;
				} );

				widgets[ className ] = {
					className: className,
					fields: fields,
					data: widget,
					isInvalid: incorrectFields || invalidWidgets.includes( className ),
					isCoreWidget: [ 'WP_Widget_Text', 'WP_Widget_Media_Audio', 'WP_Widget_Media_Image', 'WP_Widget_Media_Video', 'WP_Widget_Media_Gallery', 'WP_Widget_Custom_HTML' ].includes( className )
				};
			},

			/**
			 * Handler to update the FusionWpWidget with all the registered widgets in builderConfig
			 */
			updateFusionWpWidget: function () {
				if ( ! widgets.length ) {
					_.each( this.widgetData, this.mapToFusionWpWidget.bind( this, [ 'Fusion_Widget_Social_Links' ] ) );
				}

				this.setWidgetFields();
			},

			clean: function() {
				//NOTE: required for form only
				this.destroyWidgetOptions();
				this.deleteWpModels();
				this.cleanForm();
			},

			/**
			 * Cleans previous appended forms
			 *
			 * @since 6.0
			 * @return {void}
			 */
			cleanForm: function() {
				this.$el.find( '.fusion-widget-settings-form' ).remove();
			},

			/**
			 * Appends form into settings view
			 *
			 * @since 6.0
			 * @return {void}
			 */
			insertForm: function () {
				var container,
					event,
					formSettings,
					widgetData,
					codeElement,
					$el,
					widgetClassName,
					self = this;

				// Create form and append it below the widget selector
				container    = this.$el.find( '#general .fusion_widget .fusion-builder-option:first ' );
				widgetData   = widget.data;
				widgetClassName = widget.className;
				formSettings = {
					coreWidget: widget.isCoreWidget,
					attributes: {
						id: widgetData.id + '-' + this.cid,
						base: widgetData.id,
						form: widgetData.form,
						type: widgetClassName,
						class: widgetData.classname
					}
				};

				this.appendedForm = this.formTemplate( formSettings );

				container.after( this.appendedForm );

				// Aditional steps for core widgets
				if ( formSettings.coreWidget ) {
					$el = this.$el.find( '.widget-inside' ).parent();

					this.setFormValues( widgetClassName );

					// Create the core WP view.
					event = new jQuery.Event( 'widget-added' );

					if ( 'WP_Widget_Text' === widgetClassName ) {
						wp.textWidgets.handleWidgetAdded( event, $el );
					} else if ( 'WP_Widget_Custom_HTML' === widgetClassName ) {
						wp.customHtmlWidgets.handleWidgetAdded( event, $el );
					} else {
						wp.mediaWidgets.handleWidgetAdded( event, $el );
					}

					this.$el.find( '.widget-inside' ).show();

					if ( this.$el.find( '.wp-editor-area' ).length ) {
						setTimeout( function() {
							wp.textWidgets.widgetControls[ 'text-' + self.cid ].initializeEditor();
						}, 200 );
					}

					if ( this.$el.find( '.custom-html-widget-fields .code' ).length ) {
						codeElement = this.$el.find( '.custom-html-widget-fields .code' );
						self.codeBlock = codeElement.next( '.CodeMirror' ).get( 0 ).CodeMirror;

						if ( !self.codeBlock ) {
							self.codeBlock = CodeMirror.fromTextArea( codeElement[ 0 ], {
								lineNumbers: true,
								lineWrapping: true,
								autofocus: true,
								mode: 'htmlmixed'
							} );
						}
						// Refresh editor after initialization
						setTimeout( function() {
							self.codeBlock.refresh();
							self.codeBlock.focus();
						}, 100 );
					}
				} else {
					$el = this.$el.find( '.fusion-widget-settings-form' );

					this.setFormValues( widgetClassName );
					this.$el.find( '.widget-inside' ).show();
				}
			},

			setFormValues: function( widgetClassName ) {
				var self = this;
				// Set form values
				var paramPrefix = widgetClassName.toLowerCase() + '__';

				_.each( this.model.attributes.params, function( value, key ) {
					var $input, selector;

					if ( 'type' !== key && -1 !== key.indexOf( paramPrefix ) ) {
						selector = '[name$="[' + key.replace( paramPrefix, '' ) + ']"]';
						$input   = self.$el.find( selector );
					}

					if ( $input && $input.length ) {
						if ( $input.is( ':checkbox' ) ) {
							if ( 1 === parseInt( value ) || true === value || 'on' === value || 'enabled' === value ) {
								$input.prop( 'checked', true );
							} else {
								$input.prop( 'checked', false );
							}
						} else {
							$input.val( value );
						}
					}
				} );
			},

			/**
			 * Delete the models.
			 *
			 * @since 2.0.0
			 * @returns {void}
			 */
			deleteWpModels: function() {
				if ( 'undefined' !== typeof wp.mediaWidgets.widgetControls && 'undefined' !== typeof wp.mediaWidgets.modelCollection ) {
					wp.mediaWidgets.modelCollection.reset();
					wp.mediaWidgets.widgetControls = {};
				}
				if ( 'undefined' !== typeof wp.textWidgets.widgetControls ) {
					wp.textWidgets.widgetControls = {};
				}

				if ( 'undefined' !== typeof wp.customHtmlWidgets.widgetControls ) {
					wp.customHtmlWidgets.widgetControls = {};
				}
			},

			/**
			 * Destroy the options.
			 *
			 * @since 2.0.0
			 * @returns {void}
			 */
			destroyWidgetOptions: function() {

				// Remove each instance of tinyMCE editor from this view
				this.$el.find( '.wp-editor-area' ).each( function() {
					var editorID = jQuery( this ).attr( 'id' );
					if ( 'undefined' !== typeof window.tinyMCE ) {
						window.tinyMCE.execCommand( 'mceRemoveEditor', false, editorID );
						if ( 'undefined' !== typeof window.tinyMCE.get( editorID ) ) {
							window.tinyMCE.remove( '#' + editorID );
						}
					}
				} );
			},

			cleanWidget: function() {
				var self = this;

				if ( ! widget ) {
					return;
				}
				// Clean prev fields
				_.each( widget.fields, function( field, key ) {
					delete fusionAllElements.fusion_widget.params[ key ];
					delete self.model.attributes.params[ key ];
				} );
				widget = null;
			},

			getWidget: function() {
				return widget;
			},

			setWidget: function() {
				var className = this.model.attributes.params.type;
				if ( ! className  || ! widgets[ className ] ) {
					return;
				}

				widget = widgets[ className ];
			},

			updateWidget: function() {
				var self = this;

				if ( ! widget || widget.isCoreWidget || widget.isInvalid ) {
					return;
				}

				// Update with widget fields
				fusionAllElements.fusion_widget.params = _.extend(
					{
						type: fusionAllElements.fusion_widget.params.type
					},
					widget.fields,
					fusionAllElements.fusion_widget.params
				);

				// Set default values
				_.each( widget.fields, function( field, key ) {
					// Skip if it already have a default param
					if ( self.model.attributes.params[ key ] ) {
						return;
					}
					if ( 'object' === typeof field.value && field.value[ field[ 'default' ] ] ) {
						self.model.attributes.params[ key ] = field[ 'default' ];
					} else if ( 'object' !== typeof field.value && ( field[ 'default' ] || field.value ) ) {
						self.model.attributes.params[ key ] = field[ 'default' ] || field.value;
					}
				} );
			},

			/**
			 * Setter for widget
			 *
			 * @since 2.0.0
			 * @returns {Boolean|Object}
			 */
			setWidgetFields: function () {
				if ( widget && widget.className === this.model.attributes.params.type ) {
					return;
				}
				this.cleanWidget();

				this.setWidget();

				this.updateWidget();
			}

		} );
	} );

}( jQuery ) );
