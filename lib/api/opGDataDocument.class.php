<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opGDataDocument
 *
 * @package    opWebAPIPlugin
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
abstract class opGDataDocument
{
  protected $elements;

  const XML_DECLARATION = '<?xml version="1.0" encoding="UTF-8"?>';

  public function __construct($input = '')
  {
    if ($input)
    {
      $xml = @simplexml_load_string($input);
      if (!$xml)
      {
        throw new RuntimeException('The inputed data is not a valid XML.');
      }
      $this->elements = $this->validate($xml);
    }
    else
    {
      $this->elements = simplexml_load_string($this->getRootXMLString());
    }
  }

  public function publish()
  {
    $elements = $this->getElements();
    return $elements->asXML();
  }

  abstract protected function getRootXMLString();

  public function getElements()
  {
    return $this->elements;
  }
}
