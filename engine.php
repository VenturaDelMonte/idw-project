<?php
	header('Content-type: application/json');
	require_once('utils.php');
	require_once('mongo_helper.php');

	function loadWikipedia($data)
	{
		$mongo = new MongoHelper();
		$db = $mongo->idw;
		$assets = $db->assets;
		$query = new stdObject();
		$query->_id = new MongoId($data);
		$cursor = $assets->find($query);

		foreach ($cursor as $asset) 
		{ 
			$what = str_replace(["...", "\n", "S.P.A", ",Inc"], "", $asset['name']);
			
		}
		

		$query_url = "http://en.wikipedia.org/w/api.php?action=opensearch&search=" . urlencode($what);

		$session = curl_init($query_url);
    	curl_setopt($session, CURLOPT_RETURNTRANSFER, true);

    	$json = curl_exec($session);
    	curl_close($session);

    	$url = json_decode($json)[3][0];
  		$xpath = scrapePage($url);

		$ret = "";
		foreach ($xpath->query('//*[@id="mw-content-text"]/table[contains(@class,"infobox")]') as $node)
		{
			$ret .= nodeContent($node, true);
		} 
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

		$query_url = "http://query.yahooapis.com/v1/public/yql?q=";
		//$query_url .= urlencode(sprintf("select * from html where url='http://finance.yahoo.com/q?s=%s' " 
		//									. "and xpath='//*[@id=\"yfi_investing_content\"]/div[2]|//*[@id=\"yfi_quote_summary_data\"]'", $what));


		$query_url .= urlencode('select * from yahoo.finance.quotes where symbol in ("'.$what.'")');//urlencode("select * from yahoo.finance.quote where symbol in (\"$what\")");

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
		$res = $xpath->query('//*[@id="gf-viewc"]/div/div[2]/div[2]/div');
		$ret = "";
		foreach ($res as $a)
		{
			$ret .= nodeContent($a, true);
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

	$json = file_get_contents('php://input');
	$obj = json_decode($json);
	
	$fn = $obj->id;

	@print(json_encode(call_user_func($fn, $obj->data)));

?>