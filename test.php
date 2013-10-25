<?php

include 'Tenebras/Utils/Html/Node.php';
include 'Tenebras/Utils/Html/TextNode.php';
include 'Tenebras/Utils/Html/TagNode.php';
include 'Tenebras/Utils/Html/Document.php';

// 1. Load document
$document = new \Tenebras\Utils\Html\Document('http://www.smashingmagazine.com/');

// 2. Find all titles from home page
$titles = $document->find("h2 a");

//3. Print articles list
echo '<h2>Articles from '.$document->getUrl().'</h2>';
echo '<ul>';

foreach( $titles as $node )
{
	echo '<li><a href="'.$node->attr('href').'">'.$node->getText().'</a></li>';
}

echo '</ul>';

// 4. Show dumped page structure
echo '<h2>Document structure</h2>';
echo $document->dump();