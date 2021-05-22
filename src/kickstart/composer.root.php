<?php
/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * rooting script to pull composer meta-data into a canister
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

$source = __DIR__ . '/composer.json';
if (file_exists($source) && is_readable($source)) {
    $composerMeta = json_decode(file_get_contents($source), true);
    if ($composerMeta && is_array($composerMeta)) {
        $result = [
            'projectFile' => 'composer.json', 
        ];
        $map = [
            'version' => 'applicationVersion',
            'name' => 'applicationPackageName',
            'description' => 'applicationDescription',
            'extra' => 'applicationComposerMeta',
        ];
        foreach($map as $source => $target){
            if (array_key_exists($source, $composerMeta)) {
                $result[$target] = $composerMeta[$source];
            }
        }
        // if (array_key_exists('description', $composerMeta)) {
        //     $result['applicationDescription'] = $composerMeta['description'];
        // }
        // if (array_key_exists('version', $composerMeta)) {
        //     $result['applicationVersion'] = $composerMeta['version'];
        // }
        // if (array_key_exists('extra', $composerMeta)) {
        //     $result['applicationComposerMeta'] = $composerMeta['extra'];
        // }
        return $result;
    }
}
return [];