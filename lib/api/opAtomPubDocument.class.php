<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAtomPubDocument
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
abstract class opAtomPubDocument
{
  protected $elements;

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
    $elements = $this->validate($elements);
    return $elements->asXML();
  }

  abstract protected function getRootXMLString();

  abstract protected function validate($elements);

  public function getElements()
  {
    return $this->elements;
  }

  public function validateRequiredUniqueElement($element, $name)
  {
    if (1 != count($element->$name))
    {
      throw new LogicException('feed elements MUST contain exactly one "'.$name.'" element.');
    }
  }

  public function validateUniqueElement($element, $name)
  {
    if (2 <= count($element->$name))
    {
      throw new LogicException('feed elements MUST NOT contain more than one "'.$name.'" element.');
    }
  }
}
