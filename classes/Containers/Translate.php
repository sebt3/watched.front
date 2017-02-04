<?php
use Interop\Container\ContainerInterface as Container;

class Translate extends core {
	private $lang;
	private $trans;
	public function __construct(Container $ci) {
		parent::__construct($ci);
		$a = array_intersect(explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']), array('fr-FR','en-US'));
		$f = __DIR__.'/../../langs/'.$a[0].'.json';
		if (!empty($a) && file_exists($f)) {
			$j = file_get_contents($f);
			$this->trans = json_decode($j, true);
			if ($this->trans == null)
				$this->trans = array();
		} else	$this->trans = array();

		/* gettext is too picky to be used on a php app that will be send to users
		if (empty($a))
			$this->lang = 'en_US';
		else if($a[0]=='fr-FR') 
			$this->lang = 'fr_FR';
		else
			$this->lang = 'en_US';

		$domain = 'message';
		$dir = __DIR__.'/../../locale';
		$a = array($this->lang.".utf8", $this->lang.".UTF8", $this->lang.".utf-8", $this->lang.".UTF-8",
$this->lang);
		if (($l = setlocale(LC_ALL, $a)) === FALSE){
			echo "<strong>FAILED</strong>";
		}
		print("L set to $l");
		putenv("LANG=".$l);
		$dir = bindtextdomain($domain, $dir);
		textdomain($domain);
		*/
	}
	
	public function __invoke($str) {
		if(is_string($str)) {
			if (array_key_exists($str, $this->trans))
				return $this->trans[$str];
			return $str;
		}
		if(is_array($str)) {
			if (array_key_exists($str[0], $this->trans))
				return $this->trans[$str[0]];
			return $str[0];
		}
		$this->logger->addWarning('Translator() arg#1 is not a string but '.gettype($str)."$str");
		return "";
		//return _($str);
	}
}


?>
