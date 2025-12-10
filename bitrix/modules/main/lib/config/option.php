<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2024 Bitrix
 */

namespace Bitrix\Main\Config;

use Bitrix\Main;

class Option
{
	protected const CACHE_DIR = "b_option";

	protected static $options = [];
	protected static $loading = [];

	/**
	 * Returns a value of an option.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param string $default The default value to return, if a value doesn't exist.
	 * @param bool|string $siteId The site ID, if the option differs for sites.
	 * @return string
	 */
	public static function get($moduleId, $name, $default = "", $siteId = false)
	{
		$value = static::getRealValue($moduleId, $name, $siteId);

		if ($value !== null)
		{
			return $value;
		}

		if (isset(self::$options[$moduleId]["-"][$name]))
		{
			return self::$options[$moduleId]["-"][$name];
		}

		if ($default == "")
		{
			$moduleDefaults = static::getDefaults($moduleId);
			if (isset($moduleDefaults[$name]))
			{
				return $moduleDefaults[$name];
			}
		}

		return $default;
	}

	/**
	 * Returns the real value of an option as it's written in a DB.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param bool|string $siteId The site ID.
	 * @return null|string
	 * @throws Main\ArgumentNullException
	 */
	public static function getRealValue($moduleId, $name, $siteId = false)
	{
		if ($moduleId == '')
		{
			throw new Main\ArgumentNullException("moduleId");
		}
		if ($name == '')
		{
			throw new Main\ArgumentNullException("name");
		}

		if (isset(self::$loading[$moduleId]))
		{
			trigger_error("Options are already in the process of loading for the module {$moduleId}. Default value will be used for the option {$name}.", E_USER_WARNING);
		}

		if (!isset(self::$options[$moduleId]))
		{
			static::load($moduleId);
		}

		if ($siteId === false)
		{
			$siteId = static::getDefaultSite();
		}

		$siteKey = ($siteId == ""? "-" : $siteId);

		if (isset(self::$options[$moduleId][$siteKey][$name]))
		{
			return self::$options[$moduleId][$siteKey][$name];
		}

		return null;
	}

	/**
	 * Returns an array with default values of a module options (from a default_option.php file).
	 *
	 * @param string $moduleId The module ID.
	 * @return array
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function getDefaults($moduleId)
	{
		static $defaultsCache = [];

		if (isset($defaultsCache[$moduleId]))
		{
			return $defaultsCache[$moduleId];
		}

		if (preg_match("#[^a-zA-Z0-9._]#", $moduleId))
		{
			throw new Main\ArgumentOutOfRangeException("moduleId");
		}

		$path = Main\Loader::getLocal("modules/".$moduleId."/default_option.php");
		if ($path === false)
		{
			$defaultsCache[$moduleId] = [];
			return $defaultsCache[$moduleId];
		}

		include($path);

		$varName = str_replace(".", "_", $moduleId)."_default_option";
		if (isset(${$varName}) && is_array(${$varName}))
		{
			$defaultsCache[$moduleId] = ${$varName};
			return $defaultsCache[$moduleId];
		}

		$defaultsCache[$moduleId] = [];
		return $defaultsCache[$moduleId];
	}

	/**
	 * Returns an array of set options array(name => value).
	 *
	 * @param string $moduleId The module ID.
	 * @param bool|string $siteId The site ID, if the option differs for sites.
	 * @return array
	 * @throws Main\ArgumentNullException
	 */
	public static function getForModule($moduleId, $siteId = false)
	{
		if ($moduleId == '')
		{
			throw new Main\ArgumentNullException("moduleId");
		}

		if (!isset(self::$options[$moduleId]))
		{
			static::load($moduleId);
		}

		if ($siteId === false)
		{
			$siteId = static::getDefaultSite();
		}

		$result = self::$options[$moduleId]["-"];

		if($siteId <> "" && !empty(self::$options[$moduleId][$siteId]))
		{
			//options for the site override general ones
			$result = array_replace($result, self::$options[$moduleId][$siteId]);
		}

		return $result;
	}

	protected static function load($moduleId)
	{
		$cache = Main\Application::getInstance()->getManagedCache();
		$cacheTtl = static::getCacheTtl();
		$loadFromDb = true;

		if ($cacheTtl !== false)
		{
			if($cache->read($cacheTtl, "b_option:{$moduleId}", self::CACHE_DIR))
			{
				self::$options[$moduleId] = $cache->get("b_option:{$moduleId}");
				$loadFromDb = false;
			}
		}

		if($loadFromDb)
		{
			self::$loading[$moduleId] = true;

			$con = Main\Application::getConnection();
			$sqlHelper = $con->getSqlHelper();

			// prevents recursion and cache miss
			self::$options[$moduleId] = ["-" => []];

			// prevents recursion on early stages with cluster module installed
			$pool = Main\Application::getInstance()->getConnectionPool();
			$pool->useMasterOnly(true);

			$query = "
				SELECT NAME, VALUE
				FROM b_option
				WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}'
			";

			$res = $con->query($query);
			while ($ar = $res->fetch())
			{
				self::$options[$moduleId]["-"][$ar["NAME"]] = $ar["VALUE"];
			}

			try
			{
				//b_option_site possibly doesn't exist

				$query = "
					SELECT SITE_ID, NAME, VALUE
					FROM b_option_site
					WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}'
				";

				$res = $con->query($query);
				while ($ar = $res->fetch())
				{
					self::$options[$moduleId][$ar["SITE_ID"]][$ar["NAME"]] = $ar["VALUE"];
				}
			}
			catch(Main\DB\SqlQueryException)
			{
			}

			$pool->useMasterOnly(false);

			if($cacheTtl !== false)
			{
				$cache->setImmediate("b_option:{$moduleId}", self::$options[$moduleId]);
			}

			unset(self::$loading[$moduleId]);
		}

		/*ZDUyZmZMzZiN2ZiNTA0NzQ1YjA4YjlkZTZjOTVkNTMyZTg0NzE=*/$GLOBALS['____1223469330']= array(base64_decode('ZXh'.'wb'.'G9kZQ=='),base64_decode('cGF'.'j'.'aw=='),base64_decode('bWQ1'),base64_decode('Y29'.'uc3Rhb'.'n'.'Q='),base64_decode('aGFzaF9'.'obW'.'F'.'j'),base64_decode('c3RyY21w'),base64_decode('aX'.'Nfb2JqZWN0'),base64_decode('Y'.'2FsbF91c2VyX2Z1b'.'m'.'M='),base64_decode('Y2FsbF9'.'1c2VyX'.'2Z'.'1b'.'mM='),base64_decode('Y2FsbF91c'.'2VyX2'.'Z'.'1bmM'.'='),base64_decode('Y'.'2Fsb'.'F91c2Vy'.'X2Z1bm'.'M'.'='));if(!function_exists(__NAMESPACE__.'\\___1426618755')){function ___1426618755($_2086674420){static $_671750024= false; if($_671750024 == false) $_671750024=array('b'.'WFpbg'.'==','bWFpbg'.'='.'=','LQ==','f'.'l'.'BBU'.'kFN'.'X01B'.'WF9VU0VSUw==','bWFpbg='.'=','LQ'.'='.'=','flBBUkF'.'NX01B'.'WF9VU0VSUw='.'=','Lg'.'='.'=','SCo=',''.'Ym'.'l0cml4','T'.'El'.'DR'.'U5TRV'.'9LRV'.'k=',''.'c2'.'hhMj'.'U2','bWFpbg==',''.'LQ==','UEF'.'SQU1'.'fTUFYX'.'1VTRVJT','VV'.'NFUg='.'=','VVNFUg==','V'.'VN'.'FU'.'g==',''.'SXNBd'.'XRob3Jp'.'em'.'Vk','VVNFUg'.'==','SXNB'.'ZG1'.'p'.'bg'.'==','QVBQTElDQVRJT0'.'4=','UmVzdGFydEJ'.'1ZmZlc'.'g'.'='.'=','TG'.'9jYWxSZWR'.'pcmVj'.'d'.'A==','L2x'.'pY2Vuc2Vf'.'cmVzdHJpY3Rpb'.'24u'.'c'.'Ghw','b'.'WF'.'p'.'bg==','L'.'Q==','UEFSQU1fTUFYX1'.'VTRVJT','bWF'.'pb'.'g==','L'.'Q'.'==','UEFSQU1fT'.'UFY'.'X1VTRV'.'JT');return base64_decode($_671750024[$_2086674420]);}};if($moduleId === ___1426618755(0)){ if(isset(self::$options[___1426618755(1)][___1426618755(2)][___1426618755(3)])){ $_795310221= self::$options[___1426618755(4)][___1426618755(5)][___1426618755(6)]; list($_1177243131, $_278079593)= $GLOBALS['____1223469330'][0](___1426618755(7), $_795310221); $_1400430320= $GLOBALS['____1223469330'][1](___1426618755(8), $_1177243131); $_2133736713= ___1426618755(9).$GLOBALS['____1223469330'][2]($GLOBALS['____1223469330'][3](___1426618755(10))); $_1762880080= $GLOBALS['____1223469330'][4](___1426618755(11), $_278079593, $_2133736713, true); if($GLOBALS['____1223469330'][5]($_1762880080, $_1400430320) !==(1020/2-510)){ self::$options[___1426618755(12)][___1426618755(13)][___1426618755(14)]= round(0+3+3+3+3); if(isset($GLOBALS[___1426618755(15)]) && $GLOBALS['____1223469330'][6]($GLOBALS[___1426618755(16)]) && $GLOBALS['____1223469330'][7](array($GLOBALS[___1426618755(17)], ___1426618755(18))) &&!$GLOBALS['____1223469330'][8](array($GLOBALS[___1426618755(19)], ___1426618755(20)))){ $GLOBALS['____1223469330'][9](array($GLOBALS[___1426618755(21)], ___1426618755(22))); $GLOBALS['____1223469330'][10](___1426618755(23), ___1426618755(24), true);}} else{ self::$options[___1426618755(25)][___1426618755(26)][___1426618755(27)]= $_278079593;}} else{ self::$options[___1426618755(28)][___1426618755(29)][___1426618755(30)]= round(0+12);}}/**/
	}

	/**
	 * Sets an option value and saves it into a DB. After saving the OnAfterSetOption event is triggered.
	 *
	 * @param string $moduleId The module ID.
	 * @param string $name The option name.
	 * @param string $value The option value.
	 * @param string $siteId The site ID, if the option depends on a site.
	 * @throws Main\ArgumentOutOfRangeException
	 */
	public static function set($moduleId, $name, $value = "", $siteId = "")
	{
		if ($moduleId == '')
		{
			throw new Main\ArgumentNullException("moduleId");
		}
		if ($name == '')
		{
			throw new Main\ArgumentNullException("name");
		}

		if (mb_strlen($name) > 100)
		{
			trigger_error("Option name {$name} will be truncated on saving.", E_USER_WARNING);
		}

		if ($siteId === false)
		{
			$siteId = static::getDefaultSite();
		}

		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$updateFields = [
			"VALUE" => $value,
		];

		if($siteId == "")
		{
			$insertFields = [
				"MODULE_ID" => $moduleId,
				"NAME" => $name,
				"VALUE" => $value,
			];

			$keyFields = ["MODULE_ID", "NAME"];

			$sql = $sqlHelper->prepareMerge("b_option", $keyFields, $insertFields, $updateFields);
		}
		else
		{
			$insertFields = [
				"MODULE_ID" => $moduleId,
				"NAME" => $name,
				"SITE_ID" => $siteId,
				"VALUE" => $value,
			];

			$keyFields = ["MODULE_ID", "NAME", "SITE_ID"];

			$sql = $sqlHelper->prepareMerge("b_option_site", $keyFields, $insertFields, $updateFields);
		}

		$con->queryExecute(current($sql));

		static::clearCache($moduleId);

		static::loadTriggers($moduleId);

		$event = new Main\Event(
			"main",
			"OnAfterSetOption_".$name,
			array("value" => $value)
		);
		$event->send();

		$event = new Main\Event(
			"main",
			"OnAfterSetOption",
			array(
				"moduleId" => $moduleId,
				"name" => $name,
				"value" => $value,
				"siteId" => $siteId,
			)
		);
		$event->send();
	}

	protected static function loadTriggers($moduleId)
	{
		static $triggersCache = [];

		if (isset($triggersCache[$moduleId]))
		{
			return;
		}

		if (preg_match("#[^a-zA-Z0-9._]#", $moduleId))
		{
			throw new Main\ArgumentOutOfRangeException("moduleId");
		}

		$triggersCache[$moduleId] = true;

		$path = Main\Loader::getLocal("modules/".$moduleId."/option_triggers.php");
		if ($path === false)
		{
			return;
		}

		include($path);
	}

	protected static function getCacheTtl()
	{
		static $cacheTtl = null;

		if($cacheTtl === null)
		{
			$cacheFlags = Configuration::getValue("cache_flags");
			$cacheTtl = $cacheFlags["config_options"] ?? 3600;
		}
		return $cacheTtl;
	}

	/**
	 * Deletes options from a DB.
	 *
	 * @param string $moduleId The module ID.
	 * @param array $filter {name: string, site_id: string} The array with filter keys:
	 * 		name - the name of the option;
	 * 		site_id - the site ID (can be empty).
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentException
	 */
	public static function delete($moduleId, array $filter = array())
	{
		if ($moduleId == '')
		{
			throw new Main\ArgumentNullException("moduleId");
		}

		$con = Main\Application::getConnection();
		$sqlHelper = $con->getSqlHelper();

		$deleteForSites = true;
		$sqlWhere = '';
		$sqlWhereSite = '';

		foreach ($filter as $field => $value)
		{
			switch ($field)
			{
				case "name":
					if ($value == '')
					{
						throw new Main\ArgumentNullException("filter[name]");
					}
					$sqlWhere .= " AND NAME = '{$sqlHelper->forSql($value)}'";
					break;

				case "site_id":
					if ($value != '')
					{
						$sqlWhereSite = " AND SITE_ID = '{$sqlHelper->forSql($value, 2)}'";
					}
					else
					{
						$deleteForSites = false;
					}
					break;

				default:
					throw new Main\ArgumentException("filter[{$field}]");
			}
		}

		if($moduleId == 'main')
		{
			$sqlWhere .= "
				AND NAME NOT LIKE '~%'
				AND NAME NOT IN ('crc_code', 'admin_passwordh', 'server_uniq_id','PARAM_MAX_SITES', 'PARAM_MAX_USERS')
			";
		}
		else
		{
			$sqlWhere .= " AND NAME <> '~bsm_stop_date'";
		}

		if($sqlWhereSite == '')
		{
			$con->queryExecute("
				DELETE FROM b_option
				WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}'
					{$sqlWhere}
			");
		}

		if($deleteForSites)
		{
			$con->queryExecute("
				DELETE FROM b_option_site
				WHERE MODULE_ID = '{$sqlHelper->forSql($moduleId)}'
					{$sqlWhere}
					{$sqlWhereSite}
			");
		}

		static::clearCache($moduleId);
	}

	protected static function clearCache($moduleId)
	{
		unset(self::$options[$moduleId]);

		if (static::getCacheTtl() !== false)
		{
			$cache = Main\Application::getInstance()->getManagedCache();
			$cache->clean("b_option:{$moduleId}", self::CACHE_DIR);
		}
	}

	protected static function getDefaultSite()
	{
		static $defaultSite;

		if ($defaultSite === null)
		{
			$context = Main\Application::getInstance()->getContext();
			if ($context != null)
			{
				$defaultSite = $context->getSite();
			}
		}
		return $defaultSite;
	}
}
