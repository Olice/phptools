<?php

class OliceTranslation {

	/** 
	 * The path to the source files
	 * @var string
	 */
	protected $srcPath;
	
	/**
	 * The path for the output HTML
	 * @var string
	 */
	protected $htmlPath;
	
	/**
	 * XML file for output
	 * @var SimpleXMLElement
	 */
	protected $xml;
	
	/**
	 * Test mode
	 * @var boolean
	 */
	protected $testMode = false;
	
	/**
	 * Constructor 
	 */
	public function __construct() {
		// Create a new XML output file
		$this->xml 			= new SimpleXMLExtended('<player></player>');
	}

	public function log($str) {
		echo '<li>' . $str;
	}
	
	/**
	 * Whether to run in test mode.  If so, some random text is placed in each element
	 * @param boolean $bool
	 */
	public function setTestMode($bool) {
		$this->testMode = (boolean)$bool;
		return $this;
	}


	
	
	/**
	 * Gets random string of text for test purposes
	 * @return string
	 */ 
	private function getRandomText() {
		$text = array(
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
			'Nulla laoreet massa sed sem ultrices, eu ornare tellus tempus. ',
			'Aliquam erat volutpat. ',
			'Aenean venenatis facilisis nibh, eget ultricies augue gravida quis.',
			'Vestibulum quis rhoncus diam. Sed dictum purus ac turpis pharetra euismod. ',
			'Proin nec iaculis nisl. ',
			'Curabitur mattis nisl condimentum quam fermentum, vitae vehicula nulla tempus.',
			'Phasellus sem velit, mattis sed justo consequat, condimentum aliquam nisl. ',
			'Suspendisse posuere tellus non auctor cursus.'
		);
		return $text[array_rand($text)];
	}
	
	
	/**
	 * Set the HTML destination directory 
	 * @param string $path
	 * @return this
	 */
	public function setHtmlPath($path) {
		$this->htmlPath = $path;
		return $this;
	}
	
	
	/**
	 * Sets the path to the source HTML files
	 * @param string
	 */
	public function setSrcPath($path) {
		$this->srcPath = $path;
		return $this;
	}
	
	/**
	 * Scans the src path for HTML files - returns array of file paths
	 * @return array
	 */
	public function findHtmlFiles() {
		$dir 			= new RecursiveDirectoryIterator($this->srcPath);
		$iterator = new RecursiveIteratorIterator($dir);
		$regex 		= new RegexIterator($iterator, '/^.+\.html$/i', RecursiveRegexIterator::GET_MATCH);
		
		$htmlFiles= array();
		foreach($regex as $item) {
		  $file = (string)$item[0];
		  if(strpos($file, '_proc') === false && (!preg_match('/m[0-9]+\.html$/', $file) )) {
				$htmlFiles[] = (string)$file;
				$this->log('Source HTML file: ' . $file);
			}
		}
		return $htmlFiles;
	}
	
	
	/**
	 * Generates a string to use as the page ID from the directory/filename
	 * @param string $file the path
	 * @return string the ID
	 */
	public function getPageId($path) {

		// looks for the first match of page... in the file path
		preg_match('#page[^/]+/(.*)#i', $path, $matches);

		// Convers to ID like page1-3-1_page1-3-1
		return str_replace(array('/', '.html'), array('_', ''), $matches[0]);
	}



	
	/**
	 * Parse the files
	 */
	public function parseFiles() {
		$htmlFiles = $this->findHtmlFiles();

		$helper = new OliceXmlHelper();
		
		foreach($htmlFiles as $i => $file) {

			error_log($file);

			//$this->log('max time' . ini_get('max_execution_time'));
			$this->log('Processing ' . $file);

			// Get the HTML file content
			$pageHtml = file_get_contents($file);
		
		  	// Create XML element child for the new page
		  	$pageId = $this->getPageId($file);

			$xmlPage = $this->xml->addChild('content');
			$xmlPage->addAttribute('id', 	$this->getPageId($file));
			$xmlPage->addAttribute('lang',	'en');
			
			// Load original XML file and merge the content into this new document
			$origXmlFile = str_replace('.html', '.xml', $file);
			$origXmlIds  = array();			// Ids that were present in original XML file
			if(file_exists($origXmlFile)) {	
				$origXml = new SimpleXMLElement(file_get_contents($origXmlFile));
				foreach($origXml->content[0] as $child) {
					$attr = $child->attributes();
					$helper->copyNode($xmlPage, $child);
					$origXmlIds[] = (string)$attr['id'];
				}
			}
						
			// Parse HTML into node tree using DOMDocument
			$dom = new DomDocument();


		


			// See http://stackoverflow.com/questions/11309194/php-domdocument-failing-to-handle-utf-8-characters-%E2%98%86
			
			// If there's already a doctype, ignore
			if(preg_match('/doctype/i', $pageHtml)) {
				error_log('Has doctype');
				$dom->loadHTML($pageHtml);
			}
			// Otherwise, we have an HTML fragment
			else {
				error_log('No doctype');
				$dom->loadHTML('<meta http-equiv="content-type" content="text/html; charset=utf-8">' . $pageHtml);
			}


			// Use DOMXPath to find paragraph tags etc. - these are all block level
			$xpath 		= new DOMXpath($dom);
			$elements = $xpath->query("//td | //th | //p | //h1 | //h2 | //h3 | //h4 | //h5 | //h6 | //li | //div[@id='intro_buttons']//a | //div[@data-caption] | //div[@class='button-text'] | //a[contains(@class ,'interactive-button')] | //input[@value] | //*[@data-text-id] | //label[@data-quiz='answer']/span | //div[@class='button_text']"); 
			
			foreach($elements as $i => $element) {
	
				// Add the ability to ignore a node
				if($element->hasAttribute('data-text-ignore')) {
					continue;
				}

				// The node's current HTML ID - used to create an ID in the XML for this piece of text
				$id = $element->getAttribute('id');
				$id = empty($id) ? 'text-' . $i : $id;

				// Set new data attribute 
				$element->setAttribute('data-text-id', $id);

				// error_log($element->tagName . ' ID: ' . $id.  ' ' . $element->textContent);

				// For DIV's with data caption attribute only, these are processed differently 
				// because the text is in the attribute
				if($element->getAttribute('data-caption')) {

					// Use CData for escaping
					$xmlChild = $xmlPage->addChild('text');
					$xmlChild->addCData($element->getAttribute('data-caption'));
					$xmlChild->addAttribute('id', $id);
					
					// CHange caption so we know it's been translated
					$element->setAttribute('data-caption', '-- Translated --');

					continue;
				}

				// Don't re-add the same element found via DOMDocument that we already
				// added above from the original XML file
				if(!in_array($id, $origXmlIds)) {
				
					// This is the text only without any tags
					$textContent = $element->textContent;

					// This is the HTML content including parent tag
					$htmlContent = $dom->saveHTML($element);

					// Input fields - get value attribue
					if($element->tagName == 'input') {
						$innerContent = $element->getAttribute('value');
					}
					// Get inner content of tag with child nodes
					//elseif($element->hasChildNodes()) {
					//	error_log(get_class($element));
					// See http://php.net/manual/en/domnode.haschildnodes.php

					// If script gets stuck here, it's probably due to an 
					// unsupported nesting of elements matched by the xpath expression\
					// eg. a P inside a TD, both of which are matched
					// add the data-text-ignore to the parent, eg the TD 

					elseif($element->childNodes) {
						$innerContent = '';
						foreach($element->childNodes as $child) {
							$innerContent .= $dom->saveHTML($child);
						}
					}
					// Any other item - get text content
					else {
						$innerContent = $element->textContent;
					}
				
					// Test mode - get random content
					if($this->testMode) {
						$innerContent = $this->getRandomText();
					}
	
					// Trim content for neatness
					$innerContent = trim($innerContent);
	
					// Use CData for escaping
					$xmlChild = $xmlPage->addChild('text');
					$xmlChild->addCData($innerContent);
					$xmlChild->addAttribute('id', $id);
				}

				// Remove original English text content from output file so we can tell which bits have
				// been left out of the content.xml translation file
				switch($element->tagName) {

					// Input tags - the text was in the 'value' attribute
					case 'input' :
						$element->setAttribute('value', '-- Translated --');
						break;

					// Default - text was in nodeValue
					default:
						$element->nodeValue = '-- Translated --';
				}
	
			}


			
			// Create filename for new HTML file in new directory
			$htmlFile = $this->htmlPath . str_replace($this->srcPath, '', $file);

			$this->log('Writing to ' . $htmlFile);
			
			// Write new HTML file 
			$dom->preserveWhiteSpace = false;
			$dom->formatOutput = true;
			$htmlModified = $dom->saveHTML();
			file_put_contents($htmlFile, $htmlModified);

			$this->log('File written OK ' . $htmlFile);
			
		}
	}
	
	
	public function exportXml($dest) {
		$helper = new OliceXmlHelper();
		$helper->exportXml($this->xml, $dest);
	}
}