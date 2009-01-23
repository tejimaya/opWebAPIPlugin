<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAtomPubDocumentFeed
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opAtomPubDocumentFeed extends opAtomPubDocument
{
  protected function validate($elements)
  {
    if (!isset($elements->author))
    {
      $isError = false;
      if (empty($elements->entry))
      {
        $isError = true;
      }
      else
      {
        foreach ($elements->entry as $entry)
        {
          if (!isset($entry->author))
          {
            $isError = true;
            break;
          }
        }
      }

      if ($isError)
      {
        throw new LogicException('feed elements MUST contain one or more author elements, unless all of the feed element\'s child entry elements contain at least one author element.');
      }
    }

    $this->validateRequiredUniqueElement($elements, 'id');
    $this->validateRequiredUniqueElement($elements, 'title');
    $this->validateRequiredUniqueElement($elements, 'updated');
    $this->validateUniqueElement($elements, 'generator');
    $this->validateUniqueElement($elements, 'icon');
    $this->validateUniqueElement($elements, 'logo');
    $this->validateUniqueElement($elements, 'rights');
    $this->validateUniqueElement($elements, 'subtitle');

    return $elements;
  }

  protected function getRootXMLString()
  {
    $string = '<feed xmlns="http://www.w3.org/2005/Atom"></feed>';
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
}
