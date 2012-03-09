<?php
# Copyright (c) 2011 OpenStack, LLC.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#    http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
# implied.
# See the License for the specific language governing permissions and
# limitations under the License.
/**
 * client.php, A Swift ReST wrapper written in PHP
 * @copyright Copyright (c) 2011, OpenStack, LLC.
 * @author Conrad Weidenkeller <conrad.weidenkeller@rackspace.com>
 * @version 1.0
 * @package swift
 */
/**
 * @access private
 */
class MalformedUrl extends Exception {}
/**
 * @access private
 */
class CannotConnect extends Exception {}
/**
 * @access private
 */
class UnableToOpenFile extends Exception {}
/**
 * @access private
 */
class ClientException extends Exception {}
/**
 * @access private
 */
class ExceedsContentLength extends Exception {}
/**
 * @access private
 */
class UnsupportedTransferEncoding extends Exception {}
/**
 * @access private
 */
class http_response
{
    private $fp;
    private $headers = array();
    private $chunk_length = 0;
    private $amt_read = 0;
    private $reason;
    private $status;
    private $version;
    private $timeout;
    function __construct($fp, $timeout)
    {
        $this->timeout = $timeout;
        $this->fp = $fp;
        $this->_get_response_info();
        $this->_get_headers();
    }
    private function _smart_fread($amt)
    {
        $amt_left = $amt;
        $buff = '';
        $switch = false;
        while ($amt_left > 0) 
        {
            $tmp = fread($this->fp, $amt_left);
            if ($tmp)
            {
                $buff .= $tmp;
                $amt_left -= strlen($tmp);
            }
            else
            {
                if ($switch)
                    break;
                else
                    $switch = true;
            }
        }

        return $buff;
    }
    private function _get_response_info()
    {
        $info = fgets($this->fp);
        $info = explode(' ', $info);
        $this->version = trim($info[0]);
        unset($info[0]);
        $this->status  = trim($info[1]);
        unset($info[1]);
        $this->reason  = trim(implode(' ', $info));
    }
    private function _get_headers()
    {
        $buff = '';
        while (count(explode("\r\n\r\n", $buff)) != 2) 
            $buff .= fgets($this->fp);
        $buff = explode("\r\n\r\n", $buff);
        $tmp_h = trim($buff[0]);
        $tmp_h = explode("\r\n", $tmp_h);
        foreach ($tmp_h as $i)
        {
            $res = explode(':', $i);
            $key = trim(strtolower($res[0]));
            unset($res[0]);
            $this->headers[$key] = trim(implode(':', $res));
        }
    }
    function get_headers()
    { 
        return $this->headers; 
    }
    function get_header($key)
    {
        if(array_key_exists(strtolower($key), $this->headers))
            return $this->headers[strtolower($key)];
        else 
            return false;
    }
    function get_status() 
    { 
        return $this->status;
    }
    function get_reason() 
    { 
        return $this->reason;
    }
    function get_version()
    { 
        return $this->version; 
    }
    function read($amt=null)
    {
        if (array_key_exists('content-length', $this->headers))
        {
            if ($this->headers['content-length'] == 0) 
                return null;
            if ($this->amt_read == $this->headers['content-length']) 
                return null;
            elseif (($amt + $this->amt_read) < $this->headers['content-length']
                    and $amt) 
                $this->amt_read += $amt;
            else
            {
                $amt = $this->headers['content-length'] - $this->amt_read;
                $this->amt_read = $this->headers['content-length'];
            }
            $amt = $this->_smart_fread($amt);
            return $amt;
        }
        elseif (array_key_exists('transfer-encoding', $this->headers))
        {
            if (!strtolower($this->headers['transfer-encoding']) == 'chunked')
                throw new UnsupportedTransferEncoding("Unsupported Transfer " .
                                                      "Encoding: " .
                                                      $this->headers
                                                      ['transfer-encoding']);
            else
            {
                $count = 0;
                $resp_tmp = '';
                $resp = '';
                while (true)
                {
                    if ($this->chunk_length == 0)
                    {
                        $chunk = fgets($this->fp);
                        $chunk = explode(';', $chunk);
                        $chunk = trim($chunk[0]);
                        $this->chunk_length = hexdec($chunk);
                        if ($this->chunk_length == 0)
                        {
                            fgets($this->fp);
                            break;
                        }
                    }
                    if ($amt > $this->chunk_length or !$amt)
                    {
                        $resp_tmp = $this->_smart_fread($this->chunk_length);
                        $resp .= $resp_tmp;
                        if ($amt) 
                            $amt -= strlen($resp_tmp);
                        $this->chunk_length -= strlen($resp_tmp);
                        $resp_tmp = '';
                        if ($this->chunk_length == 0) 
                            $this->_smart_fread(2);
                    }
                    elseif ($amt <= $this->chunk_length and $amt > 0)
                    {
                        $resp_tmp = $this->_smart_fread($amt);
                        $resp .= $resp_tmp;
                        $this->chunk_length -= $amt;
                        if ($amt == $this->chunk_length) 
                            $this->_smart_fread(2);
                        return $resp;
                    }
                }
                return $resp;
            }          
        }
        else
        {
            if ($amt) 
                return $this->_smart_fread($amt);
            else
            {
                $tmp = _smart_fread(1048576);
                $body = $tmp;
                while (true)
                {
                    if (!$tmp) 
                        break;
                    $tmp = _smart_fread(1048576);
                    $body .= $tmp;
                }
                return $body;
            }
        }
    }
}
/**
 * Create an http_request manually to have a resuable HTTP Connection
 */
class http_request
{
    private $url;
    private $host;
    private $timeout;
    private $port;
    private $fp;
    private $requests = 0;
    private $headers = array();
    private $query = array();
    private $proto;
    private $errstr;
    private $errno;
    private $cl_not_sent = 0;
    private $VERSION = 'HTTP/1.1';
    /**
     * @param string $host a the host to connect to accepts proto and :port
     * @param int $timeout the TCP timeout of the socket.
     */
    function __construct($host, $timeout = 10)
    {
        $this->timeout = $timeout;
        $host = explode('://', $host);
        if (count($host) == 2)
        {
            $this->proto = $host[0];
            unset($host[0]);
        }
        else 
            $this->proto = 'https';
        $host = implode('', $host);
        $host = explode(":", $host);
        $this->host = $host[0];
        if (count($host) == 2)
            $this->port = $host[1];
        elseif ($this->proto == 'https')
            $this->port = 443;
        else
            $this->port = 80;
        $this->connection();
    }
    /**
     * @access private
     */
    private function connection()
    {
        if ($this->proto == 'https')
            $this->fp = fsockopen('tls://' . $this->host, $this->port,
                              $this->errno, $this->errstr, $this->timeout);
        else
            $this->fp = fsockopen($this->host, $this->port,
                              $this->errno, $this->errstr, $this->timeout); 
        if (!$this->fp)
            throw new CannotConnect('Unable to connect to: ' .
                                    $this->host . ':' . $this->port .
                                    "\nError: " . $this->errstr . ' : ' . 
                                    $this->errno);
    }
    /**
     * @access private
     */
    private function build_request_line()
    {
        if ($this->url != '/')
        {
            $url = array();
            foreach (explode('/', $this->url) as $i) 
                $url[] = rawurlencode($i);
            $url = implode('/', $url);
        }
        else 
            $url = $this->url;
        if (count($this->query) > 0)
        {
            $url .= '?';
            $count = 1;
            foreach ($this->query as $key => $value)
            {
                $url .= rawurlencode($key) . "=" . rawurlencode($value) ;
                if ($count != count($this->query)) 
                    $url .= '&';
                $count ++;
            } 
        }
        $request_line = $this->method . ' ' . $url . ' ' .
        $this->VERSION . "\r\n";
        return $request_line;
    }
    /**
     * @access private
     */
    private function build_headers()
    {
        $headers = '';
        if (!array_key_exists('host', $this->headers))
            $this->add_header('host',  $this->host);
        if (!array_key_exists('date', $this->headers))
            $this->add_header('date', gmdate('D, d M Y H:i:s \G\M\T', time()));
        foreach ($this->headers as $key => $value)
            $headers .= trim($key) . ":" . trim($value) . "\r\n";
        $headers .= "\r\n";
        return $headers;
    }
    /**
     * @access private
     */
    function add_headers($headers)
    {
        $headers = array_change_key_case($headers);
        $this->headers = array_merge($this->headers, $headers);
    }
    /**
     * @access private
     */
    function add_header($key, $value)
    { 
        $this->headers[strtolower($key)] = $value; 
    }
    /**
     * @access private
     */
    function add_query_strings($query_strings)
    { 
        $this->query .= array_merge($this->query, $query_strings); 
    }
    /**
     * @access private
     */
    function add_query_string($key, $value) 
    { 
        $this->query[$key] = $value; 
    }
    /**
     * @access private
     */
    function start_request($method, $url, $body = null)
    {
        $this->requests += 1;
        if (($this->requests % 100) == 0)
            {
                $this->requests = 0;
                $this->connection();
            }
        $this->method = strtoupper($method);
        $this->url = $url;
        if (!array_key_exists('content-length', $this->headers) and
            !array_key_exists('transfer-encoding', $this->headers) and $body)
            $this->add_header('content-length', strlen($body));
        elseif (!array_key_exists('content-length', $this->headers) and
               !array_key_exists('transfer-encoding', $this->headers)
               and !$body) 
            $this->add_header('content-length', '0');
        if (array_key_exists('content-length', $this->headers))
            $this->cl_not_sent = $this->headers['content-length'];
        $message = $this->build_request_line() . $this->build_headers();
        fwrite($this->fp, $message);
        if ($body) 
            $this->send($body);
    }
    /**
     * @access private
     */
    function send($data = null)
    {
        if (array_key_exists('transfer-encoding', $this->headers))
        {
            if ($data)
            {
                $len = dechex(strlen($data));
                fwrite($this->fp, $len . "\n" . $data. "\r\n");
            }
        }
        elseif ($data)
        {
            if (strlen($data) > $this->cl_not_sent)
                throw new ExceedsContentLength(
                                "Length of Data too long!\nAmt sent: " .
                                strlen($data) . "\nAmt left to send: " .
                                $this->cl_not_sent);
            $this->cl_not_sent -= strlen($data);
            fwrite($this->fp, $data);
        }
    }
    /**
     * @access private
     */
    function get_response()
    {
        if (array_key_exists('transfer-encoding', $this->headers))
            fwrite($this->fp, "0\r\n\n");
        $this->headers = array();
        $this->cl_not_sent = 0;
        $this->method = '';
        $this->url = '';
        return new http_response($this->fp, $this->timeout);
    }
}
/**
 * @access private
 */
class object_iter implements Iterator
{
    private $body = '';
    private $valid = true;
    private $n = 0;
    private $resp_chunk_size = 0;
    public function __construct($resp, $resp_chunk_size)
    {
        $this->resp = $resp;
        $this->resp_chunk_size = $resp_chunk_size;
        $this->body = $resp->read($this->resp_chunk_size);
        if (!$this->body)
        {
            $this->valid = false;
        }
    }
    function rewind(){ }
    function key()
    {
        return ++$this->n;
    }
    function next()
    {
        $this->body = $this->resp->read($this->resp_chunk_size);
        if (!$this->body)
        {
            $this->valid = null;
        }
    }
    function current()
    {
        return $this->body;
    }
    function valid()
    {
        return $this->valid;
    }
}
/**
 * @access private
 */
function prep_url($url)
{
    $arr = array();
    if (!strpos($url, '://')) $url = 'https://' . $url;
    $arr['host'] = parse_url($url, PHP_URL_SCHEME) . '://';
    $arr['host'] .= parse_url($url, PHP_URL_HOST);
    if (parse_url($url, PHP_URL_PORT)) 
        $arr['host'] .= ':' . parse_url($url, PHP_URL_PORT);
    if (parse_url($url, PHP_URL_PATH))
        $arr['path'] = parse_url($url, PHP_URL_PATH);
    return $arr;
}
/**
 * Get a Valid Auth Token and Storage URL from Swift
 * @param string $url The Auth URL accepts proto and :port
 * @param string $user The Swift Username
 * @param string $key The Users API Key
 * @param array $args Optional Configuration Options
 * @param string $args['snet'] Set if you are connecting over snet
 * @param int $args['timeout'] Set for Socket Timeout
 * @return array keys:'headers', 'status', 'reason'
 * @exception ClientException
 */
function get_auth($url, $user, $key, $args = array())
{
    if (array_key_exists('snet', $args)) 
        $snet = $args['snet'];
    else 
        $snet = null;
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
    $req->add_headers(array('x-auth-user' => $user, 'x-auth-key' => $key));
    $req->start_request('GET', $url['path']);
    $resp = $req->get_response();
    $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to GET Auth - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
    $headers = $resp->get_headers();
    if ($snet)
    {
        $headers['x-storage-url'] = explode('://', $headers['x-storage-url']);
        $headers['x-storage-url'][1] = 'snet-' . $headers['x-storage-url'][1];
        $headers['x-storage-url'] = implode('://', $headers['x-storage-url']);
    }
    return array('headers' => $headers,
                 'status' => $resp->get_status(),
                 'reason' => $resp->get_reason());
}
/**
 * Get a container listing and account stats from Swift Acct
 * @param string $url The Storage URL accepts proto and :port
 * @param string $token a Valid Auth Token
 * @param array $args Optional Configuration Options
 * @param int $args['limit'] Limit the number of results to limit.
 * @param string $args['prefix'] Only return results with starting with prefix.
 * @param string $args['marker'] Start listing from marker
 * @param int $args['timeout'] Set for Socket Timeout
 * @param http_request $args['http_conn'] Pass an http_request Object
 * to reuse the same connection.
 * @param bool $args['full_listing'] Set to True for full listing of account.
 * @return array keys:'containers', 'headers', 'status', 'reason'
 * @exception ClientException
 */
function get_account($url, $token, $args = array())
{
    $url = prep_url($url);
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
    $req->add_header('X-Auth-Token', $token);
    if (array_key_exists('marker', $args)) 
        $req->add_query_string('marker', $args['marker']);
    if (array_key_exists('limit', $args)) 
        $req->add_query_string('limit', $args['limit']);
    if (array_key_exists('prefix', $args)) 
        $req->add_query_string('prefix', $args['prefix']);
    $req->add_query_string('format', 'json');
    $req->start_request('GET', $url['path']);
    $resp = $req->get_response();
    $buff = $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to GET Account - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
    $res['containers'] = json_decode($buff, true);
    $res['headers'] = $resp->get_headers();
    $res['status'] = $resp->get_status();
    $res['reason'] = $resp->get_reason();
    if (array_key_exists('full_listing', $args))
    {
        $args['http_conn'] = $req;
        unset($args['full_listing']);
        $switch = false;
        while (count($buff['containers']))
        {
            $args['marker'] =
            $res['containers'][count($res['containers']) -1]['name'];
            while (true)
            {
                try
                {
                    $buff = get_account($url['host'] . $url['path'],
                                        $token, $args);
                    break;
                }
                catch (ClientException $e) 
               {
                   if ($switch)
                    {
                        throw new ClientException("ERROR " .
                                                  "Unable to GET Full " .
                                                  "Account Listing - " .
                                                  "Please Try Again!" .
                                                  $container. ":" .
                                                  print_r($args));
                    }
                    $switch = true; 
               }
            }
            if ($buff['containers']) 
                $res['containers'] =
                array_merge($res['containers'], $buff['containers']);
        }
    }
    return $res;
}
/**
 * HEAD a Swift account to get Acct info.
 * @param string $url The Storage URL accepts proto and :port
 * @param string $token a Valid Auth Token
 * @param array $args Optional Configuration Options
 * @param int $args['timeout'] Set for Socket Timeout
 * @param http_request $args['http_conn'] Pass an http_request Object
 * to reuse the same connection.
 * @return array keys:'headers', 'status', 'reason'
 * @exception ClientException
 */
function head_account($url, $token, $args = array())
{
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
    $req->add_header('X-Auth-Token', $token);
    $req->start_request('HEAD', $url['path']);
    $resp = $req->get_response();
    $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to HEAD Account - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
    return array('headers' => $resp->get_headers(),
                 'status' => $resp->get_status(),
                 'reason' => $resp->get_reason());
}
/**
 * POST a Swift account to update account Headers.
 * @param string $url The Storage URL accepts proto and :port
 * @param string $token a Valid Auth Token
 * @param array $headers An Assoc Array with X-Meta Headers
 * @param array $args Optional Configuration Options
 * @param int $args['timeout'] Set for Socket Timeout
 * @param http_request $args['http_conn'] Pass an http_request Object
 * to reuse the same connection.
 * @exception ClientException
 */
function post_account($url, $token, $headers, $args = array())
{
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
    $req->add_header('X-Auth-Token', $token);
    $req->add_headers($headers);
    $req->start_request('POST', $url['path']);
    $resp = $req->get_response();
    $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to POST Account - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
}
/**
 * GET an object listing and container stats from Swift Container
 * @param string $url The Storage URL accepts proto and :port
 * @param string $token a Valid Auth Token
 * @param string $container container name
 * @param array $args Optional Configuration Options
 * @param int $args['limit'] Limit the number of results to limit.
 * @param string $args['prefix'] Only return results with starting with prefix.
 * @param string $args['marker'] Start listing from marker
 * @param string $args['delimiter'] For Object Delimiter support.
 * @param int $args['timeout'] Set for Socket Timeout
 * @param http_request $args['http_conn'] Pass an http_request Object
 * to reuse the same connection.
 * @param bool $args['full_listing'] Set to True for full listing of container.
 * @return array keys: 'objects', 'headers', 'status', 'reason'
 * @exception ClientException
 */
function get_container($url, $token, $container, $args = array())
{
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
    $req->add_header('X-Auth-Token', $token);
    if (array_key_exists('marker', $args)) 
        $req->add_query_string('marker', $args['marker']);
    if (array_key_exists('limit', $args)) 
        $req->add_query_string('limit', $args['limit']);
    if (array_key_exists('prefix', $args)) 
        $req->add_query_string('prefix', $args['prefix']);
    if (array_key_exists('delimiter', $args)) 
        $req->add_query_string('delimiter', $args['delimiter']);
    $req->add_query_string('format', 'json');
    $req->start_request('GET', $url['path'] . '/' . $container);
    $resp = $req->get_response();
    $buff = $resp->read();
    if ($resp->get_status() <  200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to GET Container - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
    $res['objects'] = json_decode($buff, true);
    $res['headers'] = $resp->get_headers();
    $res['reason'] = $resp->get_reason();
    $res['status'] = $resp->get_status();
    if (array_key_exists('full_listing', $args))
    {
        $args['http_conn'] = $req;
        unset($args['full_listing']);
        while (count($buff['objects']))
        {
            $args['marker'] =
            (array_key_exists('name',
                              $res['objects']
                              [count($res['objects']) -1])) ?
            $res['objects'][count($res['objects']) -1]['name'] :
            $res['objects'][count($res['objects']) -1]['subdir'];
            $switch = false;
            while (true)
            {
                try
                {
                    $buff = get_container($url['host'] . $url['path'],
                                          $token, $container, $args);
                    $switch = false;
                    break;
                }
                catch (ClientException $e)
                {
                    if ($switch)
                    {
                        throw new ClientException("ERROR " .
                                                  "Unable to GET Full " .
                                                  "Object Listing - " .
                                                  "Please Try Again!" .
                                                  $container. ":" .
                                                  print_r($args));
                    }
                    $switch = true;
                }
            }
            if( $buff['objects']) 
                $res['objects'] =
                array_merge($res['objects'], $buff['objects']);
        }
    }
    return $res;
}
/**
 * HEAD a Swift Container to get Container info.
 * @param string $url The Storage URL accepts proto and :port.
 * @param string $token a Valid Auth Token.
 * @param string $container Container name.
 * @param array $args Optional Configuration Options.
 * @param int $args['timeout'] Set for Socket Timeout.
 * @param http_request $args['http_conn'] Pass an http_request Object.
 * to reuse the same connection.
 * @return array keys:'headers', 'status', 'reason'.
 * @exception ClientException
 */
function head_container($url, $token, $container, $args = array())
{
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host']);
    $req->add_header('X-Auth-Token', $token);
    $req->start_request('HEAD', $url['path'] . '/' . $container);
    $resp = $req->get_response();
    $buff = $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to HEAD Container - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
    return array('headers' => $resp->get_headers(),
                 'status' => $resp->get_status(),
                 'reason' => $resp->get_reason());
}
/**
 * POST a Swift container to update container Headers.
 * @param string $url The Storage URL accepts proto and :port.
 * @param string $token a Valid Auth Token.
 * @param string $container Container name.
 * @param array $headers An Assoc Array with X-Meta Headers.
 * @param array $args Optional Configuration Options.
 * @param int $args['timeout'] Set for Socket Timeout.
 * @param http_request $args['http_conn'] Pass an http_request Object.
 * to reuse the same connection.
 * @exception ClientException.
 */
function post_container($url, $token, $container, $headers, $args = array())
{
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host']);
    $req->add_header('X-Auth-Token', $token);
    $req->add_headers($headers);
    $req->start_request('POST', $url['path'] . '/' . $container);
    $resp = $req->get_response();
    $buff = $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to POST Container - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
}
/**
 * PUT to a container to create it.
 * @param string $url The Storage URL accepts proto and :port.
 * @param string $token a Valid Auth Token.
 * @param string $container Container name.
 * @param array $args Optional Configuration Options.
 * @param array $args['headers'] Array for X-Meta Headers.
 * @param int $args['timeout'] Set for Socket Timeout.
 * @param http_request $args['http_conn'] Pass an http_request Object.
 * to reuse the same connection.
 * @exception ClientException.
 */
function put_container($url, $token, $container, $args = array())
{
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
    $req->add_header('X-Auth-Token', $token);
    if (array_key_exists('headers', $args)) 
        $req->add_headers($args['headers']);
    $req->start_request('PUT', $url['path'] . '/' . $container);
    $resp = $req->get_response();
    $buff = $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to PUT Container - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
}
/**
 * DELETE an empty container.
 * @param string $url The Storage URL accepts proto and :port.
 * @param string $token a Valid Auth Token.
 * @param string $container Container name.
 * @param array $args Optional Configuration Options.
 * @param int $args['timeout'] Set for Socket Timeout.
 * @param http_request $args['http_conn'] Pass an http_request Object.
 * to reuse the same connection.
 * @exception ClientException.
 */
function delete_container($url, $token, $container, $args = array())
{
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
    $req->add_header('X-Auth-Token', $token);
    $req->start_request('DELETE', $url['path'] . '/' . $container);
    $resp = $req->get_response();
    $buff = $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to DELETE Container - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
}
/**
 * GET an object data and object stats from Swift object
 * @param string $url The Storage URL accepts proto and :port
 * @param string $token a Valid Auth Token
 * @param string $container container name
 * @param string $name The objects name.
 * @param array $args Optional Configuration Options
 * @param int $args['timeout'] Set for Socket Timeout
 * @param http_request $args['http_conn'] Pass an http_request Object
 * to reuse the same connection.
 * @param int $args['resp_chunk_size'] If passed return iterable object
 * data rather then the entire object
 * @return array keys: 'data', 'headers', 'status', 'reason'
 * @exception ClientException
 */
function get_object($url, $token, $container, $name, $args = array())
{
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
    $req->add_header('X-Auth-Token', $token);
    $req->start_request('GET', $url['path'] . '/' . $container . '/' . $name);
    $resp = $req->get_response();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        $resp->read();
        throw new ClientException("ERROR Unable to GET Object - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
    if (!array_key_exists('resp_chunk_size', $args))
         return array('headers' => $resp->get_headers(),
                      'data' => $resp->read(),
                      'status' => $resp->get_status(),
                      'reason' => $resp->get_reason());
    return array('headers' => $resp->get_headers(),
                 'data' => new object_iter($resp, $args['resp_chunk_size']),
                 'status' => $resp->get_status(),
                 'reason' => $resp->get_reason());
}
/**
 * HEAD a Swift Object to get Object info.
 * @param string $url The Storage URL accepts proto and :port.
 * @param string $token a Valid Auth Token.
 * @param string $container Container name.
 * @param string $name The objects name.
 * @param array $args Optional Configuration Options.
 * @param int $args['timeout'] Set for Socket Timeout.
 * @param http_request $args['http_conn'] Pass an http_request Object.
 * to reuse the same connection.
 * @return array keys:'headers', 'status', 'reason'.
 * @exception ClientException
 */
function head_object($url, $token, $container, $name, $args = array())
{
    if( array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
    $req->add_header('X-Auth-Token', $token);
    $req->start_request('HEAD', $url['path'] . '/' . $container . '/' . $name);
    $resp = $req->get_response();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to HEAD Object - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
    return array('headers' => $resp->get_headers(),
                 'status' => $resp->get_status(),
                 'reason' => $resp->get_reason());
}
/**
 * POST to a Swift object to update object Headers.
 * @param string $url The Storage URL accepts proto and :port.
 * @param string $token a Valid Auth Token.
 * @param string $container Container name.
 * @param string $name The Object name.
 * @param array $headers An Assoc Array with X-Meta Headers.
 * @param array $args Optional Configuration Options.
 * @param int $args['timeout'] Set for Socket Timeout.
 * @param http_request $args['http_conn'] Pass an http_request Object.
 * to reuse the same connection.
 * @exception ClientException.
 */
function post_object($url, $token, $container, $name, $headers, $args = array())
{
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'],  $timeout);
    $req->add_header('X-Auth-Token', $token);
    $req->add_headers($headers);
    $req->start_request('POST', $url['path'] . '/' . $container . '/' . $name);
    $resp = $req->get_response();
    $buff = $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to POST Object - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
}
/**
 * PUT to a Swift object to create it.
 * @param string $url The Storage URL accepts proto and :port.
 * @param string $token a Valid Auth Token.
 * @param string $container Container name.
 * @param string $name The Object name.
 * @param string/resource $contents Pass as an Open File handle or string as
 * object content
 * @param array $args Optional Configuration Options.
 * @param array $args['headers'] An Assoc Array with X-Meta Headers or Etag,
 * Content-Type, or Content-Length.
 * @param int $args['chunk_size'] Pass to specify chunk size
 * @param int $args['content-length'] Object content length will default to
 * 65536 bytes if none is set.
 * @param string $args['etag'] MD5 checksum of object
 * @param int $args['content-type'] The Objects content type
 * @param int $args['timeout'] Set for Socket Timeout.
 * @param http_request $args['http_conn'] Pass an http_request Object.
 * to reuse the same connection.
 * @exception ClientException.
 */
function put_object($url, $token, $container, $name, $contents, $args = array())
{
    if (array_key_exists('timeout', $args)) 
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    if (!array_key_exists('headers', $args)) 
        $args['headers'] = array(); 
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
        $req->add_header('X-Auth-Token', $token);
    if (array_key_exists('headers', $args)) 
        $req->add_headers($args['headers']);
    if (array_key_exists('content-length', $args))
        $req->add_header('Content-Length', $args['content-length']);
    if (array_key_exists('etag', $args)) 
        $req->add_header('etag', $args['etag']);
    if (array_key_exists('content-type', $args))
        $req->add_header('Content-Type', $args['content-type']);
    if (array_key_exists('chunk_size', $args)) 
        $chunk_size = $args['chunk_size'];
    else 
        $chunk_size = 65536;
    if (!array_key_exists('content-length', $args['headers']) and
        !array_key_exists('content-length', $args))
        $req->add_header('Transfer-Encoding', 'chunked');
    $req->start_request('PUT', $url['path'] . '/' . $container . '/' . $name);
    if (is_resource($contents))
        while (!feof($contents)) $req->send(fread($contents, $chunk_size));
    else $req->send($contents);
    $resp = $req->get_response();
    $buff = $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to PUT Object - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
}
/**
 * DELETE an object.
 * @param string $url The Storage URL accepts proto and :port.
 * @param string $token a Valid Auth Token.
 * @param string $container Container name.
 * @param string $name Object name.
 * @param array $args Optional Configuration Options.
 * @param int $args['timeout'] Set for Socket Timeout.
 * @param http_request $args['http_conn'] Pass an http_request Object.
 * to reuse the same connection.
 * @exception ClientException.
 */
function delete_object($url, $token, $container, $name, $args = array())
{
    if (array_key_exists('timeout', $args))
        $timeout = $args['timeout'];
    else 
        $timeout = 10;
    $url = prep_url($url);
    if (array_key_exists('http_conn', $args)) 
        $req = $args['http_conn'];
    else 
        $req = new http_request($url['host'], $timeout);
    $req->add_header('X-Auth-Token', $token);
    $req->start_request('DELETE', $url['path'] . '/' . $container . '/' .
                        $name);
    $resp = $req->get_response();
    $buff = $resp->read();
    if ($resp->get_status() < 200 or $resp->get_status() > 299)
    {
        throw new ClientException("ERROR Unable to DELETE Object - " .
                                  $resp->get_status(). ":" .
                                  $resp->get_reason());
    }
}
?>
