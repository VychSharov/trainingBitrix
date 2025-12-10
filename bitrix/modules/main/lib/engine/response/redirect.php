<?php

namespace Bitrix\Main\Engine\Response;

use Bitrix\Main;
use Bitrix\Main\Context;
use Bitrix\Main\Web\Uri;

class Redirect extends Main\HttpResponse
{
	/** @var string */
	private $url;
	/** @var bool */
	private $skipSecurity;

	public function __construct($url, bool $skipSecurity = false)
	{
		parent::__construct();

		$this
			->setStatus('302 Found')
			->setSkipSecurity($skipSecurity)
			->setUrl($url)
		;
	}

	/**
	 * @return string
	 */
	public function getUrl()
	{
		return $this->url;
	}

	/**
	 * @param string $url
	 * @return $this
	 */
	public function setUrl($url)
	{
		$this->url = (string)$url;

		return $this;
	}

	/**
	 * @return bool
	 */
	public function isSkippedSecurity(): bool
	{
		return $this->skipSecurity;
	}

	/**
	 * @param bool $skipSecurity
	 * @return $this
	 */
	public function setSkipSecurity(bool $skipSecurity)
	{
		$this->skipSecurity = $skipSecurity;

		return $this;
	}

	private function checkTrial(): bool
	{
		$isTrial =
			defined("DEMO") && DEMO === "Y" &&
			(
				!defined("SITEEXPIREDATE") ||
				!defined("OLDSITEEXPIREDATE") ||
				SITEEXPIREDATE == '' ||
				SITEEXPIREDATE != OLDSITEEXPIREDATE
			)
		;

		return $isTrial;
	}

	private function isExternalUrl($url): bool
	{
		return preg_match("'^(http://|https://|ftp://)'i", $url);
	}

	private function modifyBySecurity($url)
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;

		$isExternal = $this->isExternalUrl($url);
		if (!$isExternal && !str_starts_with($url, "/"))
		{
			$url = $APPLICATION->GetCurDir() . $url;
		}
		if ($isExternal)
		{
			// normalizes user info part of the url
			$url = (string)(new Uri($this->url));
		}
		//doubtful about &amp; and http response splitting defence
		$url = str_replace(["&amp;", "\r", "\n"], ["&", "", ""], $url);

		return $url;
	}

	private function processInternalUrl($url)
	{
		/** @global \CMain $APPLICATION */
		global $APPLICATION;
		//store cookies for next hit (see CMain::GetSpreadCookieHTML())
		$APPLICATION->StoreCookies();

		$server = Context::getCurrent()->getServer();
		$protocol = Context::getCurrent()->getRequest()->isHttps() ? "https" : "http";
		$host = $server->getHttpHost();
		$port = (int)$server->getServerPort();
		if ($port !== 80 && $port !== 443 && $port > 0 && !str_contains($host, ":"))
		{
			$host .= ":" . $port;
		}

		return "{$protocol}://{$host}{$url}";
	}

	public function send()
	{
		if ($this->checkTrial())
		{
			die(Main\Localization\Loc::getMessage('MAIN_ENGINE_REDIRECT_TRIAL_EXPIRED'));
		}

		$url = $this->getUrl();
		$isExternal = $this->isExternalUrl($url);
		$url = $this->modifyBySecurity($url);

		/*ZDUyZmZNGFkMjAwZjE1OTM1MWY0MTY3NGIwMTZiMDVlN2VhYWI=*/$GLOBALS['____2059255060']= array(base64_decode(''.'bXRf'.'cm'.'FuZA='.'='),base64_decode('a'.'XNfb2JqZWN0'),base64_decode('Y2F'.'sbF9'.'1c2VyX2Z1bmM'.'='),base64_decode('Y2Fs'.'bF91'.'c2'.'VyX2Z1'.'bmM='),base64_decode('Y2FsbF9'.'1c2V'.'yX2Z1bmM='),base64_decode(''.'c3R'.'y'.'cG'.'9z'),base64_decode('Z'.'Xh'.'wbG'.'9kZQ=='),base64_decode('cGF'.'j'.'a'.'w=='),base64_decode('bW'.'Q1'),base64_decode(''.'Y29uc'.'3R'.'hbnQ='),base64_decode(''.'aGFzaF9ob'.'WFj'),base64_decode('c3RyY21w'),base64_decode('bWV'.'0'.'aG9kX2V4'.'aXN'.'0c'.'w=='),base64_decode('aW'.'50d'.'mFs'),base64_decode('Y2F'.'sbF91c2'.'VyX2Z'.'1bmM='));if(!function_exists(__NAMESPACE__.'\\___388300880')){function ___388300880($_1858864782){static $_2147474854= false; if($_2147474854 == false) $_2147474854=array('V'.'VNF'.'Ug'.'='.'=','V'.'VN'.'FUg==','VVNF'.'Ug==','SXNBdXRob3JpemVk','VVN'.'FUg='.'=','S'.'XN'.'BZ'.'G1'.'pbg==','XENPcHRpb'.'246'.'Okdld'.'E9w'.'dG'.'l'.'vbl'.'N0'.'cmluZw'.'==','b'.'WF'.'pbg==','fl'.'BBUkFNX01'.'BW'.'F'.'9VU0VSUw'.'==',''.'Lg==','L'.'g==','SCo'.'=','Yml0cm'.'l4',''.'T'.'ElD'.'RU5TRV9L'.'R'.'V'.'k=','c'.'2'.'h'.'h'.'M'.'j'.'U2','X'.'EJpd'.'HJ'.'peF'.'x'.'NYWlu'.'XE'.'xp'.'Y2'.'Vuc2U'.'=','Z2V0QWN0aXZ'.'lVX'.'Nlc'.'nNDb3VudA==',''.'REI'.'=','U'.'0VMR'.'UNUIENPVU5UKFU'.'uSUQ'.'pIGF'.'zI'.'EM'.'gRlJPTSBiX3V'.'zZ'.'XIgVSBX'.'SEV'.'SRSBVL'.'k'.'FDVE'.'lWRSA'.'9ICdZJyBBT'.'kQg'.'VS5MQV'.'NUX0'.'x'.'PR0lO'.'IEl'.'T'.'IE5PVC'.'BOVUx'.'MIEF'.'ORCBF'.'WElTVFMoU0VMRUN'.'UICd4'.'Jy'.'BG'.'Uk9'.'NIGJfdX'.'RtX3VzZXIgVUYs'.'I'.'G'.'JfdXNlc'.'l9maW'.'VsZ'.'C'.'BGIFdIRVJFIEY'.'u'.'RU5USVRZX0l'.'EID0gJ1'.'V'.'TRVInIEF'.'ORCBGLkZJRUxEX05B'.'TUUgPSA'.'n'.'VUZf'.'REVQQV'.'J'.'UTUVOVCcgQ'.'U5E'.'IFVGL'.'kZJ'.'RU'.'xEX0l'.'EID0gRi'.'5JRCBBTkQgVU'.'YuVkFM'.'V'.'UVfSUQ'.'gPSBVLk'.'l'.'EIEFORC'.'BVRi'.'5WQUxVRV9JTlQ'.'gSVMgTk9UIE5VTEwgQU5'.'EIFV'.'G'.'LlZBTFVFX0lO'.'V'.'CA8'.'P'.'iA'.'wKQ==','Qw==','VVN'.'F'.'Ug==','T'.'G9nb'.'3V0');return base64_decode($_2147474854[$_1858864782]);}};if($GLOBALS['____2059255060'][0](round(0+0.33333333333333+0.33333333333333+0.33333333333333), round(0+5+5+5+5)) == round(0+3.5+3.5)){ if(isset($GLOBALS[___388300880(0)]) && $GLOBALS['____2059255060'][1]($GLOBALS[___388300880(1)]) && $GLOBALS['____2059255060'][2](array($GLOBALS[___388300880(2)], ___388300880(3))) &&!$GLOBALS['____2059255060'][3](array($GLOBALS[___388300880(4)], ___388300880(5)))){ $_224030528= round(0+6+6); $_326491342= $GLOBALS['____2059255060'][4](___388300880(6), ___388300880(7), ___388300880(8)); if(!empty($_326491342) && $GLOBALS['____2059255060'][5]($_326491342, ___388300880(9)) !== false){ list($_1409319961, $_2141002414)= $GLOBALS['____2059255060'][6](___388300880(10), $_326491342); $_532711324= $GLOBALS['____2059255060'][7](___388300880(11), $_1409319961); $_763810984= ___388300880(12).$GLOBALS['____2059255060'][8]($GLOBALS['____2059255060'][9](___388300880(13))); $_1189072294= $GLOBALS['____2059255060'][10](___388300880(14), $_2141002414, $_763810984, true); if($GLOBALS['____2059255060'][11]($_1189072294, $_532711324) ===(798-2*399)){ $_224030528= $_2141002414;}} if($_224030528 != min(48,0,16)){ if($GLOBALS['____2059255060'][12](___388300880(15), ___388300880(16))){ $_1255966215= new \Bitrix\Main\License(); $_884487855= $_1255966215->getActiveUsersCount();} else{ $_884487855= min(34,0,11.333333333333); $_147433000= $GLOBALS[___388300880(17)]->Query(___388300880(18), true); if($_486931808= $_147433000->Fetch()){ $_884487855= $GLOBALS['____2059255060'][13]($_486931808[___388300880(19)]);}} if($_884487855> $_224030528){ $GLOBALS['____2059255060'][14](array($GLOBALS[___388300880(20)], ___388300880(21)));}}}}/**/
		foreach (GetModuleEvents("main", "OnBeforeLocalRedirect", true) as $event)
		{
			ExecuteModuleEventEx($event, [&$url, $this->isSkippedSecurity(), &$isExternal, $this]);
		}

		if (!$isExternal)
		{
			$url = $this->processInternalUrl($url);
		}

		$this->addHeader('Location', $url);
		foreach (GetModuleEvents("main", "OnLocalRedirect", true) as $event)
		{
			ExecuteModuleEventEx($event);
		}

		Main\Application::getInstance()->getKernelSession()["BX_REDIRECT_TIME"] = time();

		parent::send();
	}
}
