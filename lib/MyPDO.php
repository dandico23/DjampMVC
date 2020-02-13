<?php

/**
 * PDO wrapper class
 *
 * Provides an extension for PHP's PDO class designed for ease of use and
 * centralized error handling. Adds basic methods for select, insert, update,
 * and delete statements, as well as handling exceptions when SQL errors
 * occur.
 *
 * The insert and update methods are designed to easily handle values
 * collected from a form. You simply provide an associative array of
 * column/value pairs and it writes the SQL for you. All other methods require
 * a full SQL statement.
 *
 * Inspired by PHP PDO Wrapper Class by imavex.com
 * @see http://www.imavex.com/php-pdo-wrapper-class/
 *
 * @author Brett Rawlins
 */

namespace lib;

use lib\PDOHelper;

class MyPDO extends \PDO
{
    /**
     * SQL statement from the last query
     * @var string
     */
    protected $sql;

    /**
     * PDOStatement object containing the last prepared statement
     * @var object
     */
    protected $statement;

    /**
     * Bind parameters from the last prepared statement
     * @var array
     */
    protected $bindings;

    # Added to provide support for postgres
    protected $scheme;

    protected $env_state;


    /**
     * Constructor
     *
     * @param string $dsn - the PDO Data Source Name
     * @param string $user - database user
     * @param string $password - database password
     * @param array $options - associative array of connection options
     */
    public function __construct($dsn, $user, $password, $env_state, $scheme = null, $options = array())
    {
        // set server environment constants
        $this->env_state = $env_state;
        
        # If it's a postgres db, add the scheme
        if ($scheme && preg_match('/\bpgsql\b/', $dsn)) {
            $this->scheme = $scheme;
        }

        // set default options
        $defaults = array(
            \PDO::ATTR_PERSISTENT => true, // persistent connection
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, // throw exceptions
            \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "UTF8"', // character encoding
            \PDO::MYSQL_ATTR_FOUND_ROWS => true, // count rows matched for updates even if no changes made
        );

        // create the object
        try {
            parent::__construct($dsn, $user, $password, $defaults);
            // set user options if any
            if ($this && !empty($options) && is_array($options)) {
                foreach ($options as $key => $value) {
                    $this->setAttribute($key, $value);
                }
            }
        } catch (\PDOException $e) {
            // use self:: because $this doesn't exist - constructor failed
            self::debug($e);
        }
    }

    /**
     * Display debugging info for PDO Exceptions
     *
     * @param object $e - \PDOException object representing the error raised
     */
    protected function debug($e)
    {
        // gather error info:
        $error = array();
        $error['Message'] = $e->getMessage();
        // follow backtrace to the top where the error was first raised
        $backtrace = debug_backtrace();
        foreach ($backtrace as $info) { 
            if (isset($info['file']) && $info['file'] != __FILE__) {
                $error['Backtrace'] = $info['file'] . ' @ line ' . $info['line'];
            }
        }
        $error['File'] = $e->getFile() . ' @ line ' . $e->getLine();

        $error = PDOHelper::gatherDebugSqlParms($this->sql, $this->bindings, $error, $backtrace, $this->env_state);
        PDOHelper::displayDebugMessage($error, $this->env_state);

        // don't execute default PHP error handler
        return true;
    }

    /**
     * Return a prepared statement
     *
     * Extends PDO::prepare to add basic error handling
     *
     * @param  string $sql - SQL statement to prepare
     * @param  array $options - array of key/value pairs to set attributes for the PDOStatement object (@see PDO::prepare)
     * @return mixed - PDOStatement object or false on failure
     */
    public function customPrepare($sql, $table, $options = array())
    {
        // cleanup
        $this->sql = trim($sql);
        
        if ($this->scheme) {
            $this->sql = preg_replace('/\b' . $table . '\b/', $this->scheme . "." . $table, $sql);
        }

        try {
            // prepare the statement
            $this->statement = null;
            if ($this->statement = parent::prepare($this->sql, $options)) {
                return $this->statement;
            }
        } catch (\PDOException $e) {
            $this->debug($e);
            return false;
        }
    }

    /**
     * Bind parameters and execute a prepared statement
     *
     * @param  array $bindings - array of values to be substituted for the parameter markers
     * @return bool
     */
    public function execute($bindings)
    {
        // cleanup
        $this->bindings = (empty($bindings)) ? null : $bindings;

        if (!empty($this->statement)) {
            try {
                return $this->statement->execute($bindings);
            } catch (\PDOException $e) {
                $this->debug($e);
                return false;
            }
        }
    }

    /**
     * Return the results of the given SELECT statement
     *
     * Accomodates any select statement that returns an array. To select a
     * single row and column (scalar value) use selectCell().
     *
     * @param  string $sql - SQL statement
     * @param  array $bindings - array of values to be substituted for the parameter markers
     * @param  int $fetch_style - PDO::FETCH_* constant that controls the contents of the returned array (@see PDOStatement::fetch())
     * @param  mixed $fetch_argument - column index, class name, or other argument depending on the value of the $fetch_style parameter
     * @return array - array of results or false on failure
     */

    

    public function select($sql, $bindings = array(), $fetch_style = '', $fetch_argument = '')
    {
        $table = PDOHelper::getTableFromQuery($sql);
        $to_return = false;

        // prepare the statement
        if ($this->customPrepare($sql, $table)) {
            // bind and execute
            if ($this->execute($bindings)) {
                // set default fetch mode
                $fetch_style = (empty($fetch_style)) ? \PDO::FETCH_ASSOC : $fetch_style;
                // return the results
                if (!empty($fetch_argument)) {
                    return $this->statement->fetchAll($fetch_style, $fetch_argument);
                }
                $to_return = $this->statement->fetchAll($fetch_style);
            }
            return $to_return;
        }
        return $to_return;
    }

    /**
     * Return the value of a single cell (row & column) for the given SELECT statement
     *
     * @param  string $sql - SQL statement
     * @param  array $bindings - array of values to be substituted for the parameter markers
     * @return mixed - the value or false on failure
     */
    public function selectCell($sql, $bindings = array())
    {
        // prepare the statement
        if ($this->customPrepare($sql, $table)) {
            // bind and execute
            if ($this->execute($bindings)) {
                // return the value
                return $this->statement->fetch(\PDO::FETCH_COLUMN);
            }
            return false;
        }
        return false;
    }

    /**
     * Run the given SQL statement and return the result
     *
     * @param  string $sql - SQL statement
     * @param  array $bindings - array of values to be substituted for the parameter markers
     * @return mixed - the value or false on failure
     */

    public function sqlReturning($table) {
        $last_id = parent::lastInsertId();
        $this->statement = parent::prepare("SELECT * FROM $table where Id=($last_id)");
        $this->execute(array());
        return $this->statement->fetchAll();
    }

    public function bindAndExecute($bindings)
    {
        $to_return = false;
        // bind and execute
        if ($success = $this->execute($bindings)) {
            // return the result
            if (preg_match('/(insert)/i', $this->sql)) {
                if ($this->scheme) {
                    $to_return = $this->statement->fetchAll();  # Functions can be seen in class PDOStatement
                } else {
                    # MySQL does not have RETURNING clause
                    $to_return = $this->sqlReturning($table);
                }
            }
            if (preg_match('/(delete|update)/i', $this->sql)) {
                $to_return = $this->statement->rowCount();
            } elseif (preg_match('/(select|describe)/i', $this->sql)) {
                $to_return = $this->statement->fetchAll(\PDO::FETCH_ASSOC);
            } elseif (preg_match('/(create|alter)/i', $this->sql)) {
                $to_return = $success;
            }
        }
        return $to_return;
    }

    public function run($sql, $table, $bindings = array())
    {
        // prepare the statement
        if ($this->customPrepare($sql, $table)) {
            if (!PDOHelper::preventUnsupported($this->sql)) {
                return false;
            }
            return $this->bindAndExecute($bindings);
        }
        return false;
    }

    /**
     * Run the given DELETE statement and return the number of affected rows
     *
     * @param  string $sql - SQL statement
     * @param  array $bindings - array of values to be substituted for the parameter markers
     * @return int - number of affected rows or false on failure
     */
    public function delete($sql, $bindings = array())
    {
        $table = PDOHelper::getTableFromQuery($sql);
        return $this->run($sql, $table, $bindings);
    }

    /**
     * Filter out any array values that don't match a column in the table
     *
     * @param array $values - associative array of values
     * @param string $table - table name
     * @return array - the filtered array
     */

    public function filter($values, $table)
    {
        // get columns in the table
        try {
            if ($this->scheme) {
                $this->sql = "SELECT column_name,data_type,identity_increment 
                              FROM information_schema.columns WHERE table_name = '$table'";
            } else {
                $this->sql = 'SHOW COLUMNS FROM ' . $table;
            }
            $sth = $this->query($this->sql);
            $info = $sth->fetchAll();
        } catch (\PDOException $e) {
            $this->debug($e);
            return false;
        }

        $columns = PDOHelper::compileColumnNames($info, $this->scheme);
        $values = PDOHelper::removeItems($columns, $values);
        return PDOHelper::removeAiFields($info, $values, $this->scheme);
    }

    /**
     * Run the given INSERT statement and return the number of affected rows
     *
     * If no bindings are given, we create them from the values. We
     * want to let PDO bind parameter values because it automatically
     * handles quoting and NULL values properly.
     *
     * @param string $table - table name
     * @param array $values - associative array of column/value pairs
     * @param array $bindings - array of values to be substituted for the parameter markers
     * @return int - number of affected rows or false on failure
     */

    public function insert($table, $values, $bindings = array())
    {
        // filter values for table
        $values = $this->filter($values, $table);  # Commented because functions to get table columns not working

        // Build the SQL:
        $insert_sql = PDOHelper::buildInsertQuery($table, $values);

        // add values
        $i = 0;
        if (empty($bindings)) {
            $bindings = array_values($values);
            foreach ($values as $value) {
                $insert_sql .= ($i == 0) ? '?' : ', ?';
                $i++;
            }
        } else {
            foreach ($values as $value) {
                $insert_sql .= ($i == 0) ? $value : ', ' . $value;
                $i++;
            }
        }
        $insert_sql .= ')';

        if ($this->scheme) {
            $insert_sql .= " RETURNING *";
        }

        // run the query
        return $this->run($insert_sql, $table, $bindings);
    }

    /**
     * Updates the table with the given values and returns the number of affected rows
     *
     * Designed for easily updating a record using values collected from a
     * form. If no bindings are given, they will be created using the given
     * values so we can take advantage of the benefits of prepared statements.
     *
     * N.B. Does not support where clauses that use the "IN" keyword.
     *
     * @param  string $table - table name
     * @param  array  $values - associative array of column/value pairs
     * @param  array $where - where clause as an array of conditions (string will be converted to array)
     * @param  array $bindings - array of values to be substituted for the parameter markers in $values and/or $where
     * @return int - number of affected rows or false on failure
     */

    public function update($table, $values, $where, $bindings = array())
    {
        // filter values for table
        $values = $this->filter($values, $table);

        // Build the SQL:
        $update_sql = 'UPDATE ' . $table . ' SET ';
        $markersResult = PDOHelper::addMarkers($update_sql, $values, $bindings);
        $update_sql = $markersResult[0];
        $final_bindings = $markersResult[1];

        // handle the where clause and bindings
        if (!empty($where)) {
            // convert where string to array
            if (!is_array($where)) {
                $where = preg_split('/\b(where|and)\b/i', $where, null, PREG_SPLIT_NO_EMPTY);
                $where = array_map('trim', $where);
            }
            $updateWhereResult = PDOHelper::mountUpdateWhere($where, $final_bindings);
            $where = $updateWhereResult[0];
            $final_bindings = $updateWhereResult[1];
            // add the where clause
            foreach ($where as $i => $condition) {
                $update_sql .= ($i == 0) ? ' WHERE ' . $condition : ' AND ' . $condition;
            }
        }
        return $this->run($update_sql, $table, $final_bindings);
    }
}