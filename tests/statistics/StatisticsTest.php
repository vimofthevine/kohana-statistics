<?php defined('SYSPATH') or die('No direct script access.');

/**
 * PHPUnit tests for the Statistics module
 *
 * @group       modules
 * @group       modules.statistics
 *
 * @package     Statistics
 * @category    Test
 * @author      Kyle Treubig
 * @copyright   (C) 2010 Kyle Treubig
 * @license     MIT
 */
class Statistics_StatisticsTest extends Kohana_Unittest_TestCase {

	/** Test configuration */
	private $config = array(
		'length'  => 7,
		'table'   => 'test_stats',
		'columns' => array(
			'id'       => 'id',
			'lifetime' => 'lifetime',
			'period'   => 'period',
			'data'     => 'data',
		),
	);

	/**
	 * Make sure that profiling is enabled
	 */
	public function setup()
	{
		if ( ! Kohana::config('database')->default['profiling'])
			$this->fail('Must have profiling on to use these tests!');
	}

	/**
	 * Insert some tables with data into the database
	 */
	public static function setUpBeforeClass()
	{
		parent::setUpBeforeClass();

		$queries = array(
			'DROP TABLE IF EXISTS `test_stats`;',
			'CREATE TABLE `test_stats` (
				`id` INT PRIMARY KEY AUTO_INCREMENT,
				`lifetime` INT,
				`period` INT,
				`data` VARCHAR(256)
			)',
			"INSERT INTO `test_stats` VALUES
				(1, 50, 10, '0,0,1,2,3,4,0'),
				(2, 24,  7, '1,1,1,1,1,1,1'),
				(3,  2,  0, '0,0,0,0,0,0,0'),
				(4, 19, 17, '4,2,1,5,0,3,2')",
		);

		$db = Database::instance();
		$db->connect();

		foreach ($queries as $query)
		{
			$result = mysql_query($query);
			if ($result === FALSE)
				throw new Exception(mysql_error());
		}
	}

	/**
	 * Test that factory method without any given
	 * group loads the default group from the config
	 */
	public function test_factory_with_default_group()
	{
		$stat    = Statistics::factory();
		$default = Kohana::config('statistics.default');

		$this->assertAttributeSame($default, 'config', $stat);
	}

	/**
	 * Test that factory method with a config group
	 * loads that group from the config
	 */
	public function test_factory_with_specified_group()
	{
		$stat  = Statistics::factory('testgroup');
		$group = Kohana::config('statistics.testgroup');

		$this->assertAttributeSame($group, 'config', $stat);
	}

	/**
	 * Test that the factory method throws an exception
	 * if an invalid configuration array is given
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_factory_with_invalid_config_array()
	{
		$config = array(
			'table'   => 'mesa',
			'length'  => 14,
		);

		Statistics::factory($config);
	}

	/**
	 * Test that the factory method accepts an array
	 * and uses that array for the config
	 */
	public function test_factory_with_config_array()
	{
		$stat  = Statistics::factory($this->config);

		$this->assertAttributeSame($this->config, 'config', $stat);
	}

	/**
	 * Test that the factory method accepts an
	 * ID as the second parameter and stores it
	 * for future use
	 */
	public function test_factory_with_specified_id()
	{
		$stat = Statistics::factory(NULL, 42);

		$this->assertAttributeSame(42, 'id', $stat);
	}

	/**
	 * Test that the create method throws an
	 * exception if no id is specified
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_create_with_no_id()
	{
		Statistics::factory()->create();
	}

	/**
	 * Test that the create method throws an
	 * exception if an id is specified that
	 * already exists in the database
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_create_with_existing_id()
	{
		Statistics::factory('default', 2)->create();
	}

	/**
	 * Test that a new statistics record is created
	 * with the given id and all values are set
	 * to 0
	 */
	public function test_create_with_new_id()
	{
		$q_before = $this->getQueries();

		Statistics::factory($this->config, 7)->create();

		$this->assertQueryCountIncrease(1, $q_before, $this->getQueries());
	}

	/**
	 * Test that the get_lifetime_count method
	 * throws an exception if no id is specified
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_get_lifetime_count_with_no_id()
	{
		Statistics::factory()->get_lifetime_count();
	}

	/**
	 * Test that the get_lifetime_count method
	 * throws an exception if an id is specified
	 * that does not exist in the database
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_get_lifetime_count_with_invalid_id()
	{
		Statistics::factory($this->config, 13)
			->get_lifetime_count();
	}

	/**
	 * Test that the get_lifetime_count method returns
	 * the total view count for the statistics
	 * record
	 */
	public function test_get_lifetime_count_with_valid_id()
	{
		$count = Statistics::factory($this->config, 2)
			->get_lifetime_count();

		$this->assertEquals(24, $count);
	}

	/**
	 * Test that the get_period_count method throws an
	 * exception if no id is specified
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_get_period_count_with_no_id()
	{
		Statistics::factory()->get_period_count();
	}

	/**
	 * Test that the get_period_count method
	 * throws an exception if an id is specified
	 * that does not exist in the database
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_get_period_count_with_invalid_id()
	{
		Statistics::factory($this->config, 13)
			->get_period_count();
	}

	/**
	 * Test that the get_period_count method returns
	 * the period view count for the statistics
	 * record
	 */
	public function test_get_period_count_with_valid_id()
	{
		$count = Statistics::factory($this->config, 2)
			->get_period_count();

		$this->assertEquals(7, $count);
	}

	/**
	 * Test that the increment method throws an
	 * exception if no id is specified
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_increment_with_no_id()
	{
		Statistics::factory()->increment();
	}

	/**
	 * Test that the increment method
	 * throws an exception if an id is specified
	 * that does not exist in the database
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_increment_with_invalid_id()
	{
		Statistics::factory($this->config, 13)
			->increment();
	}

	/**
	 * Test that the increment method increases
	 * the lifetime count, period count, and today's
	 * data point
	 */
	public function test_increment_with_valid_id()
	{
		Statistics::factory($this->config, 1)
			->increment();

		$expected = DB::select()
			->from($this->config['table'])
			->where($this->config['columns']['id'], '=', 1)
			->execute();

		$this->assertEquals(51,
			$expected->get($this->config['columns']['lifetime']));
		$this->assertEquals(11,
			$expected->get($this->config['columns']['period']));
		$this->assertEquals('0,0,1,2,3,4,1',
			$expected->get($this->config['columns']['data']));
	}

	/**
	 * Test that the reset_period method
	 * throws an exception if no id is specified
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_reset_period_with_no_id()
	{
		Statistics::factory()->reset_period();
	}

	/**
	 * Test that the reset_period method
	 * throws an exception if an id is specified
	 * that does not exist in the database
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_reset_period_with_invalid_id()
	{
		Statistics::factory($this->config, 13)
			->reset_period();
	}

	/**
	 * Test that the reset_period method resets
	 * the period count and shifts the data points
	 */
	public function test_reset_period_with_valid_id()
	{
		Statistics::factory($this->config, 4)
			->reset_period();

		$expected = DB::select()
			->from($this->config['table'])
			->where($this->config['columns']['id'], '=', 4)
			->execute();

		$this->assertEquals(19,
			$expected->get($this->config['columns']['lifetime']));
		$this->assertEquals(13,
			$expected->get($this->config['columns']['period']));
		$this->assertEquals('2,1,5,0,3,2,0',
			$expected->get($this->config['columns']['data']));
	}

	/**
	 * Test that the delete method
	 * throws an exception if no id is specified
	 *
	 * @expectedException Statistics_Exception
	 */
	public function test_delete_with_no_id()
	{
		Statistics::factory()->delete();
	}

	/**
	 * Test that the delete method returns
	 * false if an id is specified that does
	 * not exist in the database
	 */
	public function test_delete_with_invalid_id()
	{
		$result = Statistics::factory($this->config, 13)
			->delete();

		$this->assertFalse($result);
	}

	/**
	 * Test that the delete() method deletes
	 * the specified statistics record
	 * from the database
	 */
	public function test_delete_with_valid_id()
	{
		$result = Statistics::factory($this->config, 2)
			->delete();

		$this->assertTrue($result);

		$expected = DB::select()
			->from($this->config['table'])
			->where($this->config['columns']['id'], '=', 2)
			->execute();

		$this->assertEquals(0, $expected->count());
	}

	/**
	 * Get the currently logged set of queries from
	 * the database profiling
	 *
	 * @author Marcu Cobden
	 * @see    http://github.com/sittercity/sprig/blog/master/tests/sprig.php#L413
	 *
	 * @param   String  The database the queries will be logged under
	 * @return  array map of queries from the Profiler class
	 */
	public function getQueries($database = 'default')
	{
		$database = "database ($database)";

		$groups = Profiler::groups();
		if ( ! array_key_exists($database, $groups))
			return array();

		return $groups[$database];
	}

	/**
	 * Find the difference between two different query profiles
	 *
	 * @author Marcu Cobden
	 * @see    http://github.com/sittercity/sprig/blog/master/tests/sprig.php#L432
	 *
	 * @param   array   The queries before
	 * @param   array   The queries after
	 * @return  array(int, array) Total number of
	 *     new queries and a map of query => increase
	 */
	public function queryDiff(array $before, array $after)
	{
		$added = 0;
		$diff  = array();

		foreach ($after as $query => $ids)
		{
			if ( ! array_key_exists($query, $before))
			{
				$cmp = count($ids);
				$added += $cmp;
				$diff[$query] = $cmp;
			}
			else
			{
				$cmp = count($ids) - count($before[$query]);
				if ($cmp == 0)
					continue;

				$added += $cmp;
				$diff[$query] = $cmp;
			}
		}

		return array($added, $diff);
	}

	/**
	 * Assert that the number of queries has increased
	 * by a given amount
	 *
	 * @author Marcu Cobden
	 * @see    http://github.com/sittercity/sprig/blog/master/tests/sprig.php#L467
	 *
	 * @param   int     Expected increase in the number of queries
	 * @param   array   Queries before the test
	 * @param   array   Queries after the test
	 * @return  void
	 */
	public function assertQueryCountIncrease($increase, array $before, array $after)
	{
		list($added, $new_queries) = $this->queryDiff($before, $after);

		$this->assertEquals($increase, $added, "Expected to have $increase more queries, "
			."actual increase was $added.");
	}

}	// End of Statistics_StatisticsTest
