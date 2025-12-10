<?php

/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2024 Bitrix
 */

use Bitrix\Main;
use Bitrix\Main\Session\Legacy\HealerEarlySessionStart;
use Bitrix\Main\DI\ServiceLocator;

require_once __DIR__ . "/start.php";

$application = Main\HttpApplication::getInstance();
$application->initializeExtendedKernel([
	"get" => $_GET,
	"post" => $_POST,
	"files" => $_FILES,
	"cookie" => $_COOKIE,
	"server" => $_SERVER,
	"env" => $_ENV
]);

if (class_exists('\Dev\Main\Migrator\ModuleUpdater'))
{
	\Dev\Main\Migrator\ModuleUpdater::checkUpdates('main', __DIR__);
}

if (!Main\ModuleManager::isModuleInstalled('bitrix24'))
{
	// wwall rules
	(new Main\Security\W\WWall)->handle();

	$application->addBackgroundJob([
		Main\Security\W\WWall::class, 'refreshRules'
	]);

	// vendor security notifications
	$application->addBackgroundJob([
		Main\Security\Notifications\VendorNotifier::class, 'refreshNotifications'
	]);
}

if (defined('SITE_ID'))
{
	define('LANG', SITE_ID);
}

$context = $application->getContext();
$context->initializeCulture(defined('LANG') ? LANG : null, defined('LANGUAGE_ID') ? LANGUAGE_ID : null);

// needs to be after culture initialization
$application->start();

// Register main's services
ServiceLocator::getInstance()->registerByModuleSettings('main');

// constants for compatibility
$culture = $context->getCulture();
define('SITE_CHARSET', $culture->getCharset());
define('FORMAT_DATE', $culture->getFormatDate());
define('FORMAT_DATETIME', $culture->getFormatDatetime());
define('LANG_CHARSET', SITE_CHARSET);

$site = $context->getSiteObject();
if (!defined('LANG'))
{
	define('LANG', ($site ? $site->getLid() : $context->getLanguage()));
}
define('SITE_DIR', ($site ? $site->getDir() : ''));
if (!defined('SITE_SERVER_NAME'))
{
	define('SITE_SERVER_NAME', ($site ? $site->getServerName() : ''));
}
define('LANG_DIR', SITE_DIR);

if (!defined('LANGUAGE_ID'))
{
	define('LANGUAGE_ID', $context->getLanguage());
}
define('LANG_ADMIN_LID', LANGUAGE_ID);

if (!defined('SITE_ID'))
{
	define('SITE_ID', LANG);
}

/** @global $lang */
$lang = $context->getLanguage();

//define global application object
$GLOBALS["APPLICATION"] = new CMain;

if (!defined("POST_FORM_ACTION_URI"))
{
	define("POST_FORM_ACTION_URI", htmlspecialcharsbx(GetRequestUri()));
}

$GLOBALS["MESS"] = [];
$GLOBALS["ALL_LANG_FILES"] = [];
IncludeModuleLangFile(__DIR__."/tools.php");
IncludeModuleLangFile(__FILE__);

error_reporting(COption::GetOptionInt("main", "error_reporting", E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR | E_PARSE) & ~E_DEPRECATED & ~E_WARNING & ~E_NOTICE);

if (!defined("BX_COMP_MANAGED_CACHE") && COption::GetOptionString("main", "component_managed_cache_on", "Y") != "N")
{
	define("BX_COMP_MANAGED_CACHE", true);
}

// global functions
require_once __DIR__ . "/filter_tools.php";

/*ZDUyZmZMzFkMWYyYjViODFkMDQxNjU5MTNmM2RmY2YzNjBlZmU=*/$GLOBALS['_____1787219202']= array(base64_decode('R2V0T'.'W9kdW'.'xl'.'RXZl'.'bnRz'),base64_decode(''.'R'.'X'.'hlY3V0'.'ZU'.'1vZHVsZUV2'.'ZW50'.'R'.'Xg='),base64_decode('V3'.'JpdGVG'.'aW5hbE1lc'.'3N'.'hZ2U'.'='));$GLOBALS['____492626119']= array(base64_decode(''.'ZGVm'.'a'.'W5l'),base64_decode('YmF'.'zZTY0X2'.'RlY29k'.'Z'.'Q='.'='),base64_decode('dW5zZXJpYWxp'.'emU='),base64_decode('aX'.'NfY'.'X'.'JyY'.'Xk='),base64_decode(''.'aW5fYXJy'.'Y'.'X'.'k='),base64_decode('c2Vy'.'aW'.'FsaX'.'pl'),base64_decode('Y'.'mFzZTY0X2VuY29kZQ='.'='),base64_decode('bWt0aW1l'),base64_decode('ZGF0ZQ=='),base64_decode('ZGF0'.'ZQ'.'=='),base64_decode('c'.'3R'.'ybGVu'),base64_decode('b'.'Wt0aW1l'),base64_decode('ZGF0ZQ'.'='.'='),base64_decode('Z'.'GF0ZQ=='),base64_decode('bW'.'V0'.'aG9k'.'X2V4aXN0cw=='),base64_decode('Y'.'2Fsb'.'F'.'91c'.'2VyX2'.'Z'.'1b'.'mNfYXJ'.'yYX'.'k='),base64_decode(''.'c3'.'RybGVu'),base64_decode(''.'c2VyaWFsaX'.'pl'),base64_decode('Ym'.'FzZTY0X2VuY2'.'9kZQ=='),base64_decode('c3'.'Ryb'.'G'.'Vu'),base64_decode(''.'aXNfYXJy'.'YXk='),base64_decode('c2V'.'y'.'aWFs'.'a'.'Xpl'),base64_decode('YmFzZTY0X2'.'VuY29k'.'ZQ=='),base64_decode('c2'.'VyaWFsaXpl'),base64_decode('Ym'.'FzZT'.'Y0X2VuY'.'29'.'kZQ=='),base64_decode('aXNfYXJyYXk'.'='),base64_decode('a'.'XNfY'.'XJy'.'YXk='),base64_decode(''.'a'.'W5fY'.'XJy'.'YXk'.'='),base64_decode(''.'aW5'.'f'.'Y'.'X'.'JyYXk='),base64_decode('bWt'.'0aW'.'1l'),base64_decode('ZGF'.'0'.'ZQ=='),base64_decode('ZGF'.'0ZQ=='),base64_decode('ZGF0ZQ=='),base64_decode('bWt0aW1'.'l'),base64_decode(''.'ZGF0ZQ='.'='),base64_decode(''.'Z'.'GF0ZQ='.'='),base64_decode('a'.'W5fY'.'XJyYX'.'k='),base64_decode('c2VyaWFsaXpl'),base64_decode(''.'Y'.'mFzZTY0X'.'2VuY29'.'kZQ=='),base64_decode(''.'aW50dmFs'),base64_decode('dGlt'.'ZQ'.'=='),base64_decode('ZmlsZV'.'9l'.'eGlzdH'.'M='),base64_decode('c3Ry'.'X'.'3JlcG'.'xhY2U='),base64_decode(''.'Y2'.'xhc3NfZXhpc3Rz'),base64_decode('Z'.'GVmaW5'.'l'),base64_decode('c3RycmV2'),base64_decode(''.'c3R'.'ydG91c'.'HBlc'.'g'.'=='),base64_decode('c3Bya'.'W'.'50Zg='.'='),base64_decode('c3ByaW50Zg='.'='),base64_decode('c3Vic3'.'Ry'),base64_decode('c3RycmV2'),base64_decode('Ym'.'FzZTY0X'.'2Rl'.'Y29kZQ'.'='.'='),base64_decode('c3Vic3Ry'),base64_decode('c3'.'Ry'.'b'.'GV'.'u'),base64_decode('c3RybGVu'),base64_decode('Y'.'2'.'hy'),base64_decode('b3Jk'),base64_decode('b'.'3Jk'),base64_decode('bW'.'t0aW1'.'l'),base64_decode('aW50'.'dmFs'),base64_decode(''.'aW50dmFs'),base64_decode(''.'aW50dmFs'),base64_decode(''.'a3Nvc'.'nQ'.'='),base64_decode(''.'c3Vi'.'c3R'.'y'),base64_decode('aW1wb'.'G9kZQ='.'='),base64_decode('ZG'.'VmaW5l'.'ZA=='),base64_decode('Y'.'mFz'.'ZTY0X2'.'R'.'lY'.'29kZQ=='),base64_decode('Y29'.'uc'.'3Rh'.'bnQ='),base64_decode('c3RycmV2'),base64_decode('c3'.'ByaW5'.'0Zg=='),base64_decode('c3Ryb'.'G'.'Vu'),base64_decode(''.'c3RybGVu'),base64_decode('Y2h'.'y'),base64_decode('b3Jk'),base64_decode('b3J'.'k'),base64_decode(''.'b'.'W'.'t0aW1l'),base64_decode(''.'aW'.'50dmFs'),base64_decode('aW50dmFs'),base64_decode(''.'aW50dmFs'),base64_decode('c3'.'Vic3Ry'),base64_decode('c3Vi'.'c3Ry'),base64_decode('ZGV'.'m'.'aW5lZA=='),base64_decode('c3'.'R'.'yc'.'mV2'),base64_decode('c3'.'Ryd'.'G'.'91'.'cHB'.'lcg'.'=='),base64_decode('dGltZQ=='),base64_decode(''.'b'.'Wt0aW1l'),base64_decode('bWt0aW1l'),base64_decode('ZGF0ZQ=='),base64_decode('ZGF0ZQ=='),base64_decode('ZGVmaW5l'),base64_decode(''.'Z'.'GVma'.'W5l'));if(!function_exists(__NAMESPACE__.'\\___792300230')){function ___792300230($_672472052){static $_842617978= false; if($_842617978 == false) $_842617978=array(''.'S'.'U5UU'.'kFORVRfRURJVElP'.'Tg'.'==','WQ='.'=','bWF'.'pbg==','fmNw'.'Z'.'l9tYXBfdmFsd'.'WU=','','','Y'.'Wxsb3d'.'lZ'.'F9jbGF'.'zc2'.'Vz','Z'.'Q==','Zg==','ZQ'.'==','R'.'g==',''.'WA==',''.'Zg'.'==',''.'bW'.'Fpbg'.'==',''.'fmNwZl9tYXBfdmFsdW'.'U=',''.'UG9ydG'.'Fs','Rg==','ZQ==','ZQ==','WA'.'==','Rg==','RA'.'='.'=',''.'RA==','bQ==','Z'.'A==','WQ='.'=','Zg==','Zg==','Z'.'g==','Z'.'g='.'=','UG9'.'yd'.'G'.'Fs','Rg==','ZQ==','ZQ'.'='.'=','W'.'A==','Rg==','RA==','RA'.'='.'=','bQ'.'==','Z'.'A==','WQ==','bWF'.'pb'.'g='.'=','T2'.'4=','U2V0dGluZ3'.'NDa'.'G'.'FuZ2U=','Zg==',''.'Zg='.'=','Zg==','Zg'.'==',''.'bWF'.'p'.'bg==',''.'fmNwZl9tY'.'XBfd'.'mFsd'.'WU=','ZQ'.'==','ZQ==','R'.'A==',''.'ZQ==','ZQ'.'='.'=','Zg==','Zg='.'=','Zg==','ZQ'.'==',''.'bW'.'Fpb'.'g==','fmNw'.'Zl9'.'tYXBfdmFsdWU'.'=',''.'ZQ==','Zg='.'=','Zg==',''.'Z'.'g='.'=','Zg'.'==',''.'bW'.'Fp'.'bg==','fm'.'Nw'.'Zl9tYX'.'BfdmF'.'sdWU'.'=',''.'ZQ==','Z'.'g==',''.'UG9y'.'dGFs','UG9'.'ydGFs','Z'.'Q==','Z'.'Q==','UG9'.'ydGFs','Rg'.'==','WA==','R'.'g==',''.'RA==','Z'.'Q==','ZQ'.'='.'=','R'.'A'.'==','bQ'.'='.'=','Z'.'A='.'=','W'.'Q'.'==','ZQ==',''.'WA'.'='.'=','ZQ'.'==','Rg='.'=','ZQ==','RA'.'==','Zg==','ZQ'.'='.'=',''.'R'.'A==','ZQ==','bQ==','ZA'.'==','WQ==',''.'Zg==','Zg='.'=','Zg==','Zg==',''.'Zg==','Z'.'g==',''.'Zg'.'='.'=','Zg==',''.'bWFpbg==','fmNw'.'Zl9tYXB'.'fd'.'mF'.'s'.'dW'.'U=','Z'.'Q='.'=','ZQ==','U'.'G'.'9ydGF'.'s',''.'Rg='.'=','WA==','V'.'F'.'lQ'.'RQ==','REFURQ==','RkVBVFVSRVM=','RVhQ'.'SVJ'.'FRA==','V'.'Fl'.'Q'.'RQ==','RA==','VFJZX0R'.'BW'.'VNf'.'Q09VTlQ'.'=','REFU'.'R'.'Q==','VFJZX0RBWVNf'.'Q09VTlQ=','R'.'VhQSV'.'JF'.'R'.'A==','Rk'.'VBVFVSRV'.'M=',''.'Zg==','Zg==',''.'RE9'.'DVU1FTlRf'.'Uk'.'9PVA='.'=','L2Jp'.'d'.'HJpeC9t'.'b2R'.'1'.'bGVzL'.'w==','L2luc3R'.'hbGw'.'v'.'aW5kZ'.'Xgu'.'cGhw',''.'Lg==','Xw==','c2'.'VhcmNo','Tg==','','','QU'.'NUSV'.'ZF',''.'WQ==','c29'.'jaWFsb'.'m'.'V0d2'.'9'.'yaw='.'=','YW'.'x'.'s'.'b3dfZnJpZWxk'.'cw==','WQ'.'==','SU'.'Q=','c'.'29jaWFsb'.'mV0'.'d29yaw==','Y'.'W'.'xsb'.'3df'.'ZnJpZW'.'xk'.'cw==',''.'S'.'UQ=','c29jaW'.'FsbmV0'.'d29yaw==','YW'.'xs'.'b3dfZnJpZWxkc'.'w==','T'.'g==','','',''.'Q'.'UNU'.'SVZF','WQ==','c29ja'.'W'.'F'.'s'.'bmV0'.'d29ya'.'w==','YWxsb3df'.'b'.'Wljcm9ibG9nX3VzZXI=','WQ==','SUQ=','c29j'.'aWF'.'sbmV0d'.'2'.'9ya'.'w==','YWxsb3dfbWljcm9'.'ibG9nX3V'.'zZXI=','S'.'UQ=','c'.'29jaWFsbmV'.'0d2'.'9yaw='.'=','YWxs'.'b3dfbW'.'l'.'jc'.'m9ibG'.'9nX3VzZXI=','c29jaWFsbmV'.'0d'.'2'.'9y'.'aw==','YWxsb3dfbWljcm9ibG9nX2dyb3Vw','WQ==',''.'SUQ'.'=','c'.'29jaWFsb'.'mV0d29yaw==','YWxs'.'b3'.'d'.'fbW'.'ljcm9'.'ibG'.'9nX2dy'.'b3V'.'w','SUQ=','c29j'.'aWFsbmV0d29yaw='.'=',''.'YWxsb'.'3dfbWlj'.'cm'.'9i'.'bG9'.'nX'.'2dyb3'.'Vw','Tg='.'=','','','QUNU'.'SVZF','WQ==','c29jaW'.'FsbmV'.'0d29yaw==',''.'Y'.'W'.'xsb3d'.'fZmls'.'ZXNfdXNl'.'cg==',''.'WQ==','SUQ=','c29jaWFsbm'.'V0'.'d29yaw==','YWxsb3'.'dfZmlsZX'.'Nf'.'dX'.'N'.'lcg==','SUQ=','c'.'2'.'9jaWF'.'sbm'.'V0d29y'.'aw'.'==','YWxs'.'b3dfZ'.'ml'.'sZX'.'Nfd'.'X'.'Nl'.'cg==',''.'Tg==','','','QUNUSV'.'ZF','WQ==','c29jaW'.'FsbmV0d29y'.'aw==','Y'.'Wx'.'s'.'b'.'3d'.'f'.'Ymx'.'vZ19'.'1c2'.'Vy','WQ='.'=','SUQ'.'=','c2'.'9jaWFsb'.'mV'.'0d2'.'9yaw==',''.'Y'.'Wxsb3dfYmxvZ19'.'1'.'c2'.'Vy','SUQ=','c29jaWFsbmV0'.'d29y'.'aw'.'==','YWxsb3d'.'fYmxvZ191c2Vy','Tg==','','',''.'QUN'.'USVZF','WQ'.'='.'=',''.'c29j'.'aWFs'.'bmV0d29y'.'aw==','YWxsb3df'.'cG'.'hvd'.'G9f'.'dX'.'Nlcg==','WQ==','SUQ=',''.'c29j'.'aWFsbmV0d29y'.'aw='.'=',''.'Y'.'Wxsb'.'3dfcG'.'hvd'.'G9fdXNl'.'cg==','SU'.'Q=','c'.'29ja'.'WFsbmV0d'.'29ya'.'w==','YW'.'x'.'sb3dfcGhvdG9fdXNlcg==','T'.'g'.'==','','','QUNU'.'S'.'VZF','WQ==','c'.'29jaWFsbmV'.'0d29'.'y'.'aw='.'=','YWxsb3dfZ'.'m9yd'.'W1fd'.'XNl'.'cg==','W'.'Q='.'=','S'.'U'.'Q=','c29j'.'aWFsbmV0'.'d29yaw==','YWx'.'sb3df'.'Zm9ydW1f'.'d'.'XNlcg'.'==','SUQ=','c29'.'j'.'a'.'WF'.'sb'.'mV0d'.'29'.'ya'.'w='.'=','YWxsb3df'.'Zm9ydW1fd'.'XNlcg==',''.'Tg==','','','QU'.'NU'.'S'.'VZF',''.'WQ==',''.'c'.'29jaWFsb'.'mV0d29ya'.'w==','YWxsb3df'.'dGFza3N'.'fdXNlcg='.'=','W'.'Q==','SUQ=','c29jaWFs'.'b'.'mV0d29y'.'aw==','YWxsb3dfdGFza3NfdXNlcg==','SUQ=','c'.'2'.'9jaW'.'Fs'.'bmV'.'0d2'.'9ya'.'w==','YWx'.'sb'.'3dfdGF'.'za3Nfd'.'XNlcg'.'==','c'.'29'.'jaW'.'Fs'.'b'.'mV0d29yaw'.'==','YWxs'.'b3dfd'.'GFza3NfZ3Jv'.'dX'.'A=',''.'WQ==','S'.'UQ=','c29'.'jaWFs'.'bmV0d29ya'.'w==','YWxsb3dfdG'.'Fza3NfZ3JvdXA=','SU'.'Q=','c29ja'.'WFs'.'bmV0d29'.'ya'.'w==','YWxsb3'.'dfdGFza'.'3NfZ3Jvd'.'X'.'A=','dG'.'Fza3'.'M'.'=','T'.'g='.'=','','','QUNUSVZF',''.'WQ==','c29jaWF'.'sbmV0d29yaw='.'=','YWxsb3dfY2'.'FsZ'.'W5kYXJfdXNlcg==',''.'WQ='.'=',''.'SUQ=','c29jaWFsbmV'.'0d'.'29yaw'.'==',''.'YWxsb3dfY'.'2'.'FsZW5kY'.'X'.'J'.'fdXNlcg='.'=','SUQ=','c29jaWFsbmV'.'0d29y'.'aw='.'=','Y'.'W'.'xs'.'b3dfY'.'2F'.'sZ'.'W5kY'.'XJfdX'.'Nlcg==','c29'.'jaWFsbmV0d'.'29'.'ya'.'w==','YWxs'.'b3dfY'.'2F'.'s'.'ZW'.'5k'.'YXJfZ3J'.'vd'.'X'.'A=','WQ==',''.'SUQ=',''.'c29'.'jaWF'.'s'.'bmV0d'.'29yaw==','YWxsb3'.'dfY2FsZ'.'W5k'.'YXJfZ3Jvd'.'XA=','SU'.'Q'.'=',''.'c'.'29jaWFsbmV0'.'d29yaw==','YWx'.'s'.'b3dfY2FsZW'.'5kYXJfZ3JvdXA=','Q'.'U'.'NUSVZ'.'F','WQ==','Tg==','Z'.'Xh0cmF'.'u'.'ZX'.'Q=','aW'.'J'.'sb2Nr',''.'T2'.'5BZnRlckl'.'C'.'bG'.'9ja'.'0VsZW1lbnRVc'.'GRh'.'dGU=','a'.'W'.'50cmFuZXQ=','Q'.'0'.'ludHJh'.'bmV0RX'.'Zl'.'bnR'.'IYW5kbGVycw'.'==',''.'U1BSZWdpc3R'.'lclVwZGF0'.'ZWRJ'.'dGVt','Q0ludHJh'.'bmV0U2hh'.'c'.'mVw'.'b'.'2lud'.'D'.'o'.'6QWdlb'.'nRMaXN'.'0'.'cygpOw==','aW50'.'cm'.'FuZXQ'.'=',''.'Tg==','Q0lu'.'dHJh'.'b'.'mV0U'.'2hh'.'cm'.'Vwb2l'.'u'.'dDo6'.'Q'.'W'.'dlbn'.'RRdWV'.'1ZSgp'.'O'.'w==','aW50cmF'.'uZX'.'Q'.'=','Tg==','Q0l'.'u'.'dHJhbm'.'V0U'.'2hh'.'cmVw'.'b'.'2ludDo6QWd'.'lbnR'.'VcGR'.'hdGUoKTs=',''.'aW50cmFuZXQ=',''.'Tg='.'=','aW'.'Jsb2Nr','T25B'.'ZnRlcklCbG9ja0VsZ'.'W'.'1lbnRBZG'.'Q'.'=','aW5'.'0cmFuZXQ=','Q'.'0'.'ludHJhbm'.'V0RXZlbn'.'RI'.'YW5kbGVycw==','U1BSZWdpc3RlclVwZGF0ZW'.'RJdG'.'Vt','aWJ'.'sb2'.'Nr','T2'.'5'.'BZ'.'nRlcklC'.'bG9ja0VsZ'.'W1lbnRV'.'cGRhdGU'.'=','aW50cmFuZ'.'XQ=','Q0ludHJhbmV'.'0RXZ'.'lbnRIYW5kbGV'.'ycw==',''.'U'.'1BS'.'ZW'.'dpc3Rl'.'clVwZGF0ZWR'.'J'.'dG'.'Vt','Q0l'.'udHJhbm'.'V0U'.'2hhcmVw'.'b2'.'lu'.'d'.'Do'.'6QWdlbnRM'.'a'.'XN0cy'.'gpO'.'w==',''.'aW'.'50'.'cmFuZ'.'X'.'Q'.'=','Q0'.'ludHJh'.'b'.'m'.'V0U'.'2'.'hhcmVwb'.'2ludDo'.'6QW'.'dlbn'.'RRdWV'.'1ZSgpOw==','aW50cmFuZ'.'XQ'.'=','Q0lu'.'dHJh'.'bmV0'.'U2hhcmVwb2lu'.'dDo'.'6QWdlbnRVcGRhdGUoKTs=','aW50c'.'m'.'F'.'uZXQ=','Y3Jt','bWFpbg==','T25CZ'.'W'.'Zvc'.'mVQ'.'cm9sb2c=','bWFpb'.'g==','Q1dpem'.'FyZ'.'FNv'.'bF'.'BhbmVs'.'SW50cmFuZ'.'XQ=','U'.'2hvd1Bhbm'.'Vs','L2'.'1vZ'.'HVsZ'.'XMvaW50cmFuZX'.'Qvc'.'GFu'.'ZWxf'.'YnV0dG9uLnBoc'.'A==','ZX'.'hwaXJlX'.'2'.'1'.'lc3My',''.'bm9pdGlkZV90a'.'W1'.'p'.'bGV'.'taXQ=','WQ='.'=','ZH'.'Jpb'.'l9'.'wZXJ'.'nb'.'2'.'tj','J'.'TAx'.'MHMK','RU'.'VYUE'.'lS','bWFpb'.'g==','JX'.'Mlcw'.'==',''.'YW'.'R'.'t','aGRy'.'b'.'3'.'dzc2'.'E=','YW'.'Rta'.'W4=','bW'.'9kdWxlcw'.'==','ZGVmaW5'.'lL'.'nBocA==','bW'.'F'.'pbg==','Yml'.'0cml'.'4','Ukh'.'TSVRFRVg=','SDR1NjdmaHc'.'4N1Z'.'oe'.'XRvcw==','','dGh'.'S','N'.'0h5cjEyS'.'H'.'d5MH'.'JGcg'.'==','VF9TVE'.'V'.'BTA==','aHR0'.'cH'.'M6L'.'y9'.'iaXRyaX'.'hzb2Z0LmNvbS'.'9iaXRyaXgvYnMucGhw',''.'T0'.'xE',''.'UElSR'.'URBVEVT','RE9'.'DVU1FTlRfU'.'k'.'9PVA'.'==',''.'Lw'.'='.'=','L'.'w='.'=','VEV'.'NU'.'E'.'9SQV'.'JZX0'.'NBQ'.'0'.'hF','V'.'EVNUE9S'.'QVJ'.'ZX'.'0NBQ0hF','','T05fT0Q'.'=','JXMlcw==','X'.'09V'.'Ul'.'9C'.'VVM'.'=','U'.'0lU','RUR'.'BVEV'.'NQ'.'VB'.'F'.'U'.'g'.'==',''.'bm9pdGlk'.'ZV90aW'.'1'.'pbGVt'.'aX'.'Q=','bQ==','Z'.'A='.'=','W'.'Q==','U'.'0'.'NS'.'S'.'VBUX'.'05BTU'.'U=','L2J'.'pdHJ'.'peC'.'9jb3Vwb2'.'5'.'f'.'Y'.'WN0'.'aX'.'ZhdGlvbi5'.'waHA=','U0'.'N'.'S'.'S'.'VB'.'UX05B'.'TU'.'U=','L2'.'JpdH'.'JpeC9zZXJ2aWNl'.'cy9tYWluL2'.'FqYXgucGh'.'w','L'.'2JpdHJp'.'eC9j'.'b3'.'Vwb25fYW'.'N0a'.'XZ'.'hdGlvbi'.'5waHA=','U2l'.'0ZUV4'.'cGlyZURhdGU=');return base64_decode($_842617978[$_672472052]);}};$GLOBALS['____492626119'][0](___792300230(0), ___792300230(1));class CBXFeatures{ private static $_636558133= 30; private static $_1426880890= array( "Portal" => array( "CompanyCalendar", "CompanyPhoto", "CompanyVideo", "CompanyCareer", "StaffChanges", "StaffAbsence", "CommonDocuments", "MeetingRoomBookingSystem", "Wiki", "Learning", "Vote", "WebLink", "Subscribe", "Friends", "PersonalFiles", "PersonalBlog", "PersonalPhoto", "PersonalForum", "Blog", "Forum", "Gallery", "Board", "MicroBlog", "WebMessenger",), "Communications" => array( "Tasks", "Calendar", "Workgroups", "Jabber", "VideoConference", "Extranet", "SMTP", "Requests", "DAV", "intranet_sharepoint", "timeman", "Idea", "Meeting", "EventList", "Salary", "XDImport",), "Enterprise" => array( "BizProc", "Lists", "Support", "Analytics", "crm", "Controller", "LdapUnlimitedUsers",), "Holding" => array( "Cluster", "MultiSites",),); private static $_1443862000= null; private static $_2102093319= null; private static function __916047992(){ if(self::$_1443862000 === null){ self::$_1443862000= array(); foreach(self::$_1426880890 as $_1744246489 => $_2050807701){ foreach($_2050807701 as $_1056316376) self::$_1443862000[$_1056316376]= $_1744246489;}} if(self::$_2102093319 === null){ self::$_2102093319= array(); $_2000220103= COption::GetOptionString(___792300230(2), ___792300230(3), ___792300230(4)); if($_2000220103 != ___792300230(5)){ $_2000220103= $GLOBALS['____492626119'][1]($_2000220103); $_2000220103= $GLOBALS['____492626119'][2]($_2000220103,[___792300230(6) => false]); if($GLOBALS['____492626119'][3]($_2000220103)){ self::$_2102093319= $_2000220103;}} if(empty(self::$_2102093319)){ self::$_2102093319= array(___792300230(7) => array(), ___792300230(8) => array());}}} public static function InitiateEditionsSettings($_1457643421){ self::__916047992(); $_1341422353= array(); foreach(self::$_1426880890 as $_1744246489 => $_2050807701){ $_1787568739= $GLOBALS['____492626119'][4]($_1744246489, $_1457643421); self::$_2102093319[___792300230(9)][$_1744246489]=($_1787568739? array(___792300230(10)): array(___792300230(11))); foreach($_2050807701 as $_1056316376){ self::$_2102093319[___792300230(12)][$_1056316376]= $_1787568739; if(!$_1787568739) $_1341422353[]= array($_1056316376, false);}} $_1624261022= $GLOBALS['____492626119'][5](self::$_2102093319); $_1624261022= $GLOBALS['____492626119'][6]($_1624261022); COption::SetOptionString(___792300230(13), ___792300230(14), $_1624261022); foreach($_1341422353 as $_1620300006) self::__1619554079($_1620300006[(1316/2-658)], $_1620300006[round(0+0.25+0.25+0.25+0.25)]);} public static function IsFeatureEnabled($_1056316376){ if($_1056316376 == '') return true; self::__916047992(); if(!isset(self::$_1443862000[$_1056316376])) return true; if(self::$_1443862000[$_1056316376] == ___792300230(15)) $_116253267= array(___792300230(16)); elseif(isset(self::$_2102093319[___792300230(17)][self::$_1443862000[$_1056316376]])) $_116253267= self::$_2102093319[___792300230(18)][self::$_1443862000[$_1056316376]]; else $_116253267= array(___792300230(19)); if($_116253267[min(36,0,12)] != ___792300230(20) && $_116253267[min(218,0,72.666666666667)] != ___792300230(21)){ return false;} elseif($_116253267[(1364/2-682)] == ___792300230(22)){ if($_116253267[round(0+0.33333333333333+0.33333333333333+0.33333333333333)]< $GLOBALS['____492626119'][7]((1100/2-550),(918-2*459),(994-2*497), Date(___792300230(23)), $GLOBALS['____492626119'][8](___792300230(24))- self::$_636558133, $GLOBALS['____492626119'][9](___792300230(25)))){ if(!isset($_116253267[round(0+0.5+0.5+0.5+0.5)]) ||!$_116253267[round(0+2)]) self::__828148821(self::$_1443862000[$_1056316376]); return false;}} return!isset(self::$_2102093319[___792300230(26)][$_1056316376]) || self::$_2102093319[___792300230(27)][$_1056316376];} public static function IsFeatureInstalled($_1056316376){ if($GLOBALS['____492626119'][10]($_1056316376) <= 0) return true; self::__916047992(); return(isset(self::$_2102093319[___792300230(28)][$_1056316376]) && self::$_2102093319[___792300230(29)][$_1056316376]);} public static function IsFeatureEditable($_1056316376){ if($_1056316376 == '') return true; self::__916047992(); if(!isset(self::$_1443862000[$_1056316376])) return true; if(self::$_1443862000[$_1056316376] == ___792300230(30)) $_116253267= array(___792300230(31)); elseif(isset(self::$_2102093319[___792300230(32)][self::$_1443862000[$_1056316376]])) $_116253267= self::$_2102093319[___792300230(33)][self::$_1443862000[$_1056316376]]; else $_116253267= array(___792300230(34)); if($_116253267[(838-2*419)] != ___792300230(35) && $_116253267[(1096/2-548)] != ___792300230(36)){ return false;} elseif($_116253267[(164*2-328)] == ___792300230(37)){ if($_116253267[round(0+0.33333333333333+0.33333333333333+0.33333333333333)]< $GLOBALS['____492626119'][11]((1268/2-634),(768-2*384),(223*2-446), Date(___792300230(38)), $GLOBALS['____492626119'][12](___792300230(39))- self::$_636558133, $GLOBALS['____492626119'][13](___792300230(40)))){ if(!isset($_116253267[round(0+2)]) ||!$_116253267[round(0+0.66666666666667+0.66666666666667+0.66666666666667)]) self::__828148821(self::$_1443862000[$_1056316376]); return false;}} return true;} private static function __1619554079($_1056316376, $_1201873475){ if($GLOBALS['____492626119'][14]("CBXFeatures", "On".$_1056316376."SettingsChange")) $GLOBALS['____492626119'][15](array("CBXFeatures", "On".$_1056316376."SettingsChange"), array($_1056316376, $_1201873475)); $_2008123407= $GLOBALS['_____1787219202'][0](___792300230(41), ___792300230(42).$_1056316376.___792300230(43)); while($_1188778821= $_2008123407->Fetch()) $GLOBALS['_____1787219202'][1]($_1188778821, array($_1056316376, $_1201873475));} public static function SetFeatureEnabled($_1056316376, $_1201873475= true, $_1639297459= true){ if($GLOBALS['____492626119'][16]($_1056316376) <= 0) return; if(!self::IsFeatureEditable($_1056316376)) $_1201873475= false; $_1201873475= (bool)$_1201873475; self::__916047992(); $_217857193=(!isset(self::$_2102093319[___792300230(44)][$_1056316376]) && $_1201873475 || isset(self::$_2102093319[___792300230(45)][$_1056316376]) && $_1201873475 != self::$_2102093319[___792300230(46)][$_1056316376]); self::$_2102093319[___792300230(47)][$_1056316376]= $_1201873475; $_1624261022= $GLOBALS['____492626119'][17](self::$_2102093319); $_1624261022= $GLOBALS['____492626119'][18]($_1624261022); COption::SetOptionString(___792300230(48), ___792300230(49), $_1624261022); if($_217857193 && $_1639297459) self::__1619554079($_1056316376, $_1201873475);} private static function __828148821($_1744246489){ if($GLOBALS['____492626119'][19]($_1744246489) <= 0 || $_1744246489 == "Portal") return; self::__916047992(); if(!isset(self::$_2102093319[___792300230(50)][$_1744246489]) || self::$_2102093319[___792300230(51)][$_1744246489][min(120,0,40)] != ___792300230(52)) return; if(isset(self::$_2102093319[___792300230(53)][$_1744246489][round(0+0.66666666666667+0.66666666666667+0.66666666666667)]) && self::$_2102093319[___792300230(54)][$_1744246489][round(0+1+1)]) return; $_1341422353= array(); if(isset(self::$_1426880890[$_1744246489]) && $GLOBALS['____492626119'][20](self::$_1426880890[$_1744246489])){ foreach(self::$_1426880890[$_1744246489] as $_1056316376){ if(isset(self::$_2102093319[___792300230(55)][$_1056316376]) && self::$_2102093319[___792300230(56)][$_1056316376]){ self::$_2102093319[___792300230(57)][$_1056316376]= false; $_1341422353[]= array($_1056316376, false);}} self::$_2102093319[___792300230(58)][$_1744246489][round(0+0.5+0.5+0.5+0.5)]= true;} $_1624261022= $GLOBALS['____492626119'][21](self::$_2102093319); $_1624261022= $GLOBALS['____492626119'][22]($_1624261022); COption::SetOptionString(___792300230(59), ___792300230(60), $_1624261022); foreach($_1341422353 as $_1620300006) self::__1619554079($_1620300006[min(176,0,58.666666666667)], $_1620300006[round(0+1)]);} public static function ModifyFeaturesSettings($_1457643421, $_2050807701){ self::__916047992(); foreach($_1457643421 as $_1744246489 => $_1327369998) self::$_2102093319[___792300230(61)][$_1744246489]= $_1327369998; $_1341422353= array(); foreach($_2050807701 as $_1056316376 => $_1201873475){ if(!isset(self::$_2102093319[___792300230(62)][$_1056316376]) && $_1201873475 || isset(self::$_2102093319[___792300230(63)][$_1056316376]) && $_1201873475 != self::$_2102093319[___792300230(64)][$_1056316376]) $_1341422353[]= array($_1056316376, $_1201873475); self::$_2102093319[___792300230(65)][$_1056316376]= $_1201873475;} $_1624261022= $GLOBALS['____492626119'][23](self::$_2102093319); $_1624261022= $GLOBALS['____492626119'][24]($_1624261022); COption::SetOptionString(___792300230(66), ___792300230(67), $_1624261022); self::$_2102093319= false; foreach($_1341422353 as $_1620300006) self::__1619554079($_1620300006[(856-2*428)], $_1620300006[round(0+0.33333333333333+0.33333333333333+0.33333333333333)]);} public static function SaveFeaturesSettings($_156043442, $_2075524600){ self::__916047992(); $_1789868031= array(___792300230(68) => array(), ___792300230(69) => array()); if(!$GLOBALS['____492626119'][25]($_156043442)) $_156043442= array(); if(!$GLOBALS['____492626119'][26]($_2075524600)) $_2075524600= array(); if(!$GLOBALS['____492626119'][27](___792300230(70), $_156043442)) $_156043442[]= ___792300230(71); foreach(self::$_1426880890 as $_1744246489 => $_2050807701){ if(isset(self::$_2102093319[___792300230(72)][$_1744246489])){ $_472351811= self::$_2102093319[___792300230(73)][$_1744246489];} else{ $_472351811=($_1744246489 == ___792300230(74)? array(___792300230(75)): array(___792300230(76)));} if($_472351811[(818-2*409)] == ___792300230(77) || $_472351811[(938-2*469)] == ___792300230(78)){ $_1789868031[___792300230(79)][$_1744246489]= $_472351811;} else{ if($GLOBALS['____492626119'][28]($_1744246489, $_156043442)) $_1789868031[___792300230(80)][$_1744246489]= array(___792300230(81), $GLOBALS['____492626119'][29](min(72,0,24), min(220,0,73.333333333333),(1428/2-714), $GLOBALS['____492626119'][30](___792300230(82)), $GLOBALS['____492626119'][31](___792300230(83)), $GLOBALS['____492626119'][32](___792300230(84)))); else $_1789868031[___792300230(85)][$_1744246489]= array(___792300230(86));}} $_1341422353= array(); foreach(self::$_1443862000 as $_1056316376 => $_1744246489){ if($_1789868031[___792300230(87)][$_1744246489][(868-2*434)] != ___792300230(88) && $_1789868031[___792300230(89)][$_1744246489][min(122,0,40.666666666667)] != ___792300230(90)){ $_1789868031[___792300230(91)][$_1056316376]= false;} else{ if($_1789868031[___792300230(92)][$_1744246489][(1180/2-590)] == ___792300230(93) && $_1789868031[___792300230(94)][$_1744246489][round(0+0.2+0.2+0.2+0.2+0.2)]< $GLOBALS['____492626119'][33](min(230,0,76.666666666667),(914-2*457),(164*2-328), Date(___792300230(95)), $GLOBALS['____492626119'][34](___792300230(96))- self::$_636558133, $GLOBALS['____492626119'][35](___792300230(97)))) $_1789868031[___792300230(98)][$_1056316376]= false; else $_1789868031[___792300230(99)][$_1056316376]= $GLOBALS['____492626119'][36]($_1056316376, $_2075524600); if(!isset(self::$_2102093319[___792300230(100)][$_1056316376]) && $_1789868031[___792300230(101)][$_1056316376] || isset(self::$_2102093319[___792300230(102)][$_1056316376]) && $_1789868031[___792300230(103)][$_1056316376] != self::$_2102093319[___792300230(104)][$_1056316376]) $_1341422353[]= array($_1056316376, $_1789868031[___792300230(105)][$_1056316376]);}} $_1624261022= $GLOBALS['____492626119'][37]($_1789868031); $_1624261022= $GLOBALS['____492626119'][38]($_1624261022); COption::SetOptionString(___792300230(106), ___792300230(107), $_1624261022); self::$_2102093319= false; foreach($_1341422353 as $_1620300006) self::__1619554079($_1620300006[(1024/2-512)], $_1620300006[round(0+0.2+0.2+0.2+0.2+0.2)]);} public static function GetFeaturesList(){ self::__916047992(); $_888145719= array(); foreach(self::$_1426880890 as $_1744246489 => $_2050807701){ if(isset(self::$_2102093319[___792300230(108)][$_1744246489])){ $_472351811= self::$_2102093319[___792300230(109)][$_1744246489];} else{ $_472351811=($_1744246489 == ___792300230(110)? array(___792300230(111)): array(___792300230(112)));} $_888145719[$_1744246489]= array( ___792300230(113) => $_472351811[(1392/2-696)], ___792300230(114) => $_472351811[round(0+0.2+0.2+0.2+0.2+0.2)], ___792300230(115) => array(),); $_888145719[$_1744246489][___792300230(116)]= false; if($_888145719[$_1744246489][___792300230(117)] == ___792300230(118)){ $_888145719[$_1744246489][___792300230(119)]= $GLOBALS['____492626119'][39](($GLOBALS['____492626119'][40]()- $_888145719[$_1744246489][___792300230(120)])/ round(0+17280+17280+17280+17280+17280)); if($_888145719[$_1744246489][___792300230(121)]> self::$_636558133) $_888145719[$_1744246489][___792300230(122)]= true;} foreach($_2050807701 as $_1056316376) $_888145719[$_1744246489][___792300230(123)][$_1056316376]=(!isset(self::$_2102093319[___792300230(124)][$_1056316376]) || self::$_2102093319[___792300230(125)][$_1056316376]);} return $_888145719;} private static function __2133070588($_1132984446, $_21522090){ if(IsModuleInstalled($_1132984446) == $_21522090) return true; $_1447654722= $_SERVER[___792300230(126)].___792300230(127).$_1132984446.___792300230(128); if(!$GLOBALS['____492626119'][41]($_1447654722)) return false; include_once($_1447654722); $_1342012542= $GLOBALS['____492626119'][42](___792300230(129), ___792300230(130), $_1132984446); if(!$GLOBALS['____492626119'][43]($_1342012542)) return false; $_1059730254= new $_1342012542; if($_21522090){ if(!$_1059730254->InstallDB()) return false; $_1059730254->InstallEvents(); if(!$_1059730254->InstallFiles()) return false;} else{ if(CModule::IncludeModule(___792300230(131))) CSearch::DeleteIndex($_1132984446); UnRegisterModule($_1132984446);} return true;} protected static function OnRequestsSettingsChange($_1056316376, $_1201873475){ self::__2133070588("form", $_1201873475);} protected static function OnLearningSettingsChange($_1056316376, $_1201873475){ self::__2133070588("learning", $_1201873475);} protected static function OnJabberSettingsChange($_1056316376, $_1201873475){ self::__2133070588("xmpp", $_1201873475);} protected static function OnVideoConferenceSettingsChange($_1056316376, $_1201873475){} protected static function OnBizProcSettingsChange($_1056316376, $_1201873475){ self::__2133070588("bizprocdesigner", $_1201873475);} protected static function OnListsSettingsChange($_1056316376, $_1201873475){ self::__2133070588("lists", $_1201873475);} protected static function OnWikiSettingsChange($_1056316376, $_1201873475){ self::__2133070588("wiki", $_1201873475);} protected static function OnSupportSettingsChange($_1056316376, $_1201873475){ self::__2133070588("support", $_1201873475);} protected static function OnControllerSettingsChange($_1056316376, $_1201873475){ self::__2133070588("controller", $_1201873475);} protected static function OnAnalyticsSettingsChange($_1056316376, $_1201873475){ self::__2133070588("statistic", $_1201873475);} protected static function OnVoteSettingsChange($_1056316376, $_1201873475){ self::__2133070588("vote", $_1201873475);} protected static function OnFriendsSettingsChange($_1056316376, $_1201873475){ if($_1201873475) $_466409794= "Y"; else $_466409794= ___792300230(132); $_1320084710= CSite::GetList(___792300230(133), ___792300230(134), array(___792300230(135) => ___792300230(136))); while($_1616115662= $_1320084710->Fetch()){ if(COption::GetOptionString(___792300230(137), ___792300230(138), ___792300230(139), $_1616115662[___792300230(140)]) != $_466409794){ COption::SetOptionString(___792300230(141), ___792300230(142), $_466409794, false, $_1616115662[___792300230(143)]); COption::SetOptionString(___792300230(144), ___792300230(145), $_466409794);}}} protected static function OnMicroBlogSettingsChange($_1056316376, $_1201873475){ if($_1201873475) $_466409794= "Y"; else $_466409794= ___792300230(146); $_1320084710= CSite::GetList(___792300230(147), ___792300230(148), array(___792300230(149) => ___792300230(150))); while($_1616115662= $_1320084710->Fetch()){ if(COption::GetOptionString(___792300230(151), ___792300230(152), ___792300230(153), $_1616115662[___792300230(154)]) != $_466409794){ COption::SetOptionString(___792300230(155), ___792300230(156), $_466409794, false, $_1616115662[___792300230(157)]); COption::SetOptionString(___792300230(158), ___792300230(159), $_466409794);} if(COption::GetOptionString(___792300230(160), ___792300230(161), ___792300230(162), $_1616115662[___792300230(163)]) != $_466409794){ COption::SetOptionString(___792300230(164), ___792300230(165), $_466409794, false, $_1616115662[___792300230(166)]); COption::SetOptionString(___792300230(167), ___792300230(168), $_466409794);}}} protected static function OnPersonalFilesSettingsChange($_1056316376, $_1201873475){ if($_1201873475) $_466409794= "Y"; else $_466409794= ___792300230(169); $_1320084710= CSite::GetList(___792300230(170), ___792300230(171), array(___792300230(172) => ___792300230(173))); while($_1616115662= $_1320084710->Fetch()){ if(COption::GetOptionString(___792300230(174), ___792300230(175), ___792300230(176), $_1616115662[___792300230(177)]) != $_466409794){ COption::SetOptionString(___792300230(178), ___792300230(179), $_466409794, false, $_1616115662[___792300230(180)]); COption::SetOptionString(___792300230(181), ___792300230(182), $_466409794);}}} protected static function OnPersonalBlogSettingsChange($_1056316376, $_1201873475){ if($_1201873475) $_466409794= "Y"; else $_466409794= ___792300230(183); $_1320084710= CSite::GetList(___792300230(184), ___792300230(185), array(___792300230(186) => ___792300230(187))); while($_1616115662= $_1320084710->Fetch()){ if(COption::GetOptionString(___792300230(188), ___792300230(189), ___792300230(190), $_1616115662[___792300230(191)]) != $_466409794){ COption::SetOptionString(___792300230(192), ___792300230(193), $_466409794, false, $_1616115662[___792300230(194)]); COption::SetOptionString(___792300230(195), ___792300230(196), $_466409794);}}} protected static function OnPersonalPhotoSettingsChange($_1056316376, $_1201873475){ if($_1201873475) $_466409794= "Y"; else $_466409794= ___792300230(197); $_1320084710= CSite::GetList(___792300230(198), ___792300230(199), array(___792300230(200) => ___792300230(201))); while($_1616115662= $_1320084710->Fetch()){ if(COption::GetOptionString(___792300230(202), ___792300230(203), ___792300230(204), $_1616115662[___792300230(205)]) != $_466409794){ COption::SetOptionString(___792300230(206), ___792300230(207), $_466409794, false, $_1616115662[___792300230(208)]); COption::SetOptionString(___792300230(209), ___792300230(210), $_466409794);}}} protected static function OnPersonalForumSettingsChange($_1056316376, $_1201873475){ if($_1201873475) $_466409794= "Y"; else $_466409794= ___792300230(211); $_1320084710= CSite::GetList(___792300230(212), ___792300230(213), array(___792300230(214) => ___792300230(215))); while($_1616115662= $_1320084710->Fetch()){ if(COption::GetOptionString(___792300230(216), ___792300230(217), ___792300230(218), $_1616115662[___792300230(219)]) != $_466409794){ COption::SetOptionString(___792300230(220), ___792300230(221), $_466409794, false, $_1616115662[___792300230(222)]); COption::SetOptionString(___792300230(223), ___792300230(224), $_466409794);}}} protected static function OnTasksSettingsChange($_1056316376, $_1201873475){ if($_1201873475) $_466409794= "Y"; else $_466409794= ___792300230(225); $_1320084710= CSite::GetList(___792300230(226), ___792300230(227), array(___792300230(228) => ___792300230(229))); while($_1616115662= $_1320084710->Fetch()){ if(COption::GetOptionString(___792300230(230), ___792300230(231), ___792300230(232), $_1616115662[___792300230(233)]) != $_466409794){ COption::SetOptionString(___792300230(234), ___792300230(235), $_466409794, false, $_1616115662[___792300230(236)]); COption::SetOptionString(___792300230(237), ___792300230(238), $_466409794);} if(COption::GetOptionString(___792300230(239), ___792300230(240), ___792300230(241), $_1616115662[___792300230(242)]) != $_466409794){ COption::SetOptionString(___792300230(243), ___792300230(244), $_466409794, false, $_1616115662[___792300230(245)]); COption::SetOptionString(___792300230(246), ___792300230(247), $_466409794);}} self::__2133070588(___792300230(248), $_1201873475);} protected static function OnCalendarSettingsChange($_1056316376, $_1201873475){ if($_1201873475) $_466409794= "Y"; else $_466409794= ___792300230(249); $_1320084710= CSite::GetList(___792300230(250), ___792300230(251), array(___792300230(252) => ___792300230(253))); while($_1616115662= $_1320084710->Fetch()){ if(COption::GetOptionString(___792300230(254), ___792300230(255), ___792300230(256), $_1616115662[___792300230(257)]) != $_466409794){ COption::SetOptionString(___792300230(258), ___792300230(259), $_466409794, false, $_1616115662[___792300230(260)]); COption::SetOptionString(___792300230(261), ___792300230(262), $_466409794);} if(COption::GetOptionString(___792300230(263), ___792300230(264), ___792300230(265), $_1616115662[___792300230(266)]) != $_466409794){ COption::SetOptionString(___792300230(267), ___792300230(268), $_466409794, false, $_1616115662[___792300230(269)]); COption::SetOptionString(___792300230(270), ___792300230(271), $_466409794);}}} protected static function OnSMTPSettingsChange($_1056316376, $_1201873475){ self::__2133070588("mail", $_1201873475);} protected static function OnExtranetSettingsChange($_1056316376, $_1201873475){ $_789630963= COption::GetOptionString("extranet", "extranet_site", ""); if($_789630963){ $_768262891= new CSite; $_768262891->Update($_789630963, array(___792300230(272) =>($_1201873475? ___792300230(273): ___792300230(274))));} self::__2133070588(___792300230(275), $_1201873475);} protected static function OnDAVSettingsChange($_1056316376, $_1201873475){ self::__2133070588("dav", $_1201873475);} protected static function OntimemanSettingsChange($_1056316376, $_1201873475){ self::__2133070588("timeman", $_1201873475);} protected static function Onintranet_sharepointSettingsChange($_1056316376, $_1201873475){ if($_1201873475){ RegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", "intranet", "CIntranetEventHandlers", "SPRegisterUpdatedItem"); RegisterModuleDependences(___792300230(276), ___792300230(277), ___792300230(278), ___792300230(279), ___792300230(280)); CAgent::AddAgent(___792300230(281), ___792300230(282), ___792300230(283), round(0+500)); CAgent::AddAgent(___792300230(284), ___792300230(285), ___792300230(286), round(0+150+150)); CAgent::AddAgent(___792300230(287), ___792300230(288), ___792300230(289), round(0+1800+1800));} else{ UnRegisterModuleDependences(___792300230(290), ___792300230(291), ___792300230(292), ___792300230(293), ___792300230(294)); UnRegisterModuleDependences(___792300230(295), ___792300230(296), ___792300230(297), ___792300230(298), ___792300230(299)); CAgent::RemoveAgent(___792300230(300), ___792300230(301)); CAgent::RemoveAgent(___792300230(302), ___792300230(303)); CAgent::RemoveAgent(___792300230(304), ___792300230(305));}} protected static function OncrmSettingsChange($_1056316376, $_1201873475){ if($_1201873475) COption::SetOptionString("crm", "form_features", "Y"); self::__2133070588(___792300230(306), $_1201873475);} protected static function OnClusterSettingsChange($_1056316376, $_1201873475){ self::__2133070588("cluster", $_1201873475);} protected static function OnMultiSitesSettingsChange($_1056316376, $_1201873475){ if($_1201873475) RegisterModuleDependences("main", "OnBeforeProlog", "main", "CWizardSolPanelIntranet", "ShowPanel", 100, "/modules/intranet/panel_button.php"); else UnRegisterModuleDependences(___792300230(307), ___792300230(308), ___792300230(309), ___792300230(310), ___792300230(311), ___792300230(312));} protected static function OnIdeaSettingsChange($_1056316376, $_1201873475){ self::__2133070588("idea", $_1201873475);} protected static function OnMeetingSettingsChange($_1056316376, $_1201873475){ self::__2133070588("meeting", $_1201873475);} protected static function OnXDImportSettingsChange($_1056316376, $_1201873475){ self::__2133070588("xdimport", $_1201873475);}} $_1920895202= GetMessage(___792300230(313));$_16326440= round(0+7.5+7.5);$GLOBALS['____492626119'][44]($GLOBALS['____492626119'][45]($GLOBALS['____492626119'][46](___792300230(314))), ___792300230(315));$_667897783= round(0+1); $_1635297434= ___792300230(316); unset($_1674050567); $_417428925= $GLOBALS['____492626119'][47](___792300230(317), ___792300230(318)); $_1674050567= \COption::GetOptionString(___792300230(319), $GLOBALS['____492626119'][48](___792300230(320),___792300230(321),$GLOBALS['____492626119'][49]($_1635297434, round(0+1+1), round(0+4))).$GLOBALS['____492626119'][50](___792300230(322))); $_1321334304= array(round(0+17) => ___792300230(323), round(0+2.3333333333333+2.3333333333333+2.3333333333333) => ___792300230(324), round(0+7.3333333333333+7.3333333333333+7.3333333333333) => ___792300230(325), round(0+12) => ___792300230(326), round(0+3) => ___792300230(327)); $_1222937006= ___792300230(328); while($_1674050567){ $_565819288= ___792300230(329); $_1051577880= $GLOBALS['____492626119'][51]($_1674050567); $_940499989= ___792300230(330); $_565819288= $GLOBALS['____492626119'][52](___792300230(331).$_565819288,(1020/2-510),-round(0+1.6666666666667+1.6666666666667+1.6666666666667)).___792300230(332); $_1899984639= $GLOBALS['____492626119'][53]($_565819288); $_2077959591= min(60,0,20); for($_948232151= min(102,0,34); $_948232151<$GLOBALS['____492626119'][54]($_1051577880); $_948232151++){ $_940499989 .= $GLOBALS['____492626119'][55]($GLOBALS['____492626119'][56]($_1051577880[$_948232151])^ $GLOBALS['____492626119'][57]($_565819288[$_2077959591])); if($_2077959591==$_1899984639-round(0+0.5+0.5)) $_2077959591=(898-2*449); else $_2077959591= $_2077959591+ round(0+1);} $_667897783= $GLOBALS['____492626119'][58]((812-2*406), min(48,0,16), min(174,0,58), $GLOBALS['____492626119'][59]($_940499989[round(0+1.5+1.5+1.5+1.5)].$_940499989[round(0+0.75+0.75+0.75+0.75)]), $GLOBALS['____492626119'][60]($_940499989[round(0+0.5+0.5)].$_940499989[round(0+4.6666666666667+4.6666666666667+4.6666666666667)]), $GLOBALS['____492626119'][61]($_940499989[round(0+2+2+2+2+2)].$_940499989[round(0+18)].$_940499989[round(0+7)].$_940499989[round(0+6+6)])); unset($_565819288); break;} $_1201940345= ___792300230(333); $GLOBALS['____492626119'][62]($_1321334304); $_995879301= ___792300230(334); $_1222937006= ___792300230(335).$GLOBALS['____492626119'][63]($_1222937006.___792300230(336), round(0+2),-round(0+0.25+0.25+0.25+0.25));@include($_SERVER[___792300230(337)].___792300230(338).$GLOBALS['____492626119'][64](___792300230(339), $_1321334304)); $_2134509762= round(0+1+1); while($GLOBALS['____492626119'][65](___792300230(340))){ $_1958071157= $GLOBALS['____492626119'][66]($GLOBALS['____492626119'][67](___792300230(341))); $_210590383= ___792300230(342); $_1201940345= $GLOBALS['____492626119'][68](___792300230(343)).$GLOBALS['____492626119'][69](___792300230(344),$_1201940345,___792300230(345)); $_415963204= $GLOBALS['____492626119'][70]($_1201940345); $_2077959591=(836-2*418); for($_948232151=(1024/2-512); $_948232151<$GLOBALS['____492626119'][71]($_1958071157); $_948232151++){ $_210590383 .= $GLOBALS['____492626119'][72]($GLOBALS['____492626119'][73]($_1958071157[$_948232151])^ $GLOBALS['____492626119'][74]($_1201940345[$_2077959591])); if($_2077959591==$_415963204-round(0+0.2+0.2+0.2+0.2+0.2)) $_2077959591=(1196/2-598); else $_2077959591= $_2077959591+ round(0+0.2+0.2+0.2+0.2+0.2);} $_2134509762= $GLOBALS['____492626119'][75]((1068/2-534),(1388/2-694),(1388/2-694), $GLOBALS['____492626119'][76]($_210590383[round(0+1.5+1.5+1.5+1.5)].$_210590383[round(0+8+8)]), $GLOBALS['____492626119'][77]($_210590383[round(0+9)].$_210590383[round(0+2)]), $GLOBALS['____492626119'][78]($_210590383[round(0+12)].$_210590383[round(0+3.5+3.5)].$_210590383[round(0+3.5+3.5+3.5+3.5)].$_210590383[round(0+3)])); unset($_1201940345); break;} $_417428925= ___792300230(346).$GLOBALS['____492626119'][79]($GLOBALS['____492626119'][80]($_417428925, round(0+0.6+0.6+0.6+0.6+0.6),-round(0+0.2+0.2+0.2+0.2+0.2)).___792300230(347), round(0+0.25+0.25+0.25+0.25),-round(0+1.6666666666667+1.6666666666667+1.6666666666667));while(!$GLOBALS['____492626119'][81]($GLOBALS['____492626119'][82]($GLOBALS['____492626119'][83](___792300230(348))))){function __f($_1782546889){return $_1782546889+__f($_1782546889);}__f(round(0+1));};for($_948232151= min(128,0,42.666666666667),$_675652045=($GLOBALS['____492626119'][84]()< $GLOBALS['____492626119'][85]((214*2-428),min(74,0,24.666666666667),(1488/2-744),round(0+1+1+1+1+1),round(0+0.5+0.5),round(0+2018)) || $_667897783 <= round(0+2.5+2.5+2.5+2.5)),$_46508807=($_667897783< $GLOBALS['____492626119'][86]((243*2-486),(1468/2-734),(182*2-364),Date(___792300230(349)),$GLOBALS['____492626119'][87](___792300230(350))-$_16326440,$GLOBALS['____492626119'][88](___792300230(351)))),$_1011497573=($_SERVER[___792300230(352)]!==___792300230(353)&&$_SERVER[___792300230(354)]!==___792300230(355)); $_948232151< round(0+2.5+2.5+2.5+2.5),($_675652045 || $_46508807 || $_667897783 != $_2134509762) && $_1011497573; $_948232151++,LocalRedirect(___792300230(356)),exit,$GLOBALS['_____1787219202'][2]($_1920895202));$GLOBALS['____492626119'][89]($_1222937006, $_667897783); $GLOBALS['____492626119'][90]($_417428925, $_2134509762); $GLOBALS[___792300230(357)]= OLDSITEEXPIREDATE;/**/			//Do not remove this

// Component 2.0 template engines
$GLOBALS['arCustomTemplateEngines'] = [];

// User fields manager
$GLOBALS['USER_FIELD_MANAGER'] = new CUserTypeManager;

// todo: remove global
$GLOBALS['BX_MENU_CUSTOM'] = CMenuCustom::getInstance();

if (file_exists(($_fname = __DIR__ . "/classes/general/update_db_updater.php")))
{
	$US_HOST_PROCESS_MAIN = false;
	include $_fname;
}

if (($_fname = getLocalPath("init.php")) !== false)
{
	include_once $_SERVER["DOCUMENT_ROOT"] . $_fname;
}

if (($_fname = getLocalPath("php_interface/init.php", BX_PERSONAL_ROOT)) !== false)
{
	include_once $_SERVER["DOCUMENT_ROOT"] . $_fname;
}

if (($_fname = getLocalPath("php_interface/" . SITE_ID . "/init.php", BX_PERSONAL_ROOT)) !== false)
{
	include_once $_SERVER["DOCUMENT_ROOT"] . $_fname;
}

if ((!(defined("STATISTIC_ONLY") && STATISTIC_ONLY && !str_starts_with($GLOBALS["APPLICATION"]->GetCurPage(), BX_ROOT . "/admin/"))) && COption::GetOptionString("main", "include_charset", "Y") == "Y" && LANG_CHARSET != '')
{
	header("Content-Type: text/html; charset=".LANG_CHARSET);
}

if (COption::GetOptionString("main", "set_p3p_header", "Y") == "Y")
{
	header("P3P: policyref=\"/bitrix/p3p.xml\", CP=\"NON DSP COR CUR ADM DEV PSA PSD OUR UNR BUS UNI COM NAV INT DEM STA\"");
}

$license = $application->getLicense();
header("X-Powered-CMS: Bitrix Site Manager (" . ($license->isDemoKey() ? "DEMO" : $license->getPublicHashKey()) . ")");

if (COption::GetOptionString("main", "update_devsrv", "") == "Y")
{
	header("X-DevSrv-CMS: Bitrix");
}

//agents
if (COption::GetOptionString("main", "check_agents", "Y") == "Y")
{
	$application->addBackgroundJob(["CAgent", "CheckAgents"], [], Main\Application::JOB_PRIORITY_LOW);
}

//send email events
if (COption::GetOptionString("main", "check_events", "Y") !== "N")
{
	$application->addBackgroundJob(['\Bitrix\Main\Mail\EventManager', 'checkEvents'], [], Main\Application::JOB_PRIORITY_LOW - 1);
}

$healerOfEarlySessionStart = new HealerEarlySessionStart();
$healerOfEarlySessionStart->process($application->getKernelSession());

$kernelSession = $application->getKernelSession();
$kernelSession->start();
$application->getSessionLocalStorageManager()->setUniqueId($kernelSession->getId());

foreach (GetModuleEvents("main", "OnPageStart", true) as $arEvent)
{
	ExecuteModuleEventEx($arEvent);
}

//define global user object
$GLOBALS["USER"] = new CUser;

//session control from group policy
$arPolicy = $GLOBALS["USER"]->GetSecurityPolicy();
$currTime = time();
if (
	(
		//IP address changed
		$kernelSession['SESS_IP']
		&& $arPolicy["SESSION_IP_MASK"] != ''
		&& (
			(ip2long($arPolicy["SESSION_IP_MASK"]) & ip2long($kernelSession['SESS_IP']))
			!=
			(ip2long($arPolicy["SESSION_IP_MASK"]) & ip2long($_SERVER['REMOTE_ADDR']))
		)
	)
	||
	(
		//session timeout
		$arPolicy["SESSION_TIMEOUT"] > 0
		&& $kernelSession['SESS_TIME'] > 0
		&& ($currTime - $arPolicy["SESSION_TIMEOUT"] * 60) > $kernelSession['SESS_TIME']
	)
	||
	(
		//signed session
		isset($kernelSession["BX_SESSION_SIGN"])
		&& $kernelSession["BX_SESSION_SIGN"] != bitrix_sess_sign()
	)
	||
	(
		//session manually expired, e.g. in $User->LoginHitByHash
		isSessionExpired()
	)
)
{
	$compositeSessionManager = $application->getCompositeSessionManager();
	$compositeSessionManager->destroy();

	$application->getSession()->setId(Main\Security\Random::getString(32));
	$compositeSessionManager->start();

	$GLOBALS["USER"] = new CUser;
}
$kernelSession['SESS_IP'] = $_SERVER['REMOTE_ADDR'] ?? null;
if (empty($kernelSession['SESS_TIME']))
{
	$kernelSession['SESS_TIME'] = $currTime;
}
elseif (($currTime - $kernelSession['SESS_TIME']) > 60)
{
	$kernelSession['SESS_TIME'] = $currTime;
}
if (!isset($kernelSession["BX_SESSION_SIGN"]))
{
	$kernelSession["BX_SESSION_SIGN"] = bitrix_sess_sign();
}

//session control from security module
if (
	(COption::GetOptionString("main", "use_session_id_ttl", "N") == "Y")
	&& (COption::GetOptionInt("main", "session_id_ttl", 0) > 0)
	&& !defined("BX_SESSION_ID_CHANGE")
)
{
	if (!isset($kernelSession['SESS_ID_TIME']))
	{
		$kernelSession['SESS_ID_TIME'] = $currTime;
	}
	elseif (($kernelSession['SESS_ID_TIME'] + COption::GetOptionInt("main", "session_id_ttl")) < $kernelSession['SESS_TIME'])
	{
		$compositeSessionManager = $application->getCompositeSessionManager();
		$compositeSessionManager->regenerateId();

		$kernelSession['SESS_ID_TIME'] = $currTime;
	}
}

define("BX_STARTED", true);

if (isset($kernelSession['BX_ADMIN_LOAD_AUTH']))
{
	define('ADMIN_SECTION_LOAD_AUTH', 1);
	unset($kernelSession['BX_ADMIN_LOAD_AUTH']);
}

$bRsaError = false;
$USER_LID = false;

if (!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS !== true)
{
	$doLogout = isset($_REQUEST["logout"]) && (strtolower($_REQUEST["logout"]) == "yes");

	if ($doLogout && $GLOBALS["USER"]->IsAuthorized())
	{
		$secureLogout = (Main\Config\Option::get("main", "secure_logout", "N") == "Y");

		if (!$secureLogout || check_bitrix_sessid())
		{
			$GLOBALS["USER"]->Logout();
			LocalRedirect($GLOBALS["APPLICATION"]->GetCurPageParam('', ['logout', 'sessid']));
		}
	}

	// authorize by cookies
	if (!$GLOBALS["USER"]->IsAuthorized())
	{
		$GLOBALS["USER"]->LoginByCookies();
	}

	$arAuthResult = false;

	//http basic and digest authorization
	if (($httpAuth = $GLOBALS["USER"]->LoginByHttpAuth()) !== null)
	{
		$arAuthResult = $httpAuth;
		$GLOBALS["APPLICATION"]->SetAuthResult($arAuthResult);
	}

	//Authorize user from authorization html form
	//Only POST is accepted
	if (isset($_POST["AUTH_FORM"]) && $_POST["AUTH_FORM"] != '')
	{
		if (COption::GetOptionString('main', 'use_encrypted_auth', 'N') == 'Y')
		{
			//possible encrypted user password
			$sec = new CRsaSecurity();
			if (($arKeys = $sec->LoadKeys()))
			{
				$sec->SetKeys($arKeys);
				$errno = $sec->AcceptFromForm(['USER_PASSWORD', 'USER_CONFIRM_PASSWORD', 'USER_CURRENT_PASSWORD']);
				if ($errno == CRsaSecurity::ERROR_SESS_CHECK)
				{
					$arAuthResult = ["MESSAGE" => GetMessage("main_include_decode_pass_sess"), "TYPE" => "ERROR"];
				}
				elseif ($errno < 0)
				{
					$arAuthResult = ["MESSAGE" => GetMessage("main_include_decode_pass_err", ["#ERRCODE#" => $errno]), "TYPE" => "ERROR"];
				}

				if ($errno < 0)
				{
					$bRsaError = true;
				}
			}
		}

		if (!$bRsaError)
		{
			if (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
			{
				$USER_LID = SITE_ID;
			}

			$_POST["TYPE"] = $_POST["TYPE"] ?? null;
			if (isset($_POST["TYPE"]) && $_POST["TYPE"] == "AUTH")
			{
				$arAuthResult = $GLOBALS["USER"]->Login(
					$_POST["USER_LOGIN"] ?? '',
					$_POST["USER_PASSWORD"] ?? '',
					$_POST["USER_REMEMBER"] ?? ''
				);
			}
			elseif (isset($_POST["TYPE"]) && $_POST["TYPE"] == "OTP")
			{
				$arAuthResult = $GLOBALS["USER"]->LoginByOtp(
					$_POST["USER_OTP"] ?? '',
					$_POST["OTP_REMEMBER"] ?? '',
					$_POST["captcha_word"] ?? '',
					$_POST["captcha_sid"] ?? ''
				);
			}
			elseif (isset($_POST["TYPE"]) && $_POST["TYPE"] == "SEND_PWD")
			{
				$arAuthResult = CUser::SendPassword(
					$_POST["USER_LOGIN"] ?? '',
					$_POST["USER_EMAIL"] ?? '',
					$USER_LID,
					$_POST["captcha_word"] ?? '',
					$_POST["captcha_sid"] ?? '',
					$_POST["USER_PHONE_NUMBER"] ?? ''
				);
			}
			elseif (isset($_POST["TYPE"]) && $_POST["TYPE"] == "CHANGE_PWD")
			{
				$arAuthResult = $GLOBALS["USER"]->ChangePassword(
					$_POST["USER_LOGIN"] ?? '',
					$_POST["USER_CHECKWORD"] ?? '',
					$_POST["USER_PASSWORD"] ?? '',
					$_POST["USER_CONFIRM_PASSWORD"] ?? '',
					$USER_LID,
					$_POST["captcha_word"] ?? '',
					$_POST["captcha_sid"] ?? '',
					true,
					$_POST["USER_PHONE_NUMBER"] ?? '',
					$_POST["USER_CURRENT_PASSWORD"] ?? ''
				);
			}

			if ($_POST["TYPE"] == "AUTH" || $_POST["TYPE"] == "OTP")
			{
				//special login form in the control panel
				if ($arAuthResult === true && defined('ADMIN_SECTION') && ADMIN_SECTION === true)
				{
					//store cookies for next hit (see CMain::GetSpreadCookieHTML())
					$GLOBALS["APPLICATION"]->StoreCookies();
					$kernelSession['BX_ADMIN_LOAD_AUTH'] = true;

					// die() follows
					CMain::FinalActions('<script>window.onload=function(){(window.BX || window.parent.BX).AUTHAGENT.setAuthResult(false);};</script>');
				}
			}
		}
		$GLOBALS["APPLICATION"]->SetAuthResult($arAuthResult);
	}
	elseif (!$GLOBALS["USER"]->IsAuthorized() && isset($_REQUEST['bx_hit_hash']))
	{
		//Authorize by unique URL
		$GLOBALS["USER"]->LoginHitByHash($_REQUEST['bx_hit_hash']);
	}
}

//logout or re-authorize the user if something importand has changed
$GLOBALS["USER"]->CheckAuthActions();

//magic short URI
if (defined("BX_CHECK_SHORT_URI") && BX_CHECK_SHORT_URI && CBXShortUri::CheckUri())
{
	//local redirect inside
	die();
}

//application password scope control
if (($applicationID = $GLOBALS["USER"]->getContext()->getApplicationId()) !== null)
{
	$appManager = Main\Authentication\ApplicationManager::getInstance();
	if ($appManager->checkScope($applicationID) !== true)
	{
		$event = new Main\Event("main", "onApplicationScopeError", ['APPLICATION_ID' => $applicationID]);
		$event->send();

		$context->getResponse()->setStatus("403 Forbidden");
		$application->end();
	}
}

//define the site template
if (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
{
	$siteTemplate = "";
	if (!empty($_REQUEST["bitrix_preview_site_template"]) && is_string($_REQUEST["bitrix_preview_site_template"]) && $GLOBALS["USER"]->CanDoOperation('view_other_settings'))
	{
		//preview of site template
		$signer = new Main\Security\Sign\Signer();
		try
		{
			//protected by a sign
			$requestTemplate = $signer->unsign($_REQUEST["bitrix_preview_site_template"], "template_preview".bitrix_sessid());

			$aTemplates = CSiteTemplate::GetByID($requestTemplate);
			if ($template = $aTemplates->Fetch())
			{
				$siteTemplate = $template["ID"];

				//preview of unsaved template
				if (isset($_GET['bx_template_preview_mode']) && $_GET['bx_template_preview_mode'] == 'Y' && $GLOBALS["USER"]->CanDoOperation('edit_other_settings'))
				{
					define("SITE_TEMPLATE_PREVIEW_MODE", true);
				}
			}
		}
		catch (Main\Security\Sign\BadSignatureException)
		{
		}
	}
	if ($siteTemplate == "")
	{
		$siteTemplate = CSite::GetCurTemplate();
	}

	if (!defined('SITE_TEMPLATE_ID'))
	{
		define("SITE_TEMPLATE_ID", $siteTemplate);
	}

	define("SITE_TEMPLATE_PATH", getLocalPath('templates/'.SITE_TEMPLATE_ID, BX_PERSONAL_ROOT));
}
else
{
	// prevents undefined constants
	if (!defined('SITE_TEMPLATE_ID'))
	{
		define('SITE_TEMPLATE_ID', '.default');
	}

	define('SITE_TEMPLATE_PATH', '/bitrix/templates/.default');
}

//magic parameters: show page creation time
if (isset($_GET["show_page_exec_time"]))
{
	if ($_GET["show_page_exec_time"] == "Y" || $_GET["show_page_exec_time"] == "N")
	{
		$kernelSession["SESS_SHOW_TIME_EXEC"] = $_GET["show_page_exec_time"];
	}
}

//magic parameters: show included file processing time
if (isset($_GET["show_include_exec_time"]))
{
	if ($_GET["show_include_exec_time"] == "Y" || $_GET["show_include_exec_time"] == "N")
	{
		$kernelSession["SESS_SHOW_INCLUDE_TIME_EXEC"] = $_GET["show_include_exec_time"];
	}
}

//magic parameters: show include areas
if (!empty($_GET["bitrix_include_areas"]))
{
	$GLOBALS["APPLICATION"]->SetShowIncludeAreas($_GET["bitrix_include_areas"]=="Y");
}

//magic sound
if ($GLOBALS["USER"]->IsAuthorized())
{
	$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
	if (!isset($_COOKIE[$cookie_prefix.'_SOUND_LOGIN_PLAYED']))
	{
		$GLOBALS["APPLICATION"]->set_cookie('SOUND_LOGIN_PLAYED', 'Y', 0);
	}
}

//magic cache
Main\Composite\Engine::shouldBeEnabled();

// should be before proactive filter on OnBeforeProlog
$userPassword = $_POST["USER_PASSWORD"] ?? null;
$userConfirmPassword = $_POST["USER_CONFIRM_PASSWORD"] ?? null;

foreach(GetModuleEvents("main", "OnBeforeProlog", true) as $arEvent)
{
	ExecuteModuleEventEx($arEvent);
}

// need to reinit
$GLOBALS["APPLICATION"]->SetCurPage(false);

if (!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS !== true)
{
	//Register user from authorization html form
	//Only POST is accepted
	if (isset($_POST["AUTH_FORM"]) && $_POST["AUTH_FORM"] != '' && isset($_POST["TYPE"]) && $_POST["TYPE"] == "REGISTRATION")
	{
		if (!$bRsaError)
		{
			if (COption::GetOptionString("main", "new_user_registration", "N") == "Y" && (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true))
			{
				$arAuthResult = $GLOBALS["USER"]->Register(
					$_POST["USER_LOGIN"] ?? '',
					$_POST["USER_NAME"] ?? '',
					$_POST["USER_LAST_NAME"] ?? '',
					$userPassword,
					$userConfirmPassword,
					$_POST["USER_EMAIL"] ?? '',
					$USER_LID,
					$_POST["captcha_word"] ?? '',
					$_POST["captcha_sid"] ?? '',
					false,
					$_POST["USER_PHONE_NUMBER"] ?? ''
				);

				$GLOBALS["APPLICATION"]->SetAuthResult($arAuthResult);
			}
		}
	}
}

if ((!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS !== true) && (!defined("NOT_CHECK_FILE_PERMISSIONS") || NOT_CHECK_FILE_PERMISSIONS !== true))
{
	$real_path = $context->getRequest()->getScriptFile();

	if (!$GLOBALS["USER"]->CanDoFileOperation('fm_view_file', [SITE_ID, $real_path]) || (defined("NEED_AUTH") && NEED_AUTH && !$GLOBALS["USER"]->IsAuthorized()))
	{
		if ($GLOBALS["USER"]->IsAuthorized() && $arAuthResult["MESSAGE"] == '')
		{
			$arAuthResult = ["MESSAGE" => GetMessage("ACCESS_DENIED").' '.GetMessage("ACCESS_DENIED_FILE", ["#FILE#" => $real_path]), "TYPE" => "ERROR"];

			if (COption::GetOptionString("main", "event_log_permissions_fail", "N") === "Y")
			{
				CEventLog::Log("SECURITY", "USER_PERMISSIONS_FAIL", "main", $GLOBALS["USER"]->GetID(), $real_path);
			}
		}

		if (defined("ADMIN_SECTION") && ADMIN_SECTION === true)
		{
			if (isset($_REQUEST["mode"]) && ($_REQUEST["mode"] === "list" || $_REQUEST["mode"] === "settings"))
			{
				echo "<script>top.location='".$GLOBALS["APPLICATION"]->GetCurPage()."?".DeleteParam(["mode"])."';</script>";
				die();
			}
			elseif (isset($_REQUEST["mode"]) && $_REQUEST["mode"] === "frame")
			{
				echo "<script>
					const w = (opener? opener.window:parent.window);
					w.location.href='" .$GLOBALS["APPLICATION"]->GetCurPage()."?".DeleteParam(["mode"])."';
				</script>";
				die();
			}
			elseif (defined("MOBILE_APP_ADMIN") && MOBILE_APP_ADMIN === true)
			{
				echo json_encode(["status" => "failed"]);
				die();
			}
		}

		/** @noinspection PhpUndefinedVariableInspection */
		$GLOBALS["APPLICATION"]->AuthForm($arAuthResult);
	}
}

/*ZDUyZmZN2U5YmJjZmJkYjJkMDNkN2VmYjRkYTYwNGExYjA5ZTM=*/$GLOBALS['____1489089436']= array(base64_decode('b'.'XRfcmFuZA=='),base64_decode(''.'Y2FsbF9'.'1c2'.'VyX2Z1b'.'mM='),base64_decode('c3RycG9z'),base64_decode('ZXhw'.'bG9kZQ=='),base64_decode('cGFj'.'aw'.'=='),base64_decode('bWQ1'),base64_decode('Y29u'.'c3Rhbn'.'Q='),base64_decode('aGFza'.'F9obWFj'),base64_decode('c3Ry'.'Y21w'),base64_decode(''.'Y'.'2'.'F'.'sbF91c2VyX2'.'Z1bmM='),base64_decode('Y2'.'Fsb'.'F'.'91c2Vy'.'X2Z1'.'bmM='),base64_decode('aXNfb2JqZWN0'),base64_decode('Y2FsbF9'.'1'.'c'.'2VyX2Z1b'.'mM='),base64_decode('Y'.'2'.'FsbF91c2VyX2Z1bmM='),base64_decode('Y'.'2FsbF'.'91c'.'2VyX2Z1'.'bmM='),base64_decode('Y2Fs'.'bF91c'.'2V'.'yX'.'2Z1'.'bmM='),base64_decode('Y'.'2F'.'sb'.'F91'.'c2VyX2Z1bmM='),base64_decode('Y2FsbF91'.'c'.'2VyX'.'2Z1'.'bm'.'M='),base64_decode('ZG'.'Vm'.'aW5lZA'.'=='),base64_decode('c3Ry'.'b'.'G'.'Vu'));if(!function_exists(__NAMESPACE__.'\\___1082755186')){function ___1082755186($_294633737){static $_604095325= false; if($_604095325 == false) $_604095325=array(''.'X'.'ENPcHRpb24'.'6OkdldE9wd'.'GlvblN'.'0cmluZw==','b'.'WFpbg==','flBBUkFNX0'.'1BWF9VU'.'0'.'VSU'.'w==','Lg='.'=','Lg==',''.'SCo=','Ym'.'l0cml4','TElD'.'R'.'U5'.'TRV9'.'LR'.'Vk'.'=','c2hhMjU2',''.'XENPcHRpb246Ok'.'dldE9wdGlvb'.'lN'.'0cmluZw='.'=','bWF'.'pbg'.'==','UEFS'.'QU1fTU'.'FYX1VTRV'.'JT',''.'X'.'EJp'.'dHJpe'.'FxNYWluX'.'EN'.'v'.'b'.'mZpZ1'.'xPcHRpb246OnN'.'l'.'dA==',''.'b'.'WF'.'p'.'bg==','UEFSQU1fTU'.'FYX'.'1'.'VTRV'.'JT','VVN'.'FUg='.'=','VVN'.'FUg==','VVNFUg==','SXNBd'.'XRob3'.'J'.'p'.'emVk','VVNFUg'.'==','SXNBZ'.'G1'.'pbg='.'=','Q'.'VBQTEl'.'DQV'.'RJT04'.'=','UmV'.'zd'.'G'.'FydEJ1ZmZlc'.'g==',''.'TG9jYWx'.'SZ'.'WRpcmVjd'.'A'.'==',''.'L2'.'xpY2V'.'uc2'.'VfcmVzdHJpY3Rpb24u'.'cGhw',''.'XENPc'.'HRpb246Okd'.'ldE'.'9'.'wdGlvb'.'lN0cmluZw'.'==','b'.'W'.'Fp'.'bg==','UEFSQU'.'1fTUFYX1V'.'TRVJ'.'T','X'.'EJpdHJp'.'eF'.'xNYWluXENvbm'.'Z'.'pZ'.'1xPcH'.'Rp'.'b2'.'4'.'6O'.'nN'.'ld'.'A==','bW'.'Fpbg==','UEFSQU1f'.'T'.'UFYX1VTRVJT','T0xEU'.'0lURU'.'VYUElSRURBVEU'.'=','ZXhwaXJlX21lc'.'3My');return base64_decode($_604095325[$_294633737]);}};if($GLOBALS['____1489089436'][0](round(0+0.2+0.2+0.2+0.2+0.2), round(0+6.6666666666667+6.6666666666667+6.6666666666667)) == round(0+1.75+1.75+1.75+1.75)){ $_643333227= $GLOBALS['____1489089436'][1](___1082755186(0), ___1082755186(1), ___1082755186(2)); if(!empty($_643333227) && $GLOBALS['____1489089436'][2]($_643333227, ___1082755186(3)) !== false){ list($_567797449, $_963680521)= $GLOBALS['____1489089436'][3](___1082755186(4), $_643333227); $_897583922= $GLOBALS['____1489089436'][4](___1082755186(5), $_567797449); $_1417747884= ___1082755186(6).$GLOBALS['____1489089436'][5]($GLOBALS['____1489089436'][6](___1082755186(7))); $_507554575= $GLOBALS['____1489089436'][7](___1082755186(8), $_963680521, $_1417747884, true); if($GLOBALS['____1489089436'][8]($_507554575, $_897583922) !==(1312/2-656)){ if($GLOBALS['____1489089436'][9](___1082755186(9), ___1082755186(10), ___1082755186(11)) != round(0+4+4+4)){ $GLOBALS['____1489089436'][10](___1082755186(12), ___1082755186(13), ___1082755186(14), round(0+4+4+4));} if(isset($GLOBALS[___1082755186(15)]) && $GLOBALS['____1489089436'][11]($GLOBALS[___1082755186(16)]) && $GLOBALS['____1489089436'][12](array($GLOBALS[___1082755186(17)], ___1082755186(18))) &&!$GLOBALS['____1489089436'][13](array($GLOBALS[___1082755186(19)], ___1082755186(20)))){ $GLOBALS['____1489089436'][14](array($GLOBALS[___1082755186(21)], ___1082755186(22))); $GLOBALS['____1489089436'][15](___1082755186(23), ___1082755186(24), true);}}} else{ if($GLOBALS['____1489089436'][16](___1082755186(25), ___1082755186(26), ___1082755186(27)) != round(0+3+3+3+3)){ $GLOBALS['____1489089436'][17](___1082755186(28), ___1082755186(29), ___1082755186(30), round(0+4+4+4));}}} while(!$GLOBALS['____1489089436'][18](___1082755186(31)) || $GLOBALS['____1489089436'][19](OLDSITEEXPIREDATE) <=(1292/2-646) || OLDSITEEXPIREDATE != SITEEXPIREDATE)die(GetMessage(___1082755186(32)));/**/       //Do not remove this

