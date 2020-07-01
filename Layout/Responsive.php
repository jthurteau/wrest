<?php  //#SCOPE_OS_PUBLIC
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility helper class for managing responsive ui

*******************************************************************************/

class Saf_Layout_Responsive{

    public static function forceDesktopToggleButton($label = 'Switch to Desktop View')
    {
?>
<div class="row collapse small-12 hideForCustom">
	<a class="buttonLink calendarButton prominent" href="?forceDesktop=true"><?php Saf_Layout::printIcon('th-list'); ?> <?php print($label); ?></a>
</div>
<?php
    }
    
}