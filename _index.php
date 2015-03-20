<?php 


/*
A script to Automatically create the English version 
- Update templates
- Any tag without an ID, and with some text, give it an ID and put the text in the XML file 
- XML file per page

- Read in the HTML, automatically generate XML, HTML files might be in sub-folders, 1 level deep

Logic is:
- If it has an ID, leave it as is, and the attribute
- If not, make one up, and set both the ID and attribute 

- Maybe one big XML file in per language 


<body data-page=“1”>
<page id=“1”>
          <content id=“interactive-1”>
</page>

*/


ob_start();

error_reporting(E_ALL);

// Ensure we don't run out of time
set_time_limit(120);


// Load in class files
include 'classes/SimpleXMLExtended.php';
include 'classes/OliceTranslation.php';
include 'classes/OliceXmlSplitter.php';
include 'classes/OliceXmlHelper.php';


$module = 6;

$src = 'm' . $module . '/';
$dest= 'm' . $module . '-exported/';


// Reads in HTML files, automatically extracts content from P, H1-H6, LI tags and places
// in XML file.  Automatically adds data attribute to source HTML file
$olice = new OliceTranslation();
$olice->setSrcPath($src);
$olice->setHtmlPath($dest);
$olice->parseFiles();
$olice->exportXml($dest . 'content.xml');



ob_flush();
/*
// Split an XML file out into individual files for each lang
$splitter = new OliceXmlSplitter();
$splitter->setSrcXml('modules/content_es.xml')
				 ->setDestPath('modules/content_es/')
			   ->setLang('es');
$splitter->export();
*/

