<?php

class Autoloader
{
	/**
	 * Fix the __autoload function.
	 *
	 * @static
	 * @return bool
	 */
	public static function fixAutoloader()
	{
		$strFile = TL_ROOT . '/system/functions.php';

		if (!is_writable($strFile)) {
			$_SESSION['TL_ERROR'][] = 'Cannot rewrite system/functions.php, please make the file writeable to enable autoloader replacement!';
			return false;
		}

		// Rewrite function __autoload to contao_autoload
		$strBuffer = file_get_contents($strFile);
		$strBuffer = str_replace('function __autoload', "\$strFile = dirname(__FILE__) . '/modules/autoloader/Autoloader.php';

if (file_exists(\$strFile)) {
    include(\$strFile);

    // Register the custom autoloader
    spl_autoload_register(array('Autoloader', 'autoload'));
}
else {
	spl_autoload_register('contao_autoload');
}

function contao_autoload", $strBuffer);
		file_put_contents($strFile, $strBuffer);

		$strLocation = Environment::getInstance()->url . Environment::getInstance()->requestUri;

		// Ajax request
		if (Environment::getInstance()->isAjaxRequest) {
			echo $strLocation;
			exit;
		}

		if (headers_sent()) {
			exit;
		}

		header('Location: ' . $strLocation);
		exit;
	}

	/**
	 * Registered namespaces.
	 */
	protected static $arrNamespaces = array();

	public static function registerNamespace($strNamespace, $strPath, $blnSubdir = false)
	{
		if (!preg_match('#\\\\$', $strNamespace)) {
			$strNamespace .= '\\';
		}
		// prepare the namespace for preg_match
		$strRgxp = preg_quote($strNamespace);
		$strRgxp = '#^' . $strRgxp . '#iS';

		self::$arrNamespaces[$strNamespace]            = new stdClass();
		self::$arrNamespaces[$strNamespace]->namespace = $strNamespace;
		self::$arrNamespaces[$strNamespace]->rgxp      = $strRgxp;
		self::$arrNamespaces[$strNamespace]->path      = $strPath;
		self::$arrNamespaces[$strNamespace]->subdir    = $blnSubdir;
	}

	/**
	 * Autoload the given class.
	 *
	 * @static
	 *
	 * @param string $strClassName
	 *
	 * @return bool
	 */
	public static function autoload($strClassName)
	{
		var_dump($strClassName);
		exit;
		$objCache = FileCache::getInstance('autoload');

		// Try to load the class name from the session cache
		if (!$GLOBALS['TL_CONFIG']['debugMode'] && isset($objCache->$strClassName)) {
			if (@include_once(TL_ROOT . '/' . $objCache->$strClassName)) {
				return; // The class could be loaded
			}
			else {
				unset($objCache->$strClassName); // The class has been removed
			}
		}

		// load the contao library
		if (self::autoloadLibrary($strClassName, $objCache)) {
			return true;
		}

		// load the contao way
		if (self::autoloadModules($strClassName, $objCache)) {
			return true;
		}

		// load namespaced class
		if (strpos($strClassName, '\\') !== false &&
			self::autoloadNamespacedClass($strClassName, $objCache)
		) {
			return true;
		}

		// load via swift
		if (self::autoloadSwift($strClassName, $objCache)) {
			return true;
		}

		// load via dompdf
		if (self::autoloadDomPDF($strClassName, $objCache)) {
			return true;
		}

		return false;
	}

	/**
	 * Autoload a class from the system library.
	 *
	 * @static
	 *
	 * @param $strClassName
	 * @param $objCache
	 *
	 * @return bool
	 */
	protected static function autoloadLibrary($strClassName, $objCache)
	{
		$strLibrary = TL_ROOT . '/system/libraries/' . $strClassName . '.php';

		// Check for libraries first
		if (file_exists($strLibrary)) {
			include_once($strLibrary);
			$objCache->$strClassName = 'system/libraries/' . $strClassName . '.php';
			return true;
		}

		return false;
	}

	/**
	 * Autoload a class from the modules directories.
	 *
	 * @static
	 *
	 * @param $strClassName
	 * @param $objCache
	 *
	 * @return bool
	 */
	protected static function autoloadModules($strClassName, $objCache)
	{
		// Then check the modules folder
		foreach (scan(TL_ROOT . '/system/modules/') as $strFolder) {
			if (substr($strFolder, 0, 1) == '.') {
				continue;
			}

			$strModule = TL_ROOT . '/system/modules/' . $strFolder . '/' . $strClassName . '.php';

			if (file_exists($strModule)) {
				include_once($strModule);
				$objCache->$strClassName = 'system/modules/' . $strFolder . '/' . $strClassName . '.php';
				return true;
			}
		}

		return false;
	}

	/**
	 * Autoload a namespaced class.
	 *
	 * @static
	 *
	 * @param $strClassName
	 * @param $objCache
	 *
	 * @return bool
	 */
	public static function autoloadNamespacedClass($strClassName, $objCache)
	{
		foreach (self::$arrNamespaces as $strNamespace => $objConfig) {
			if (preg_match($objConfig->rgxp, $strClassName)) {
				$strClassFile = $objConfig->subdir ? preg_replace($objConfig->rgxp, '', $strClassName) : $strClassName;
				$strClassFile = $objConfig->path . '/' . str_replace('\\', '/', $strClassFile) . '.php';

				if (file_exists(TL_ROOT . '/' . $strClassFile)) {
					include_once($strClassFile);
					$objCache->$strClassName = $strClassFile;
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Autoload a swift class.
	 *
	 * @static
	 *
	 * @param $strClassName
	 * @param $objCache
	 *
	 * @return bool
	 */
	public static function autoloadSwift($strClassName, $objCache)
	{
		// HOOK: include Swift classes
		if (class_exists('Swift', false)) {
			Swift::autoload($strClassName);
			return class_exists($strClassName, false);
		}
		return false;
	}

	/**
	 * Autoload a DomPDF class.
	 *
	 * @static
	 *
	 * @param $strClassName
	 * @param $objCache
	 *
	 * @return bool
	 */
	public static function autoloadDomPDF($strClassName, $objCache)
	{
		// HOOK: include DOMPDF classes
		if (function_exists('DOMPDF_autoload')) {
			DOMPDF_autoload($strClassName);
			return class_exists($strClassName, false);
		}
		return false;
	}
}
