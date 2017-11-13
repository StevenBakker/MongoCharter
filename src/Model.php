<?php
/**
 * @package MongoCharter
 *
 * @author Steven Bakker <steven.bakker@npo.nl>
 * @uses MongoCharter\Collection
 *
 */

/**
 * This is the file you will extend your model classes from that describe your database collections and documents
 * In this file are some methods to parse your model class file properties and create a matching collection in the database.
 * The name of the model you create will be the collection name.
 * The other properties will help to describe the data it will reprecent but are not required.
 */

  namespace MongoCharter;

  /**
   * Model basic frame class for use to define data collections and documents
   */
  class Model extends Collection {

    /**
     * __construct important for this class because it start the constructor for collection but also parses child class properties
     */
    public function __construct() {
      // call parent
      parent::__construct();
      // build model attribuets
      $this->setFields();
    }

    /**
     * toJson convert data object to Json output to send back to clients or cache on file system
     * @return sting json string of the data represented in the model at the time of calling the function
     */
    public function toJson() {
      $arr = array();
      foreach (get_object_vars($this) as $key => $value) {
        if(is_object($value)) {
          foreach ($value as $sub_key => $sub_value) {
            if($sub_key === '_id' || $sub_key === 'createdAt') {
              $arr[$key][$sub_key] = $sub_value->__toString();
            } else {
              $arr[$key][$sub_key] = $sub_value;
            }
          }
        } else {
          if($key === '_id' || $key === 'createdAt') {
            $arr[$key] = $value->__toString();
          } else {
            $arr[$key] = $value;
          }
        }
      }
      return json_encode($arr);
    }

    /**
     * __get here to capture the translation diffrence of id or _id. a mongo dataset always holds _id property, but here you can also call $modulex->id to get the same value
     * @param  string $key requisted property name
     * @return any      property value
     */
    public function __get($key) {
      return ($key == 'id') ? $this->_id : $this->$key;
    }

    /**
     * clearObject A reset button on de dataobject, will unset all properties withing the object and rebuild as a fresh and shiny new one without creating new instance
     */
    public function clearObject() {
      foreach (get_object_vars($this) as $key => $value) {
        unset($this->$key);
      }
      $this->setFields();
    }

    /**
     * setFields will set some default field values and is called by __construct function
     * also filters out a id field if precent because mongoDB has _id that must be used.
     */
    private function setFields(){
      // ensure _id is set
      if(empty($this->_id)) $this->_id = $this->genID();
      // remove property id if given
      if(array_key_exists('id', get_object_vars($this))) unset($this->id);
      // ensure create date
      if(empty($this->createdAt)) $this->createdAt = $this->setCreateDate();
    }

  }
