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
    $inlineTools = ['pipe'];
    $resolvableTools = [];
    $mergeKeys = ['inlineTools','resolvableTools'];
    $devBulb = 
        is_readable($devBulbPath)
        ? (require($devBulbPath))
        : [];
    if (!is_array($devBulb) && !($devBulb instanceof ArrayAccess)) {
        $devBulb = ['invalidBulb' => [$devBulbPath => 'Dev Bulb Invalid']];
    }
    foreach($mergeKeys as $key) {
        if (isset($$key) && key_exists($key, $devBulb)) {
            is_array($devBulb[$key]) || ($devBulb[$key] = [$devBulb[$key]]);
            $devBulb[$key] = array_unique(array_merge($devBulb[$key], $$key));
        }
    }
    $appHandle = basename(__DIR__);
    return $devBulb + [ #NOTE values in $devBulb override those that follow...
        'applicationHandle' => $appHandle,
        'inlineTools' => $inlineTools,
        'resolvableTools' => $resolvableTools,
    ];
})();