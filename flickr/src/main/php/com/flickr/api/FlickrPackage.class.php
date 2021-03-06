<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  /**
   * Base class for a flickr packages
   *
   * @see      xp://com.flickr.xmlrpc.FlickrClient
   * @purpose  Flickr package base class
   */
  class FlickrPackage extends Object {
    public
      $client   = NULL;
    
    /**
     * Sets the client for this package
     *
     * @param   &com.flickr.xmlrpc.FlickrClient client
     */
    public function setClient($client) {
      $this->client= $client;
    }    
  }
?>
