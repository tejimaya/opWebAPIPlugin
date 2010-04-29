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
    $string = self::XML_DECLARATION.'<entry xmlns="http://www.w3.org/2005/Atom" xmlns:gd="'.self::GDATA_NAMESPACE.'"></entry>';
    return $string;
  }

  public function setTitle($title, $type = 'text')
  {
    $element = $this->getElements()->addChild('title');

    $this->addValidStringToNode($element, $title);

    $element->addAttribute('type', $type);
  }

  public function setId($id)
  {
    $this->getElements()->addChild('id', $id);
  }

  public function setAuthor($name = null, $id = null, $email = null)
  {
    if (!func_num_args())
    {
      return null;
    }

    $author = $this->getElements()->addChild('author');

    if ($name)
    {
      $author->addChild('name', $name);
    }
    if ($id)
    {
      if (is_int($id))
      {
        $author->addChild('id', $id);
      }
      else
      {
        $author->addChild('uri', $id);
      }
    }
    if ($email)
    {
      $author->addChild('email', $email);
    }
  }

  public function setAuthorByMember(Member $member)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));

    $uri = url_for('@feeds_member_retrieve_resource_normal?model=member&id='.$member->getId(), true);
    $this->setAuthor($member->getName(), $uri);
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
    $element = $this->getElements()->addChild('content');

    $this->addValidStringToNode($element, $content);

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

  public function addCategory($term, $label = '', $scheme = '')
  {
    if (!$label)
    {
      $label = $term;
    }

    $category = $this->getElements()->addChild('category');
    $category->addAttribute('term', $term);
    $category->addAttribute('label', $label);

    if ($scheme)
    {
      $category->addAttribute('scheme', $scheme);
    }
  }
}
