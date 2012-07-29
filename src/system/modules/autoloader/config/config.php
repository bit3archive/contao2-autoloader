<?php

$autoloadFunctions = spl_autoload_functions();
if (TL_MODE == 'BE' &&
	function_exists('__autoload')
) {
	// try to fix the autoloader
	Autoloader::fixAutoloader();
}
