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
    protected $is_postgres;

    protected $env_state;
    protected $config;


    /**
     * Constructor
     *
     * @param string $dsn - the PDO Data Source Name
     * @param string $user - database user
     * @param string $password - database password
     * @param array $options - associative array of connection options
     */
    public function __construct($dsn, $user, $password, $env_state, $type, $config, $options = array())
    {
        // set server environment constants
        $this->env_state = $env_state;
        $this->config = $config;
        
        # If it's a postgres db, some functions use different queries
        $this->is_postgres = false;
        if (preg_match('/\bpgsql\b/', $dsn)) {
            $this->is_postgres = true;
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
     * @param  array $options - array of key/value pairs to set attributes
     *               for the PDOStatement object (@see PDO::prepare)
     * @return mixed - PDOStatement object or false on failure
     */
    public function customPrepare($sql, $table, $options = array())
    {
        // cleanup
        $this->sql = trim($sql);

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
     * @param  int $fetch_style - PDO::FETCH_* constant that controls
     *         the contents of the returned array (@see PDOStatement::fetch())
     * @param  mixed $fetch_argument - column index, class name, or other argument
     *               depending on the value of the $fetch_style parameter
     * @return array - array of results or false on failure
     */
    public function select($sql, $bindings = array(), $fetch_style = '', $fetch_argument = '')
    {
        $table = PDOHelper::getTableFromQuery($sql);

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
                return $this->statement->fetchAll($fetch_style);
            }
        }
        return false;
    }

    /**
     * Return the total number of pages given the number of elements per page
     *
     * @param string $table - table name
     * @param  array $limit - number of elements per page
     * @param  array $where - where clause as an array of conditions (must be an array)
     * @return array - list with the total number of pages, the first page,
     *                  cipher_text and iv (used to paginate following pages)
     */
    public function paginateGetTotalPages($table, $limit, $where = array())
    {
        $query = "SELECT count(*) FROM $table";
        $final_bindings = array();
        if (!empty($where)) {
            $query .= " WHERE ";
            $buildResult = $this->buildSQL($query, $table, $where);
            $query = $buildResult[0];
            $final_bindings = $buildResult[1];
        }

        if ($this->customPrepare($query, $table)) {
            if ($this->execute($final_bindings)) {
                $total_results = $this->statement->fetchColumn();
                $total_pages = ceil($total_results / $limit);
                
                $paginateCode = PDOHelper::generatePaginateCode($table, $limit, $where);
                $encryptedResult = PDOHelper::encryptSSL(
                    $paginateCode,
                    $this->config['ssl_encrypt']['cipher_type'],
                    $this->config['ssl_encrypt']['cipher_key']
                );
                list($cipher_text, $iv) = array($encryptedResult["cipher_text"],$encryptedResult["iv"]);
                $first_page = $this->selectPaginate($cipher_text, $iv, 1);

                return array('total_pages' => $total_pages, 'first_page' => $first_page,
                            "cipher_text" => $cipher_text, "iv" => $iv);
            }
        }
        return false;
    }

    public function buildSQL($sql, $table, $values, $bindings = array())
    {
        // filter values for table
        $filtered_values = $this->filter($values, $table);
        if ($values && !$filtered_values) {
            throw new \PDOException('Where arguments do not exist in the table');
        } else {
            $markersResult = PDOHelper::addMarkers($sql, $values, $bindings);
            return $markersResult;
        }
    }

    /**
     * Return the page given the number of elements per page
     *
     * @param string $table - table name
     * @param  array $page - number of the desired page
     * @param  array $limit - number of elements per page
     * @param  array $where - where clause as an array of conditions (must be an array)
     * @return mixed - the elements of the requested page
     */
    public function selectPaginate($cipherText, $iv, $page)
    {
        $decryptedCode = PDOHelper::decryptSSL(
            $cipherText,
            $this->config["ssl_encrypt"]["cipher_type"],
            $this->config["ssl_encrypt"]["cipher_key"],
            $iv
        );
        $paginateInfo = PDOHelper::recoverPaginateInfoFromCode($decryptedCode);
        list($table, $limit, $where) = $paginateInfo;

        $paginate_sql  = "SELECT * FROM $table";
        $final_bindings = array();
        if (!empty($where)) {
            $paginate_sql .= " WHERE ";
            $buildResult = $this->buildSQL($paginate_sql, $table, $where);
            list($paginate_sql, $final_bindings) = $buildResult;
        }

        $start = ($page - 1) * $limit;
        if (!$this->is_postgres) {
            # For mysql
            $paginate_sql .=  " LIMIT $start,$limit";
        } else {
            # For postgres
            $paginate_sql .=  " LIMIT :limit offset :start";
            $final_bindings[':limit'] = $limit;
            $final_bindings[':start'] = $start;
        }

        if ($this->customPrepare($paginate_sql, $table)) {
            if ($this->execute($final_bindings)) {
                return $this->statement->fetchAll(\PDO::FETCH_ASSOC);
            }
        }
        return false;
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

    public function getPrimaryKeyColumnName($table)
    {
        $get_primary_string = "SHOW KEYS FROM $table WHERE Key_name = 'PRIMARY'";
        $this->statement = parent::prepare($get_primary_string);
        $this->execute(array());
        return $this->statement->fetch()['Column_name'];
    }

    /**
     * Run the given SQL statement and return the result
     *
     * @param  string $sql - SQL statement
     * @param  array $bindings - array of values to be substituted for the parameter markers
     * @return mixed - the value or false on failure
     */
    public function sqlReturning($table)
    {
        $last_id = parent::lastInsertId();
        $primary_column_name = $this->getPrimaryKeyColumnName($table);
        $this->statement = parent::prepare("SELECT * FROM $table where $primary_column_name=($last_id)");
        $this->execute(array());
        return $this->statement->fetchAll();
    }

    public function bindAndExecute($bindings, $table)
    {
        $to_return = false;
        // bind and execute
        if ($success = $this->execute($bindings)) {
            // return the result
            if (preg_match('/(insert)/i', $this->sql)) {
                if ($this->is_postgres) {
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
            return $this->bindAndExecute($bindings, $table);
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
            if ($this->is_postgres) {
                $split = explode('.', $table);
                $scheme = $split[0];
                $tabela = $split[1];
                $this->sql = "SELECT column_name,data_type,identity_increment 
                              FROM INFORMATION_SCHEMA.COLUMNS
                              where TABLE_SCHEMA = '$scheme' and TABLE_NAME = '$tabela'";
            } else {
                $this->sql = 'SHOW COLUMNS FROM ' . $table;
            }
            $sth = $this->query($this->sql);
            $info = $sth->fetchAll();
        } catch (\PDOException $e) {
            $this->debug($e);
            return false;
        }

        $columns = PDOHelper::compileColumnNames($info, $this->is_postgres);
        $values = PDOHelper::removeItems($columns, $values);
        return PDOHelper::removeAiFields($info, $values, $this->is_postgres);
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

        if ($this->is_postgres) {
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

    public function handleWhereClause($sql, $where, $final_bindings)
    {
        // convert where string to array
        $where = PDOHelper::convertWhereToArray($where);
        $updateWhereResult = PDOHelper::mountUpdateWhere($where, $final_bindings);
        $where = $updateWhereResult[0];
        $final_bindings = $updateWhereResult[1];
        // add the where clause
        foreach ($where as $i => $condition) {
            $sql .= ($i == 0) ? ' WHERE ' . $condition : ' AND ' . $condition;
        }
        return $sql;
    }

    public function update($table, $values, $where = array(), $bindings = array())
    {
        // Build the SQL:
        $update_sql = 'UPDATE ' . $table . ' SET ';

        $buildResult = $this->buildSQL($update_sql, $table, $values, $bindings);
        $update_sql = $buildResult[0];
        $final_bindings = $buildResult[1];

        // handle the where clause and bindings
        if (!empty($where)) {
            $update_sql = $this->handleWhereClause($update_sql, $where, $final_bindings);
        }
        return $this->run($update_sql, $table, $final_bindings);
    }
}
