<?php
namespace Packaged\Http;

class Response extends \Symfony\Component\HttpFoundation\Response
{
  protected $_callTime;
  protected $_sendDebugHeaders = true;

  public function __construct($content = '', $status = 200, $headers = [])
  {
    parent::__construct('', $status, $headers);
    $this->with($content);
  }

  /**
   * @return string
   */
  public function getStatusText()
  {
    return $this->statusText;
  }

  /**
   * @return $this
   */
  public function disableDebugHeaders()
  {
    $this->_sendDebugHeaders = false;
    return $this;
  }

  /**
   * @return $this
   */
  public function enableDebugHeaders()
  {
    $this->_sendDebugHeaders = true;
    return $this;
  }

  /**
   * Set the microtime(true) value when the call started
   *
   * @param $time
   *
   * @return $this
   */
  public function setCallStartTime($time)
  {
    $this->_callTime = $time;
    return $this;
  }

  protected $_originalSource;

  /**
   * Automatically detect the source, and create the correct response type
   *
   * @param $source
   *
   * @return $this
   */
  public function with($source)
  {
    $this->_originalSource = $source;

    if(is_object($source) || is_array($source))
    {
      if(method_exists($source, '__toString'))
      {
        $this->setContent((string)$source);
      }
      else
      {
        $this->asJson($source);
      }
    }
    else if(is_bool($source))
    {
      $this->setContent($source ? 'true' : 'false');
    }
    else
    {
      $this->setContent($source);
    }

    return $this;
  }

  /**
   * Set the response to be a json representation of the object
   *
   * @param $object
   *
   * @return $this
   */
  public function asJson($object)
  {
    $this->_originalSource = $object;
    $response = json_encode($object);

    // Prevent content sniffing attacks by encoding "<" and ">", so browsers
    // won't try to execute the document as HTML
    $response = str_replace(
      ['<', '>'],
      ['\u003c', '\u003e'],
      $response
    );

    $this->setContent($response);
    $this->headers->set("Content-Type", "application/json");

    return $this;
  }

  /**
   * Set the response to be a json encoded object using the JSONP standard;
   * http://bob.ippoli.to/archives/2005/12/05/remote-json-jsonp/
   *
   * @param string $responseKey
   * @param object $object
   *
   * @return $this
   */
  public function asJsonp($responseKey, $object)
  {
    $this->_originalSource = $object;
    $responseObject = json_encode($object);
    $response = "{$responseKey}({$responseObject})";

    // Prevent content sniffing attacks by encoding "<" and ">", so browsers
    // won't try to execute the document as HTML
    $response = str_replace(
      ['<', '>'],
      ['\u003c', '\u003e'],
      $response
    );

    $this->setContent($response);
    $this->headers->set("Content-Type", "application/json");

    return $this;
  }

  /**
   * Set the response to be plain text
   *
   * @param $text
   *
   * @return $this
   */
  public function asText($text)
  {
    $this->_originalSource = $text;
    $this->setContent($text);
    $this->headers->set("Content-Type", "text/plain");

    return $this;
  }

  /**
   * Add Debug Headers before sending the response
   *
   * @inheritdoc
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function send()
  {
    $this->setDebugHeaders();
    return parent::send();
  }

  /**
   * Define Debug Headers
   *
   * Automatically called by ->send()
   */
  public function setDebugHeaders()
  {
    if($this->_sendDebugHeaders)
    {
      //Add the exec time as a header if PHP_START has been defined by the project
      if(defined('PHP_START'))
      {
        $this->headers->set(
          "X-Execution-Time",
          number_format((microtime(true) - PHP_START) * 1000, 3) . ' ms'
        );
      }

      if($this->_callTime > 0)
      {
        $this->headers->set(
          'X-Call-Time',
          number_format((microtime(true) - $this->_callTime) * 1000, 3) . ' ms'
        );
      }
    }
  }

  /**
   * Retrieve the original data used to create the response
   *
   * @return mixed
   */
  public function getOriginalResponse()
  {
    return $this->_originalSource;
  }
}
