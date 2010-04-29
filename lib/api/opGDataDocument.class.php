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
  const GDATA_NAMESPACE = 'http://schemas.google.com/g/2005';

  public function __construct($input = '')
  {
    if ($input)
    {
      $xml = @simplexml_load_string($input);
      if (!$xml)
      {
        throw new RuntimeException('The inputed data is not a valid XML.');
      }
      $this->elements = $xml;
    }
    else
    {
      $this->elements = simplexml_load_string($this->getRootXMLString());
    }
  }

  public function publish()
  {
    $elements = $this->getElements();
    $result = $elements->asXML();

    return $result;
  }

  abstract protected function getRootXMLString();

  public function getElements()
  {
    return $this->elements;
  }

  public function addValidStringToNode($node, $string)
  {
    $domNode = dom_import_simplexml($node);
    $doc = $domNode->ownerDocument;

    if (Doctrine::getTable('SnsConfig')->get('op_web_api_plugin_using_cdata', false))
    {
      $child = $doc->createCDataSection((string)$string);
    }
    else
    {
      $child = $doc->createTextNode((string)$string);
    }

    $domNode->appendChild($child);
  }
}
