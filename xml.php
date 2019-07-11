<?php

class Xml {
	// xmlを見やすくダンプ
	//# 引数はSimpleXMLElementでもstringでもOK!
	public static function dump($xml) {
		echo self::xmlstr($xml);
	}

	// xmlを見やすい形式の文字列に変換
	//# 引数はSimpleXMLElementでもstringでもOK!
	public static function xmlstr($xml) {
		$dom = new DOMDocument('1.0');
		$dom->loadXML($xml instanceof SimpleXMLElement ? $xml->asXML() : $xml);
		$dom->formatOutput = true;
		return $dom->saveXML();
	}

	public static function xmlobj($xml) {
		$tidy = new tidy;
		$tidy->parseString($xml, array('output-xhtml' => true), 'UTF8');
		$tidy->cleanRepair();

		$body = $tidy->html();
		$body = preg_replace('/\sxmlns="[^"]+"/', '', $body);
		$body = str_replace('&', '&amp;', $body);

		try {
			$xmlobj = new SimpleXMLElement($body);
		}
		catch (Exception $e) {
			throw $e;
		}
		return $xmlobj;
	}

	public static function arrayToXML($array, &$node) {
		foreach($array as $key => $value) {
			// 属性のとき
			if ($key == '@attributes') {
				MXml::addAttributes($value, $node);
				continue;
			}
			
			// 現ノード内に配列がある場合は再帰的に処理
			if (is_array($value)) {
				foreach($value AS $subkey => $subValue){
					
					// 数字のとき自身のキーをスキップして現在キーに直接要素を挿入
					$is_number_node = false;
					if(is_numeric($subkey)){
						$is_number_node = true;
						$somenode = $node->addChild($key);
						MXml::arrayToXML($subValue, $somenode);
					}
				}
				
				if($is_number_node){
					// 次の要素へスキップ
					continue;
				}
				else{
					$subnode = $node->addChild($key);
					MXml::arrayToXML($value, $subnode);
				}
			}
			// 配列がない場合は子ノードと値を設定
			else {
				$node->addChild($key, $value);
			}
		}
	}

	private function addAttributes($array, &$node) {
		foreach ($array as $key => $value) {
			$node->addAttribute($key,$value);
		}
	}

	public function convert_xml($root, $req) {
		$root_node = new SimpleXMLElement("<{$root}></{$root}>");
		MXml::arrayToXML($req, $root_node);

		return $root_node->asXML();
	}
}

