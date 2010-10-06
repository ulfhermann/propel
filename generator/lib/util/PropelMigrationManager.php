<?php

/**
 * This file is part of the Propel package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

require_once dirname(__FILE__) . '/../model/Table.php';
require_once dirname(__FILE__) . '/../model/Column.php';
require_once dirname(__FILE__) . '/PropelSQLParser.php';

/**
 * Service class for preparing and executing migrations
 *
 * @author     François Zaninotto
 * @version    $Revision$
 * @package    propel.generator.util
 */
class PropelMigrationManager
{
	protected $connections;
	protected $pdoConnections = array();
	protected $migrationTable = 'propel_migration';
	protected $migrationDir;
	
	/**
	 * Set the database connection settings
	 *
	 * @param array $connections
	 */
	public function setConnections($connections)
	{
		$this->connections = $connections;
	}

	/**
	 * Get the database connection settings
	 *
	 * @return array
	 */
	public function getConnections()
	{
		return $this->connections;
	}
	
	public function getConnection($datasource)
	{
		if (!isset($this->connections[$datasource])) {
			throw new InvalidArgumentException(sprintf('Unkown datasource "%s"', $datasource));
		}
		return $this->connections[$datasource];
	}
	
	public function getPdoConnection($datasource)
	{
		if (!isset($pdoConnections[$datasource])) {
			$buildConnection = $this->getConnection($datasource);
			$dsn = str_replace("@DB@", $datasource, $buildConnection['dsn']);

			// Set user + password to null if they are empty strings or missing
			$username = isset($buildConnection['user']) && $buildConnection['user'] ? $buildConnection['user'] : null;
			$password = isset($buildConnection['password']) && $buildConnection['password'] ? $buildConnection['password'] : null;

			$pdo = new PDO($dsn, $username, $password);
			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			$pdoConnections[$datasource] = $pdo;
		}
		
		return $pdoConnections[$datasource];
	}
	
	public function getPlatform($datasource)
	{
		$params = $this->getConnection($datasource);
		$adapter = $params['adapter'];
		$adapterClass = ucfirst($adapter) . 'Platform';
		require_once sprintf('%s/../platform/%s.php',
			dirname(__FILE__),
			$adapterClass
		);
		return new $adapterClass();
	}
	
	/**
	 * Set the migration table name
	 *
	 * @param string $migrationTable
	 */
	public function setMigrationTable($migrationTable)
	{
		$this->migrationTable = $migrationTable;
	}

	/**
	 * get the migration table name
	 *
	 * @return string
	 */
	public function getMigrationTable()
	{
		return $this->migrationTable;
	}
	
	/**
	 * Set the path to the migration classes
	 *
	 * @param string $migrationDir
	 */
	public function setMigrationDir($migrationDir)
	{
		$this->migrationDir = $migrationDir;
	}

	/**
	 * Get the path to the migration classes
	 *
	 * @return string
	 */
	public function getMigrationDir()
	{
		return $this->migrationDir;
	}

	
	
	public function getOldestDatabaseVersion()
	{
		if (!$connections = $this->getConnections()) {
			throw new Exception('You must define database connection settings in a buildtime-conf.xml file to use migrations');
		}
		$oldestMigrationTimestamp = null;
		$migrationTimestamps = array();
		foreach ($connections as $name => $params) {
			$pdo = $this->getPdoConnection($name);
			$sql = sprintf('SELECT version FROM %s', $this->getMigrationTable());
			$stmt = $pdo->prepare($sql);
			try {
				$stmt->execute();
				if ($migrationTimestamp = $stmt->fetchColumn()) {
					$migrationTimestamps[$name] = $migrationTimestamp;
				}
			} catch (PDOException $e) {
				$this->createMigrationTable($name);
				$oldestMigrationTimestamp = 0;
			}
		}
		if ($oldestMigrationTimestamp === null && $migrationTimestamps) {
			sort($migrationTimestamps);
			$oldestMigrationTimestamp = array_shift($migrationTimestamps);
		}
		
		return $oldestMigrationTimestamp;
	}
	
	public function migrationTableExists($datasource)
	{
		$pdo = $this->getPdoConnection($datasource);
		$sql = sprintf('SELECT version FROM %s', $this->getMigrationTable());
		$stmt = $pdo->prepare($sql);
		try {
			$stmt->execute();
			return true;
		} catch (PDOException $e) {
			return false;
		}
	}
	
	public function createMigrationTable($datasource)
	{
		$platform = $this->getPlatform($datasource);
		// modelize the table
		$database = new Database($datasource);
		$database->setPlatform($platform);
		$table = new Table($this->getMigrationTable());
		$database->addTable($table);
		$column = new Column('version');
		$column->getDomain()->copy($platform->getDomainForType('INTEGER'));
		$column->setDefaultValue(0);
		$table->addColumn($column);
		// insert the table into the database
		$statements = $platform->getAddTableDDL($table);
		$pdo = $this->getPdoConnection($datasource);
		$res = PropelSQLParser::executeString($statements, $pdo);
		if (!$res) {
			throw new Exception(sprintf('Unable to create migration table in datasource "%s"', $datasource));
		}
	}
	
	public function getMigrationTimestamps()
	{
		$path = $this->getMigrationDir();
		$migrationTimestamps = array();
		$migrationsDir = new PhingFile($path);
		$files = scandir($path);
		foreach ($files as $file) {
			if (preg_match('/^PropelMigration_(\d+)\.php$/', $file, $matches)) {
				$migrationTimestamps[] = (integer) $matches[1];
			}
		}
		
		return $migrationTimestamps;
	}
	
	public function getValidMigrationTimestamps()
	{
		$oldestMigrationTimestamp = $this->getOldestDatabaseVersion();
		$migrationTimestamps = $this->getMigrationTimestamps();
		// removing already executed migrations
		foreach ($migrationTimestamps as $key => $value) {
			if ($value <= $oldestMigrationTimestamp) {
				unset($migrationTimestamps[$key]);
			}
		}
		sort($migrationTimestamps);
		
		return $migrationTimestamps;
	}
	
	public function getMigrationClassName($timestamp)
	{
		return sprintf('PropelMigration_%d', $timestamp);
	}
	
	public function getMigrationObject($timestamp)
	{
		$className = $this->getMigrationClassName($timestamp);
		require_once sprintf('%s/%s.php',
			$this->getMigrationDir(),
			$className
		);
		return new $className();
	}
}