<?php
	header('Content-type: application/json');
	require_once('utils.php');
	require_once('mongo_helper.php');

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