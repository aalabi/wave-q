<?php

/**
 * ColumnMgr
 *
 * A class for common column validation in db's table
 * @author      Alabi A. <alabi.adebayo@alabiansolutions.com>
 * @copyright   Alabian Solutions Limited
 * @version 	1.0 => October 2023
 * @link        alabiansolutions.com
*/

class ColumnMgrExpection extends Exception
{
}

class ColumnMgr
{
    /** @var array collection of permitted image file extension */
    protected const IMG_EXTENSIONS = ['jpeg','jpg','png','gif', 'jpeg'=>'jpeg','jpg'=>'jpg','png'=>'png','gif'=>'gif'];

    /**
     * for validating id column as integer type
     *
     * @param int $id the id value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public static function id(int $id):array
    {
        $data = $id;
        $errors = [];
        if(!$id) {
            $data = null;
            $errors[] = "id is zero";
        }
        if(!SqlType::isIntOk($id)) {
            $data = null;
            $errors[] = "larger than MySQL integer";
        }

        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating created_at column
     *
     * @param DateTime $createdAt the created_at value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public static function createdAt(DateTime $createdAt):array
    {
        $validation = ColumnMgr::date($createdAt);
        return ['data'=>$validation['data'], 'errors'=>($validation['error'] == null ? [] : $validation['error'])];
    }

    /**
     * for validating updated_at column
     *
     * @param DateTime $updatedAt the updated_at value
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public static function updatedAt(DateTime $updatedAt):array
    {
        $validation = ColumnMgr::date($updatedAt);
        return ['data'=>$validation['data'], 'errors'=>($validation['error'] == null ? [] : $validation['error'])];
    }

    /**
     * for validating date value
     *
     * @param DateTime $date the date value
     * @param string $column the column'sname most used for customizing the error message
     * @return array ["data"=>$data, "error"=>$error] empty data implies no data, empty error implies no error
     */
    public static function date(DateTime $date, string $column = ""):array
    {
        $data = $date->format("Y-m-d H:i:s");
        $error = null;
        if(!SqlType::isDateTimeOk($date)) {
            $data = null;
            $error = "$column invalid date value for MySQL";
        }
        return ['data'=>$data, 'errors'=>$error];
    }

    /**
     * for validating varchar of max 255 characters
     *
     * @param string $varchar the varchar value
     * @param string $column the column'sname most used for customizing the error message
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public static function varcharMax255(string $varchar, string $colunm = ""):array
    {
        $data = $varchar;
        $errors = [];
        if(strlen(trim($varchar)) > 255) {
            $data = null;
            $errors[] = "$colunm more than 255 characters";
        }
        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating value to be max 255 characters and not empty
     *
     * @param string $value the value been checked
     * @param string $column the column'sname most used for customizing the error message
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public static function nonEmptyVarcharMax255(string $value, string $column):array
    {
        $data = $value;
        $errors = [];
        if(empty(trim($value))) {
            $data = null;
            $errors[] = "blank $column";
        }
        if($error = ColumnMgr::varcharMax255($value)['errors']) {
            $data = null;
            $errors[] = implode(", ", $error);
        }

        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating value to be of type MySQL text
     *
     * @param string $text the text been checked
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public static function text(string $text):array
    {
        $data = $text;
        $errors = [];
        if(!SqlType::isTextOk($text)) {
            $data = null;
            $errors[] = "more than ".SqlType::TEXT_LENGTH." characters";
        }
        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating value to be of type MySQL text and not empty
     *
     * @param string $text the text been checked
     * @param string $column the column's name most used for customizing the error message
     * @return array
     */
    public static function nonEmptyText(string $text, string $column):array
    {
        $data = $text;
        $errors = [];
        if(empty(trim($text))) {
            $data = null;
            $errors[] = "blank $column";
        }
        if($error = ColumnMgr::text($text)['errors']) {
            $data = null;
            $errors[] = implode(", ", $error);
        }

        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating a filename and max 255 characters
     *
     * @param string $filename the filename been checked
     * @param string $column the column'sname most used for customizing the error message
     * @param array $extensions the permitted file extensions
     * @return array
     */
    public static function filename(string $filename, string $column, array $extensions):array
    {
        $data = $filename;
        $errors = [];
        if(empty(trim($filename))) {
            $data = null;
            $errors[] = "blank $column";
        }
        if($error = ColumnMgr::varcharMax255($filename)['errors']) {
            $data = null;
            $errors[] = implode(", ", $error);
        }
        if(!in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), $extensions)) {
            $data = null;
            $errors[] = "$column file extension is not among (".implode(",", $extensions).")";
        }

        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating enum column
     *
     * @param string $value a value for the enum column
     * @param array $collection a collection of all possibles for the enum
     * @param string $column the column's name most used for customizing the error message
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validatePublish(string $value, array $collection, string $column):array
    {
        $data = $value;
        $errors = [];
        if(!in_array($value, $collection)) {
            $data = null;
            $errors[] = "invalid $column value";
        }

        return ['data'=>$data, 'errors'=>$errors];
    }

    /**
     * for validating the id, created_at and update_at columns in a row of data to be inserting/updating into the table
     *
     * @param mixed $row the row of data [id=>id, created_at=>created, updated_at=>updated]
     *  id = title = ... = [value, isValue/isFunction], created and updated must be DateTime object
     * @return array ["data"=>$data, "errors"=>$errors] empty data implies no data, empty errors array implies no errors
     */
    public function validateRow(array $row): array
    {
        $data = $row;
        $errors = [];
        if(isset($row['id'])) {
            if($error = ColumnMgr::id($row['id'][0])['errors']) {
                $errors[] = implode(", ", $error);
            }
        }
        if(isset($row['created_at'])) {
            if($error = ColumnMgr::createdAt($row['created_at'][0])['errors']) {
                $errors[] = implode(", ", $error);
            } else {
                $data['created_at'][0] = $row['created_at'][0]->format("Y-m-d H:i:s");
            }
        }
        if(isset($row['updated_at'])) {
            if($error = ColumnMgr::updatedAt($row['updated_at'][0])['errors']) {
                $errors[] = implode(", ", $error);
            } else {
                $data['updated_at'][0] = $row['updated_at'][0]->format("Y-m-d H:i:s");
            }
        }

        return ['data'=>$data, 'errors'=>$errors];
    }

}
