<?php //#SCOPE_OS_PUBLIC
namespace Saf\Environment;
/*******************************************************************************
#LIC_FULL

@author Troy Hurteau <jthurtea@ncsu.edu>

Utility class for managing paths.

*******************************************************************************/

//#TODO #1.0.0 update function header docs
class Parser
{

	public static function fileContainsClassDeclaration(string $class, string $file)
	{
		if (Path::fileExistsInPath($file)) {
			$max = 1000;
			$namespaceMatch = false;
			$classDeclaration = file_get_contents($file, true, null, 0, $max);
			$classDeclaration = str_replace(["\t", '  ', "\n"], ' ', $classDeclaration);
			if (strpos($class, '\\') !== false) {
				$namespace = explode('\\', $class);
				$className = array_pop($namespace);
				$namespace = implode('\\', $namespace);
				$namespaceMatch =
					strpos($classDeclaration, "namespace {$namespace};") !== false
					&& (
						strpos($classDeclaration, "class {$className}{") !== false
						|| strpos($classDeclaration, "class {$className} {") !== false
					);
			}
			return 
				$namespaceMatch
				|| strpos($classDeclaration, "class {$class}{") !== false
				|| strpos($classDeclaration, "class {$class} {") !== false;
		}
	}

}