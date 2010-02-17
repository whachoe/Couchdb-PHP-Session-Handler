<?php
   require_once 'couchdbwrapper.php';

   /**
    * CouchdbSessionHandler - a PHP Session Handler using Couchdb as backend
    * This class is more or less autoconfiguring.
    * If it doesn't find the database it is pointed to in the config, it tries to make it
    * and proceeds to load up the necessary design-docs it needs to function.
    *
    * @author Jo Giraerts <jo.giraerts@gmail.com>
    * @version 0.0.1
    * @package CouchdbSessionHandler
    */
   class CouchdbSessionHandler extends couchdbwrapper
   {
       public $lifeTime;
       public $initSessionData;
       protected $revision;
       
       function __construct($config = null)
       {
           if (!$config)
             return false;
             
           parent::__construct($config);
           $designdoc = '{
   "_id": "_design/sessions",
   "language": "javascript",
   "views": {
       "gc": {"map": "function(doc) { if (doc.type == \'session\') { emit(doc.expiration,doc._rev); }}"}
   }
}';
           register_shutdown_function("session_write_close");

           $this->lifeTime = intval(ini_get("session.gc_maxlifetime"));
           $this->initSessionData = null;

           // Let's try to make the database in case it doesn't exist
           $result = $this->send('GET', '');
           if (isset($result->error) && $result->error == "not_found") {
              $this->send('PUT', 'http://'.$this->config->couchdb->host.':'.$this->config->couchdb->port.'/'.$this->config->couchdb->dbname);

              // Load the design documents
              $result = $this->send('PUT', '_design/sessions', $designdoc);
           }
       }

       function open($savePath="",$sessionName="")
       {
           $sessionID = session_id();
           if ($sessionID !== "") {
               $this->initSessionData = $this->read($sessionID);
           }

           return true;
       }

       function close()
       {
           $this->lifeTime = null;
           $this->initSessionData = null;

           // Cleanup
           $this->gc();

           return true;
       }

       function read($sessionID)
       {
           $result = $this->send("GET", ''.$sessionID);

           if (isset($result->_id)) {
             $this->revision = $result->_rev;
             return $result->data;
           }
           else
            return '';
       }

       function write($sessionID,$data)
       {
           $exp = time() + $this->lifeTime;
           $rev = '';
           if ($this->revision != '')
            $rev = '"_rev": "'.$this->revision.'",';
           
           $result = $this->send("PUT", ''. $sessionID, '{'.$rev.'"type": "session","data": "'.addslashes($data).'", "data_unencoded":'.json_encode($_SESSION).',  "expiration": "'.$exp.'"}');

           if (isset($result->rev))
             $this->revision = $result->rev;
            
           return isset($result->ok) && $result->ok;
       }

       function destroy($sessionID, $revision=false)
       {
           // Let's go get the latest revision so we can get rid of this session cleanly
           if (!$revision) {
               $result = $this->send("GET", $sessionID);
               if (isset($result->_rev))
                 $revision = $result->_rev;
           }

           // Delete!
           if ($revision) {
             # Called when a user logs out...
             $result = $this->send("DELETE", $sessionID."?rev=".$revision);
             return isset($result->ok) && $result->ok;
           }

           return false;
       }

       function gc($maxlifetime=0) {
           $exp = time() - $this->lifeTime;
           $query =  "_design/sessions/_view/gc?endkey=\"".$exp."\"";
           
           $expired = $this->send("GET", $query);
           
           if (isset($expired->rows) && count($expired->rows) > 0) {
             foreach ($expired->rows as $row) {
               $this->destroy($row->id, $row->value);
             }
           }
           
           return true;
       }
   }
