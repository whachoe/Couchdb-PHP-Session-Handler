<?php
/**
 * This is a wrapper class to facilitate talking to a couchdb-instance.
 * It will use CURL if installed, else it falls back on sockets with fsockopen.
 * 
 * @author Jo Giraerts <jo.giraerts@gmail.com>
 * @version 0.0.5
 * @package CouchdbSessionHandler
 */
class couchdbwrapper
{
    protected $config;
    public $headers, $body; // contains returned headers and body of the last request
    
    function __construct($config = null)
    {
        if (!$config)
            return false;
            
        $this->config = $config;
    }

    public function send($method, $url, $post_data = NULL)
    {
      // Automatically fill in the dbname if it isn't provided at the start of the query
      if (strpos($url, '/'.$this->config->couchdb->dbname) !== 0 && strpos($url, "http://") === false) {
        $url = '/'.$this->config->couchdb->dbname.'/'.$url;
      }

      // We use curl if it exists
      if (function_exists('curl_init')) {
        if (strpos($url, 'http://') === false) {
            $url = 'http://'.$this->config->couchdb->host.':'.$this->config->couchdb->port.$url;
        }

        $c = curl_init($url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($c, CURLOPT_HEADER, 1);
        
        if ($method != 'GET') {
            curl_setopt($c, CURLOPT_CUSTOMREQUEST, $method);

            if (in_array($method, array('PUT', 'POST'))) {
                curl_setopt($c, CURLOPT_HTTPHEADER, array('Content-Length: '.strlen($post_data)));
                curl_setopt($c, CURLOPT_POSTFIELDS, $post_data);
            }
        }
        
        if ($this->config->couchdb->credentials) {
            curl_setopt($c, CURLOPT_USERPWD, $this->config->couchdb->credentials);
            curl_setopt($c, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        }

        $response = curl_exec($c);
        
      } else { // Fall back on sockets. Disadvantage: we can't do http-auth or i'm too dumb to do it
          $s = fsockopen($this->config->couchdb->host, $this->config->couchdb->port, $errno, $errstr);
          if(!$s) {
    //         echo "$errno: $errstr\n";
             return false;
          }

          $request = "$method $url HTTP/1.0\r\nHost: localhost\r\n";

          if($post_data) {
             $request .= "Content-Length: ".strlen($post_data)."\r\n\r\n";
             $request .= "$post_data\r\n";
          }
          else {
             $request .= "\r\n";
          }

          fwrite($s, $request);
          $response = "";

          while(!feof($s)) {
             $response .= fgets($s);
          }
          

      }
      
      list($this->headers, $this->body) = explode("\r\n\r\n", $response);
      return json_decode($this->body);
   }
}
