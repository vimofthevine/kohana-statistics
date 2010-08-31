<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Configuration file for page-view statistics
 */
return array(
	'default' => array(
		// Number of data-points per period
		'length' => 7,
		// Database table
		'table'  => 'statistics',
		// Database columns
		'columns' => array(
			'id'       => 'id',
			'lifetime' => 'lifetime',
			'period'   => 'period',
			'data'     => 'data',
		),
	),
);

