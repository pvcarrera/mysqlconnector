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

	public function test_count(
	) {
		$expected = array(array("COUNT(*)" => "0"));
		$this->assertEquals($expected, $this->connection->query("SELECT COUNT(*) FROM tabla"));
		$this->connection->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$expected = array(array("COUNT(*)" => "1"));
		$this->assertEquals($expected, $this->connection->query("SELECT COUNT(*) FROM tabla"));
		$this->connection->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$expected = array(array("COUNT(*)" => "2"));
		$this->assertEquals($expected, $this->connection->query("SELECT COUNT(*) FROM tabla"));
	}

	public function max_cases(
	) {
		return array(
			"max of ids" => array(
				"tabla",
				array(
					"INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 23)",
					"INSERT INTO tabla(campo1, campo2) VALUES ('valor3', 21)"
				),
				"SELECT MAX(id) FROM tabla",
				array(array("MAX(id)" => 2))
			),
			"max of numeric field" => array(
				"tabla",
				array(
					"INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 23)",
					"INSERT INTO tabla(campo1, campo2) VALUES ('valor3', 21)"
				),
				"SELECT MAX(campo2) FROM tabla",
				array(array("MAX(campo2)" => 23))
			),
			"max of alphabetic field" => array(
				"tabla",
				array(
					"INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 23)",
					"INSERT INTO tabla(campo1, campo2) VALUES ('valor3', 21)"
				),
				"SELECT MAX(campo1) FROM tabla",
				array(array("MAX(campo1)" => "valor3"))
			),
			"max when empty rows" => array(
				"tabla",
				array(
					"INSERT INTO tabla() VALUES ()"
				),
				"SELECT MAX(id) FROM tabla",
				array(array("MAX(id)" => 1))
			),
			"max when empty rows (b)" => array(
				"tabla",
				array(
					"INSERT INTO tabla() VALUES ()"
				),
				"SELECT MAX(id) as id FROM tabla",
				array(array("id" => 1))

			)
		);
	}

	/**
	 * @dataProvider max_cases
	 */
	public function test_max(
		$tableName,
		$insertQueries,
		$selectQuery,
		$expectedResult
	) {
		$this->connection->addAutoInc($tableName);
		foreach ($insertQueries as $query) {
			$this->connection->query($query);
		}
		$this->assertEquals($expectedResult, $this->connection->query($selectQuery));
	}

	public function test_values_with_quotes(
	) {
		$this->connection->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 23)");
		$expected = array(array("COUNT(*)" => "1"));
		$this->assertEquals($expected, $this->connection->query("SELECT COUNT(*) FROM tabla WHERE campo2=23"));
		$this->assertEquals($expected, $this->connection->query("SELECT COUNT(*) FROM tabla WHERE campo2='23'"));
	}

	public function test_add_fields(
	) {
		$this->connection->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 23)");
		$this->assertEquals(
			array(
				array("campo1" => "valor1", "campo2" => "23")
			),
			$this->connection->query("SELECT * FROM tabla")
		);
		$this->connection->query("ALTER TABLE tabla ADD campo3 VARCHAR(30)");
		$this->connection->query("ALTER TABLE tabla ADD campo4 VARCHAR(30)");
		$this->assertEquals(
			array(
				array("campo1" => "valor1", "campo2" => "23", "campo3" => null, "campo4" => null)
			),
			$this->connection->query("SELECT * FROM tabla")
		);
	}

	
	public function test_update(
	) {
		$this->connection->addAutoInc("tabla");
		$this->connection->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->connection->query("INSERT INTO tabla(campo1, campo2) VALUES ('valor1', 'valor2')");
		$this->connection->query("UPDATE tabla SET campo1='valor3', campo2='valor4' WHERE id=2");
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "valor1", "campo2" =>"valor2"),
				array("id" => 2, "campo1" => "valor3", "campo2" =>"valor4")
			),
			$this->connection->query("SELECT * FROM tabla")
		);
		$this->connection->query("UPDATE tabla SET campo1='VVV', campo2='VVV'");
		$this->assertEquals(
			array(
				array("id" => 1, "campo1" => "VVV", "campo2" =>"VVV"),
				array("id" => 2, "campo1" => "VVV", "campo2" =>"VVV")
			),
			$this->connection->query("SELECT * FROM tabla")
		);
	}
}	
