<?php

namespace lib;

class PDOHelper extends MyPDO
{
    public static function displayDebugMessage($error, $env_state)
    {
        // get css
        $css = '';
        $file = dirname(__FILE__) . '/debug.css';
        if (is_readable($file)) {
            $css = trim(file_get_contents($file));
        }

        // build the message
        $msg = '';
        $msg .= "\n" . '<style type="text/css">' . "\n" . $css . "\n" . '</style>';
        $msg .= "\n" . '<div class="debug">' . "\n\t" . '<h3>' . __METHOD__ . '</h3>';
        foreach ($error as $key => $value) {
            $msg .= "\n\t" . '<label>' . $key . ':</label>' . $value;
        }
        $msg .= "\n" . '</div>';

        // customize error handling based on environment:
        if ($env_state == 'Default') {  # Em produção
            // do nothing
        } else {
            echo $msg;
        }
    }

    public static function gatherDebugSqlParms($sql, $bindings, $error, $backtrace, $env_state)
    {
        // gather SQL params
        if (!empty($sql)) {
            $error['SQL statement'] = $sql;
        }
        $open_pre = '<pre>';
        $close_pre = '</pre>';
        if (!empty($bindings)) {
            $error['Bind Parameters'] = $open_pre . print_r($bindings, true) . $close_pre;
        }
        // show args if set
        if (!empty($backtrace[1]['args'])) {
            $error['Args'] = $open_pre . print_r($backtrace[1]['args'], true) . $close_pre;
        }
        // don't show variables if GLOBALS are set
        if (!empty($context) && empty($context['GLOBALS'])) {
            $error['Current Variables'] = $open_pre . print_r($context, true) . $close_pre;
        }
        $error['Environment'] = $env_state;

        return $error;
    }

    public function preventUnsupported($sql)
    {
        // require a WHERE clause for deletes
        try {
            if (preg_match('/delete/i', $sql) && !preg_match('/where/i', $sql)) {
                throw new \PDOException('Missing WHERE clause for DELETE statement');
            }
        } catch (\PDOException $e) {
            $this->debug($e);
            return false;
        }
        // prevent unsupported actions
        try {
            if (!preg_match('/(select|describe|delete|insert|update|create|alter)+/i', $sql)) {
                throw new \PDOException('Unsupported SQL command');
            }
        } catch (\PDOException $e) {
            $this->debug($e);
            return false;
        }
        return true;
    }

    public function getTableFromQuery($sql)
    {
        try {
            $query_structure = explode(' ', strtolower(preg_replace('!\s+!', ' ', $sql)));
            $searches_from = array_keys($query_structure, 'from');
            $searches_delete = array_keys($query_structure, 'delete');
            $searches = array_merge($searches_from, $searches_delete);

            foreach ($searches as $search) {
                if (isset($query_structure[$search + 1])) {
                    return trim($query_structure[$search + 1], '` ');
                }
            }
        } catch (\PDOException $e) {
            # It will not arrive here if the sql query is correct
            $this->debug($e);
            return false;
        }
    }

    public static function getMarkerBiding($value, $column, $bindings)
    {
        $marker = $bound_value = null;
        if (preg_match('/(:\w+|\?)/', $value, $matches)) {
            if (strpos(':', $matches[1]) !== false) {
                // look up the value (named parameters can be in any order)
                $marker = $matches[1];
                $bound_value = $bindings[$matches[1]];
            } else {
                // get the next value (question mark parameters are given in order)
                $marker = ':' . $column;
                $bound_value = array_shift($bindings);
            }
        // create the binding
        } else {
            $marker = ':' . $column;
            $bound_value = $value;
        }
        return array($marker, $bound_value);
    }

    public static function addMarkers($sql, $values, $bindings)
    {
        // add columns and parameter markers
        $markers_bindings = array();
        $i = 0;
        foreach ($values as $column => $value) {
            // get the binding
            $binding_result = PDOHelper::getMarkerBiding($value, $column, $bindings);
            $marker = $binding_result[0];
            $bound_value = $binding_result[1];

            // add the binding
            $markers_bindings[$marker] = $bound_value;

            // add the SQL
            $sql .= ($i == 0) ? $column . ' = ' . $marker : ', ' . $column . ' = ' . $marker;
            $i++;
        }
        return array($sql, $markers_bindings);
    }


    public static function buildInsertQuery($table, $values)
    {
        // Build the SQL:
        $sql = 'INSERT INTO ' . $table . ' (';
        // add column names
        $i = 0;

        foreach ($values as $column => $value) {
            $sql .= ($i == 0) ? $column : ', ' . $column;
            $i++;
        }
        return $sql . ') VALUES (';
    }

    public static function mountUpdateWhere($where, $final_bindings)
    {
        // loop through each condition
        foreach ($where as $i => $condition) {
            $marker = $bound_value = null;
            // split up condition into parts (column, operator, value)
            preg_match('/(\w+)\s*(=|<|>|!)+\s*(.+)/i', $condition, $parts);
            if (!empty($parts)) {
                // assign parts to variables
                list( , $column, , $value) = $parts;
                // get the binding
                if (preg_match('/(:\w+|\?)/', $value, $matches)) {
                    if (strpos(':', $matches[1]) !== false) {
                        // look up the value (named parameters can be in any order)
                        $marker = $matches[1];
                        $bound_value = $final_bindings[$matches[1]];
                    } else {
                        // get the next value (question mark parameters are given in order)
                        $marker = ':where_' . $column;
                        $bound_value = array_shift($final_bindings);
                    }
                // create the binding
                } else {
                    $marker = ':where_' . $column;
                    $bound_value = $value;
                }
                // add the binding
                $final_bindings[$marker] = $bound_value;
                // update the condition (replace value with marker)
                $where[$i] = substr_replace($condition, $marker, strpos($condition, $value));
            }
        }
        return array($where, $final_bindings);
    }

    public static function getIdValues($scheme)
    {
        $id_variable = "extra";
        $column_name = "Field";
        if ($scheme) {
            $column_name = "column_name";
            $id_variable = "identity_increment";
        }
        return array($id_variable,$column_name);
    }

    public static function compileColumnNames($info, $scheme)
    {
        $variables_names = PDOHelper::getIdValues($scheme);
        $column_name = $variables_names[1];

        // compile the column names
        $columns = array();
        foreach ($info as $item) {
            $columns[] = $item[$column_name];
        }
        return $columns;
    }

    public static function removeItems($columns, $values)
    {
        // remove items that don't match a column
        foreach ($values as $name => $value) {
            if (!in_array($name, $columns)) {
                unset($values[$name]);
            }
        }
        return $values;
    }

    public static function removeAiFields($info, $values, $scheme)
    {
        $variables_names = PDOHelper::getIdValues($scheme);
        $id_variable = $variables_names[0];
        $column_name = $variables_names[1];

        $ai_fields = array(); // auto-increment fields
        foreach ($info as $item) {
            if (isset($item[$id_variable]) && $item[$id_variable] != null) {
                $ai_fields[] = $item[$column_name];
            }
        }
        // remove auto-increment fields
        if (!empty($ai_fields)) {
            foreach ($ai_fields as $item) {
                unset($values[$item]);
            }
        }
        return $values;
    }
}
