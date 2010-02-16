<?php
   include_once 'config.inc.php';

   // Session stuff
   require_once 'couchdb_session_handler.php';
   ini_set("session.gc_maxlifetime",60*30); # 30 minutes
   //session_set_cookie_params(0,"/",".localdomain",false,true);
   session_name("testsession");
   $sessionHandler = new CouchdbSessionHandler($config);
   session_set_save_handler(array ($sessionHandler,"open"),array
($sessionHandler,"close"),array ($sessionHandler,"read"),array
($sessionHandler,"write"),array ($sessionHandler,"destroy"),array
($sessionHandler,"gc"));
   session_start();



   echo "<b>Before:</b> ".$_SESSION['blah']."<br/>\n";
   var_dump($_SESSION['blahobject']);
   echo "<br/>\n";
   $_SESSION['blah'] = (isset($_SESSION['blah']) ? $_SESSION['blah']+1 : 0);
   $blahobject = (isset($_SESSION['blahobject']) ? $_SESSION['blahobject'] : new stdclass);
   $blahobject->name = "blahobject test";
   $blahobject->counter = is_numeric($blahobject->counter) ? $blahobject->counter + 1: 0;
   $_SESSION['blahobject'] = $blahobject;

   echo "<b>After:</b> ".$_SESSION['blah']."<br/>\n";
   var_dump($_SESSION['blahobject']);

   // Testing garbage collection
   // $sessionHandler->gc();
?>
