<?php
/* This class is part of the XP framework
 *
 * $Id$ 
 */

  uses(
    'unittest.TestCase',
    'com.flickr.xmlrpc.FlickrClient'
  );

  /**
   * FlickR client test
   *
   * @purpose  Testcase
   */
  class FlickrClientTest extends TestCase {
    public 
      $client= NULL;

    /**
     * Setup method. Creates client member
     *
     */
    public function setUp() {
      $this->client= new FlickrClient(new XmlRpcHttpTransport(FLICKR_XMLRPC_ENDPOINT));
    }
    
    /**
     * Test deserialization of a scalar
     *
     */
    #[@test]
    public function unserializeScalar() {
      $this->assertEquals(
        array('foo' => 'bar'),
        $this->client->unserialize('<foo>bar</foo>')
      );
    }
    
    /**
     * Test deserialization of a hash
     *
     */
    #[@test]
    public function unserializeArray() {
      $this->assertEquals(
        array('foo' => 'bar', 'bar' => 'baz'),
        $this->client->unserialize('<foo>bar</foo><bar>baz</bar>')
      );
    }

    /**
     * Test deserialization of a complex (hash of hashes)
     *
     */
    #[@test]
    public function unserializeComplex() {
      $this->assertEquals(
        array('foo' => array('bar' => 'baz')),
        $this->client->unserialize('<foo><bar>baz</bar></foo>')
      );
    }

    /**
     * Test deserialization of attributes
     *
     */
    #[@test]
    public function unserializeAttributes() {
      $this->assertEquals(
        array('foo' => array('bar' => 'baz')),
        $this->client->unserialize('<foo bar="baz"/>')
      );
    }
  }
?>
