<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 * sample application root with localizer for SAF
 * @link saf.src:kickstart/example.app.root.php
 */

return (function(){
    $devBulbPath = __DIR__ . '/local-dev.root.php';
    $devBulb = 
        is_readable($devBulbPath)
        ? (require($devBulbPath))
        : [];
    if (is_array($devBulb) && $devBulb instanceof ArrayAccess) {
        $devBulb = ['invalidBulb' => [$devBulbPath => 'Dev Bulb Invalid']];
    }
    $inlineTools = ['pipe'];
    if (key_exists('inlineTools', $devBulb) && is_array($devBulb['inlineTools'])) {
        $devBulb['inlineTools'] = array_unique(array_merge($devBulb['inlineTools'], $inlineTools));
    }
    $appHandle = basename(__DIR__);
    return $devBulb + [ #NOTE values in $devBulb override those that follow...
        'applicationHandle' => $appHandle,
        'inlineTools' => $inlineTools,
    ];
})();