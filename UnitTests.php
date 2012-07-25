<?php
require 'client.php';
class TestClient extends PHPUnit_Framework_TestCase 
{
    public function testGetAuth() 
    {
        $client = new Client(60, new FakeHTTPFactory());
        $res = $client->get_auth('http://foo.com', 'asdf', 'get_auth');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testGetAuthFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $res = $client->get_auth('http://foo.com', 'asdf', 'get_auth_fail');
    }
    /**
     * @expectedException ClientException
     **/
    public function testGetAuthFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $res = $client->get_auth('http://foo.com', 'asdf', 'get_auth_fail_401');
    }

    public function testGetAuthFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $res = $client->get_auth('http://foo.com', 'asdf', 'get_auth_fail_success');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
    }
    public function testGetAuthSnet()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $res = $client->get_auth('http://foo.com', 'asdf', 'get_auth', true);
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
        $this->assertEquals($headers['x-storage-url'], 'http://snet-foo.com');
    }
    public function testGetAccount()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $res = $client->get_account('http://foo.com', 'get_account');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $containers = $res->GetContainers();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
        $this->assertEquals($containers[0]['name'], 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testGetAccountFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->get_account('http://foo.com', 'get_account_fail');
    }
    /**
     * @expectedException ClientException
     **/
    public function testGetAccountFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->get_account('http://foo.com', 'get_account_fail_401');
    }

    public function testGetAccountFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $res = $client->get_account('http://foo.com', 'get_account_fail_success');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $containers = $res->GetContainers();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
        $this->assertEquals($containers[0]['name'], 'foo');
    }
    public function testGetAccountFullListing()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $args = array('full_listing' => true);
        $res = $client->get_account('http://foo.com', 'get_account_full_listing', $args);
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $containers = $res->GetContainers();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
        $this->assertEquals($containers[0]['name'], 'foo');
        $this->assertEquals($containers[1]['name'], 'foo2');
    }
    public function testHeadAccount()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $res = $client->head_account('http://foo.com', 'head_account');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testHeadAccountFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->head_account('http://foo.com', 'head_account_fail');
    }
    /**
     * @expectedException ClientException
     **/
    public function testHeadAccountFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->head_account('http://foo.com', 'head_account_fail_401');
    }
    public function testHeadAccountFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $res = $client->head_account('http://foo.com', 'head_account_fail_success');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
    }

    public function testPostAccount()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->post_account('http://foo.com', 'post_account');
    }
    /**
     * @expectedException ClientException
     **/
    public function testPostAccountFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->post_account('http://foo.com', 'post_account_fail');
    }
    /**
     * @expectedException ClientException
     **/
    public function testPostAccountFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->post_account('http://foo.com', 'post_account_fail_401');
    }
    public function testPostAccountFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $client->post_account('http://foo.com', 'post_account_fail_success');
    }
    public function testGetContainer()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $res = $client->get_container('http://foo.com', 'get_container', 'foo');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $objects = $res->GetObjects();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
        $this->assertEquals($objects[0]['name'], 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testGetContainerFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->get_container('http://foo.com', 'get_container_fail', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testGetContainerFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->get_container('http://foo.com', 'get_container_fail_401', 'foo');
    }
    public function testGetContainerFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $res = $client->get_container('http://foo.com', 'get_container_fail_success', 'foo');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $objects = $res->GetObjects();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
        $this->assertEquals($objects[0]['name'], 'foo');
    }
    public function testGetContainerFullListing()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $args = array('full_listing' => true);
        $res = $client->get_container('http://foo.com', 'get_container_full_listing', 'foo', $args);
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $objects = $res->GetObjects();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
        $this->assertEquals($objects[0]['name'], 'foo');
        $this->assertEquals($objects[1]['name'], 'foo2');
    }
    public function testHeadContainer()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $res = $client->head_container('http://foo.com', 'head_container', 'foo');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testHeadContainerFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->head_container('http://foo.com', 'head_container_fail', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testHeadContainerFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->head_container('http://foo.com', 'head_container_fail_401', 'foo');
    }
    public function testHeadContainerFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $res = $client->head_container('http://foo.com', 'head_container_fail_success', 'foo');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
    }
    public function testPostContainer()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->post_container('http://foo.com', 'post_container', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testPostContainerFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->post_container('http://foo.com', 'post_container_fail', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testPostContainerFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->post_container('http://foo.com', 'post_container_fail_401', 'foo');
    }
    public function testPostContainerFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $client->post_container('http://foo.com', 'post_container_fail_success', 'foo');
    }
    public function testPutContainer()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->put_container('http://foo.com', 'put_container', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testPutContainerFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->put_container('http://foo.com', 'put_container_fail', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testPutContainerFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->put_container('http://foo.com', 'put_container_fail_401', 'foo');
    }
    public function testPutContainerFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $client->put_container('http://foo.com', 'put_container_fail_success', 'foo');
    }
    public function testDeleteContainer()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->delete_container('http://foo.com', 'delete_container', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testDeleteContainerFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->delete_container('http://foo.com', 'delete_container_fail', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testDeleteContainerFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->delete_container('http://foo.com', 'delete_container_fail_401', 'foo');
    }
    public function testDeleteContainerFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $client->delete_container('http://foo.com', 'delete_container_fail_success', 'foo');
    }
    public function testGetObject()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $res = $client->get_object('http://foo.com', 'get_object', 'foo', 'foo');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
        $this->assertEquals($res->GetData(), 'asdf');
    }
    /**
     * @expectedException ClientException
     **/
    public function testGetObjectFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->get_object('http://foo.com', 'get_object_fail', 'foo', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testGetObjectFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->get_object('http://foo.com', 'get_object_fail_401', 'foo', 'foo');
    }
    public function testGetObjectFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $res = $client->get_object('http://foo.com', 'get_object_fail_success', 'foo', 'foo');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
        $this->assertEquals($res->GetData(), 'asdf');
    }
    public function testObjectContainer()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $res = $client->head_object('http://foo.com', 'head_object', 'foo', 'foo');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testHeadObjectFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->head_object('http://foo.com', 'head_container_fail', 'foo', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testHeadObjectFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->head_object('http://foo.com', 'head_container_fail_401', 'foo', 'foo');
    }
    public function testHeadObjectFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $res = $client->head_object('http://foo.com', 'head_container_fail_success', 'foo', 'foo');
        $this->assertEquals($res->GetStatus(), 200);
        $this->assertEquals($res->GetReason(), 'foo');
        $headers = $res->GetHeaders();
        $this->assertTrue(array_key_exists('x-unit-test-header', $headers));
        $this->assertEquals($headers['x-unit-test-header'], 'foo');
    }
    public function testPostObject()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->post_object('http://foo.com', 'post_object', 'foo', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testPostObjectFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->post_object('http://foo.com', 'post_object_fail', 'foo', 'foo');
    }
     /**
     * @expectedException ClientException
     **/
    public function testPostObjectFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->post_object('http://foo.com', 'post_object_fail_401', 'foo', 'foo');
    }
    public function testPostObjectFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $client->post_object('http://foo.com', 'post_object_fail_success', 'foo', 'foo');
    }
    public function testPutObject()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->put_object('http://foo.com', 'put_object', 'foo', 'foo', 'asdf');
    }
    /**
     * @expectedException ClientException
     **/
    public function testPutObjectFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->put_object('http://foo.com', 'put_object_fail', 'foo', 'foo', 'asdf');
    }
    /**
     * @expectedException ClientException
     **/
    public function testPutObjectFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->put_object('http://foo.com', 'put_object_fail_401', 'foo', 'foo', 'asdf');
    }

    public function testPutObjectFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $client->put_object('http://foo.com', 'put_object_fail_success', 'foo', 'foo', 'asdf');
    }
    public function testDeleteObject()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->delete_object('http://foo.com', 'delete_object', 'foo', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testDeleteObjectFail()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->delete_object('http://foo.com', 'delete_object_fail', 'foo', 'foo');
    }
    /**
     * @expectedException ClientException
     **/
    public function testDeleteObjectFail401()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->delete_object('http://foo.com', 'delete_object_fail_401', 'foo', 'foo');
    }
    public function testDeleteObjectFailSuccess()
    {
        $client = new Client(60, new FakeHTTPFactory());
        $client->retries = 1;
        $client->delete_object('http://foo.com', 'delete_object_fail_success', 'foo', 'foo');
    }
}
class FakeHTTPFactory
{
    private $full_listing_container = 0;
    private $full_listing_account = 0;
    private $get_auth_fail_success = false;
    private $get_account_fail_success = false;
    private $head_account_fail_success = false;
    private $post_account_fail_success = false;
    private $put_container_fail_success = false;
    private $get_container_fail_success = false;
    private $post_container_fail_success = false;
    private $head_container_fail_success = false;
    private $delete_container_fail_success = false;
    private $put_object_fail_success = false;
    private $get_object_fail_success = false;
    private $head_object_fail_success = false;
    private $post_object_fail_success = false;
    private $delete_object_fail_success = false;
    private $_headers = array('x-unit-test-header' => 'foo', 'x-storage-url' => 'http://foo.com');
    private $_get_body = '[{"name":"foo"}]';
    private $_get_body2 = '[{"name":"foo2"}]';
    private $_get_object_body = 'foo';
    public function __construct($timeout=60)
    {
    }
    public function get_http_response($url, $method, $headers=null, $query=null, $body=null, $chunk_size=10240)
    {
        $token = (array_key_exists('X-Auth-Key', $headers)) ? $headers['X-Auth-Key'] : $headers['X-Auth-Token'];
        if ($token === 'get_auth_fail')
        {
            return $this->_get_auth_fail();
        }
        if ($token === 'get_auth_fail_401')
        {
            return $this->_get_auth_fail_401();
        }

        elseif ($token === 'get_auth')
        {
            return $this->_get_auth();
        }
        elseif ($token === 'get_auth_fail_success')
        {
            return $this->_get_auth_fail_success();
        }
        elseif ($token === 'get_account_fail')
        {
            return $this->_get_account_fail();
        }
        elseif ($token === 'get_account_fail_401')
        {
            return $this->_get_account_fail_401();
        }
        elseif ($token === 'get_account')
        {
            return $this->_get_account();
        }
        elseif ($token === 'get_account_fail_success')
        {
            return $this->_get_account_fail_success();
        }
        elseif ($token === 'get_account_full_listing')
        {
            return $this->_get_account_full_listing();
        }
        elseif ($token === 'head_account_fail')
        {
            return $this->_head_account_fail();
        }
        elseif ($token === 'head_account_fail_401')
        {
            return $this->_head_account_fail_401();
        }
        elseif ($token === 'head_account')
        {
            return $this->_head_account();
        }
        elseif ($token === 'head_account_fail_success')
        {
            return $this->_head_account_fail_success();
        }
        elseif ($token === 'post_account_fail')
        {
            return $this->_post_account_fail();
        }
        elseif ($token === 'post_account_fail_401')
        {
            return $this->_post_account_fail_401();
        }
        elseif ($token === 'post_account')
        {
            return $this->_post_account();
        }
        elseif ($token === 'post_account_fail_success')
        {
            return $this->_post_account_fail_success();
        }
        elseif ($token === 'put_container_fail')
        {
            return $this->_put_container_fail();
        }
        elseif ($token === 'put_container_fail_401')
        {
            return $this->_put_container_fail_401();
        }
        elseif ($token === 'put_container')
        {
            return $this->_put_container();
        }
        elseif ($token === 'put_container_fail_success')
        {
            return $this->_put_container_fail_success();
        }
        elseif ($token === 'get_container_fail')
        {
            return $this->_get_container_fail();
        }
        elseif ($token === 'get_container_fail_401')
        {
            return $this->_get_container_fail_401();
        }
        elseif ($token === 'get_container')
        {
            return $this->_get_container();
        }
        elseif ($token === 'get_container_fail_success')
        {
            return $this->_get_container_fail_success();
        }
        elseif ($token === 'get_container_full_listing')
        {
            return $this->_get_container_full_listing();
        }
        elseif ($token === 'head_container_fail')
        {
            return $this->_head_container_fail();
        }
        elseif ($token === 'head_container_fail_401')
        {
            return $this->_head_container_fail_401();
        }
        elseif ($token === 'head_container')
        {
            return $this->_head_container();
        }
        elseif ($token === 'head_container_fail_success')
        {
            return $this->_head_container_fail_success();
        }
        elseif ($token === 'post_container_fail')
        {
            return $this->_post_container_fail();
        }
        elseif ($token === 'post_container_fail_401')
        {
            return $this->_post_container_fail_401();
        }
        elseif ($token === 'post_container')
        {
            return $this->_post_container();
        }
        elseif ($token === 'post_container_fail_success')
        {
            return $this->_post_container_fail_success();
        }
        elseif ($token === 'delete_container_fail')
        {
            return $this->_delete_container_fail();
        }
        elseif ($token === 'delete_container_fail_401')
        {
            return $this->_delete_container_fail_401();
        }

        elseif ($token === 'delete_container')
        {
            return $this->_delete_container();
        }
        elseif ($token === 'delete_container_fail_success')
        {
            return $this->_delete_container_fail_success();
        }
        elseif ($token === 'put_object_fail')
        {
            return $this->_put_object_fail();
        }
        elseif ($token === 'put_object_fail_401')
        {
            return $this->_put_object_fail_401();
        }
        elseif ($token === 'put_object')
        {
            return $this->_put_object();
        }
        elseif ($token === 'put_object_fail_success')
        {
            return $this->_put_object_fail_success();
        }
        elseif ($token === 'get_object_fail')
        {
            return $this->_get_object_fail();
        }
        elseif ($token === 'get_object_fail_401')
        {
            return $this->_get_object_fail_401();
        }
        elseif ($token === 'get_object')
        {
            return $this->_get_object();
        }
        elseif ($token === 'get_object_fail_success')
        {
            return $this->_get_object_fail_success();
        }
        elseif ($token === 'head_object_fail')
        {
            return $this->_head_object_fail();
        }
        elseif ($token === 'head_object_fail_401')
        {
            return $this->_head_object_fail_401();
        }
        elseif ($token === 'head_object')
        {
            return $this->_head_object();
        }
        elseif ($token === 'head_object_fail_success')
        {
            return $this->_head_object_fail_success();
        }
        elseif ($token === 'post_object_fail')
        {
            return $this->_post_object_fail();
        }
        elseif ($token === 'post_object_fail_401')
        {
            return $this->_post_object_fail_401();
        }
        elseif ($token === 'post_object')
        {
            return $this->_post_object();
        }
        elseif ($token === 'post_object_fail_success')
        {
            return $this->_post_object_fail_success();
        }
        elseif ($token === 'delete_object_fail')
        {
            return $this->_delete_object_fail();
        }
        elseif ($token === 'delete_object_fail_401')
        {
            return $this->_delete_object_fail_401();
        }
        elseif ($token === 'delete_object')
        {
            return $this->_delete_object();
        }
        elseif ($token === 'delete_object_fail_success')
        {
            return $this->_delete_object_fail_success();
        }
    }
    private function _get_auth_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _get_auth_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _get_auth()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _get_auth_fail_success()
    {
        if ($this->get_auth_fail_success === false)
        {
            $this->get_auth_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->get_auth_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _get_account_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _get_account_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _get_account()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo', $this->_get_body);
    }
    private function _get_account_full_listing()
    {
        if ($this->full_listing_account === 0)
        {
            $this->full_listing_account++;
            return new FakeHTTPResponse($this->_headers, 200, 'foo', $this->_get_body);
        }
        elseif ($this->full_listing_account === 1)
        {
            $this->full_listing_account++;
            return new FakeHTTPResponse($this->_headers, 200, 'foo', $this->_get_body2);
        }
        else
        {
            $this->full_listing_account = 0;
            return new FakeHTTPResponse($this->_headers, 200, 'foo', '');
        }
    }
    private function _get_account_fail_success()
    {
        if ($this->get_account_fail_success === false)
        {
            $this->get_account_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->get_account_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo', $this->_get_body);
    }
    private function _head_account_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _head_account_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _head_account()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _head_account_fail_success()
    {
        if ($this->head_account_fail_success === false)
        {
            $this->head_account_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->head_account_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }

    private function _post_account_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _post_account_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _post_account()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _post_account_fail_success()
    {
        if ($this->post_account_fail_success === false)
        {
            $this->post_account_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->post_account_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _put_container_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _put_container_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _put_container()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _put_container_fail_success()
    {
        if ($this->put_container_fail_success === false)
        {
            $this->put_container_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->put_account_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _get_container_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _get_container_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _get_container()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo', $this->_get_body);
    }
    private function _get_container_full_listing()
    {
        if ($this->full_listing_container === 0)
        {
            $this->full_listing_container++;
            return new FakeHTTPResponse($this->_headers, 200, 'foo', $this->_get_body);
        }
        elseif ($this->full_listing_container === 1)
        {
            $this->full_listing_container++;
            return new FakeHTTPResponse($this->_headers, 200, 'foo', $this->_get_body2);
        }
        else
        {
            $this->full_listing_container = 0;
            return new FakeHTTPResponse($this->_headers, 200, 'foo', '');
        }
    }
    private function _get_container_fail_success()
    {
        if ($this->get_container_fail_success === false)
        {
            $this->get_container_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->get_container_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo', $this->_get_body);
    }
    private function _head_container_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _head_container_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _head_container()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _head_container_fail_success()
    {
        if ($this->head_container_fail_success === false)
        {
            $this->head_container_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->head_container_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _post_container_fail()
    {
       return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _post_container_fail_401()
    {
       return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _post_container()
    {
       return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _post_container_fail_success()
    {
        if ($this->post_container_fail_success === false)
        {
            $this->post_container_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->post_container_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _delete_container_fail()
    {
       return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _delete_container_fail_401()
    {
       return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _delete_container()
    {
       return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _delete_container_fail_success()
    {
        if ($this->delete_container_fail_success === false)
        {
            $this->delete_container_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->delete_container_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _put_object_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _put_object_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _put_object()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _put_object_fail_success()
    {
        if ($this->put_object_fail_success === false)
        {
            $this->put_object_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->put_object_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _get_object_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _get_object_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo', 'asdf');
    }
    private function _get_object()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo', 'asdf');
    }
    private function _get_object_fail_success()
    {
        if ($this->get_object_fail_success === false)
        {
            $this->get_object_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->get_object_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo', 'asdf');
    }
    private function _head_object_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _head_object_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _head_object()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _head_object_fail_success()
    {
        if ($this->head_object_fail_success === false)
        {
            $this->head_object_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->head_object_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _post_object_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _post_object_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }
    private function _post_object()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _post_object_fail_success()
    {
        if ($this->post_object_fail_success === false)
        {
            $this->post_object_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->post_object_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _delete_object_fail()
    {
        return new FakeHTTPResponse($this->_headers, 500, 'foo');
    }
    private function _delete_object_fail_401()
    {
        return new FakeHTTPResponse($this->_headers, 401, 'foo');
    }

    private function _delete_object()
    {
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
    private function _delete_object_fail_success()
    {
        if ($this->delete_object_fail_success === false)
        {
            $this->delete_object_fail_success = true;
            return new FakeHTTPResponse($this->_headers, 500, 'foo');
        }
        $this->delete_object_fail_success = false;
        return new FakeHTTPResponse($this->_headers, 200, 'foo');
    }
}
class FakeHTTPResponse
{
    private $headers = array();
    private $status = null;
    private $reason = null;
    private $body = null;
    
    public function __construct($headers, $status, $reason, $body=null)
    {
        $this->headers = $headers;
        $this->status = $status;
        $this->reason = $reason;
        $this->body = $body;
    }
    public function get_headers()
    {
        return $this->headers;
    }
    public function get_header($key)
    {
        return $this->headers[$key];
    }
    public function get_status()
    {
        return $this->status;
    }
    public function get_reason()
    {
        return $this->reason;
    }
    public function get_version()
    {
        return 'fake-http';
    }
    public function read($amt=null)
    {
        return $this->body;
    }
}
?>
