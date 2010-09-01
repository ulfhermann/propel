<?php

/*
 *  $Id$
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

require_once 'builder/sql/DDLBuilder.php';

/**
 * Baseclass for schema-supporting SQL DDL-building classes.
 *
 * @author     Noah Fontes <fontes@audoptic.com>
 * @author     Ulf Hermann <ulfhermann@kulturserver.de>
 * @package    propel.engine.builder.sql
 */
abstract class SchemaDDLBuilder extends DDLBuilder {

	/**
	 * Array that keeps track of already added schema names.
	 *
	 * @var        array
	 */
	protected static $addedSchemas = array();

	/**
	 * Reset static vars between db iterations.
	 */
	public static function reset()
	{
		self::$addedSchemas = array();
	}

	/**
	 * Gets the name of this table's schema.
	 *
	 * @return     The name of the schema, or null if not specified.
	 */
	protected function getSchema()
	{
		return $this->getTable()->getSchema();
	}

	/**
	 * Add a schema to the generated SQL script
	 *
	 * @author     Markus Lervik <markus.lervik@necora.fi>
	 * @author     Noah Fontes <fontes@audoptic.com>
	 * @access     protected
	 * @return     string with CREATE SCHEMA statement if
	 *         applicable, else empty string
	 **/
	protected function addSchema(&$script)
	{
		$schemaName = $this->getSchema();

		if ($schemaName !== null) {

			if (!in_array($schemaName, self::$addedSchemas)) {
				self::$addedSchemas[] = $schemaName;
				$script .= "\nCREATE SCHEMA " . $this->quoteIdentifier($schemaName) . ";\n";
			}

		}

	}
}