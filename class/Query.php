<?php

/**
 * Query
 *
 * A class for handling database query
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2022
 * @link        alabiansolutions.com
*/

class QueryExpection extends Exception
{
}

class Query
{
    /** @var string database table been used by sql  */
    protected string $table;

    /** @var DbConnect an instance of DbConnect  */
    protected DbConnect $dbConnect;

    /**
     * instantiation of Query
     *
     * @param string table default table in the database to be used as the sql query
     */
    public function __construct(?string $table = "", bool $checkTableExists = true)
    {
        $DbConnect = DbConnect::getInstance(SETTING_FILE);
        $this->dbConnect = $DbConnect;
        if ($table && $checkTableExists) {
            $this->throwExpectionIfTableDontExist($table);
        }
        $this->table = $table;
    }
    
    /**
     * get the table been used for query
     *
     * @return string the table name
    */
    public function getTable():string
    {
        return $this->table;
    }

    /**
     * set the table to be used for query
     *
     * @param string table the table to used
    */
    public function setTable(string $table)
    {
        if ($table) {
            $this->throwExpectionIfTableDontExist($table);
        }
        $this->table = $table;
    }

    /**
     * get the DbConnect been used for query
     *
     * @return DbConnect the DbConnect
    */
    public function getDbConnect():DbConnect
    {
        return $this->dbConnect;
    }

    /**
     * set the DbConnect been used for query
     *
     * @param DbConnect dbConnect to be used
    */
    public function setDbConnect(DbConnect $dbConnect)
    {
        $this->dbConnect = $dbConnect;
    }

    /**
     * throw exception if table does not exist
     *
     * @param string $table the table name been checked for
     */
    private function throwExpectionIfTableDontExist(string $table)
    {
        if (!$this->doTableExist($table)) {
            throw new QueryExpection("Query Error: Table '$table' does not exist", -1);
        }
    }

    /**
     * throw exception if any columns in cols does not exists in table
     *
     * @param array $cols an array of the columns to be checked
     * @param string $table the table name
     */
    private function throwExpectionIfColDontExistInTable(array $cols, string $table)
    {
        if ($cols) {
            foreach ($cols as $aCol) {
                if (!in_array($aCol, $this->getColumnsInTable($table))) {
                    throw new QueryExpection("Query Error: Column '$aCol' does not exist in Table '$table'", -1);
                }
            }
        }
    }

    /**
     * check whether table exists
     *
     * @param string $table the table name been checked for
     * @return bool  true if the table exists or false otherwise
     */
    public function doTableExist(string $table):bool
    {
        $tableExists = false;
        if (in_array($table, $this->getTablesInDb())) {
            $tableExists = true;
        }
        return $tableExists;
    }

    /**
     * get all the tables in the database
     * @return array an array of all tables in the database
    */
    public function getTablesInDb():array
    {
        $tables = [];
        $Settings = new Settings(SETTING_FILE, true);
        $sql = "SHOW TABLES FROM {$Settings->getDetails()->database->name}";
        if ($result = $this->executeSql($sql)['rows']) {
            foreach ($result as $key=>$value) {
                $tables[] = $value["Tables_in_{$Settings->getDetails()->database->name}"];
            }
        }
        return $tables;
    }

    /**
     * get all the columns in a table
     *
     * @param string table a table name in the database
     * @return array an array of the columns in a table
    */
    public function getColumnsInTable(string $table = ""):array
    {
        if (!$table) {
            $table = $this->table;
        }
        $this->throwExpectionIfTableDontExist($table);
        $columns = [];

        $sql = "SHOW COLUMNS FROM $table";
        if ($result = $this->executeSql($sql)['rows']) {
            foreach ($result as $key=>$value) {
                $columns[] = $value['Field'];
            }
        }
        return $columns;
    }
    
    /**
     * generate where clause to be used in sql queries
     *
     * @param array where 2 dimensional array [col1=>[operator, value/function, isValue/isFunction, join], ...coln=>[operator, isValue/isFunction, value/function]]]
     *	isFunction if the value is an sql function, join must be 'and' or 'or', join is not compulsory for the last item
     * @param string table a table name in the database
     * @return array [whereClause, bindArray]
     */
    private function generateWhereClause(array $where, string $table):array
    {
        $length = 0;
        $operators = ['=', '>', '<', '>=', '<=', '<>', '!=', 'IS', 'IS NOT', 'IS NULL', 'IS NOT NULL', 'LIKE', 'NOT LIKE'];
        foreach ($where as $key => $value) {
            if (!in_array($key, $this->getColumnsInTable($table))) {
                throw new QueryExpection("Query Error: invalid column in where parameter");
            }
            if (!in_array(trim(strtoupper($value[0])), $operators)) {
                throw new QueryExpection("Query Error: unsupported operator in where parameter");
            }
            if (!in_array(trim($value[2]), ['isValue', 'isFunction'])) {
                throw new QueryExpection("Query Error: indicate value as either 'isValue' or 'isFunction'");
            }
            ++$length;
            if ($length != count($where) && count($value) < 4) {
                throw new QueryExpection("Query Error: non-last index in the where parameter is missing join");
            }
            if (isset($value[3]) && (trim(strtolower($value[3])) != 'and' && trim(strtolower($value[3])) != 'or')) {
                throw new QueryExpection("Query Error: join in non-last index in the where parameter must be either 'and' or 'or'");
            }
        }

        $whereClause = " WHERE ";
        $bindArray=[];
        $length = 0;
        foreach ($where as $key => $value) {
            $operator = $value[0];
            ++$length;
            $join = isset($value[3]) && $length != count($where) ? $value[3] : "";
            if ($value[2] == 'isValue') {
                $bindArray["$key"] = $value[1];
                $whereClause .= " $key $operator :$key $join ";
            } else {
                $whereClause .= " $key $operator {$value[1]} $join ";
            }
        }
        return ['whereClause'=>$whereClause, 'bindArray'=>$bindArray];
    }

    /**
     * for running PDO prepare and PDOStatement execution methods
     *
     * @param string $sql the sql statement to be executed
     * @param array $params the parameters to be passed to the sql statement
     * @return array [rows=>[], lastInsertId=>int, rowCount=>int]
     */
    private function pdoPrepareExecute(string $sql, ?array $params = null):array
    {
        $rows = [];

        try {
            if (!$PdoStatment = $this->dbConnect->prepare($sql)) {
                throw new QueryExpection("Query Error 1: SQL preparation failed '$sql'", -1);
            }
            if ($params) {
                foreach ($params as $col => $val) {
                    $PdoStatment->bindValue(":$col", $val);
                }
            }
            if (!$PdoStatment->execute()) {
                throw new QueryExpection("Query Error 2: {$PdoStatment->errorInfo()[2]}", $PdoStatment->errorInfo()[0]);
            }
            $rows = $PdoStatment->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new QueryExpection("Query Error 3: {$e->getMessage()}", $e->getCode());
        }

        return ['rows'=>$rows, 'lastInsertId'=>$this->dbConnect->lastInsertId(), 'rowCount'=>$PdoStatment->rowCount()];
    }

    /**
     * select data from a table
     * @param array cols a an array whose elements are columns in table [col1, col2, ...coln]
     * @param array where 2 dimensional array [col1=>[operator, value/function, isValue/isFunction, join], ...coln=>[operator, isValue/isFunction, value/function]]]
     *	function if the value is an sql function, join must be a valid logic, join is not compulsory for the last item
     * @param string table a table name in the database
     * @return array 2 dimensional array of selected rows or empty array if no match
    */
    public function select(array $cols = [], array $where = [], ?string $table = null):array
    {
        if (!$table) {
            $table = $this->table;
        }
        $this->throwExpectionIfTableDontExist($table);
        $this->throwExpectionIfColDontExistInTable($cols, $table);
        
        $bindArray = [];
        $colList = "*";
        $whereClause = "";
        if ($cols) {
            $colList = implode(", ", $cols);
        }
        if ($where) {
            $generatedWhereClause = $this->generateWhereClause($where, $table);
            $whereClause = $generatedWhereClause['whereClause'];
            $bindArray = $generatedWhereClause['bindArray'];
        }
        $sql = "SELECT $colList FROM $table $whereClause";
        $preparedExecuted = $this->pdoPrepareExecute($sql, $bindArray);
        return $preparedExecuted['rows'];
    }
    
    /**
     * insert data into a table
     * @param array cols an array [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue], ...coln=>[value, isFunction/isValue]]]
     *	isFunction if the value is an sql function
     * @param string table a table name in the database
     * @param int the id of the last inserted id
    */
    public function insert(array $cols, ?string $table = null):int
    {
        if (!$table) {
            $table = $this->table;
        }
        $this->throwExpectionIfTableDontExist($table);
        $colNames = array_keys($cols);
        $this->throwExpectionIfColDontExistInTable($colNames, $table);

        $colList = implode(", ", $colNames);
        $bindArray = [];
        $values = "";
        foreach ($cols as $key => $aColVal) {
            if ($aColVal[1] != 'isValue' && $aColVal[1] != 'isFunction') {
                throw new QueryExpection("Query Error: indicate value as either 'isValue' or 'isFunction'");
            }
            if ($aColVal[1] == 'isValue') {
                $values .=" :$key, ";
                $bindArray[$key] = $aColVal[0];
            } else {
                $values .=" {$aColVal[0]}, ";
            }
        }
        $values = rtrim($values, ", ");

        $sql = "INSERT INTO $table ( $colList ) VALUES ( $values )";
        $preparedExecuted = $this->pdoPrepareExecute($sql, $bindArray);
        return $preparedExecuted['lastInsertId'];
    }

    /**
     * update data in a table
     * @param array cols an array [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue], ...coln=>[value, isFunction/isValue]]]
     *	function if the value is an sql function
     * @param array where 2 dimensional array [col1=>[operator, value/function, isValue/isFunction, join], ...coln=>[operator, isValue/isFunction, value/function]]]
     *	function if the value is an sql function, join must be a valid logic, join is not compulsory for the last item
     * @param string table a table name in the database
     * @param int the no of updated rows
    */
    public function update(array $cols, array $where, ?string $table = null):int
    {
        if (!$table) {
            $table = $this->table;
        }
        $this->throwExpectionIfTableDontExist($table);
        $colNames = array_keys($cols);
        $this->throwExpectionIfColDontExistInTable($colNames, $table);
        $colNames = array_keys($where);
        $this->throwExpectionIfColDontExistInTable($colNames, $table);

        $colBindArray = [];
        $colValList = "";
        foreach ($cols as $key => $aColVal) {
            if ($aColVal[1] != 'isValue' && $aColVal[1] != 'isFunction') {
                throw new QueryExpection("Query Error: indicate value as either 'isValue' or 'isFunction'");
            }
            if ($aColVal[1] == 'isValue') {
                $colValList .=" $key = :col_{$key}, ";
                $colBindArray["col_{$key}"] = $aColVal[0];
            } else {
                $colValList .=" $key = {$aColVal[0]}, ";
            }
        }
        $colValList = rtrim($colValList, ", ");

        $generatedWhereClause = $this->generateWhereClause($where, $table);
        $whereClause = $generatedWhereClause['whereClause'];
        $bindArray = $generatedWhereClause['bindArray'];
        if ($colBindArray) {
            foreach ($colBindArray as $colKey=>$colValue) {
                $bindArray[$colKey] = $colValue;
            }
        }
        
        $sql = "UPDATE $table SET $colValList $whereClause";
        $preparedExecuted = $this->pdoPrepareExecute($sql, $bindArray);
        return $preparedExecuted['rowCount'];
    }
    
    /**
     * delete data from a table
     * where 2 dimensional array [col1=>[operator, value/function, isValue/isFunction, join], ...coln=>[operator, isValue/isFunction, value/function]]]
     *	function if the value is an sql function, join must be a valid logic, join is not compulsory for the last item
     * @param string table a table name in the database
     * @param @param int the no of deleted rows
    */
    public function delete(array $where, ?string $table = null):int
    {
        if (!$table) {
            $table = $this->table;
        }
        $this->throwExpectionIfTableDontExist($table);

        $generatedWhereClause = $this->generateWhereClause($where, $table);
        $whereClause = $generatedWhereClause['whereClause'];
        $bindArray = $generatedWhereClause['bindArray'];
        
        $sql = "DELETE FROM $table $whereClause";
        $preparedExecuted = $this->pdoPrepareExecute($sql, $bindArray);
        return $preparedExecuted['rowCount'];
    }

    /**
     * select data from a table based on the id
     * @param int rowId the id of the row to be deleted
     * @param string table a table name in the database
     * @return array an array of selected row or empty array if id is invalid
    */
    public function get(int $rowId, ?string $table = null):array
    {
        if (!$table) {
            $table = $this->table;
        }
        $this->throwExpectionIfTableDontExist($table);

        $sql = "SELECT * FROM $table WHERE id = :id";
        $bindArray = ["id"=>$rowId];
        $result = $this->executeSql($sql, $bindArray);
        return isset($result['rows'][0]) ? $result['rows'][0] : [];
    }

    /**
     * select the first row from a table that has id as auto no
     * @param string table a table name in the database
     * @return array an array of the first row or empty array if the table is empty
    */
    public function getFirst(?string $table = null):array
    {
        if (!$table) {
            $table = $this->table;
        }
        $this->throwExpectionIfTableDontExist($table);

        $sql = "SELECT * FROM $table ORDER BY id ASC LIMIT 1";
        $result = $this->executeSql($sql);
        return isset($result['rows'][0]) ? $result['rows'][0] : [];
    }

    /**
     * select the first row from a table that has id as auto no
     * @param string table a table name in the database
     * @return array an array of the latest row or empty array if the table is empty
    */
    public function getLast(?string $table = null):array
    {
        if (!$table) {
            $table = $this->table;
        }
        $this->throwExpectionIfTableDontExist($table);

        $sql = "SELECT * FROM $table ORDER BY id DESC LIMIT 1";
        $result = $this->executeSql($sql);
        return isset($result['rows'][0]) ? $result['rows'][0] : [];
    }

    /**
     * execute an sql statement, for passed parameter append full colon eg "SELECT * from tbl WHERE id = :paramKey"
     *   to prevent sql injection all value should be pass via paramKey & paramVal.
     *   Use WHERE clause to reduce the return dataset incase large dataset is crashing your app
     * @param string sql the sql statement
     * @param array $params the parameters[paramKey1=>paramVal1, paramKey2=>paramVal2...] to be passed to the query
     * @return array [rows=>[], lastInsertId=>int, rowCount=>int]
    */
    public function executeSql(string $sql, array $params = []):array
    {
        $preparedExecuted = $this->pdoPrepareExecute($sql, $params);
        return ['rows'=>$preparedExecuted['rows'], 'lastInsertId'=>$preparedExecuted['lastInsertId'], 'rowCount'=>$preparedExecuted['rowCount']];
    }

    /**
     * execute some collection of sql as transaction
     *
     * @param array sqlCollection an array that contains sql and bind param [['sql'=>'', 'bind'=>[]], ['sql'=>'', 'bind'=>[]],...]
     *   sql - "SELECT * from tbl WHERE id = :paramKey"
     *   bind - [paramKey1=>paramVal1, paramKey2=>paramVal2...]
     * @return boolean true if all the transaction is successful or false if rollback
    */
    public function executeTransaction(array $sqlCollection):bool
    {
        try {
            $this->dbConnect->beginTransaction();
            
            foreach ($sqlCollection as $anSqlCollection) {
                $this->executeSql($anSqlCollection['sql'], $anSqlCollection['bind']);
            }
            
            $this->dbConnect->commit();
            $executed = true;
        } catch (Exception $e) {
            $this->dbConnect->rollBack();
            $executed = false;
        }

        return $executed;
    }

    /**
     * create an sql table
     * @param string table a table name in the database
     * @param array tableStructure an array representing the table struture
    */
    public function createTable(string $table, array $tableStructure)
    {
        //TODO the implementation was not completed
        //exit;
        $tableStructure = [
            "id" => [
                "index"=> "pk", "type"=> "int unsigned", "null"=> "no", "enum_val"=> "", "default"=> "",
                "others"=> "auto no", "relationship"=> "", "comment"=> ""],
            "code" => [
                "index"=> "", "type"=> "varchar(255)", "null"=> "no", "enum_val"=> "", "default"=> "",
                "others"=> "", "relationship"=> "", "comment"=> ""],
            "tablename" => [
                "index"=> "", "type"=> "int unsigned", "null"=> "no", "enum_val"=> "", "default"=> "",
                "others"=> "", "relationship"=> "", "comment"=> ""],
            "name" => [
                "index"=> "", "type"=> "varchar(255)", "null"=> "no", "enum_val"=> "", "default"=> "",
                "others"=> "", "relationship"=> "", "comment"=> ""]
        ];
        /*
            START TRANSACTION;

            DROP TABLE IF EXISTS `tablecode`;
            CREATE TABLE IF NOT EXISTS `tablecode` (
            `id` int UNSIGNED NOT NULL AUTO_INCREMENT,
            `code` varchar(255) NOT NULL,
            `tablename` int UNSIGNED NOT NULL,
            `name` varchar(255) NOT NULL DEFAULT 'alabi',

            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`),
            KEY `tablename` (`tablename`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

            ALTER TABLE `tablecode`
            ADD CONSTRAINT `tablecode_ibfk_1` FOREIGN KEY (`tablename`) REFERENCES `tablename` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE;
            COMMIT;
        */
        if (in_array($table, $this->getTablesInDb())) {
            throw new QueryExpection("Query Error: Table '$table' already exist", -1);
        }

        $columns = "";
        $keys = "";
        $alterStatement = "";
        $kanter = 0;
        foreach ($tableStructure as $colName => $properties) {
            $type = $nullable = $enumVal = $autoIncrement = $default = $autoTime = $comment = "";
            foreach ($properties as $propertyKey => $propertyValue) {
                if ($propertyKey == "index") {
                    if ($index = trim($propertyValue)) {
                        if ($index  == "pk") {
                            $keys .= " PRIMARY KEY ($colName), ";
                        } else {
                            foreach (explode(",", $index) as $anIndex) {
                                $anIndex = strtolower(trim($anIndex));
                                if ($anIndex == "unique") {
                                    $keys .= " UNIQUE KEY ($colName), ";
                                }
                                if ($anIndex != "unique") {
                                    $foreignTbl = explode(".", $anIndex)[0];
                                    $this->throwExpectionIfTableDontExist($foreignTbl);
                                    $foreignCol = explode(".", $anIndex)[1];
                                    $this->throwExpectionIfColDontExistInTable([$foreignCol], $foreignTbl);
                                    $keys .= " KEY $colName ($colName), ";
                                    ++$kanter;
                                    $delete = explode(".", $properties['relationship'])[0];
                                    $update = explode(".", $properties['relationship'])[1];
                                    $alterStatement .= " ALTER TABLE $table ADD CONSTRAINT {$table}_ibfk_$kanter FOREIGN KEY ($foreignTbl) 
                                        REFERENCES $foreignTbl ($foreignCol) ON $delete RESTRICT ON $update CASCADE, ";
                                }
                            }
                        }
                    }
                    
                }
                if ($propertyKey == "type") {
                    $type = " $propertyValue ";
                }
                if ($propertyKey == "null") {
                    $nullable = $propertyValue == "no" ? "NOT NULL" : "NULL";
                }
                if ($propertyKey == "enum_val" && $propertyValue) {
                    $value = "";
                    foreach (explode(",", $propertyValue) as $property) {
                        $value .="'".trim($property)."'";
                    }
                    $enumVal = "enum($value)";
                }
                if ($propertyKey == "default" && $propertyValue) {
                    $default = strtolower($propertyValue) == "current_time" ?  "DEFAULT $propertyValue" : "DEFAULT '$propertyValue'" ;
                }
                if ($propertyKey == "others" && $propertyValue) {
                    $autoTime = trim(strtolower($propertyValue)) == "on update current_time" ? "ON UPDATE CURRENT_TIMESTAMP" : "";
                    $autoIncrement = trim(strtolower($propertyValue)) == "auto no" ? "AUTO_INCREMENT" : "";
                }
                if ($propertyKey == "comment" && $propertyValue) {
                    $comment = "COMMENT '$propertyValue'";
                }
            }
            $columns .= " $colName $type $nullable $enumVal $autoIncrement $default $autoTime $comment, <br />";
        }
        $columns = rtrim($columns, ", <br />");
        $keys = rtrim($keys, ", ");
        $alterStatement = rtrim($alterStatement, ", ");
        $alterStatement = $alterStatement ? "$alterStatement;" : "";

        $sql = "START TRANSACTION;<br />";
        $sql .= " CREATE TABLE IF NOT EXISTS $table (<br />
            $columns,<br />
            $keys<br />
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
        $alterStatement<br />";
        $sql .= "COMMIT;";
        echo $sql;
        exit;
        $this->executeSql($sql);
    }

    /**
     * for populating a table with dummy records
     *
     * @param int noOfRecord no of records to be generated
     * @param string table a table name in the database
    */
    public function populateTable(int $noOfRecord, string $table="")
    {
        
    }
}
