<?php
/**
 * @package MongoCharter
 *
 * @author Steven Bakker <steven.bakker@npo.nl>
 * @uses MongoDB\BSON\Persistable
 *
 * @TODO add support for sharding clusters
 */

/**
 * Basic class for creating MongoDB document objects.
 * it handels the translation from php object to bson object and handels setting and getting of the object property's.
 * Part of this is to handle the difference between id and _id, thes because mongoDB uses _id but developers don't....
 * So prefenting unpredictive storing of the id key/value what later could result in confused developers..... and whe don't want confused developers
 */

namespace MongoCharter;

/**
 * To create a new Document Object that is able to be stored in MongoDB or able to do the reverse
 * propertys of the class are dynamicly created to represent the document in the database
 * The methods called on creation is the translation bsonSerialize from MongoDB library
 *
 * @TODO Rewrite documentation block because its stupid but works for now
 *
 * @property Object $_id The ID of the document, created on creation of the object
 * @property Object $createdAt The create data of the document, created on creation of the object
 */
class Document implements \MongoDB\BSON\Persistable {
  // default values
  // protected $_id;
  // protected $createdAt;

  /**
   * __construct does nothing itself it just here to be here ;~)
   */
  public function __construct() {}

  /**
   * __set setting variable as property of the class but filtering out id key/value pair
   * @param string  $key name of the property to create
   * @param any     $value value of the property can be of any type
   */
  public function __set($key, $value) {
    // set var if not 'id'
    if($key != 'id') $this->$key = $value;
  }

  /**
   * __get getter for reading the propertys captures when $key === id and returns _id property
   * @param  string $key
   * @return any    Returns the value of the given key as property name
   */
  public function __get($key) {
    return ($key == 'id') ? $this->_id : $this->$key;
  }

  /**
   * bsonSerialize method to translate Object propertys to BSON accepted by the mongoDB server.
   * This method is called when you store(save) a Object to the database
   * @return array Array ready for MongoDB
   */
  public function bsonSerialize() {
    $arr = [];
    foreach ($this as $key => $value) {
      $arr[$key] = $value;
    }
    return $arr;
  }

  /**
   * bsonUnserialize Turn a BSON array into a Object
   * This is called when you retrive a dataset from the database. This function will then be used to transform the documents to Objects.
   * it will assing the key/value pairs as propertys of the Object
   * @param  array  $data Given when retrived data from DB
   */
  public function bsonUnserialize(array $data)  {
    foreach ($data as $key => $value) {
      $this->$key = $value;
    }
  }

}
