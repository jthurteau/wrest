<?php

/**
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * Snoop log viewer
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 * 
 */

declare(strict_types=1);

return function($params) use (&$canister) {
    $logModules = key_exists('logModules', $canister) ? $canister['logModules'] : [];
    $logPath = '/var/log/httpd';
    $logFile = $params && count($params) > 0 ? $params[0] : 'index';
    $path = "{$logPath}/{$logFile}";
    $out = '';
    $errors = '';
    $lines = [];
    if ($logFile != 'index') {
        if (file_exists($path)) {
            $max = filesize($path);
            $bufferSize = 10000;
            $cap = min($max, $bufferSize);
            $out = file_get_contents($path, false, null, $max - $cap, $bufferSize); //requires +rx
            $lines = array_reverse(explode(PHP_EOL , $out));
            $trunk = $max > $bufferSize ? array_pop($lines) : '';    
        } else {
            $errors = "<p class=\"error\">ERROR: unable to access \"{$logFile}\" .<p>\n";
        }
    } else {
        if (is_readable($logPath)) {
            $out = scandir($logPath); //requires +r
        } else {
            $out = "Unable to access logs in {$logPath}";
        }
    }

    $base = rtrim($canister['baseUri'], '/');
    $resolver = 
        array_key_exists('resolverPylon', $canister) 
        ? (
            strpos($canister['resolverPylon'], '.') !== false 
            ? "{$canister['resolverPylon']}.php" #NOTE multiviews doesn't handle files with extra dots so well.
            : $canister['resolverPylon']
        ) : 'log';
    $baseLogUri = "{$base}/{$resolver}"; //#TODO get pylon 
    print('<pre>');
    print($errors ? "{$errors}\n" : '');
    foreach($logModules as $module => $moduleName) {
        print("<a href=\"{$baseLogUri}/.{$module}\">View {$moduleName}</a>\n");
    }
    if (count($logModules) > 1) {
        print("\n");
    }
    if (is_array($out)) {
        foreach($out as $file) {
            if (in_array($file, ['.', '..'])) {
                continue;
            } elseif (!file_exists("{$logPath}/{$file}")) {
                print('<p class="error">ERROR: unable to access ' . $file . '</p>');
            } else {
                $path = "{$logPath}/{$file}";
                $time = filemtime($path);
                $size =  filesize($path);
                $date = date('Y-M-D g:i', $time);
                $age = 
                    key_exists('startTime', $canister) 
                    ? (($canister['startTime'] - $time) . ' ago') 
                    : ''; 
                print('<p><a href="' . "{$baseLogUri}/{$file}" . '">' . $file . "</a> {$date} {$size} {$age}</p>");
            }
        }
    } elseif (is_string($out) && $out) {
        print("{$out}\n\n");

    } else {
        $currentTime = date('D M d H:i:s Y');
        print("<a href=\"{$baseLogUri}\">Retun to Log File List...</a>\n\n");
        print("Most recent entries: ($currentTime)\n");
        foreach($lines as $line) {
            
            $line = htmlentities($line);
            print("{$line}\n");
        }
    }
    return 1;
};