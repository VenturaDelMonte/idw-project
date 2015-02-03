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
	//ricerchiamo qualsiasi tipo di nodo nel documento che ha un attributo class il cui valore
	//è "table_dati". selezioniamo poi tutti i discendenti td di tale nodo che abbiano un attributo
	//class il cui valore sia "name". Ci posizioniamo sulle ancora dei nodi trovati.
	$res = $xpath->query('//*[@class="table_dati"]//td[@class="name"]/a');
	
	foreach ($res as $e) 
	{	
		
		$index = new stdObject(['name' => clean($e->textContent)]);
		$url = $base . str_replace('summary', 'home', $e->getAttribute('href'));
		$flag = true;
		$indices->insert($index);
		//file_put_contents('log_emi.txt', print_r($e->getAttribute('href'), true));
		//break;
		
		while (true)
		{
			$xpa = scrapePage($url, true);

			//ricerchiamo qualsiasi tipo di nodo nel documento che ha un attributo class il cui valore
			//è "table_dati".Selezioniamo poi il primo elemento td e il secondo elemento
			// figlio di tali nodi. 
			$list = $xpa->query('//*[@class="table_dati"]//td[1]|//*[@class="table_dati"]//td[2]');

			if ($list->length == 0)
			{
				$flag = false;
				break;
			}
			for ($i = 0; $i < $list->length; $i++) 
			{
				//file_put_contents('log_emi.txt', print_r($list->item($i++)->textContent, true));
				
				$sym = clean($list->item($i++)->textContent);
				$name = clean($list->item($i)->nodeValue); 
				
				$asset = new stdObject();
				$asset->name = $name;
				$asset->sym = $sym;
				$asset->index = (string) $index->_id;

				$assets->insert($asset);
			}

			//ricerchiamo qualsiasi tipo di nodo nel documento che ha un attributo id
			//il cui valore è "pi-colonna1-display". Selezioniamo poi il primo elemento div 
			//figio della selezione precedene. A partire da questo nodo, selezioniamo il 
			//secondo elemento p. Ci posizioniamo sui figli a il cui attributo title è "Next"
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