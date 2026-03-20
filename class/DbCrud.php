<?php

/**
 * DbCrud
 *
 * An interface that spells out the database CRUD for a table
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2023
 * @link        alabiansolutions.com
 */

interface DbCrud
{
    /**
     * create a new row
     * @param array cols 2 dimensional array [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] isFunction if the value is an sql function
     * @return int the id of the last created id
    */
    public function create(array $cols):int;

    /**
     * select row(s) from the table
     * @param array cols an array of columns to be retrieved [col1, col2, ...coln]
     * @param array where 2X2 array used for where clause [col1=>[operator, value/function, isValue/isFunction, join],
     *  ...coln=>[operator, isValue/isFunction, value/function]]] function if the value is an sql function, join must be a valid
     *  logic, join is not compulsory for the last item
     * @return array 2 dimensional array of selected rows or empty array if no match
    */
    public function retrieve(array $cols = [], array $where = []):array;
    
    /**
     * select a row from the table
     * @param int id the id of the row to be selected
     * @param array cols an array of columns to be retrieved [col1, col2, ...coln]
     * @return array an array of columns in the selected row or empty array if no match
    */
    public function retrieveOne(int $id, array $cols = []):array;

    /**
     * select the first row from the table
     * @param array cols an array of columns to be retrieved [col1, col2, ...coln]
     * @return array an array of columns in the first row or empty array if no match
    */
    public function retrieveFirst(array $cols = []):array;

    /**
     * select the last row from the table
     * @param array cols an array of columns to be retrieved [col1, col2, ...coln]
     * @return array an array of columns in the last row or empty array if no match
    */
    public function retrieveLast(array $cols = []):array;

    /**
     * update row(s) in the table
     * @param array cols a 2X2 array of columns to updated and the new data [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] function if the value is an sql function
     * @param array where 2X2 array used for where clause [col1=>[operator, value/function, isValue/isFunction, join],
     *  ...coln=>[operator, isValue/isFunction, value/function]]] function if the value is an sql function, join must be a
     *  valid logic, join is not compulsory for the last item
     * @return int the no of updated rows or zero if no rows was updated
    */
    public function update(array $cols, array $where):int;

    /**
     * update a row in the table
     * @param int id the id of the row to be updated
     * @param array cols a 2X2 array of columns to updated and the new data [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] function if the value is an sql function
     * @return int one or zero if no rows was updated
    */
    public function updateOne(int $id, array $cols):int;

    /**
     * update the first row in the table
     * @param array cols a 2X2 array of columns to updated and the new data [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] function if the value is an sql function
     * @return int one or zero if no rows was updated
    */
    public function updateFirst(array $cols):int;

    /**
     * update the last row in the table
     * @param array cols a 2X2 array of columns to updated and the new data [col1=>[value, isFunction/isValue], col2=>[value, isFunction/isValue],
     *  ...coln=>[value, isFunction/isValue]]] function if the value is an sql function
     * @return int one or zero if no rows was updated
    */
    public function updateLast(array $cols):int;
    
    /**
     * delete row(s) from a table
     * @param array where 2X2 array used for where clause [col1=>[operator, value/function, isValue/isFunction, join],
     *  ...coln=>[operator, isValue/isFunction, value/function]]] function if the value is an sql function, join must be a
     *  valid logic, join is not compulsory for the last item
     * @return int the no of deleted rows or zero if no rows are deleted
    */
    public function delete(array $where):int;

    /**
     * delete a row from the table
     * @param int id the id of the row to be updated
     * @return int one or zero if no rows are deleted
    */
    public function deleteOne(int $id):int;

    /**
     * delete the first row from the table
     * @return int one or zero if table is empty
    */
    public function deleteFirst():int;

    /**
     * delete a row from the table
     * @return int one or zero if table is empty
    */
    public function deleteLast(int $id):int;
}
