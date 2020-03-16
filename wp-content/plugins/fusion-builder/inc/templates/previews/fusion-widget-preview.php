<?php
/**
 * Underscore.js template.
 *
 * @package fusion-builder
 */

?>
<script type="text/template" id="fusion-builder-block-module-widget-preview-template">
	<#
	var widget_name = '';

	if ( 'undefined' !== typeof params.type && '' !== params.type ) {

		if ( 'undefined' !== typeof builderConfig ) {
			if ( 'undefined' !== typeof builderConfig.widget_data[params.type] ) {
				widget_name = builderConfig.widget_data[params.type].name;
			}
		} else {
			if ( 'undefined' !== typeof fusionBuilderConfig.widget_data[params.type] ) {
				widget_name = fusionBuilderConfig.widget_data[params.type].name;
			}
		}
	}
	#>

	<# if ( '' !== widget_name ) { #>
		<h4 class="fusion_module_title"><span class="fusion-module-icon {{ fusionAllElements[element_type].icon }}"></span>{{ fusionAllElements[element_type].name }}</h4>
		{{{ widget_name }}}
	<# } else { #>
		{{{ fusionBuilderText.select_widget }}}
	<# } #>
</script>
