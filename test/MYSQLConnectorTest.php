<?php
require_once dirname(__FILE__).'/../src/MySQLConnector.php';

class DBMysqlConnectorTest extends PHPUnit_Framework_TestCase {

	private $connection;

	public function setUp(){
		$config = parse_ini_file('config.inc',true);
		$dbConfig = $config['DATABASE_CONFIG'] ;
		
		$this->connection = new MySQLConnector (
			$dbConfig['bd.host'],
			$dbConfig['bd.name'],
			$dbConfig['bd.user'],
			$dbConfig['bd.pass']
		);

		$this->connection->emptyDatabase();
	}

	public function insert_cases(
	) {
		return array(
			"normal query" => array(
				"INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')",
				"SELECT * FROM tabla",
				array(array("campo1" => "valor1", "campo2" =>"valor2"))
			),
			"empty query" => array(
				"INSERT INTO tabla() VALUES ()",
				"SELECT * FROM tabla",
				array(array("campo1" => "", "campo2" => ""))
			)
		);
	}

	/**
	 * @dataProvider insert_cases
	 */
	public function test_insert(
		$insertQuery,
		$selectQuery,
		$expectedReturn
	) {
		$this->connection->query($insertQuery);
		$this->assertEquals($expectedReturn, $this->connection->query($selectQuery));
	}

}	
