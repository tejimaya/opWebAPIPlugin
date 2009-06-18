<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opGDataDocumentFeed
 *
 * @package    opWebAPIPlugin
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opGDataDocumentFeed extends opGDataDocument
{
  const OPEN_SEARCH_NAMESPACE = 'http://a9.com/-/spec/opensearchrss/1.0/';

  protected function getRootXMLString()
  {
    $string = self::XML_DECLARATION.'<feed xmlns="http://www.w3.org/2005/Atom" xmlns:openSearch="'.self::OPEN_SEARCH_NAMESPACE.'"></feed>';
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

  public function setSubtitle($subtitle)
  {
    $this->getElements()->addChild('subtitle', $subtitle);
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

  public function setCopyright($right)
  {
    $this->getElements()->addChild('rights', $right);
  }

  public function setAuthor($name, $email)
  {
    $author = $this->getElements()->addChild('author');
    $author->addChild('name', $name);
    $author->addChild('email', $email);
  }

  public function setUpdated($updated)
  {
    $datetime = new DateTime($updated);
    $this->getElements()->addChild('updated', $datetime->format(DATE_RFC3339));
  }

  public function setGenerator($generator, $uri)
  {
    $generator = $this->getElements()->addChild('generator', $generator);
    $generator->addAttribute('uri', $uri);
  }

  public function setLogo($logo)
  {
    $this->getElements()->addChild('logo', $logo);
  }

  public function addEntry()
  {
    return $this->getElements()->addChild('entry');
  }

  public function publish()
  {
    $this->setGenerator('OpenPNE', 'http://www.openpne.jp/');
    return parent::publish();
  }

  public function setOpenSearch($totalResults, $startIndex, $itemsParPage)
  {
    $this->getElements()->addChild('totalResults', $totalResults, self::OPEN_SEARCH_NAMESPACE);
    $this->getElements()->addChild('startIndex', $startIndex, self::OPEN_SEARCH_NAMESPACE);
    $this->getElements()->addChild('itemsPerPage', $itemsParPage, self::OPEN_SEARCH_NAMESPACE);
  }
}
