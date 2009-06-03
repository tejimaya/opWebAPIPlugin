<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opGDataDocumentEntry
 *
 * @package    opWebAPIPlugin
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opGDataDocumentEntry extends opGDataDocument
{
  protected $isInsert = false;

  public function __construct($entry = null, $isInsert = false)
  {
    $this->isInsert = $isInsert;
    if (is_null($entry))
    {
      parent::__construct();
    }
    elseif ($entry instanceof SimpleXMLElement)
    {
      $this->elements = $entry;
    }
    else
    {
      parent::__construct($entry);
    }
  }

  protected function getRootXMLString()
  {
    $string = self::XML_DECLARATION.'<entry xmlns="http://www.w3.org/2005/Atom"></entry>';
    return $string;
  }

  public function setTitle($title)
  {
    $this->getElements()->addChild('title', $title);
  }

  public function setId($id)
  {
    $this->getElements()->addChild('id', $id);
  }

  public function setAuthor($name, $id)
  {
    $author = $this->getElements()->addChild('author');
    $author->addChild('name', $name);
    $author->addChild('id', $id);
  }

  public function setAuthorByMember(Member $member)
  {
    $this->setAuthor($member->getName(), $member->getId());
  }

  public function setPublished($published)
  {
    $datetime = new DateTime($published);
    $this->getElements()->addChild('published', $datetime->format(DATE_RFC3339));
  }

  public function setUpdated($updated)
  {
    $datetime = new DateTime($updated);
    $this->getElements()->addChild('updated', $datetime->format(DATE_RFC3339));
  }

  public function setContent($content, $type = 'text')
  {
    $element = $this->getElements()->addChild('content', $content);
    $element->addAttribute('type', $type);
  }

  public function setLink($href, $rel = '', $type = '')
  {
    $link = $this->getElements()->addChild('link');
    $link->addAttribute('href', $href);
    if ($rel)
    {
      $link->addAttribute('rel', $rel);
    }
    if ($type)
    {
      $link->addAttribute('type', $type);
    }
  }
}
