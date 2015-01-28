<?php
	header('Content-type: application/json');
	define('REBUILD_DATAWAREHOUSE', false);
	require_once('mongo_helper.php');
	
	class stdObject {
		public function __construct(array $arguments = array()) {
			if (!empty($arguments)) {
				foreach ($arguments as $property => $argument) {
					$this->{$property} = $argument;
				}
			}
		}

		public function __call($method, $arguments) {
			$arguments = array_merge(array("stdObject" => $this), $arguments); // Note: method argument 0 will always referred to the main class ($this).
			if (isset($this->{$method}) && is_callable($this->{$method})) {
				return call_user_func_array($this->{$method}, $arguments);
			} else {
				throw new Exception("Fatal error: Call to undefined method stdObject::{$method}()");
			}
		}
	}
	
	function scrapePage($source)
	{
		$curl = curl_init();
		$doc = new DOMDocument();
		$tidy = new tidy();

		curl_setopt($curl, CURLOPT_URL, $source);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
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

	function loadIndices($data)
	{

		$mongo = new MongoHelper();
		$db = $mongo->idw;
		$indices = $db->indices;
		$assets = $db->assets;

		$ret = [];

		if (REBUILD_DATAWAREHOUSE) {

			$source = 'http://www.londonstockexchange.com/exchange/prices-and-markets/international-markets/indices/home.html';
			$ret = [];
			$xpath = scrapePage($source);
			$res = $xpath->query('//*[@class="table_dati"]//td[@class="name"]/a');
			
			foreach ($res as $e) 
			{	
				$index = new stdObject(['name' => $e->textContent]);
				$url = 'http://www.londonstockexchange.com' . str_replace('summary', 'home', $e->getAttribute('href'));
				$indices->insert($index);
				$xpa = scrapePage($url);
				$ret[] = $index;
				$list = $xpa->query('//*[@class="table_dati"]//td[1]|//*[@class="table_dati"]//td[2]');
				
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
			}

		}
		else
		{
			$cursor = $indices->find();
			foreach ($cursor as $obj) 
			{
				$ret[] = $obj;
			}
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
		$cursor = $assets->find($query, ['name' => 1]);

		foreach ($cursor as $obj)
		{
			$ret[] = $obj['name'];
		}
		return $ret;

	}

	$json = file_get_contents('php://input');
	$obj = json_decode($json);
	
	$fn = $obj->id;

	@print(json_encode(call_user_func($fn, $obj->data)));

?>