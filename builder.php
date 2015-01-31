<?php

header('Content-type: application/json');

require_once('mongo_helper.php');


require_once('utils.php');


$mongo = new MongoHelper();
$db = $mongo->idw;
$indices = $db->indices;
$assets = $db->assets;

function clean($str)
{
	return trim(str_replace("\n", "", $str));
}

if (true) {
	$base = 'http://www.londonstockexchange.com';
	$source = 'http://www.londonstockexchange.com/exchange/prices-and-markets/international-markets/indices/home.html';
	$ret = [];
	$xpath = scrapePage($source);
	$res = $xpath->query('//*[@class="table_dati"]//td[@class="name"]/a');
	
	foreach ($res as $e) 
	{	
		$index = new stdObject(['name' => clean($e->textContent)]);
		$url = $base . str_replace('summary', 'home', $e->getAttribute('href'));
		$flag = true;
		$indices->insert($index);
		
		while (true)
		{
			$xpa = scrapePage($url, true);
			$list = $xpa->query('//*[@class="table_dati"]//td[1]|//*[@class="table_dati"]//td[2]');
			if ($list->length == 0)
			{
				$flag = false;
				break;
			}
			for ($i = 0; $i < $list->length; $i++) 
			{
				$sym = clean($list->item($i++)->textContent);
				$name = clean($list->item($i)->nodeValue); 
				
				$asset = new stdObject();
				$asset->name = $name;
				$asset->sym = $sym;
				$asset->index = (string) $index->_id;

				$assets->insert($asset);
			}

			$next = $xpa->query('//*[@id="pi-colonna1-display"]/div[1]/p[2]/a[@title="Next"]');
			if ($next->length == 0)
				break;
			$a = $next->item(0);
			$url = $base . $a->getAttribute('href');
			print($url."\n");
		}
		if (!$flag)
		{
			$indices->remove(array('name' => $index->name));
		}
		
	}

}


?>