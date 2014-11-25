<?php

class OliceXMLSplitter {

	/**
	 * The lang attribute to put in the destination files
	 * @var string
	 */
	protected $lang = 'en';

	/**
	 * The XML source file 
	 * @var string
	 */
	protected $src;
	
	/**
	 * The destination path 
	 * @var string
	 */
	protected $destPath;
	
	/**
	 * Sets the lang attribute to place in the destination file
	 */
	public function setLang($lang) {
		$this->lang = $lang;
		return $this;
	}
	
	/**
	 * Sets the path to the source XML file
	 * @param string $path
	 */
	public function setSrcXml($file) {
		$this->src = $file;
		return $this;
	}
	
	
	/**
	 * Sets the destination path where the XML files will be placed
	 * @param string $path
	 */
	public function setDestPath($path) {
		$this->destPath = $path;
		return $this;
	}
	
	
	private function addChildNode(SimpleXMLElement $to, SimpleXMLElement $from) {
    $toDom 	 = dom_import_simplexml($to);
    $fromDom = dom_import_simplexml($from);
    $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
	}
	
	
	/**
	 * Split the files
	 */
	public function export() {
		$xml = new SimpleXMLElement(file_get_contents($this->src));
		
		$helper = new OliceXmlHelper();
		
		foreach($xml->children() as $content) {
		
			// Get ID attribute from <content> element of source file
			$attr   = $content->attributes();
			$pageId = (string)$attr['id'];
			
			// Generate destination filename in translation folder
			$parts = explode('_', $pageId);
			$last  = end($parts);
			$file  = $this->destPath . implode('/', $parts) . '/' . $last . '.xml';
		
			
			// Create new XML element - new file for each <content> element in source file 
			$newXml = new SimpleXMLExtended('<player></player>');
			
			// Set lang attribute
			$attr = $content->attributes();
			$attr['lang'] = $this->lang;
			
			$this->addChildNode($newXml, $content);
			$helper->exportXml($newXml, $file);
		}
		
	}
}