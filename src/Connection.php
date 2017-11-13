<?php
/**
 * @package MongoCharter
 *
 * @author Steven Bakker <steven.bakker@npo.nl>
 * @uses MongoDB\BSON
 *
 * @TODO add support for sharding clusters
 */

/**
 * This file is used in other parts of the MongoCharter module to maintain connection to the database of duration of the session and prevent duplicate connections.
 */

namespace MongoCharter;
use MongoDB\BSON\Regex;

/**
 * Connection class for creating a mongoDB connection and storing it for duration of the session.
 * @param object $connection static singlethon storing itself in this variable.
 * @param object $pipe Connection Object "the pipe to MongoDB"
 */
class Connection {


  /**
   * @var Object $connection static object of it self
   */
  protected static $connection = null;
  /**
   * @var Object $pipe Connection pipe to the MongoDB server
   */
  public $pipe;

  /**
   * session static function to load already open connection or create a new connection to DB
   * @param  string $host        Hostname or IPadress where MongoDB is listening on
   * @param  string $port        port nummer where MongoDB server is listening on
   * @param  string $username    username used to connect to MongoDB server
   * @param  string $password    password used to connect to MongoDB server
   * @param  string $replsetName when using a MongoDB replicaSet give the name of te replicaset as a string value
   * @return Object              return self from property (singelthon object)
   */
  public static function session($host = null, $port = null, $username = null, $password = null, $replsetName = null)
  {
    if (!isset($connection)) {
      static::$connection = new Connection($host, $port, $username, $password, $replsetName);
    }
    return static::$connection;
  }

  /**
   * __construct used to create a new DB connection object. please only try to use the public static function to prevent multible DB connections per session
   * @param  string $host        Hostname or IPadress where MongoDB is listening on
   * @param  string $port        port nummer where MongoDB server is listening on
   * @param  string $username    username used to connect to MongoDB server
   * @param  string $password    password used to connect to MongoDB server
   * @param  string $replsetName when using a MongoDB replicaSet give the name of te replicaset as a string value
   */
  protected function __construct($host, $port, $username = null, $password = null, $replsetName = null) {
    $host = preg_replace('/[[:space:]]/', '', $host);
    $hostlist = explode(',', $host);
    foreach ($hostlist as $key => $value) {
      if(empty($hostx)) {
        $hostx =  "${value}:${port}";
      } else {
        $hostx =  "${hostx},${value}:${port}";
      }
    }
    // sample url for connecting to single mongodb server or replica set.
    // $url = "mongodb://${username}:${password}@${hostx}";
    if(!empty($host) && !empty($port) && !empty($username) && !empty($password) && !empty($replsetName) ) {
      $this->pipe = new \MongoDB\Driver\Manager("mongodb://${username}:${password}@${hostx}/?replicaSet=${replsetName}");
    } elseif(!empty($host) && !empty($port) && !empty($username) && !empty($password) ) {
      $this->pipe = new \MongoDB\Driver\Manager("mongodb://${username}:${password}@${hostx}");
    } elseif (!empty($host) && !empty($port)) {
      $this->pipe = new \MongoDB\Driver\Manager("mongodb://${hostx}");
    }

  }

  /**
   * __clone Preventing clone php function on this object
   * @return null returns a null value
   */
  public function __clone() {
    return null;
  }

}
