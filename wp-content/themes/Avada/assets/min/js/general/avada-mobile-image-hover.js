function fusionDeactivateMobileImagHovers(){jQuery("body").fusion_deactivate_mobile_image_hovers()}!function(a){"use strict";a.fn.fusion_deactivate_mobile_image_hovers=function(){Number(avadaMobileImageVars.disable_mobile_image_hovers)||(Modernizr.mq("only screen and (max-width:"+avadaMobileImageVars.side_header_break_point+"px)")?a(this).removeClass("fusion-image-hovers"):a(this).addClass("fusion-image-hovers"))}}(jQuery),jQuery(document).ready(function(){fusionDeactivateMobileImagHovers(),jQuery(window).on("resize",function(){fusionDeactivateMobileImagHovers()})});