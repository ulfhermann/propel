<?php

/*
 *  $Id: SoftDeleteBehavior.php $
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
 
/**
 * Gives a model class the ability to remain in database even when the user deletes object
 * Uses an additional column storing the deletion date
 * And an additional condition for every read query to only consider rows with no deletion date
 *
 * @author     François Zaninotto
 * @version    $Revision: 1066 $
 * @package    propel.engine.behavior
 */
class SoftDeleteBehavior extends Behavior
{
	// default parameters value
  protected $parameters = array(
    'add_columns'    => 'true',
    'deleted_column' => 'deleted_at',
  );
  
  /**
   * Add the deleted_column to the current table
   */
  public function modifyTable()
  {
    if ($this->getParameter('add_columns') == 'true')
    {
      $this->getTable()->addColumn(array(
        'name' => $this->getParameter('deleted_column'),
        'type' => 'TIMESTAMP'
      ));
    }
  }
  
  protected function getColumnSetter()
  {
  	return 'set' . $this->getColumnForParameter('deleted_column')->getPhpName();
  }
  
  public function preDelete()
  {
  	return <<<EOT
if (\$this->isSoftDeleteEnabled() && {$this->getTable()->getPhpName()}Peer::isSoftDeleteEnabled()) {
	\$this->{$this->getColumnSetter()}(time());
	\$this->save();
	\$con->commit();
	return;
}
EOT;
  }
  
  public function preSelect()
  {
  	return <<<EOT
if ({$this->getTable()->getPhpName()}Peer::isSoftDeleteEnabled()) {
	\$criteria->add({$this->getColumnForParameter('deleted_column')->getConstantName()}, null, Criteria::ISNULL);
} else {
	self::enableSoftDelete();
}
EOT;
  }
  
  public function objectAttributes()
  {
  	return "protected \$softDelete = true;
";
  }
  
  public function objectMethods()
  {
  	return <<<EOT

/**
 * Enable the soft_delete behavior for this object
 */
public function enableSoftDelete()
{
	\$this->softDelete = true;
}

/**
 * Disable the soft_delete behavior for this object
 */
public function disableSoftDelete()
{
	\$this->softDelete = false;
}

/**
 * Check the soft_delete behavior for this object
 *
 * @return boolean true if the soft_delete behavior is enabled
 */
public function isSoftDeleteEnabled()
{
	return \$this->softDelete;
}

/**
 * Bypass the soft_delete behavior and force a hard delete of the current object
 */
public function forceDelete(PropelPDO \$con = null)
{
	\$this->disableSoftDelete();
	\$this->delete(\$con);
}

/**
 * Undelete a row that was soft_deleted
 *
 * @return     int The number of rows affected by this update and any referring fk objects' save() operations.
 */
public function unDelete(PropelPDO \$con = null)
{
	\$this->{$this->getColumnSetter()}(null);
	return \$this->save(\$con);
}
EOT;
  }
  
  public function staticAttributes()
  {
  	return "protected static \$softDelete = true;
";
  }
  
  public function staticMethods()
  {
  	return <<<EOT

/**
 * Enable the soft_delete behavior for this model
 */
public static function enableSoftDelete()
{
	self::\$softDelete = true;
}

/**
 * Disable the soft_delete behavior for this model
 */
public static function disableSoftDelete()
{
	self::\$softDelete = false;
}

/**
 * Check the soft_delete behavior for this model
 * @return boolean true if the soft_delete behavior is enabled
 */
public static function isSoftDeleteEnabled()
{
	return self::\$softDelete;
}
EOT;
  }
}