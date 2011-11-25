<?php
require_once dirname(__FILE__)."/QueryAnalyzer.php";

class MySQLConnector {

	private $connection;
	private $host;
	private $dbName;
	private $user;
	private $password;

	public function __construct($host, $dbName, $user, $password) {
		$this->host = $host;
		$this->dbName = $dbName;
		$this->user = $user;
		$this->password = $password;
	}

	public function query($sql){

		$this->openConnection();
		$result = mysql_query($sql, $this->connection); 
		if (mysql_errno($this->connection)) {
		    throw new Exception("Error while executing query: " . $sql);
		}
		if($this->isSelectQuery($sql)) {
			$rows = array();
			while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
			    $rows[] = $row;
			}
			$this->closeConnection();
			return $rows;
		} else
			return true;
	}

	public function emptyDatabase(){
		$tables = $this->getTables();
		foreach($tables as $table){
			$this->query("DROP TABLE {$table}");
		}
	}

	private function getTables(){
		$this->openConnection();
		$result = mysql_query("SHOW TABLES", $this->connection); 
		if (mysql_errno($this->connection)) {
		    throw new Exception("Error while executing query: " . $sql);
		}
		$rows = array();
		while ($row = mysql_fetch_assoc($result)) {
		    $rows[] = $row['Tables_in_mysqlconnector'];
		}

		$this->closeConnection();

		return $rows;
	}


	private function isSelectQuery($sql){
		$lowerCase = strtolower($sql);
		if (preg_match('/select/',$lowerCase))
			return true;
		else
			return false;
	}

	private function openConnection (){
		$connect = mysql_connect($this->host, $this->user, $this->password);
		if($connect == false)
			throw new Exception('Cannot connect to mysql database');
		else{
			$this->connection = $connect;
			mysql_select_db($this->dbName, $this->connection);
		}
	}

	private function closeConnection (){
		$closed = mysql_close ($this->connection);
		if($closed == false)
			throw new Exception('Cannot close connection to mysql database');
	}

}	
