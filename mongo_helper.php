<?php

class MongoHelper extends MongoClient {
	public function __construct() 
	{
		$mongo_host='localhost:27017';
		$mongo_db=''; 
		$mongo_user='admin';
		$mongo_pwd='admin';
		parent::__construct("mongodb://$mongo_user:$mongo_pwd@$mongo_host$mongo_db");
	}
}

?>