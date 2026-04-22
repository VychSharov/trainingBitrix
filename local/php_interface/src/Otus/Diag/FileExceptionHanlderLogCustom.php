<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Title");
?>&lt;?php<br>
 declare(strict_types=1);<br>
 <br>
 namespace Diag;<br>
 <br>
 use Bitrix\Main;<br>
 use Bitrix\Main\Diag\ExceptionHandlerFormatter;<br>
 use Bitrix\Main\Diag\FileExceptionHandlerLog;<br>
 use Bitrix\Main\Diag\FileLogger;<br>
 use Bitrix\Main\Localization\Loc;<br>
 <br>
 class FileExceptionHandlerLogCustom extends FileExceptionHandlerLog<br>
 {<br>
		 /** @var int|null */<br>
		 private $level = null;<br>
 <br>
		 public function initialize(array $options): void<br>
		 {<br>
				 Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/otus/debug.php');<br>
 <br>
				 $logFile = '/local/logs/exceptions.log';<br>
				 if (!empty($options['file']))<br>
				 {<br>
						 $logFile = (string)$options['file'];<br>
				 }<br>
 <br>
				 if ($logFile[0] === '/')<br>
				 {<br>
						 $logFile = Main\Application::getDocumentRoot() . $logFile;<br>
				 }<br>
 <br>
				 $maxLogSize = !empty($options['log_size']) ? (int)$options['log_size'] : 1000000;<br>
 <br>
				 if (isset($options['level']))<br>
				 {<br>
						 $this-&gt;level = (int)$options['level'];<br>
				 }<br>
 <br>
				 $this-&gt;logger = new FileLogger($logFile, $maxLogSize);<br>
		 }<br>
 <br>
		 public function write($exception, $logType): void<br>
		 {<br>
				 $text = Loc::getMessage('EXCEPTION_TEST') ?: 'тестовое исключение';<br>
 <br>
				 $formatted = ExceptionHandlerFormatter::format($exception, false, $this-&gt;level);<br>
 <br>
				 $lines = preg_split('/\r?\n/', trim($formatted));<br>
				 $clean = [];<br>
 <br>
				 if (isset($lines[0])) {<br>
				 		 $clean[] = $lines[0]; <br>
				 }<br>
				 if (isset($lines[1])) {<br>
						 $clean[] = $lines[1];<br>
				 }<br>
 <br>
				 $formattedClean = implode("\n", $clean);<br>
 <br>
				 $date = date('Y-m-d H:i:s');<br>
 <br>
				 $logLevel = static::logTypeToLevel($logType);<br>
 <br>
				 $message = "OTUS - {$date} &nbsp;- {$text}\n{$formattedClean}\n";<br>
 <br>
				 $this-&gt;logger-&gt;log($logLevel, $message, [<br>
				 <br>
				 ]);<br>
		 }<br>
 }<br>
 <br><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>