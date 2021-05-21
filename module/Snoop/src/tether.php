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
    $storagePath = 
        (
            key_exists('storageRoot', $canister) 
            ? $canister['storageRoot']
            : '/var/www/storage'
        );
    $storageBin = 
        key_exists('applicationHandle', $canister) 
        ? $canister['applicationHandle']
        : 'temp'; 
    $snoopLogPath = 
        array_key_exists('snoopLog', $canister) 
        ? (is_string($canister['snoopLog']) ? $canister['snoopLog'] : "${storagePath}/{$storageBin}/snoop.log") 
        :  false;

    $out = '';
    $lines = [];
    $base = rtrim($canister['baseUri'], '/');
    $resolver = 
        array_key_exists('resolverPylon', $canister) 
        ? (
            strpos($canister['resolverPylon'], '.') !== false 
            ? "{$canister['resolverPylon']}.php" #NOTE multiviews doesn't handle files with extra dots so well.
            : $canister['resolverPylon']
        ) : 'log';
    $baseLogUri = "{$base}/{$resolver}";
    $randoLength = 2;
    print('<pre>');
    print("<a href=\"{$baseLogUri}\">Retun to Log File List...</a>\n\n");
    if (!$snoopLogPath) {
        print("<p class=\"error\">ERROR: Snoop log not configured, specify a path in application environment.<p>");
    } else if (!file_exists("${storagePath}/{$storageBin}")) {
        print("<p class=\"error\">ERROR: unable to access storage for ({$storageBin}).<p>");
    } elseif(!file_exists($snoopLogPath)) {
        print("<p class=\"error\">NOTICE: Snoop log for ({$storageBin}) is empty.<p>");
    } else {
        $max = filesize($snoopLogPath);
        $bufferSize = 100000;
        $cap = min($max, $bufferSize);
        $out = file_get_contents($snoopLogPath, false, null, $max - $cap, $bufferSize); //requires +rx
        $lines = array_reverse(explode(PHP_EOL , $out));
        $trunk = $max > $bufferSize ? array_pop($lines) : '';
        $transactions = [];
        $transaction = [];
        foreach($lines as $line) { #TODO beef up the algorythm to handle jumbled randos
            $transaction[] = $line;  
            if (strpos($transaction[count($transaction) - 1], '--- start ') === $randoLength) {
                if (
                    count($transactions) == 0 
                    && strpos($transaction[0], '--- end ') !== $randoLength
                ) {
                    array_unshift($transaction, '^... current transaction in progress');
                    array_unshift($transaction, '');
                }
                $transactions[] = array_reverse($transaction);
                $transaction = [];
            }   
        }

        $lines = array_merge(...$transactions);
    }

    $base = rtrim($canister['baseUri'], '/');
    if (count($lines)) {
        $currentTime = date('H:i:s d M Y');
        print("Most recent entries: ({$currentTime})\n");
        foreach($lines as $line) {
            $line = htmlentities($line);
            print("\n{$line}");
        }
    }
    return 1;
};