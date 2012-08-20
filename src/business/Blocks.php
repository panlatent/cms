<?php
namespace Blocks;

require_once dirname(__FILE__).'/../blocks_info.php';

/**
 *
*/
class Blocks extends \Yii
{
	private static $_storedBlocksInfo;

	/**
	 * Returns the Blocks version number, as defined by the BLOCKS_VERSION constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getVersion()
	{
		return BLOCKS_VERSION;
	}

	/**
	 * Returns the Blocks version number, as defined in the blx_info table.
	 * @static
	 * @return string
	 */
	public static function getStoredVersion()
	{
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->version : null;
	}

	/**
	 * Returns the Blocks build number, as defined by the BLOCKS_BUILD constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getBuild()
	{
		return BLOCKS_BUILD;
	}

	/**
	 *
	 * Returns the Blocks build number, as defined in the blx_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredBuild()
	{
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->build : null;
	}

	/**
	 * Returns the Blocks release date, as defined by the BLOCKS_RELEASE_DATE constant.
	 *
	 * @static
	 * @return string
	 */
	public static function getReleaseDate()
	{
		return BLOCKS_RELEASE_DATE;
	}

	/**
	 * Returns the Blocks relesae date, as defined in the blx_info table.
	 *
	 * @static
	 * @return string
	 */
	public static function getStoredReleaseDate()
	{
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->release_date : null;
	}

	/**
	 * Returns the site name.
	 *
	 * @static
	 * @return string
	 */
	public static function getSiteName()
	{
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->site_name : null;
	}

	/**
	 * Returns the site URL.
	 *
	 * @static
	 * @return string
	 */
	public static function getSiteUrl()
	{
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->site_url : null;
	}

	/**
	 * Returns the license key.
	 *
	 * @static
	 * @return string
	 */
	public static function getLicenseKey()
	{
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->license_key : null;
	}

	/**
	 * Returns whether the system is on.
	 *
	 * @static
	 * @return bool
	 */
	public static function isSystemOn()
	{
		$storedBlocksInfo = self::_getStoredInfo();
		return $storedBlocksInfo ? $storedBlocksInfo->on == 1 : false;
	}

	/**
	 * Turns the system on.
	 *
	 * @static
	 * @return bool
	 */
	public static function turnSystemOn()
	{
		// Don't use the the static property $_storedBlocksInfo.  We want the latest info possible.
		$blocksInfo = Info::model()->find();

		if ($blocksInfo)
		{
			$blocksInfo->on = true;
			if ($blocksInfo->save())
				return true;
		}

		return false;
	}

	/**
	 * Turns the system off.
	 *
	 * @static
	 * @return bool
	 */
	public static function turnSystemOff()
	{
		// Don't use the the static property $_storedBlocksInfo.  We want the latest info possible.
		$blocksInfo = Info::model()->find();

		if ($blocksInfo)
		{
			$blocksInfo->on = false;
			if ($blocksInfo->save())
				return true;
		}

		return false;
	}

	/**
	 * Return the saved stored blocks info.  If it's not set, get it from the database and return it.
	 *
	 * @static
	 * @return Info
	 */
	private static function _getStoredInfo()
	{
		if (!isset(static::$_storedBlocksInfo))
		{
			if (blx()->getIsInstalled())
				self::$_storedBlocksInfo = Info::model()->find();
			else
				self::$_storedBlocksInfo = false;
		}

		return self::$_storedBlocksInfo;
	}

	/**
	 * Returns the Yii framework version.
	 *
	 * @static
	 * @return mixed
	 */
	public static function getYiiVersion()
	{
		return parent::getVersion();
	}

	/**
	 * @static
	 * @param $target
	 * @return string
	 */
	public static function dump($target)
	{
		\CVarDumper::dump($target, 10, true);
	}

	/**
	 * @static
	 * @param      $alias
	 * @param bool $forceInclude
	 * @return string|void
	 */
	public static function import($alias, $forceInclude = false)
	{
		$segs = explode('.', $alias);
		if (isset($segs[0]))
		{
			switch ($segs[0])
			{
				case 'app':
				{
					$rootPath = BLOCKS_APP_PATH;
					break;
				}
				case 'config':
				{
					$rootPath = BLOCKS_CONFIG_PATH;
					break;
				}
				case 'plugins':
				{
					$rootPath = BLOCKS_PLUGINS_PATH;
					break;
				}
				case 'runtime':
				{
					$rootPath = BLOCKS_RUNTIME_PATH;
					break;
				}
				case 'templates':
				{
					$rootPath = BLOCKS_TEMPLATES_PATH;
					break;
				}
				default:
				{
					$rootPath = BLOCKS_APP_PATH;
				}
			}
		}
		else
		{
			$rootPath = BLOCKS_APP_PATH;
		}

		$path = $rootPath.implode('/', array_slice($segs, 1));

		$directory = (substr($path, -2) == '/*');
		if ($directory)
		{
			$path = substr($path, 0, -1);
			$files = glob($path."*.php");
			if (is_array($files) && count($files) > 0)
			{
				foreach ($files as $file)
				{
					self::_importFile(realpath($file));
				}
			}
		}
		else
		{
			$file = $path.'.php';
			self::_importFile($file);

			if ($forceInclude)
				require_once $file;
		}
	}

	/**
	 * @static
	 * @param string $category
	 * @param        $msgKey
	 * @param array  $data
	 */
	public static function logActivity($category, $msgKey, $data = array())
	{
		$encodedData = Json::encode($data);

		if (($currentUser = blx()->users->getCurrentUser()) !== null)
			$userId = $currentUser->id;
		else
			$userId = null;

		$logger = self::getLogger();
		$logger->log($userId.'///'.$msgKey.'///'.$encodedData, 'activity', $category);
	}

	/**
	 * @static
	 *
	 * @param string $category
	 * @param string $message
	 * @param array  $params
	 * @param null   $source
	 * @param null   $language
	 *
	 * @return string|void
	 */
	public static function t($message, $params = array(), $category = 'blocks', $source = null, $language = null)
	{
		// Normalize the param keys
		$normalizedParams = array();
		foreach ($params as $key => $value)
		{
			$key = '{'.trim($key, '{}').'}';
			$normalizedParams[$key] = $value;
		}

		return '%'.parent::t($category, $message, $normalizedParams, $source, $language).'%';
	}

	/**
	 * @static
	 * @param $file
	 */
	private static function _importFile($file)
	{
		$class = __NAMESPACE__.'\\'.pathinfo($file, PATHINFO_FILENAME);
		\Yii::$classMap[$class] = $file;
	}
}

/**
 * Returns the current blx() instance.  This is a wrapper function for the Blocks::app() instance.
 * @return App
 */
function blx()
{
	return Blocks::app();
}
