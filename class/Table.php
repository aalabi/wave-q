<?php

/**
 * Table
 *
 * A class for handling db table CRUB operations
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2023
 * @link        alabiansolutions.com
*/

class TableExpection extends Exception
{
}

class Table
{
    /** @var string database table been used by sql  */
    protected string $table;

    /** @var dbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /** @var query an instance of Query  */
    protected Query $query;

    /**
     * instantiation of Table
     *
     * @param string table default table in the database to be used as the sql query
     */
    public function __construct(string $table)
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        $this->query = new Query($table);
        $this->table = $table;
    }

    /**
     * get the table been used for query
     * @return string the table name
    */
    public function getTable():string
    {
        return $this->table;
    }

    /**
     * get the DbConnect been used for query
     * @return DbConnect the DbConnect
    */
    public function getDbConnect():DbConnect
    {
        return $this->dbConnect;
    }

    /**
     * set the DbConnect been used for query
     * @param DbConnect dbConnect to be used
    */
    public function setDbConnect(DbConnect $dbConnect)
    {
        $this->dbConnect = $dbConnect;
    }

    /**
     * create a new row
     * @param array cols 2 dimensional array [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] isFunction if the value is an sql function
     * @return int the id of the last created id
    */
    public function create(array $cols):int
    {
        return $this->query->insert($cols);
    }

    /**
     * create a new row
     * @param array cols 2 dimensional array [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] isFunction if the value is an sql function
     * @return int the id of the last created id
    */
    public function insert(array $cols):int
    {
        return $this->query->insert($cols);
    }

    /**
     * find the row from the table where column is equal to the value
     * @param string $column the column been search
     * @param int|string $value the value to be searched
     * @param array $cols an array of columns to be retrieved [col1, col2, ...coln]
     * @param string $order either first(ASC) or last(DESC) row
     * @param int $count the number of rows to be retrieved
     * @param int $start the row to start the retrievial from
     * @return array an array of columns in the selected row or empty array if no match
    */
    public function find(string $column, int|string $value, array $cols = [], string $order = "ASC", int $count = Functions::INFINITE, int $start = 1):array
    {
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            throw new TableExpection("order must be either 'ASC' or 'DESC'");
        }
        $columns = "*";
        if ($cols) {
            $columns = implode(",", $cols);
        }
        $limitAndCount = "";
        if ($count) {
            $limitAndCount = "LIMIT $count";
        }
        if ($count && $start) {
            --$start;
            $limitAndCount = "LIMIT $count OFFSET $start";
        }
        $sql = "SELECT $columns FROM $this->table WHERE $column = :value ORDER BY id $order $limitAndCount";
        return $this->query->executeSql($sql, ['value'=>$value])['rows'];
    }

    /**
     * find the first row from the table where column is equal to the value
     * @param string $column the column been search
     * @param int|string $value the value to be searched
     * @param array $cols an array of columns to be retrieved [col1, col2, ...coln]
     * @return array an array of columns in the selected row or empty array if no match
    */
    public function findFirst(string $column, int|string $value, array $cols = []):array
    {
        return $this->findExtremes($column, $value, "ASC", $cols);
    }

    /**
     * find the last row from the table where column is equal to the value
     * @param string $column the column been search
     * @param int|string $value the value to be searched
     * @param array $cols an array of columns to be retrieved [col1, col2, ...coln]
     * @return array an array of columns in the selected row or empty array if no match
    */
    public function findLast(string $column, int|string $value, array $cols = []):array
    {
        return $this->findExtremes($column, $value, "DESC", $cols);
    }

    /**
     * find the extremes row from the table where column is equal to the value
     * @param string $column the column been search
     * @param int|string $value the value to be searched
     * @param string $order either first(ASC) or last(DESC) row
     * @param array $cols an array of columns to be retrieved [col1, col2, ...coln]
     * @return array an array of columns in the selected row or empty array if no match
    */
    private function findExtremes(string $column, int|string $value, string $order, array $cols = []):array
    {
        $columns = "*";
        if ($cols) {
            $columns = implode(",", $cols);
        }
        $sql = "SELECT $columns FROM $this->table WHERE $column = :value ORDER BY id $order LIMIT 1";
        $row = [];
        $result = $this->query->executeSql($sql, ['value'=>$value])['rows'];
        $row = isset($result[0]) ? $result[0] : [];
        return $row;
    }

    /**
     * select row(s) from the table
     * @param array cols an array of columns to be retrieved [col1, col2, ...coln]
     * @param array where 2X2 array used for where clause [col1=>[operator, value, isValue/isFunction, join],
     *  ...coln=>[operator, value, isValue/isFunction]]] function if the value is an sql function, join must be a valid
     *  logic, join is not compulsory for the last item
     * @param string $order either first(ASC) or last(DESC) row
     * @param int $count the number of rows to be retrieved
     * @param int $start the row to start the retrievial from
     * @return array 2 dimensional array of selected rows or empty array if no match
    */
    public function retrieve(array $cols = [], array $where = [], string $order = "ASC", int $count = Functions::INFINITE, int $start = 1):array
    {
        if (!in_array(strtolower($order), ['asc', 'desc'])) {
            throw new TableExpection("order must be either 'ASC' or 'DESC'");
        }
        $columns = "*";
        if ($cols) {
            $columns = implode(",", $cols);
        }
        $whereClause = "";
        $binds = [];
        if ($where) {
            $whereClause = " WHERE ";
            $length = count($where);
            $kanter = 0;
            foreach ($where as $aWhereCol=>$aColInfo) {
                ++$kanter;
                $join = isset($aColInfo[3]) && $kanter != $length ? $aColInfo[3] : "";
                if ($aColInfo[2] == "isValue") {
                    $whereClause .=  " $aWhereCol {$aColInfo[0]} :$aWhereCol $join ";
                    $binds[$aWhereCol] = $aColInfo[1];
                } else {
                    $whereClause .=  " $aWhereCol {$aColInfo[0]} {$aColInfo[1]} $join ";
                }
            }
        }
        $limitAndCount = "";
        if ($count) {
            $limitAndCount = "LIMIT $count";
        }
        if ($count && $start) {
            --$start;
            $limitAndCount = "LIMIT $count OFFSET $start";
        }
        $sql = "SELECT $columns FROM $this->table $whereClause ORDER BY id $order $limitAndCount";
        return $binds ? $this->query->executeSql($sql, $binds)['rows'] : $this->query->executeSql($sql)['rows'];
    }
    
    /**
     * select a row from the table for table with autoincrement id column
     * @param int id the id of the row to be selected
     * @param array cols an array of columns to be retrieved [col1, col2, ...coln]
     * @return array an array of columns in the selected row or empty array if no match
    */
    public function retrieveOne(int $id, array $cols = []):array
    {
        return $this->selectById($id, $cols);
    }

    /**
     * select the first row from the table for table with autoincrement id column
     * @param array cols an array of columns to be retrieved [col1, col2, ...coln]
     * @return array an array of columns in the first row or empty array if no match
    */
    public function retrieveFirst(array $cols = []):array
    {
        return $this->selectFirst($cols);
    }

    /**
     * select the last row from the table for table with autoincrement id column
     * @param array cols an array of columns to be retrieved [col1, col2, ...coln]
     * @return array an array of columns in the last row or empty array if no match
    */
    public function retrieveLast(array $cols = []):array
    {
        return $this->selectLast($cols);
    }

    /**
     * update row(s) in the table
     * @param array cols a 2X2 array of columns to updated and the new data [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] function if the value is an sql function
     * @param array where 2X2 array used for where clause [col1=>[operator, value/function, isValue/isFunction, join],
     *  ...coln=>[operator, isValue/isFunction, value/function]]] function if the value is an sql function, join must be a
     *  valid logic, join is not compulsory for the last item
     * @return int the no of updated rows or zero if no rows was updated
    */
    public function update(array $cols, array $where):int
    {
        return $this->query->update($cols, $where);
    }

    /**
     * update a row in the table for table with autoincrement id column
     * @param int id the id of the row to be updated
     * @param array cols a 2X2 array of columns to updated and the new data [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] function if the value is an sql function
     * @return int one or zero if no rows was updated
    */
    public function updateOne(int $id, array $cols):int
    {
        return $this->query->update($cols, ['id'=>['=', $id, 'isValue']]);
    }

    /**
     * update the first row in the table for table with autoincrement id column
     * @param array cols a 2X2 array of columns to updated and the new data [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] function if the value is an sql function
     * @return int one or zero if no rows was updated
    */
    public function updateFirst(array $cols):int
    {
        return $this->updateExtremes("ASC", $cols);
    }

    /**
     * update the last row in the table for table with autoincrement id column
     * @param array cols a 2X2 array of columns to updated and the new data [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] function if the value is an sql function
     * @return int one or zero if no rows was updated
    */
    public function updateLast(array $cols):int
    {
        return $this->updateExtremes("DESC", $cols);
    }

    /**
     * update extreme row ie first or last for table with autoincrement id column
     *
     * @param string $order either first(ASC) or last(DESC) row
     * @param array cols a 2X2 array of columns to updated and the new data [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] function if the value is an sql function
     * @return int one or zero if no rows was updated
     */
    private function updateExtremes(string $order, array $cols):int
    {
        $columns = "";
        $binds = [];
        foreach ($cols as $colName => $colValueDetails) {
            if ($colValueDetails[1] == 'isValue') {
                $colValue = ":$colName";
                $binds[$colName] = $colValueDetails[0];
            } else {
                $colValue =  $colValueDetails[0];
            }
            $columns .= " $colName = $colValue ,";
        }
        $columns = rtrim($columns, " ,");
        $sql = "UPDATE $this->table SET $columns ORDER by id $order LIMIT 1";
        return $binds ? $this->query->executeSql($sql, $binds)['rowCount'] : $this->query->executeSql($sql)['rowCount'];
    }
    
    /**
     * delete row(s) from a table
     * @param array where 2X2 array used for where clause [col1=>[operator, value/function, isValue/isFunction, join],
     *  ...coln=>[operator, isValue/isFunction, value/function]]] function if the value is an sql function, join must be a
     *  valid logic, join is not compulsory for the last item
     * @return int the no of deleted rows or zero if no rows are deleted
    */
    public function delete(array $where):int
    {
        return $this->query->delete($where);
    }

    /**
     * delete a row from the table for table with autoincrement id column
     * @param int id the id of the row to be updated
     * @return int one or zero if no rows are deleted
    */
    public function deleteOne(int $id):int
    {
        return $this->query->delete(['id'=>['=', $id, 'isValue']]);

    }

    /**
     * delete the first row from the table for table with autoincrement id column
     * @return int one or zero if table is empty
    */
    public function deleteFirst():int
    {
        return $this->deleteExtremes("ASC");
    }

    /**
     * delete a row from the table for table with autoincrement id column
     * @return int one or zero if table is empty
    */
    public function deleteLast():int
    {
        return $this->deleteExtremes("DESC");
    }

    /**
     * delete extreme row ie first or last for table with autoincrement id column
     *
     * @param string $order either first(ASC) or last(DESC) row
     * @return int one or zero if no rows was deleted
     */
    private function deleteExtremes(string $order):int
    {
        $sql = "DELETE FROM $this->table ORDER by id $order LIMIT 1";
        return $this->query->executeSql($sql)['rowCount'];
    }

    /**
     * select data from table
     * @param array cols an array whose elements are columns in table [col1, col2, ...coln]
     * @param array where 2 dimensional array [col1=>[operator, value/function, isValue/isFunction, join], ...coln=>[operator, value/function, isValue/isFunction]]]
     *	function if the value is an sql function, join must be a valid logic, join is not compulsory for the last item
     * @param string table a table name in the database
     * @return array 2 dimensional array of selected rows or empty array if no match
    */
    public function select(array $cols = [], array $where = [], ?string $table = null):array
    {
        return $this->query->select($cols, $where);
    }

    /**
     * select data from table based on an id
     *
     * @param int id value of id column in the table
     * @param array cols an array whose elements are columns in table [col1, col2, ...coln]
     * @param string table a table name in the database
     * @return array an array of columns in selected row or empty array if no match
    */
    public function selectById(int $id, array $cols = []):array
    {
        $row = [];
        if ($rows =  $this->query->select($cols, ['id'=>['=', $id, 'isValue']])) {
            $row = $rows[0];
        }
        return $row;
    }

    /**
     * select the first row in the table
     *
     * @param array cols an array whose elements are columns in table [col1, col2, ...coln]
     * @return array an array of columns in the last row or empty array if no match
    */
    public function selectFirst(array $cols = []):array
    {
        return $this->selectExtremes("ASC", $cols);
    }

    /**
     * select the last row in the table
     *
     * @param array cols an array whose elements are columns in table [col1, col2, ...coln]
     * @return array an array of columns in the last row or empty array if no match
    */
    public function selectLast(array $cols = []):array
    {
        return $this->selectExtremes("DESC", $cols);
    }

    /**
     * select extreme row ie first or last
     *
     * @param string $order either first(ASC) or last(DESC) row
     * @param array $cols an array whose elements are columns in table [col1, col2, ...coln]
     * @return array an array of columns in the last row or empty array if no match
     */
    private function selectExtremes(string $order, array $cols = []):array
    {
        $columns = "*";
        if ($cols) {
            $columns = implode(",", $cols);
        }
        $sql = "SELECT $columns FROM $this->table ORDER BY id $order LIMIT 1";
        $firstRow = [];
        $result = $this->query->executeSql($sql)['rows'];
        $firstRow = isset($result[0]) ? $result[0] : [];
        return $firstRow;
    }

    /**
     * delete data from table based on an id
     *
     * @param int id value of id column in the table
     * @return int the no of deleted rows
    */
    public function deleteById(int $id):int
    {
        return $this->query->delete(['id'=>['=', $id, 'isValue']]);
    }

    /**
     * method is deprecated and should not be used. select data from table based on the id
     * @param int rowId the id of the row to be deleted
     * @deprecated method is no longer recommended. Use retrieveOne(int id) or selectById(int id) instead.
     * @return array an array of selected row or empty array if id is invalid
    */
    public function get(int $rowId):array
    {
        return $this->query->get($rowId);
    }

    /**
     * method is deprecated and should not be used. select the first row from table
     * @deprecated method is no longer recommended. Use retrieveFirst(int id) or selectFirst(int id) instead.
     * @return array an array of the first row or empty array if the table is empty
    */
    public function getFirst():array
    {
        return $this->query->getFirst();
    }

    /**
     * method is deprecated and should not be used. select the latest row from table
     * @deprecated method is no longer recommended. Use retrieveLast(int id) or selectLast(int id) instead.
     * @return array an array of the latest row or empty array if the table is empty
    */
    public function getLast():array
    {
        return $this->query->getLast();
    }

    /**
     * for getting all the data in a column of field of the table
     *
     * @param string $colName the column name
     * @param array where 2 dimensional array [col1=>[operator, value/function, isValue/isFunction, join], ...coln=>[operator, isValue/isFunction, value/function]]]
     *	function if the value is an sql function, join must be a valid logic, join is not compulsory for the last item
     * @return array
     */
    public function getColumn(string $colName, $where = []):array
    {
        $data = [];
        if ($result = $this->query->select([$colName], $where)) {
            foreach ($result as $aResult) {
                $data[] = $aResult[$colName];
            }
        }
        return $data;
    }

    /**
     * for getting all the data in a column, return as array indexed by another column
     *
     * @param string $colIndex the column for indexing
     * @param string $colValue the column for value
     * @param array where 2 dimensional array [col1=>[operator, value/function, isValue/isFunction, join], ...coln=>[operator, isValue/isFunction, value/function]]]
     *	function if the value is an sql function, join must be a valid logic, join is not compulsory for the last item
     * @return array
     */
    public function getColumnByIndex(string $colIndex, string $colValue, $where = []):array
    {
        $records = [];
        $result = $where ? $this->query->select([$colIndex, $colValue], [$where[0] => ['=', $where[1], 'isValue']]) :
            $this->query->select([$colIndex, $colValue]);
        if ($result) {
            foreach ($result as $aResult) {
                $records[$aResult[$colIndex]] = $aResult[$colValue];
            }
        }
        return $records;
    }

    /**
     * get an array of all rows in a table
     *
     * @param boolean $indexById whether to index the array by table.id or natural number
     * @param array $where the where clause
     * @return array the list of rows in the table
     */
    public function getAllRows(bool $indexById = true, array $where = []):array
    {
        $rows = [];
        if ($result = $this->select(where:$where)) {
            $rows = $result;
            if ($indexById) {
                $rows = [];
                foreach ($result as $aResult) {
                    $rows[$aResult['id']] = $aResult;
                }
            }
        }
        return $rows;
    }
}
