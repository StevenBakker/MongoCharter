<?php
/**
 * @package MongoCharter
 *
 * @author Steven Bakker <steven.bakker@npo.nl>
 * @uses MongoDB\Collection
 *
 */

/**
 * This will contain all the methods that are required for interaction with mongoDB.
 * You can use the default methods from the MongoDB\Collection class in some cases but be aware that certain methods are written to handle the translation between Object and MongoDB.
 * To fetch/create/store and update data use the functions defined in this file or else you will have a hard time...
 */


namespace MongoCharter;

/**
 * Collection is the main class with most logic for using this module.
 * It handels the correct translation from object to MongoDB and back.
 * Also it provides the methods to interact with the Database and retrive/create/update or delete data.
 *
 * It written that you only need to fetch required data once in a session and save when all manupulations are done.
 *
 * @TODO create option to cache data requests and there response in json format. This will make it possible to static serve datasets that dont update reguraly
 *
 */
class Collection extends \MongoDB\Collection
{

  /**
   * private A private array of key's to filter out of the object when retrived or saved
   * @var $mongoFilter Array
   * @TODO Think about a option to update and add keys to the filter
   */
  private $mongoFilter = array('__pclass', 'mongoFilter');

  /**
   * __construct Loads required configuration from the config module.
   * Then create connection object to the database to use.
   * lastly call the parent contructer function to correctly init Collection object part.
   *
   * @TODO Make docblock better....
   */
  public function __construct() {
    // start config manager
    $configurationManager = new \Config\ConfigFiles();
    $config = $configurationManager->getConfigFiles();
    // select mongoCharterConfig
    $dbconf = $config['MongoCharter']['db'];

    // fetch DB Connection
    $conn = Connection::session($dbconf['host'], $dbconf['port'], $dbconf['username'], $dbconf['password'], $dbconf['replsetName']);
    $driver = $conn->pipe;

    // execute parent Constructer method to init de collection object
    parent::__construct($driver, $dbconf['database'], end(explode("\\",get_class($this))));
  }


  /**
   * genID generate a new object ID for MongoDB
   * @return Object $objectID MongoDB
   */
  protected function genID() {
    $objectID = new \MongoDB\BSON\ObjectID;
    return $objectID;
  }

  /**
   * setCreateDate Generate a MongoDB compatible create date object
   * @return Object $createdAt a MongoDB Compitable date object
   */
  protected function setCreateDate() {
    $msec = floor(microtime(true) * 1000);
    $createdAt = new \MongoDB\BSON\UTCDateTime($msec);
    return $createdAt;
  }

  /**
   * objectToList Transforms the object to a array with objects to support multible documents withing a collection as a object
   */
  protected function objectToList() {
    if(!empty($this->_id) && !empty($this->createdAt)) {
      // create array of model properties
      $properties = get_object_vars($this);
      // create new document object
      $document = new Document();
      foreach($properties as $key => $value) {
        if(!in_array($key, $this->mongoFilter)){
          $document->$key = $value;
          unset($this->$key);
        }
      }
      // get doc id
      $doc = $document->_id;
      // set document in model object as property
      $this->$doc = $document;
    }
  }

  /**
   * listToObject Transform a list to a single object from a single record(document) from the list idetifed by its ID
   * @param  string $id MongoDB object ID in string format
   */
  protected function listToObject($id) {
    $documents = get_object_vars($this);
    foreach($documents as $key => $value) {
      if($key == $id) {
        foreach($value as $documentKey => $documentValue) {
          $this->$documentKey = $documentValue;
        }
      }
      if($key != 'mongoFilter') unset($this->$key);
    }
  }

  /**
   * addObjectToList Add a new Object to the list if there is a list. if there is no list the current object will be converted to a list and the new object will be added
   * @param array $args array of key/values to add to the new object
   * @uses $this->objectToList()
   * @uses $this->genID()
   * @uses $this->setCreateDate()
   */
  protected function addObjectToList(array $args) {
    if(!empty($this->_id)) $this->objectToList();
    // create new document
    $document = new Document();

    if(!array_key_exists('_id', $args)) $args['_id'] = $this->genID();
    if(!array_key_exists('createdAt', $args)) $args['createdAt'] = $this->setCreateDate();
    // fill the document
    foreach($args as $key => $value) {
      if(!in_array($key, $this->mongoFilter)){
        $document->$key = $value;
      }
    }
    // get doc id
    $doc = $document->_id;
    // set document in model object as property
    $this->$doc = $document;
  }

  /**
   * removeObjectFromList Removes items from the object list based on the given $id, the id === array key
   * @param  string $id Document ID (key in the list array)
   * @return boolean     True succesfull OR False unsuccesfull
   */
  protected function removeObjectFromList($id) {
    if(array_key_exists($id, get_object_vars($this))) {
      $this->$id->remove = true;
    } else {
      return false;
    }
  }

  /**
   * documentToObject Translate a single MongoDB Document to a Object propertys
   * @param  MongoDB Document $document The returnt document for mongoDB
   */
  protected function documentToObject($document) {
    // drop current data
    foreach(get_object_vars($this) as $key => $value) {
      if(!in_array($key, $this->mongoFilter)){
        unset($this->$key);
      }
    }

    // populate Object with new data
    foreach($document as $key => $value) {
      if(!in_array($key, $this->mongoFilter)){
        $this->$key = $value;
      }
    }
  }

  /**
   * saveObjectToDB Save the current Object to the database filtering out mongoDB conflicting key's.
   * This will add it by using the MongoDB findOneAndUpdate() method so if document with current ID exists it will be updated.
   * If the Document does not exists it will create it in the database with insertOne()
   * @param  Object $object Current Object representing the database Document
   * @uses $this->objectToList()
   */
  private function saveObjectToDB($object) {
    if(!empty($object->_id)) $this->objectToList();

    foreach ($object as $id => $document) {
      if($id != 'mongoFilter') {
        $result = $this::findOneAndUpdate(array('_id' => $document->_id), array('$set' => $document));
        if($result == null) $this::insertOne($document);
      }
    }
  }

  /**
   * findSingle finds a single document in the database based on key value pairs in the given array.
   * The first document it finds that matches will be used and returnt
   * @param  array  $query the search params in array like array('$key'=>'$value') where you want to match on
   * @return boolean  true on success False on failure
   * @uses $this->documentToObject()
   */
  public function findSingle(array $query) {
    // get data from db
    $queryResult = ($query ? parent::findOne($query) : false);

    // build object from data
    if($queryResult != false) $this->documentToObject($queryResult);
    if($queryResult != false) {
      return true;
    } else {
      return false;
    }

  }

  /**
   * findAll depending if a array query is given it will fetch all result that match the query or are in the collection.
   * if only one result is returnt from the database then it will turn it into a object else it will be a list(array)
   * @param  array  $query       The search query array "array('$key'=>'$value')" defaults null
   * @param  boolean $dropOldData Drop already existing data in object? defaults to true
   * @return boolean  true on success False on failure also returns false on results <= 0
   * @uses $this->objectToList()
   * @uses $this->listToObject()
   */
  public function findAll($query = null, $dropOldData = true) {

    $amount = 0;

    // get data from db
    $queryResult = ($query ? parent::find($query) : parent::find());

    // if object has data turn to list or drop
    if($dropOldData) {
      foreach(get_object_vars($this) as $key => $value) {
        if(!in_array($key, $this->mongoFilter)){
          unset($this->$key);
        }
      }
    } else {
      $this->objectToList();
    }

    // build list from found data
    foreach($queryResult as $value) {
      $docId = $value->_id;
      unset($value->__pclass);
      $this->$docId = $value;
      $amount++;
    }

    // if its just one port from list to object
    if($amount <= 1 ) {
      $this->listToObject($docId);
    }

    if($amount <= 0 ) {
      return false;
    } else {
      return true;
    }
  }

  /**
   * save Store your object or list to the datebase. there are here 2 handler functions when they exist in the model class will be called.
   *
   * 1. $this->before_save()
   *
   * 2. $this->after_save()
   *
   *
   * @param  Object $object default value null it will use itself else give Document Object
   * @uses $this->saveObjectToDB()
   */
  public function save($object = null) {
    if(method_exists($this, 'before_save')) $this->before_save();
    ($object == null ? $this->saveObjectToDB($this) : $this->saveObjectToDB($object));
    if(method_exists($this, 'after_save')) $this->after_save();
  }

  /**
   * delete delete document in the database, always requires string ID ob a database document
   * @param  string $id Docuement ID in string format
   * @return boolean   return boolean succes state
   */
  public function delete(string $id) {
    $id = new \MongoDB\BSON\ObjectID($id);
    $result = (parent::findOneAndDelete(array('_id' => $id)) ? true : false);
    return $result;
  }

  // public function test_object() {
  //   return get_object_vars($this);
  // }

}
