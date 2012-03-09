<?php
require_once 'PHPUnit/Framework.php';
require_once 'client.php';
$username = '';//Username Goes here can be Username:Account
$api_key = '';//Api Key or Password goes here
$auth_url = '';//AuthUrl Goes Here ex. http://swiftrocks.com:8080/auth/v1.0
class client_test extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        global $username, $api_key, $auth_url;
        $this->auth = get_auth($auth_url, $username, $api_key);
    }
    function test_get_auth()
    {
        global $username, $api_key, $auth_url;
        $res = get_auth($auth_url, $username, $api_key);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
    }
    function test_get_auth_fail()
    {
        global $auth_url;
        $this->setExpectedException('ClientException');
        $res = get_auth($auth_url, '', '');
    }
    function test_snet_timeout_get_auth()
    {
        global $username, $api_key, $auth_url;
        $res = get_auth($auth_url, $username, $api_key, array('snet' => true,
                                                              'timeout' => 10));
        $storage_url = $res['headers']['x-storage-url'];
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(strstr($storage_url, 'snet-') == true);
    }
    function test_http_conn_get_auth()
    {
        global $username, $api_key, $auth_url;
        $host = prep_url($auth_url);
        $req = new http_request($host['host']);
        $res = get_auth($auth_url, $username, $api_key, array('http_conn' =>
                                                              $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
    }
    function test_get_account()
    {
        $res = get_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token']);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
        $this->assertTrue(count($res['containers']) ==
                                $res['headers']['x-account-container-count']);
        if (count($res['containers']))
        {
            $this->assertTrue(array_key_exists('name', $res['containers'][0]));
            $this->assertTrue(array_key_exists('count', $res['containers'][0]));
            $this->assertTrue(array_key_exists('bytes', $res['containers'][0]));
        }
    }
    function test_get_account_fail()
    {
        $this->setExpectedException('ClientException');
        $res = get_account($this->auth['headers']['x-storage-url'], 'ASDF');
    }
    function test_prefix_marker_limit_timeout_http_conn_get_account()
    {
        $surl = prep_url($this->auth['headers']['x-storage-url']);
        $conn = new http_request($surl['host']);
        $res = get_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'],
                           array('marker' => '', 'limit' => 1, 'prefix' => '',
                                 'timeout' => 10, 'http_conn' => $conn));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
        $res = get_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'],
                           array('marker' => '', 'limit' => 1, 'prefix' => '',
                                 'timeout' => 10, 'http_conn' => $conn));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
    }
    function test_full_listing_get_account()
    {
        $cont = array(uniqid('.swift__'), uniqid('.swift__'));
        foreach ($cont as $i)
        {
            put_container($this->auth['headers']['x-storage-url'],
                          $this->auth['headers']['x-storage-token'], $i);
        }
        $res = get_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'],
                           array('full_listing' => true, 'limit' => 1));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
        $this->assertTrue(count($res['containers']) ==
                                $res['headers']['x-account-container-count']);
        foreach ($res['containers'] as $i)
        {
            $this->assertTrue(array_key_exists('name', $i));
            $this->assertTrue(array_key_exists('count', $i));
            $this->assertTrue(array_key_exists('bytes', $i));
        }
        $this->assertTrue(count($res['containers']) >= 2);
        foreach ($cont as $i)
        {
            delete_container($this->auth['headers']['x-storage-url'],
                             $this->auth['headers']['x-storage-token'], $i);
        }
    }
    function test_head_account()
    {
        $res = head_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token']);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
    }
    function test_head_account_fail()
    {
        $this->setExpectedException('ClientException');
        $res = head_account($this->auth['headers']['x-storage-url'], 'asdf');
    }
    function test_timeout_http_conn_head_account()
    {
        $surl = prep_url($this->auth['headers']['x-storage-url']);
        $req = new http_request($surl['host']);
        $res = head_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'],
                           array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
        $res = head_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'],
                           array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
    }
    function test_post_account()
    {
        post_account($this->auth['headers']['x-storage-url'],
                     $this->auth['headers']['x-auth-token'],
                     array('x-account-meta-unit-test' => 'test'));
        $res = head_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token']);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-meta-unit-test',
                                           $res['headers']));
        $this->assertTrue($res['headers']['x-account-meta-unit-test'] ==
                          'test');
        post_account($this->auth['headers']['x-storage-url'],
                     $this->auth['headers']['x-auth-token'],
                     array('x-account-meta-unit-test' => ''));
        $res = head_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token']);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
        $this->assertFalse(array_key_exists('x-account-meta-unit-test',
                                           $res['headers']));
    }
    function test_post_account_fail()
    {
        $this->setExpectedException('ClientException');
        $res = post_account($this->auth['headers']['x-storage-url'], 'asdf',
                            array());
    }
    function test_timeout_http_conn_post_account()
    {
        $surl = prep_url($this->auth['headers']['x-storage-url']);
        $req = new http_request($surl['host']);
        post_account($this->auth['headers']['x-storage-url'],
                     $this->auth['headers']['x-auth-token'],
                     array('x-account-meta-unit-test' => 'test'),
                     array('timeout' => 10, 'http_conn' => $req));
        $res = head_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token']);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-meta-unit-test',
                                           $res['headers']));
        $this->assertTrue($res['headers']['x-account-meta-unit-test'] ==
                          'test');
        post_account($this->auth['headers']['x-storage-url'],
                     $this->auth['headers']['x-auth-token'],
                     array('x-account-meta-unit-test' => ''),
                     array('timeout' => 10, 'http_conn' => $req));
        $res = head_account($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token']);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-account-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-container-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-account-bytes-used',
                                           $res['headers']));
        $this->assertFalse(array_key_exists('x-account-meta-unit-test',
                                           $res['headers']));
    }
    function test_get_container()
    {
        $cont = uniqid('swift__');
        $obj = uniqid('swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'], $cont, $obj, '');
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('name', $res['objects'][0]));
        $this->assertTrue(array_key_exists('hash', $res['objects'][0]));
        $this->assertTrue(array_key_exists('bytes', $res['objects'][0]));
        $this->assertTrue(array_key_exists('content_type', $res['objects'][0]));
        $this->assertTrue(array_key_exists('last_modified',
                                           $res['objects'][0]));
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_get_container_fail()
    {
        $this->setExpectedException('ClientException');
        get_container($this->auth['headers']['x-storage-url'],
                      'asdf', 'zxcv');
    }
    function
    test_timeout_http_conn_limit_prefix_marker_delimiter_get_container()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        $surl = prep_url($this->auth['headers']['x-storage-url'],
                 $this->auth['headers']['x-auth-token']);
        $req = new http_request($surl['host']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'], $cont, $obj, '');
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont,
                      array('limit' => 1, 'marker' => '', 'prefix' => '',
                            'delimiter' => '', 'timeout' => 10,
                            'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('name', $res['objects'][0]));
        $this->assertTrue(array_key_exists('hash', $res['objects'][0]));
        $this->assertTrue(array_key_exists('bytes', $res['objects'][0]));
        $this->assertTrue(array_key_exists('content_type', $res['objects'][0]));
        $this->assertTrue(array_key_exists('last_modified',
                                           $res['objects'][0]));
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $surl = prep_url($this->auth['headers']['x-storage-url'],
                 $this->auth['headers']['x-auth-token']);
        $req = new http_request($surl['host']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'], $cont, $obj, '');
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont,
                      array('limit' => 1, 'marker' => '', 'prefix' => '',
                            'delimiter' => '', 'timeout' => 10,
                            'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('name', $res['objects'][0]));
        $this->assertTrue(array_key_exists('hash', $res['objects'][0]));
        $this->assertTrue(array_key_exists('bytes', $res['objects'][0]));
        $this->assertTrue(array_key_exists('content_type', $res['objects'][0]));
        $this->assertTrue(array_key_exists('last_modified',
                                           $res['objects'][0]));
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_full_listing_get_container()
    {
        $obj = array(uniqid('.swift__'), uniqid('.swift__'));
        $cont = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        foreach ($obj as $i)
        {
            put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'], $cont, $i, '');
        }
        $res = get_container($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'], $cont,
                           array('full_listing' => true, 'limit' => 1));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        foreach ($res['objects'] as $i)
        {
            $this->assertTrue(array_key_exists('name', $i));
            $this->assertTrue(array_key_exists('hash', $i));
            $this->assertTrue(array_key_exists('bytes', $i));
            $this->assertTrue(array_key_exists('content_type', $i));
            $this->assertTrue(array_key_exists('last_modified', $i));
        }
        $this->assertTrue(count($res['objects']) >= 2);
        foreach ($obj as $i)
        {
            delete_object($this->auth['headers']['x-storage-url'],
                          $this->auth['headers']['x-storage-token'],$cont, $i);
        }
        $obj = array(uniqid('.swift__/'), uniqid('.sswift__/'));
        foreach ($obj as $i)
        {
            put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'], $cont, $i, '');
        }
        $res = get_container($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'], $cont,
                           array('full_listing' => true, 'limit' => 1,
                                 'delimiter' => '/'));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        foreach ($res['objects'] as $i)
        {
            $this->assertTrue(array_key_exists('name', $i) or
                              array_key_exists('subdir', $i));
        }
        $this->assertTrue(count($res['objects']) >= 2);
        foreach ($obj as $i)
        {
            delete_object($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-storage-token'],$cont, $i);
        }
        delete_container($this->auth['headers']['x-storage-url'],
                         $this->auth['headers']['x-auth-token'], $cont);
    }
    function head_container()
    {
        $cont = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);

        $res = head_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        delete_container($this->auth['headers']['x-storage-url'],
                         $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_head_container_fail()
    {
        $this->setExpectedException('ClientException');
        head_container($this->auth['headers']['x-storage-url'],
                         'asdf', 'zxcv');
    }
    function test_http_conn_timeout_head_container()
    {
        $cont = uniqid('.swift__');
        $surl = prep_url($this->auth['headers']['x-storage-url'],
                $this->auth['headers']['x-auth-token']);
        $req = new http_request($surl['host']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        delete_container($this->auth['headers']['x-storage-url'],
                         $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_post_container()
    {
        $cont = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        post_container($this->auth['headers']['x-storage-url'],
                     $this->auth['headers']['x-auth-token'], $cont,
                     array('x-container-meta-unit-test' => 'test'));
        $res = head_container($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-meta-unit-test',
                                           $res['headers']));
        $this->assertTrue($res['headers']['x-container-meta-unit-test'] ==
                          'test');
        post_container($this->auth['headers']['x-storage-url'],
                     $this->auth['headers']['x-auth-token'], $cont,
                     array('x-container-meta-unit-test' => ''));
        $res = head_container($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        $this->assertFalse(array_key_exists('x-container-meta-unit-test',
                                           $res['headers']));
        delete_container($this->auth['headers']['x-storage-url'],
                         $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_post_container_fail()
    {
        $this->setExpectedException('ClientException');
        $res = post_container($this->auth['headers']['x-storage-url'], 
                              'asdf', 'asdf', array());
    }
    function test_timeout_http_conn_post_container()
    {
        $cont = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $surl = prep_url($this->auth['headers']['x-storage-url']);
        $req = new http_request($surl['host']);
        post_container($this->auth['headers']['x-storage-url'],
                     $this->auth['headers']['x-auth-token'], $cont,
                     array('x-container-meta-unit-test' => 'test'),
                     array('timeout' => 10, 'http_conn' => $req));
        $res = head_container($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-meta-unit-test',
                                           $res['headers']));
        $this->assertTrue($res['headers']['x-container-meta-unit-test'] ==
                          'test');
        post_container($this->auth['headers']['x-storage-url'],
                     $this->auth['headers']['x-auth-token'], $cont,
                     array('x-container-meta-unit-test' => ''),
                     array('timeout' => 10, 'http_conn' => $req));
        $res = head_container($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('x-container-object-count',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-container-bytes-used',
                                           $res['headers']));
        $this->assertFalse(array_key_exists('x-container-meta-unit-test',
                                           $res['headers']));
        delete_container($this->auth['headers']['x-storage-url'],
                         $this->auth['headers']['x-auth-token'], $cont);
    }
    function put_container()
    {
        $cont = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        delete_container($this->auth['headers']['x-storage-url'],
                         $this->auth['headers']['x-auth-token'], $cont);
        
    }
    function test_put_container_fail()
    {
        $this->setExpectedException('ClientException');
        put_container($this->auth['headers']['x-storage-url'],
                         '', 'zxcv');
    }
    function test_http_conn_timeout_put_container()
    {
        $cont = uniqid('.swift__');
        $surl = prep_url($this->auth['headers']['x-storage-url'],
                $this->auth['headers']['x-auth-token']);
        $req = new http_request($surl['host']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont,
                      array('timeout' => 10, 'http_conn' => $req));
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont,
                      array('timeout' => 10, 'http_conn' => $req));
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        delete_container($this->auth['headers']['x-storage-url'],
                         $this->auth['headers']['x-auth-token'], $cont);
    }
    function delete_container()
    {
        $cont = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        delete_container($this->auth['headers']['x-storage-url'],
                         $this->auth['headers']['x-auth-token'], $cont);
        $this->setExpectedException('ClientException');
        get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        
    }
    function test_delete_container_fail()
    {
        $this->setExpectedException('ClientException');
        delete_container($this->auth['headers']['x-storage-url'],
                         '', 'zxcv');
    }
    function test_http_conn_timeout_delete_container()
    {
        $cont = uniqid('.swift__');
        $surl = prep_url($this->auth['headers']['x-storage-url'],
                $this->auth['headers']['x-auth-token']);
        $req = new http_request($surl['host']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->setExpectedException('ClientException');
        get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont,
                      array('timeout' => 10, 'http_conn' => $req));
        $res = get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->setExpectedException('ClientException');
        get_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_get_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift');
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue($res['data'] == 'swift');
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_get_object_iterate()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift');
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('resp_chunk_size' => 1));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $iter = '';
        foreach ($res['data'] as $i) $iter .= $i;
        $this->assertTrue($iter == 'swift');
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_get_object_fail()
    {
        $this->setExpectedException('ClientException');
        get_object($this->auth['headers']['x-storage-url'],
                         '', 'zxcv', '');
    }
    function test_http_conn_timeout_get_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        $surl = prep_url($this->auth['headers']['x-storage-url'],
                $this->auth['headers']['x-auth-token']);
        $req = new http_request($surl['host']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift');
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue($res['data'] == 'swift');
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('resp_chunk_size' => 1, 'timeout' => 10,
                            'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $iter = '';
        foreach ($res['data'] as $i) $iter .= $i;
        $this->assertTrue($iter == 'swift');
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_head_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift');
        $res = head_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_head_object_fail()
    {
        $this->setExpectedException('ClientException');
        head_object($this->auth['headers']['x-storage-url'],
                         '', 'zxcv', array());
    }
    function test_timeout_http_conn_head_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        $surl = prep_url($this->auth['headers']['x-storage-url'],
                $this->auth['headers']['x-auth-token']);
        $req = new http_request($surl['host']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift');
        $res = head_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $res = head_object($this->auth['headers']['x-storage-url'],
                           $this->auth['headers']['x-auth-token'], $cont, $obj,
                           array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
        function test_post_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift');
        post_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('x-object-meta-unit-test' => 'test'));
        $res = head_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-object-meta-unit-test',
                                           $res['headers']));
        $this->assertTrue($res['headers']['x-object-meta-unit-test'] == 'test');
        post_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array());
        $res = head_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertFalse(array_key_exists('x-object-meta-unit-test',
                                           $res['headers']));
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_post_object_fail()
    {
        $this->setExpectedException('ClientException');
        post_object($this->auth['headers']['x-storage-url'],
                         '', 'zxcv', '', array());
    }
    function test_timeout_http_conn_post_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        $surl = prep_url($this->auth['headers']['x-storage-url'],
                $this->auth['headers']['x-auth-token']);
        $req = new http_request($surl['host']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift');
        post_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('x-object-meta-unit-test' => 'test'),
                            array('timeout' => 10, 'http_conn' => $req));
        $res = head_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('x-object-meta-unit-test',
                                           $res['headers']));
        $this->assertTrue($res['headers']['x-object-meta-unit-test'] == 'test');
        post_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array(), array('timeout' => 10, 'http_conn' => $req));
        $res = head_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertFalse(array_key_exists('x-object-meta-unit-test',
                                           $res['headers']));
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_put_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        $tmpf = tmpfile();
        fwrite($tmpf, 'swift');
        fseek($tmpf, 0);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, $tmpf);
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue($res['data'] == 'swift');
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swiftly');
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue($res['data'] == 'swiftly');
        fclose($tmpf);
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_put_object_fail()
    {
        $this->setExpectedException('ClientException');
        put_object($this->auth['headers']['x-storage-url'],
                         '', 'zxcv', '', '');
    }
    function test_http_conn_timeout_chunk_size_etag_put_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        $tmpf = tmpfile();
        fwrite($tmpf, 'swift');
        fseek($tmpf, 0);
        $surl = prep_url($this->auth['headers']['x-storage-url'],
                $this->auth['headers']['x-auth-token']);
        $req = new http_request($surl['host']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, $tmpf,
                   array('timeout' => 10, 'http_conn' => $req,
                         'chunk_size' => 1, 'etag' => md5('swift')));
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue($res['data'] == 'swift');
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swiftly',
                   array('timeout' => 10, 'http_conn' => $req,
                         'chunk_size' => 1, 'etag' => md5('swiftly')));
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue($res['data'] == 'swiftly');
        fclose($tmpf);
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_content_length_content_type_put_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        $tmpf = tmpfile();
        fwrite($tmpf, 'swift');
        fseek($tmpf, 0);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, $tmpf,
                   array('content-length' => 5,
                         'content-type' => 'swift/awesome'));
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue($res['headers']['content-type'] =='swift/awesome');
        $this->assertTrue($res['data'] == 'swift');
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swiftly',
                   array('content-length' => 7,
                         'content-type' => 'swift/awesome'));
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue($res['headers']['content-type'] =='swift/awesome');
        $this->assertTrue($res['data'] == 'swiftly');
        fclose($tmpf);
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_content_length_content_type_etag_headers_array_put_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        $tmpf = tmpfile();
        fwrite($tmpf, 'swift');
        fseek($tmpf, 0);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift',
                   array('headers' => array('content-length' => 5,
                         'content-type' => 'swift/awesome',
                         'etag' => md5('swift'))));
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue($res['headers']['content-type'] =='swift/awesome');
        $this->assertTrue($res['data'] == 'swift');
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swiftly',
                   array('headers' => array('content-length' => 7,
                         'content-type' => 'swift/awesome',
                         'etag' => md5('swiftly'))));
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        $this->assertTrue($res['headers']['content-type'] =='swift/awesome');
        $this->assertTrue($res['data'] == 'swiftly');
        fclose($tmpf);
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_delete_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift');
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        $this->setExpectedException('ClientException');
        get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj);
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
    function test_delete_object_fail()
    {
        $this->setExpectedException('ClientException');
        delete_object($this->auth['headers']['x-storage-url'],
                         '', 'zxcv', '');
    }
    function test_timeout_http_conn_delete_object()
    {
        $cont = uniqid('.swift__');
        $obj = uniqid('.swift__');
        $surl = prep_url($this->auth['headers']['x-storage-url'],
                $this->auth['headers']['x-auth-token']);
        $req = new http_request($surl['host']);
        put_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift');
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->setExpectedException('ClientException');
        get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('timeout' => 10, 'http_conn' => $req));
        put_object($this->auth['headers']['x-storage-url'],
                   $this->auth['headers']['x-auth-token'],
                   $cont, $obj, 'swift');
        $res = get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->assertLessThan(300, $res['status']);
        $this->assertGreaterThan(199, $res['status']);
        $this->assertTrue(array_key_exists('etag',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('last-modified',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-length',
                                           $res['headers']));
        $this->assertTrue(array_key_exists('content-type',
                                           $res['headers']));
        delete_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('timeout' => 10, 'http_conn' => $req));
        $this->setExpectedException('ClientException');
        get_object($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont, $obj,
                      array('timeout' => 10, 'http_conn' => $req));
        delete_container($this->auth['headers']['x-storage-url'],
                      $this->auth['headers']['x-auth-token'], $cont);
    }
}
?>
