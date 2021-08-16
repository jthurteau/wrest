<?php

/*
 * #SCOPE_OS_PUBLIC #LIC_FULL
 * 
 * @author Troy Hurteau <jthurtea@ncsu.edu>
 *
 * Utility class for parsing XML
 */

namespace Saf;

use Saf\Client\Http;

class Xml
{

    protected $raw = null;

    public function __construct(string $file, string $namespaceOrPrefix = '', bool $isPrefix = false)
	{
        if (Http::isUri($file)) {
            $fileContents = Http::get($file);
        } else {
            $fileContents = file_get_contents($file);
        }
        if (is_numeric($fileContents)) {
            $xmlResult = simplexml_load_string("<null status=\"{$fileContents}\"></null>");
        } elseif (!is_null($fileContents)) {
            $xmlResult = simplexml_load_string(
                $fileContents, 
                'SimpleXMLElement', 
                0, 
                $namespaceOrPrefix, 
                $isPrefix
            );
        } else {
            $xmlResult = null;
        }
        $this->raw = $xmlResult;
	}

    public function get()
    {
        return $this->raw;
    }

}