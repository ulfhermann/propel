<?php


/*
 *  $Id: QueryBuilder.php 1451 2010-01-12 22:10:50Z francois $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://propel.phpdb.org>.
 */

require_once 'builder/om/OMBuilder.php';

/**
 * Generates a PHP5 base Query class for user object model (OM).
 *
 * This class produces the base query class (e.g. BaseBookQuery) which contains all
 * the custom-built query methods.
 *
 * @author     Francois Zaninotto
 * @package    propel.generator.builder.om
 */
class QueryBuilder extends OMBuilder
{

	/**
	 * Gets the package for the [base] object classes.
	 * @return     string
	 */
	public function getPackage()
	{
		return parent::getPackage() . ".om";
	}

	/**
	 * Returns the name of the current class being built.
	 * @return     string
	 */
	public function getUnprefixedClassname()
	{
		return $this->getBuildProperty('basePrefix') . $this->getStubQueryBuilder()->getUnprefixedClassname();
	}

	/**
	 * Adds the include() statements for files that this class depends on or utilizes.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addIncludes(&$script)
	{
	}

	/**
	 * Adds class phpdoc comment and openning of class.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addClassOpen(&$script)
	{
		$table = $this->getTable();
		$tableName = $table->getName();
		$tableDesc = $table->getDescription();
		$queryClass = $this->getStubQueryBuilder()->getClassname();
		$modelClass = $this->getStubObjectBuilder()->getClassname();
		$parentClass = $this->getBehaviorContent('parentClass');
		$parentClass = null === $parentClass ? 'ModelCriteria' : $parentClass;
		$script .= "

/**
 * Base class that represents a query for the '$tableName' table.
 *
 * $tableDesc
 *";
		if ($this->getBuildProperty('addTimeStamp')) {
			$now = strftime('%c');
			$script .= "
 * This class was autogenerated by Propel " . $this->getBuildProperty('version') . " on:
 *
 * $now
 *";
		}
		
		// magic orderBy() methods, for IDE completion
		foreach ($this->getTable()->getColumns() as $column) {
			$script .= "
 * @method     $queryClass orderBy" . $column->getPhpName() . "(\$order = Criteria::ASC) Order by the " . $column->getName() . " column";
		}
		$script .= "
 *";
		
 		// magic groupBy() methods, for IDE completion
		foreach ($this->getTable()->getColumns() as $column) {
			$script .= "
 * @method     $queryClass groupBy" . $column->getPhpName() . "() Group by the " . $column->getName() . " column";
		}
		$script .= "
 *";

		// override the signature of ModelCriteria::findOne() to specify the class of the returned object, for IDE completion
		$script .= "
 * @method     $modelClass findOne(PropelPDO \$con = null) Return the first $modelClass matching the query";

		// magic findBy() methods, for IDE completion
		foreach ($this->getTable()->getColumns() as $column) {
			$script .= "
 * @method     $modelClass findOneBy" . $column->getPhpName() . "(" . $column->getPhpType() . " \$" . $column->getName() . ") Return the first $modelClass filtered by the " . $column->getName() . " column";
		}
		$script .= "
 *";
		foreach ($this->getTable()->getColumns() as $column) {
			$script .= "
 * @method     array findBy" . $column->getPhpName() . "(" . $column->getPhpType() . " \$" . $column->getName() . ") Return $modelClass objects filtered by the " . $column->getName() . " column";
		}
		
		$script .= "
 *
 * @package    propel.generator.".$this->getPackage()."
 */
abstract class ".$this->getClassname()." extends " . $parentClass . "
{
";
	}

	/**
	 * Specifies the methods that are added as part of the stub object class.
	 *
	 * By default there are no methods for the empty stub classes; override this method
	 * if you want to change that behavior.
	 *
	 * @see        ObjectBuilder::addClassBody()
	 */
	protected function addClassBody(&$script)
	{
		// apply behaviors
		$this->applyBehaviorModifier('queryAttributes', $script, "	");
		$this->addConstructor($script);
		$this->addFindPk($script);
		$this->addFindPks($script);
		$this->addFilterByPrimaryKey($script);
		$this->addFilterByPrimaryKeys($script);
		foreach ($this->getTable()->getColumns() as $col) {
			$this->addFilterByCol($script, $col);
		}
		foreach ($this->getTable()->getForeignKeys() as $fk) {
			$this->addFilterByFK($script, $fk);
			$this->addUseFKQuery($script, $fk);
		}
		foreach ($this->getTable()->getReferrers() as $refFK) {
			$this->addFilterByRefFK($script, $refFK);
			$this->addUseRefFKQuery($script, $refFK);
		}
		foreach ($this->getTable()->getCrossFks() as $fkList) {
			list($refFK, $crossFK) = $fkList;
			$this->addFilterByCrossFK($script, $refFK, $crossFK);
		}
		$this->addPrune($script);
		$this->addBasePreSelect($script);
		$this->addBasePreDelete($script);
		$this->addBasePreUpdate($script);
		// apply behaviors
		$this->applyBehaviorModifier('queryMethods', $script, "	");
	}

	/**
	 * Closes class.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addClassClose(&$script)
	{
		$script .= "
} // " . $this->getClassname() . "
";
		$this->applyBehaviorModifier('queryFilter', $script, "");
	}	

	/**
	 * Adds the constructor for this object.
	 * @param      string &$script The script will be modified in this method.
	 * @see        addConstructor()
	 */
	protected function addConstructor(&$script)
	{
		$this->addConstructorComment($script);
		$this->addConstructorOpen($script);
		$this->addConstructorBody($script);
		$this->addConstructorClose($script);
	}

	/**
	 * Adds the comment for the constructor
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addConstructorComment(&$script)
	{
		$script .= "
	/**
	 * Initializes internal state of ".$this->getClassname()." object.
	 *
	 * @param     string \$dbName The dabase name
	 * @param     string \$modelName The phpName of a model, e.g. 'Book'
	 * @param     string \$modelAlias The alias for the model in this query, e.g. 'b'
	 */";
	}

	/**
	 * Adds the function declaration for the constructor
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addConstructorOpen(&$script)
	{
		$table = $this->getTable();
		$script .= "
	public function __construct(\$dbName = '" . $table->getDatabase()->getName() . "', \$modelName = '" . $this->getNewStubObjectBuilder($table)->getClassname() . "', \$modelAlias = null)
	{";
	}

	/**
	 * Adds the function body for the constructor
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addConstructorBody(&$script)
	{
		$script .= "
		parent::__construct(\$dbName, \$modelName, \$modelAlias);";
	}

	/**
	 * Adds the function close for the constructor
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addConstructorClose(&$script)
	{
		$script .= "
	}
";
	}
		
	/**
	 * Adds the findPk method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addFindPk(&$script)
	{
		$script .= "
	/**
	 * Find object by primary key";
		if (count($this->getTable()->getPrimaryKey()) === 1) {
			$script .= "
	 * Use instance pooling to avoid a database query if the object exists
	 * <code>
	 * \$obj  = \$c->findPk(12, \$con);";
		} else {	
			$script .= "
	 * <code>
	 * \$obj = \$c->findPk(array(34, 634), \$con);";
		}
	 	$script .= "
	 * </code>
	 * @param     mixed \$key Primary key to use for the query
	 * @param     PropelPDO \$con an optional connection object
	 *
	 * @return    mixed the result, formatted by the current formatter
	 */
	public function findPk(\$key, \$con = null)
	{";
		$table = $this->getTable();
		$pks = $table->getPrimaryKey();
		if (count($pks) === 1) {
			$poolKeyHashParams = '$key';
		} else {
			$poolKeyHashParams = array();
			for ($i = 0, $count = count($pks); $i < $count; $i++) {
				$poolKeyHashParams[]= '$key[' . $i . ']';
			}
		}
		$script .= "
		if (\$this->getFormatter()->isObjectFormatter() && (null !== (\$obj = ".$this->getPeerClassname()."::getInstanceFromPool(".$this->getPeerBuilder()->getInstancePoolKeySnippet($poolKeyHashParams).")))) {
			// the object is alredy in the instance pool
			return \$obj;
		} else {
			// the object has not been requested yet, or the formatter is not an object formatter
			return \$this
				->filterByPrimaryKey(\$key)
				->findOne(\$con);
		}
	}
";
	}
	
	/**
	 * Adds the findPks method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addFindPks(&$script)
	{
		$table = $this->getTable();
		$pks = $table->getPrimaryKey();
		$count = count($pks);
		$script .= "
	/**
	 * Find objects by primary key
	 * <code>";
		if ($count === 1) {
			$script .= "
	 * \$objs = \$c->findPks(array(12, 56, 832), \$con);";
		} else {
			$script .= "
	 * \$objs = \$c->findPks(array(array(12, 56), array(832, 123), array(123, 456)), \$con);";
		}
		$script .= "
	 * </code>
	 * @param     array \$keys Primary keys to use for the query
	 * @param     PropelPDO \$con an optional connection object
	 *
	 * @return    the list of results, formatted by the current formatter
	 */
	public function findPks(\$keys, \$con = null)
	{	
		return \$this
			->filterByPrimaryKeys(\$keys)
			->find(\$con);
	}
";
	}
	
	/**
	 * Adds the filterByPrimaryKey method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addFilterByPrimaryKey(&$script)
	{
		$script .= "
	/**
	 * Filter the query by primary key
	 *
	 * @param     mixed \$key Primary key to use for the query
	 *
	 * @return    " . $this->getStubQueryBuilder()->getClassname() . " The current query, for fluid interface
	 */
	public function filterByPrimaryKey(\$key)
	{";
		$table = $this->getTable();
		$pks = $table->getPrimaryKey();
		if (count($pks) === 1) {
			// simple primary key
			$col = $pks[0];
			$const = $this->getColumnConstant($col);
			$script .= "
		return \$this->addUsingAlias($const, \$key, Criteria::EQUAL);";
		} else {
			// composite primary key
			$i = 0;
			foreach ($pks as $col) {
				$const = $this->getColumnConstant($col);
				$script .= "
		\$this->addUsingAlias($const, \$key[$i], Criteria::EQUAL);";
				$i++;
			}
			$script .= "
		
		return \$this;";
		}
		$script .= "
	}
";
	}
	
		/**
	 * Adds the filterByPrimaryKey method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addFilterByPrimaryKeys(&$script)
	{
		$script .= "
	/**
	 * Filter the query by a list of primary keys
	 *
	 * @param     array \$keys The list of primary key to use for the query
	 *
	 * @return    " . $this->getStubQueryBuilder()->getClassname() . " The current query, for fluid interface
	 */
	public function filterByPrimaryKeys(\$keys)
	{";
		$table = $this->getTable();
		$pks = $table->getPrimaryKey();
		if (count($pks) === 1) {
			// simple primary key
			$col = $pks[0];
			$const = $this->getColumnConstant($col);
			$script .= "
		return \$this->addUsingAlias($const, \$keys, Criteria::IN);";
		} else {
			// composite primary key
			$script .= "
		foreach (\$keys as \$key) {";
			$i = 0;
			foreach ($pks as $col) {
				$const = $this->getColumnConstant($col);
				$script .= "
			\$cton$i = \$this->getNewCriterion($const, \$key[$i], Criteria::EQUAL);";
				if ($i>0) {
					$script .= "
			\$cton0->addAnd(\$cton$i);";
				}
				$i++;
			}
			$script .= "
			\$this->addOr(\$cton0);
		}";
			$script .= "
		
		return \$this;";
		}
		$script .= "
	}
";
	}
	
	/**
	 * Adds the filterByCol method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addFilterByCol(&$script, $col)
	{
		$colPhpName = $col->getPhpName();
		$colName = $col->getName();
		$variableName = $col->getStudlyPhpName();
		$qualifiedName = $this->getColumnConstant($col);
		$script .= "
	/**
	 * Filter the query on the $colName column
	 * ";
		if ($col->isNumericType() || $col->isTemporalType()) {
			$script .= "
	 * @param     " . $col->getPhpType() . "|array \$$colName The value to use as filter.
	 *            Accepts an associative array('min' => \$minValue, 'max' => \$maxValue)";
		} elseif ($col->isTextType()) {
			$script .= "
	 * @param     string \$$colName The value to use as filter.
	 *            Accepts wildcards (* and % trigger a LIKE)";
		} elseif ($col->isBooleanType()) {
			$script .= "
	 * @param     boolean|string \$$variableName The value to use as filter.
	 *            Accepts strings ('false', 'off', '-', 'no', 'n', and '0' are false, the rest is true)";
		} else {
			$script .= "
	 * @param     mixed \$$colName The value to use as filter";
		}
		$script .= "
	 *
	 * @return    " . $this->getStubQueryBuilder()->getClassname() . " The current query, for fluid interface
	 */
	public function filterBy$colPhpName(\$$variableName = null)
	{";
		if ($col->isPrimaryKey() && ($col->getType() == PropelTypes::INTEGER || $col->getType() == PropelTypes::BIGINT)) {
			$script .= "
		if (is_array(\$$variableName)) {
			return \$this->addUsingAlias($qualifiedName, \${$variableName}, Criteria::IN);
		} else {
			return \$this->addUsingAlias($qualifiedName, \$$variableName, Criteria::EQUAL);
		}";
		} elseif ($col->isNumericType() || $col->isTemporalType()) {
			$script .= "
		if (is_array(\$$variableName)) {
			if (array_values(\$$variableName) === \$$variableName) {
				return \$this->addUsingAlias($qualifiedName, \${$variableName}, Criteria::IN);
			} else {
				if (isset(\${$variableName}['min'])) {
					\$this->addUsingAlias($qualifiedName, \${$variableName}['min'], Criteria::GREATER_EQUAL);
				}
				if (isset(\${$variableName}['max'])) {
					\$this->addUsingAlias($qualifiedName, \${$variableName}['max'], Criteria::LESS_EQUAL);
				}
				return \$this;	
			}
		} else {
			return \$this->addUsingAlias($qualifiedName, \$$variableName, Criteria::EQUAL);
		}";
		} elseif ($col->isTextType()) {
			$script .= "
		if (is_array(\$$variableName)) {
			return \$this->addUsingAlias($qualifiedName, \${$variableName}, Criteria::IN);
		} elseif(preg_match('/[\%\*]/', \$$variableName)) {
			return \$this->addUsingAlias($qualifiedName, str_replace('*', '%', \$$variableName), Criteria::LIKE);
		} else {
			return \$this->addUsingAlias($qualifiedName, \$$variableName, Criteria::EQUAL);
		}";
		} elseif ($col->isBooleanType()) {
			$script .= "
		if(is_string(\$$variableName)) {
			\$$colName = in_array(strtolower(\$$variableName), array('false', 'off', '-', 'no', 'n', '0')) ? false : true;
		}
		return \$this->addUsingAlias($qualifiedName, \$$variableName, Criteria::EQUAL);";
		} else {
			$script .= "
		return \$this->addUsingAlias($qualifiedName, \$$variableName, Criteria::EQUAL);";
		}
		$script .= "
	}
";
	}
	
	/**
	 * Adds the filterByFk method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addFilterByFk(&$script, $fk)
	{
		$table = $this->getTable();
		$queryClass = $this->getStubQueryBuilder()->getClassname();
		$fkTable = $this->getForeignTable($fk);
		$fkPhpName = $this->getNewStubObjectBuilder($fkTable)->getClassname();
		$relationName = $this->getFKPhpNameAffix($fk);
		$objectName = '$' . $fkTable->getStudlyPhpName();
		$script .= "
	/**
	 * Filter the query by a related $fkPhpName object
	 *
	 * @param     $fkPhpName $objectName  the related object to use as filter
	 *
	 * @return    $queryClass The current query, for fluid interface
	 */
	public function filterBy$relationName($objectName)
	{
		return \$this";
		foreach ($fk->getLocalForeignMapping() as $localColumn => $foreignColumn) {
			$localColumnObject = $table->getColumn($localColumn);
			$foreignColumnObject = $fkTable->getColumn($foreignColumn);
			$script .= "
			->addUsingAlias(" . $this->getColumnConstant($localColumnObject) . ", " . $objectName . "->get" . $foreignColumnObject->getPhpName() . "(), Criteria::EQUAL)";
		}
		$script .= ";
	}
";
	}

	/**
	 * Adds the filterByRefFk method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addFilterByRefFk(&$script, $fk)
	{
		$table = $this->getTable();
		$queryClass = $this->getStubQueryBuilder()->getClassname();
		$fkTable = $this->getTable()->getDatabase()->getTable($fk->getTableName());
		$fkPhpName = $this->getNewStubObjectBuilder($fkTable)->getClassname();
		$relationName = $this->getRefFKPhpNameAffix($fk);
		$objectName = '$' . $fkTable->getStudlyPhpName();
		$script .= "
	/**
	 * Filter the query by a related $fkPhpName object
	 *
	 * @param     $fkPhpName $objectName  the related object to use as filter
	 *
	 * @return    $queryClass The current query, for fluid interface
	 */
	public function filterBy$relationName($objectName)
	{
		return \$this";
		foreach ($fk->getForeignLocalMapping() as $localColumn => $foreignColumn) {
			$localColumnObject = $table->getColumn($localColumn);
			$foreignColumnObject = $fkTable->getColumn($foreignColumn);
			$script .= "
			->addUsingAlias(" . $this->getColumnConstant($localColumnObject) . ", " . $objectName . "->get" . $foreignColumnObject->getPhpName() . "(), Criteria::EQUAL)";
		}
		$script .= ";
	}
";
	}
	
	/**
	 * Adds the useFkQuery method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addUseFkQuery(&$script, $fk)
	{
		$table = $this->getTable();
		$fkTable = $this->getForeignTable($fk);
		$queryClass = $this->getNewStubQueryBuilder($fkTable)->getClassname();
		$relationName = $this->getFKPhpNameAffix($fk);
		$this->addUseRelatedQuery($script, $fkTable, $queryClass, $relationName);
	}

	/**
	 * Adds the useFkQuery method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addUseRefFkQuery(&$script, $fk)
	{
		$table = $this->getTable();
		$fkTable = $this->getTable()->getDatabase()->getTable($fk->getTableName());
		$queryClass = $this->getNewStubQueryBuilder($fkTable)->getClassname();
		$relationName = $this->getRefFKPhpNameAffix($fk);
		$this->addUseRelatedQuery($script, $fkTable, $queryClass, $relationName);
	}

	/**
	 * Adds the useFkQuery method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addUseRelatedQuery(&$script, $fkTable, $queryClass, $relationName)
	{
		$script .= "
	/**
	 * Use the $relationName relation " . $fkTable->getPhpName() . " object
	 *
	 * @see       useQuery()
	 * 
	 * @param     string \$relationAlias optional alias for the relation,
	 *                                   to be used as main alias in the secondary query
	 * @param     string \$joinType Accepted values are null, 'left join', 'right join', 'inner join'
	 *
	 * @return    $queryClass A secondary query class using the current class as primary query
	 */
	public function use" . $relationName . "Query(\$relationAlias = '', \$joinType = Criteria::INNER_JOIN)
	{
		return \$this
			->join('$relationName' . (\$relationAlias ? ' ' . \$relationAlias : ''), \$joinType)
			->useQuery(\$relationAlias ? \$relationAlias : '$relationName', '$queryClass');
	}
";
	}
	
	protected function addFilterByCrossFK(&$script, $refFK, $crossFK)
	{
		$queryClass = $this->getStubQueryBuilder()->getClassname();
		$crossRefTable = $crossFK->getTable();
		$foreignTable = $crossFK->getForeignTable();
		$fkPhpName =  $foreignTable->getPhpName();
		$crossTableName = $crossRefTable->getName();
		$relName = $this->getFKPhpNameAffix($crossFK, $plural = false);
		$objectName = '$' . $foreignTable->getStudlyPhpName();
		$relationName = $this->getRefFKPhpNameAffix($refFK, $plural = false);
		$script .= "
	/**
	 * Filter the query by a related $fkPhpName object
	 * using the $crossTableName table as cross reference
	 *
	 * @param     $fkPhpName $objectName the related object to use as filter
	 *
	 * @return    $queryClass The current query, for fluid interface
	 */
	public function filterBy{$relName}($objectName)
	{
		return \$this
			->use{$relationName}Query()
				->filterBy{$relName}($objectName)
			->endUse();
	}
	";
	}
	
	/**
	 * Adds the prune method for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addPrune(&$script)
	{
		$table = $this->getTable();
		$class = $this->getStubObjectBuilder()->getClassname();
		$objectName = '$' . $table->getStudlyPhpName();
		$script .= "
	/**
	 * Exclude object from result
	 *
	 * @param     $class $objectName Object to remove from the list of results
	 *
	 * @return    " . $this->getStubQueryBuilder()->getClassname() . " The current query, for fluid interface
	 */
	public function prune($objectName = null)
	{
		if ($objectName) {";
		$pks = $table->getPrimaryKey();
		if (count($pks) > 1) {
			$i = 0;
			$conditions = array();
			foreach ($pks as $col) {
				$const = $this->getColumnConstant($col);
				$condName = "'pruneCond" . $i . "'";
				$conditions[]= $condName;
				$script .= "
			\$this->addCond(". $condName . ", \$this->getAliasedColName($const), " . $objectName . "->get" . $col->getPhpName() . "(), Criteria::NOT_EQUAL);";
				$i++;
				}
			$conditionsString = implode(', ', $conditions);
			$script .= "
			\$this->combine(array(" . $conditionsString . "), Criteria::LOGICAL_OR);";
		} else {
			$col = $pks[0];
			$const = $this->getColumnConstant($col);
			$script .= "
			\$this->addUsingAlias($const, " . $objectName . "->get" . $col->getPhpName() . "(), Criteria::NOT_EQUAL);";
		}
		$script .= "
	  }
	  
		return \$this;
	}
";
	}
	
	/**
	 * Adds the basePreSelect hook for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addBasePreSelect(&$script)
	{
		$this->addBasePreSelectComment($script);
		$this->addBasePreSelectOpen($script);
		$this->addBasePreSelectBody($script);
		$this->addBasePreSelectClose($script);
	}

	/**
	 * Adds the comment for the basePreSelect
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreSelectComment(&$script)
	{
		$script .= "
	/**
	 * Code to execute before every SELECT statement
	 * 
	 * @param     PropelPDO \$con The connection object used by the query
	 */";
	}

	/**
	 * Adds the function declaration for the basePreSelect
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreSelectOpen(&$script)
	{
		$script .= "
	protected function basePreSelect(PropelPDO \$con)
	{";
	}

	/**
	 * Adds the function body for the basePreSelect
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreSelectBody(&$script)
	{
		// apply behaviors
		$this->applyBehaviorModifier('preSelectQuery', $script, "		");
		$script .= "
		return \$this->preSelect(\$con);";
	}

	/**
	 * Adds the function close for the basePreSelect
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreSelectClose(&$script)
	{
		$script .= "
	}
";
	}

	/**
	 * Adds the basePreDelete hook for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addBasePreDelete(&$script)
	{
		$this->addBasePreDeleteComment($script);
		$this->addBasePreDeleteOpen($script);
		$this->addBasePreDeleteBody($script);
		$this->addBasePreDeleteClose($script);
	}

	/**
	 * Adds the comment for the basePreDelete
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreDeleteComment(&$script)
	{
		$script .= "
	/**
	 * Code to execute before every DELETE statement
	 * 
	 * @param     PropelPDO \$con The connection object used by the query
	 */";
	}

	/**
	 * Adds the function declaration for the basePreDelete
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreDeleteOpen(&$script)
	{
		$script .= "
	protected function basePreDelete(PropelPDO \$con)
	{";
	}

	/**
	 * Adds the function body for the basePreDelete
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreDeleteBody(&$script)
	{
		// apply behaviors
		$this->applyBehaviorModifier('preDeleteQuery', $script, "		");
		$script .= "
		return \$this->preDelete(\$con);";
	}

	/**
	 * Adds the function close for the basePreDelete
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreDeleteClose(&$script)
	{
		$script .= "
	}
";
	}

	/**
	 * Adds the basePreUpdate hook for this object.
	 * @param      string &$script The script will be modified in this method.
	 */
	protected function addBasePreUpdate(&$script)
	{
		$this->addBasePreUpdateComment($script);
		$this->addBasePreUpdateOpen($script);
		$this->addBasePreUpdateBody($script);
		$this->addBasePreUpdateClose($script);
	}

	/**
	 * Adds the comment for the basePreUpdate
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreUpdateComment(&$script)
	{
		$script .= "
	/**
	 * Code to execute before every UPDATE statement
	 * 
	 * @param     array \$values The associatiove array of columns and values for the update
	 * @param     PropelPDO \$con The connection object used by the query
	 */";
	}

	/**
	 * Adds the function declaration for the basePreUpdate
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreUpdateOpen(&$script)
	{
		$script .= "
	protected function basePreUpdate(&\$values, PropelPDO \$con)
	{";
	}

	/**
	 * Adds the function body for the basePreUpdate
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreUpdateBody(&$script)
	{
		// apply behaviors
		$this->applyBehaviorModifier('preUpdateQuery', $script, "		");
		$script .= "
		return \$this->preUpdate(\$values, \$con);";
	}

	/**
	 * Adds the function close for the basePreUpdate
	 * @param      string &$script The script will be modified in this method.
	 **/
	protected function addBasePreUpdateClose(&$script)
	{
		$script .= "
	}
";
	}

	/**
	 * Checks whether any registered behavior on that table has a modifier for a hook
	 * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
	 * @return boolean
	 */
	public function hasBehaviorModifier($hookName, $modifier = null)
	{
	 	return parent::hasBehaviorModifier($hookName, 'QueryBuilderModifier');
	}

	/**
	 * Checks whether any registered behavior on that table has a modifier for a hook
	 * @param string $hookName The name of the hook as called from one of this class methods, e.g. "preSave"
	 * @param string &$script The script will be modified in this method.
	 */
	public function applyBehaviorModifier($hookName, &$script, $tab = "		")
	{
		return $this->applyBehaviorModifierBase($hookName, 'QueryBuilderModifier', $script, $tab);
	}

	/**
	 * Checks whether any registered behavior content creator on that table exists a contentName
	 * @param string $contentName The name of the content as called from one of this class methods, e.g. "parentClassname"
	 */
	public function getBehaviorContent($contentName)
	{
		return $this->getBehaviorContentBase($contentName, 'QueryBuilderModifier');
	}

}
