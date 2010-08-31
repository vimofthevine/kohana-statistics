<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Page-View Statistics
 *
 * @package     Statistics
 * @category    Base
 * @author      Kyle Treubig
 * @copyright   (C) 2010 Kyle Treubig
 * @license     MIT
 */
abstract class Kohana_Statistics {

	/** Group config settings */
	protected $config = NULL;

	/** Statistics record ID */
	protected $id = NULL;

	/**
	 * Statistics factory method
	 *
	 * @chainable
	 * @param   string  The configuration group to use
	 * @param   array   An array of config settings to use
	 * @param   int     [optional] ID of statistics record
	 */
	public static function factory($group = 'default', $id = NULL)
	{
		Kohana::$log->add(Kohana::DEBUG,
			'Executing Statistics::factory');

		return new Statistics($group, $id);
	}

	/**
	 * Constructor
	 *
	 * @param   string  The configuration group to use
	 * @param   array   An array of config settings to use
	 * @param   int     [optional] ID of statistics record
	 */
	public function __construct($group = 'default', $id = NULL)
	{
		Kohana::$log->add(Kohana::DEBUG,
			'Executing Statistics::__construct');

		if (is_array($group))
		{
			// Check that all fields are given
			if ( ! isset($group['table'])
				OR ! isset($group['length'])
				OR ! isset($group['columns']))
				throw new Statistics_Exception('Invalid config array given, must contain all necessary keys');

			// Use given array as the config
			$this->config = $group;
		}
		else
		{
			// Get group configuration
			$this->config = Kohana::config('statistics.'.$group);
		}

		// Save record ID, if given
		if ($id !== NULL)
		{
			$this->id = $id;
		}
	}

	/**
	 * Create new statistics record
	 *
	 * @chainable
	 * @return  Statistics instance
	 * @throws  Statistics_Exception
	 */
	public function create()
	{
		Kohana::$log->add(Kohana::DEBUG,
			'Executing Statistics::create');

		if ($this->id === NULL)
			throw new Statistics_Exception('No ID specified');

		try
		{
			// Create empty data array
			$data = array_fill(0, $this->config['length'], 0);
			$data = implode(",", $data);

			// Insert into database
			list($insert_id, $total_rows) = DB::insert( $this->config['table'], array(
					$this->config['columns']['id'],
					$this->config['columns']['lifetime'],
					$this->config['columns']['period'],
					$this->config['columns']['data']))
				->values(array($this->id, 0, 0, $data))
				->execute();

			if ($total_rows != 1)
			{
				throw new Statistics_Exception('Statistics record was not created');
			}

			return $this;
		}
		catch (Database_Exception $e)
		{
			Kohana::$log->add(Kohana::ERROR, $e->getMessage());
			throw new Statistics_Exception('Error creating statistics record');
		}
	}

	/**
	 * Get total view count
	 *
	 * @return  view count total
	 * @throws  Statistics_Exception
	 */
	public function get_lifetime_count()
	{
		Kohana::$log->add(Kohana::DEBUG,
			'Executing Statistics::get_lifetime_count');

		if ($this->id === NULL)
			throw new Statistics_Exception('No ID specified');

		try
		{
			$record = DB::select($this->config['columns']['lifetime'])
				->from($this->config['table'])
				->where($this->config['columns']['id'], '=', $this->id)
				->execute();

			if ($record->count() != 1)
				throw new Statistics_Exception('Invalid ID specified');

			return $record->get($this->config['columns']['lifetime']);
		}
		catch (Database_Exception $e)
		{
			Kohana::$log->add(Kohana::ERROR, $e->getMessage());
			throw new Statistics_Exception('Error retrieving statistics record');
		}
	}

	/**
	 * Get view count for current period
	 *
	 * @return  periodic view count
	 * @throws  Statistics_Exception
	 */
	public function get_period_count()
	{
		Kohana::$log->add(Kohana::DEBUG,
			'Executing Statistics::get_period_count');

		if ($this->id === NULL)
			throw new Statistics_Exception('No ID specified');

		try
		{
			$record = DB::select($this->config['columns']['period'])
				->from($this->config['table'])
				->where($this->config['columns']['id'], '=', $this->id)
				->execute();

			if ($record->count() != 1)
				throw new Statistics_Exception('Invalid ID specified');

			return $record->get($this->config['columns']['period']);
		}
		catch (Database_Exception $e)
		{
			Kohana::$log->add(Kohana::ERROR, $e->getMessage());
			throw new Statistics_Exception('Error retrieving statistics record');
		}
	}

	/**
	 * Increment view count
	 *
	 * @chainable
	 * @return  Statistics instance
	 * @throws  Statistics_Exception
	 */
	public function increment()
	{
		Kohana::$log->add(Kohana::DEBUG,
			'Executing Statistics::increment');

		if ($this->id === NULL)
			throw new Statistics_Exception('No ID specified');

		try
		{
			// Get statistics record
			$record = DB::select()
				->from($this->config['table'])
				->where($this->config['columns']['id'], '=', $this->id)
				->execute();

			if ($record->count() != 1)
				throw new Statistics_Exception('Invalid ID specified');

			$record = $record->current();

			// Increment counts
			$record[$this->config['columns']['lifetime']]++;
			$record[$this->config['columns']['period']]++;

			// Get data
			$data = $record[$this->config['columns']['data']];
			$data = explode(",", $data);

			// Increment today's count
			$today = $this->config['length'] - 1;
			$data[$today]++;

			$data = implode(",", $data);

			// Update in database
			$total_rows = DB::update($this->config['table'])
				->set(array(
					$this->config['columns']['lifetime'] => $record[$this->config['columns']['lifetime']],
					$this->config['columns']['period']   => $record[$this->config['columns']['period']],
					$this->config['columns']['data']     => $data))
				->where($this->config['columns']['id'], '=', $this->id)
				->execute();

			if ($total_rows != 1)
			{
				throw new Statistics_Exception('Statistics record was not incremented correctly');
			}

			return $this;
		}
		catch (Database_Exception $e)
		{
			Kohana::$log->add(Kohana::ERROR, $e->getMessage());
			throw new Statistics_Exception('Error incrementing statistics record');
		}
	}

	/**
	 * Reset period count
	 *
	 * @chainable
	 * @throws  Statistics_Exception
	 */
	public function reset_period()
	{
		Kohana::$log->add(Kohana::DEBUG,
			'Executing Statistics::reset_period');

		if ($this->id === NULL)
			throw new Statistics_Exception('No ID specified');

		try
		{
			// Get statistics record
			$record = DB::select()
				->from($this->config['table'])
				->where($this->config['columns']['id'], '=', $this->id)
				->execute();

			if ($record->count() != 1)
				throw new Statistics_Exception('Invalid ID specified');

			$record = $record->current();

			// Get data
			$data = $record[$this->config['columns']['data']];
			$data = explode(",", $data);

			// Shift data points
			array_shift($data);

			// Reset "today's" data
			$data[] = 0;

			$sum  = array_sum($data);
			$data = implode(",", $data);

			// Update in database
			$total_rows = DB::update($this->config['table'])
				->set(array(
					$this->config['columns']['period']  => $sum,
					$this->config['columns']['data']    => $data))
				->where($this->config['columns']['id'], '=', $this->id)
				->execute();

			if ($total_rows != 1)
			{
				throw new Statistics_Exception('Statistics record was not reset correctly');
			}

			return $this;
		}
		catch (Database_Exception $e)
		{
			Kohana::$log->add(Kohana::ERROR, $e->getMessage());
			throw new Statistics_Exception('Error resetting statistics record');
		}
	}

	/**
	 * Delete statistics record
	 *
	 * @return  True on success, else false
	 * @throws  Statistics_Exception
	 */
	public function delete()
	{
		Kohana::$log->add(Kohana::DEBUG,
			'Executing Statistics::delete');

		if ($this->id === NULL)
			throw new Statistics_Exception('No ID specified');

		try
		{
			$total_rows = DB::delete($this->config['table'])
				->where($this->config['columns']['id'], '=', $this->id)
				->execute();

			return ($total_rows == 1);
		}
		catch (Database_Exception $e)
		{
			Kohana::$log->add(Kohana::ERROR, $e->getMessage());
			throw new Statistics_Exception('Error deleting statistics record');
		}
	}

}	// End of Kohana_Statistics

