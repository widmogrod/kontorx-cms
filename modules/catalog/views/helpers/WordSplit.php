<?php
require_once 'Zend/View/Helper/Abstract.php';
class Catalog_View_Helper_WordSplit extends Zend_View_Helper_Abstract {
	public function wordSplit($string, $wordsLength) {
		$text = null;
		$more = null;

		if (!is_integer($wordsLength)) {
			$wordsLength = 200;
		}

		$words = array();
		$contexts = explode('.', $string);
		$contextsLength = 0;

		while ($context = array_shift($contexts)) {
			$contextsLength += strlen($context);

			if ($contextsLength > $wordsLength) {
				break;
			} else {
				$words[] = $context;
			}
		}

		// tekst przed
		$text = implode('.', $words);
		
		// jeżeli zostały jakieś części po...
		// to do więcej
		if (count($contexts) > 0) {
			$more = implode('.', $contexts);
		}

		return array($text, $more);
	}
}