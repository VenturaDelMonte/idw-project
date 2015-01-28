<?php

header('Content-type: application/json');

require_once('mongo_helper.php');


require_once('utils.php');

function scrapePage($source)
{
	$curl = curl_init();
	$doc = new DOMDocument();
	$tidy = new tidy();

	curl_setopt($curl, CURLOPT_URL, $source);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.91 Safari/537.36");
	curl_setopt($curl, CURLOPT_FAILONERROR, true);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($curl, CURLOPT_AUTOREFERER, true);
	curl_setopt($curl, CURLOPT_HEADER, false);
	curl_setopt($curl, CURLOPT_TIMEOUT, 10);
	$result = curl_exec($curl);
	
	curl_close($curl);

	$clean = $tidy->repairString($result);
	$doc->strictErrorChecking = false;
	$doc->recover = true;
	$doc->loadHTML($clean);

	return new DOMXPath($doc);
}

$mongo = new MongoHelper();
$db = $mongo->idw;
$indices = $db->indices;
$assets = $db->assets;

if (true) {
	$base = 'http://www.londonstockexchange.com';
	$source = 'http://www.londonstockexchange.com/exchange/prices-and-markets/international-markets/indices/home.html';
	$ret = [];
	$xpath = scrapePage($source);
	$res = $xpath->query('//*[@class="table_dati"]//td[@class="name"]/a');
	
	foreach ($res as $e) 
	{	
		$index = new stdObject(['name' => $e->textContent]);
		$url = $base . str_replace('summary', 'home', $e->getAttribute('href'));
		$flag = true;
		$indices->insert($index);
		
		while (true)
		{
			$xpa = scrapePage($url);
			$list = $xpa->query('//*[@class="table_dati"]//td[1]|//*[@class="table_dati"]//td[2]');
			if ($list->length == 0)
			{
				$flag = false;
				break;
			}
			for ($i = 0; $i < $list->length; $i++) 
			{
				$sym = $list->item($i++)->textContent;
				$name = $list->item($i)->nodeValue; 
				
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