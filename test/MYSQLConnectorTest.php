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
		$this->connection->query("CREATE TABLE tabla(campo1 varchar(100),campo2 varchar(100))");
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

	public function test_double_insert(
	) {
		$this->connection->addAutoInc("tabla");
		$this->connection->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->connection->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2"),
				array("id" => 2, "campo1" => "valor1", "campo2" =>"valor2")
			),
			$this->connection->query("SELECT * FROM tabla")
		);
	}

	public function test_select(
	) {
		$this->connection->addAutoInc("tabla");
		$this->connection->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->connection->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor3', 'valor2')");
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2")
			),
			$this->connection->query("SELECT * FROM tabla WHERE campo1='valor1'")
		);
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2")
			),
			$this->connection->query("SELECT * FROM tabla WHERE campo1='valor1' AND campo2='valor2'")
		);
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2"),
				array("id" => 2, "campo1" => "valor3", "campo2" =>"valor2")
			),
			$this->connection->query("SELECT * FROM tabla WHERE campo2='valor2'")
		);
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2")
			),
			$this->connection->query("SELECT * FROM tabla WHERE id=1")
		);
		$this->assertEquals(
			array(),
			$this->connection->query("SELECT * FROM tabla WHERE campo1='valor1' AND campo2='valor3'")
		);
	}

}	
