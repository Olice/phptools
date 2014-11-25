<?php

class OliceXmlHelper {

	/**
	 * Export a SimpleXMLElement as an XML file including nice formatting
	 * @param SimpleXMLElement $xml
	 * @param string $dest
	 */
	private function export(SimpleXMLElement $xml, $dest, $mode = 'xml') {
		// Save nicely formatted file
		$dom = dom_import_simplexml($xml)->ownerDocument;
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$xmlStr = $mode == 'html' ? $dom->saveHTML() : $dom->saveXML();
		file_put_contents($dest, $xmlStr);
	}
	
	public function exportXml(SimpleXMLElement $xml, $dest) {
		$this->export($xml, $dest, 'xml');
	}
	
	public function exportHtml(SimpleXMLElement $xml, $dest) {
		$this->export($xml, $dest, 'html');
	}
	
	public function copyNode(SimpleXMLElement $to, SimpleXMLElement $from) {
    $toDom 	 = dom_import_simplexml($to);
    $fromDom = dom_import_simplexml($from);
    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
	}
	
}
