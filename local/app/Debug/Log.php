<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");
$APPLICATION->SetTitle("Title");
?>&lt;?php<br>
 declare(strict_types=1);<br>
 class Logger<br>
 {<br>
 &nbsp; &nbsp; private string $filePath;<br>
 <br>
		 public function __construct(string $filePath)<br>
		{<br>
 			$this-&gt;filePath = $filePath;<br>
		}<br>
 <br>
		 public function write(string $message): void<br>
		 {<br>
			 $dir = dirname($this-&gt;filePath);<br>
 			 if (!is_dir($dir)) <br>
			 {<br>
 				 mkdir($dir, 0755, true);<br>
 			 }<br>
 <br>
 		 $line = 'OTUS ' . $message . PHP_EOL;<br>
 		 <br>
 				 file_put_contents($this-&gt;filePath, $line, FILE_APPEND | LOCK_EX);<br>
 		 <br>
  }<br>
 <br>
 public function clear(): void<br>
 <br>
 		 file_put_contents($this-&gt;filePath, '');<br>
 }<br>
 }<br>
 <br><?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>