<?php

$config = new stdClass;
$config->couchdb = new stdClass;
$config->couchdb->host = 'localhost';
$config->couchdb->port = '5984';
$config->couchdb->dbname = 'copywaste_sessions';

// credentials of admin user on couchdb so we can create the db if it doesn't exist
// Once the database is created, you can delete this line if you don't like your credentials in web-readable files
$config->couchdb->credentials = "mp3db:kuttekop"; 
