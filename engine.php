<?php
	header('Content-type: application/json; charset=utf-8');
	require_once('utils.php');
	require_once('mongo_helper.php');

	function loadWikipedia($data)
	{
		//data is an id
		$mongo = new MongoHelper();
		$db = $mongo->idw;
		$assets = $db->assets;
		$query = new stdObject();
		/*Creates a new id for database objects
		$data is the string used as the id*/
		$query->_id = new MongoId($data);
		$cursor = $assets->find($query);

		foreach ($cursor as $asset) 
		{ 
			$what = str_replace([".", ",", "\n", "_", "S.P.A", ",Inc", ", Inc"], " ", $asset['name']);
			$what = trim($what);
		}
		
		/*Get pages whose name matches a given string. When limit is reached, results are ordered 
		by number of incoming links. See: http://www.mediawiki.org/wiki/API%3aOpensearch*/
		$query_url = "http://en.wikipedia.org/w/api.php?action=opensearch&search=" . urlencode($what);

		
		/* Initialize a cURL session*/
		$session = curl_init($query_url);
		/*CURLOPT_RETURNTRANSFER: TRUE to return the transfer as a string of the 
		return value of curl_exec() instead of outputting it out directly. */
    	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    	/*Convert curl_exec output to UTF8*/
    	//json = 
    	//["Activision Blizzard  Inc",["Activision Blizzard Inc."],[""],["http://en.wikipedia.org/wiki/Activision_Blizzard_Inc."]]
    	//[query, [titoli articoli trovati in base alla query], 
    	//        [breve descrizione degli articoli trovati in base alla query], 
    	//        [url degli articoli trovati in base alla query]]
    	$json = curl_exec_utf8($session);
 
    	curl_close($session);

    	$url = json_decode($json)[3][0];
  		$xpath = scrapePage($url);

		$ret = "";
		// ricerchiamo qualsiasi tipo di nodo nel documento che ha un attributo id
		// e il cui valore sia 'mw-content-text'.
		// ci posizioniamo sul figlio table e selezioniamo quello il cui attributo classe
		// contiene il valore infobox
		foreach ($xpath->query('//*[@id="mw-content-text"]/table[contains(@class,"infobox")]') as $node)
		{
			$ret .= nodeContent($node, true);
		} 
		/*Perform a regular expression search and replace*/
		$ret = preg_replace("/href=\"(.*)\"/iU", " ", $ret);
		$ret = preg_replace("/<a/iU", "<span", $ret);
		return new stdObject(['data' => $ret, 'url' => $url]);
	}

	function loadTrends($data)
	{
		$mongo = new MongoHelper();
		$db = $mongo->idw;
		$assets = $db->assets;
		$query = new stdObject();
		$query->_id = new MongoId($data);
		$cursor = $assets->find($query);

		foreach ($cursor as $asset) 
		{ 
			$what = $asset['sym'];	
		}

		$xpath=scrapePage("http://www.bigcharts.com/quickchart/quickchart.asp?symb=$what&insttype=Stock");
		//ricerchiamo qualsiasi tipo di nodo nel documento che ha un attributo class
		//abbia come valore "padded vatop" e ci posizioniamo sul figlio img
		$res = $xpath -> query("//*[@class=\"padded vatop\"]/img");
    	return($res->item(0)->getAttribute("src"));
	}

	function loadYahooFinance($data)
	{
		$mongo = new MongoHelper();
		$db = $mongo->idw;
		$assets = $db->assets;
		$query = new stdObject();
		$query->_id = new MongoId($data);
		$cursor = $assets->find($query);

		foreach ($cursor as $asset) 
		{ 
			$what = $asset['sym'];	
		}

		/*For YQL see: https://developer.yahoo.com/yql/guide/running-chapt.html*/
		$query_url = "http://query.yahooapis.com/v1/public/yql?q=";
		//$query_url .= urlencode(sprintf("select * from html where url='http://finance.yahoo.com/q?s=%s' " 
		//									. "and xpath='//*[@id=\"yfi_investing_content\"]/div[2]|//*[@id=\"yfi_quote_summary_data\"]'", $what));


		$query_url .= urlencode("select * from yahoo.finance.quotes where symbol = \"$what\"");//urlencode("select * from yahoo.finance.quote where symbol in (\"$what\")");

		$query_url .= "&format=json&env=store://datatables.org/alltableswithkeys";

		$session = curl_init($query_url);
    	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    	$json = json_decode(curl_exec($session));

    	curl_close($session);

    	$ret = [];

    	foreach ($json->query->results->quote as $k => $v)
    	{
    		if (!is_null($v))
    			$ret[$k] = $v;
    	}
    	return new stdObject(['data' => $ret]);
	}


	function loadYahooHistoricalData($data)
	{
		$mongo = new MongoHelper();
		$db = $mongo->idw;
		$assets = $db->assets;
		$query = new stdObject();
		$query->_id = new MongoId($data->name);
		$cursor = $assets->find($query);

		foreach ($cursor as $asset) 
		{ 
			$what = $asset['sym'];	
		}

		$query_url = "http://query.yahooapis.com/v1/public/yql?q=";
		//$query_url .= urlencode(sprintf("select * from html where url='http://finance.yahoo.com/q?s=%s' " 
		//									. "and xpath='//*[@id=\"yfi_investing_content\"]/div[2]|//*[@id=\"yfi_quote_summary_data\"]'", $what));

		$start_data = $data->startDate; //"2009-09-11";
		$end_data = $data->endDate; //"2010-03-10";

		$query_url .= urlencode("select * from yahoo.finance.historicaldata where symbol = \"$what\" and startDate = \"$start_data\" and endDate = \"$end_data\"");

		$query_url .= "&format=json&env=store://datatables.org/alltableswithkeys";

		$session = curl_init($query_url);
    	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    	$json = json_decode(curl_exec($session));
    	
    	curl_close($session);

    	//return new stdObject([$json]);
    	return new stdObject([$json->query->results->quote]);
	}

	function loadGoogleNews($data)
	{

		$mongo = new MongoHelper();
		$db = $mongo->idw;
		$assets = $db->assets;
		$query = new stdObject();
		$query->_id = new MongoId($data);
		$cursor = $assets->find($query);

		foreach ($cursor as $asset) 
		{ 
			$what = $asset['sym'];	
		}

		$query = "https://www.google.com/finance/company_news?q=$what";

		$xpath = scrapePage($query);
/*		$res = $xpath->query('//*[@id="gf-viewc"]/div/div[2]/div[2]/div');
		$ret = "";
		foreach ($res as $a)
		{
			$ret .= nodeContent($a, true);
		}
		return $ret;
*/
		// ricerchiamo qualsiasi tipo di nodo nel documento che ha un attributo id
		// il cui valore è "news-main" e ci posizioniamo sui div figli che 
		// contengono le notizie
		$res = $xpath->query('//*[@id="news-main"]/div');
		$ret = [];
		foreach ($res as $a)
		{
			$ret[] = nodeContent($a, true);
			
		}
		
		return $ret;
	}


	function loadIndices($data)
	{
		$mongo = new MongoHelper();
		$db = $mongo->idw;
		$indices = $db->indices;
		$ret = [];
		$cursor = $indices->find();
		foreach ($cursor as $obj) 
		{
			$ret[] = $obj;
		}

		return $ret;
	}

	function loadAssets($data)
	{
		$mongo = new MongoHelper();
		$db = $mongo->idw;
		$assets = $db->assets;
		$query = new stdObject();
		$query->index = new MongoRegex('/'.utf8_encode("$data").'/i');
		$ret = [];
		$cursor = $assets->find($query);

		foreach ($cursor as $obj)
		{
			$ret[] = new stdObject(['name' => $obj['name'], 'id' => $obj['_id']]);
		}
		return $ret;

	}

	function search($data)
	{
		$mongo = new MongoHelper();
		$db = $mongo->idw;
		$assets = $db->assets;
		$indices = $db->indices;
		$ret = [];
		$cursor = $assets->find(new stdObject(["name" => new MongoRegex("/^$data/i")]));
		foreach ($cursor as $obj) 
		{
			//$ret[] = $obj;
			$tmp = new stdObject();
			$tmp->name = $obj["name"];
			$tmp->_id = $obj["_id"];
			$cr = $indices->find(new stdObject(["_id" => new MongoId($obj["index"])]));
			foreach ($cr as $v) 
			{
				$tmp->market = $v["name"];
				$tmp->market_id =$v["_id"];
			}
			$ret[] = $tmp;
		}
		return ($ret);
	}

	
	$json = file_get_contents('php://input');
	$obj = json_decode($json);
	

	// check if $obj->id is a valid function -- SANITIZE !!!!

	@print(json_encode(call_user_func($obj->id, $obj->data)));


?>