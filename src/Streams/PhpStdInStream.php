<?php
namespace Packaged\Http\Streams;

class PhpStdInStream extends Stream
{
  public function __construct($mode = self::MODE_READ_WRITE)
  {
    $this->attach('php://stdin', $mode);
  }
}
