<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAPI
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
abstract class opAPI
{
  protected
    $parameters = array(),

    $orderByTable = array(
      'published' => 'created_at',
      'updated'   => 'updated_at',
    ),

    $routeObject = null;

  public function __construct($parameters = array())
  {
    $this->parameters = $parameters;
  }

  public function hasParameter($name)
  {
    return isset($this->parameters[$name]);
  }

  public function getParameter($name, $default = null)
  {
    if (!$this->hasParameter($name))
    {
      return $default;
    }

    return $this->parameters[$name];
  }

  public function getGeneralFeed($title, $totalCount = 0)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));
    $internalUri = sfContext::getInstance()->getRouting()->getCurrentInternalUri();

    $feed = new opGDataDocumentFeed();
    $feed->setTitle($title.' - '.opConfig::get('sns_name'));
    $feed->setId(md5($internalUri));
    $feed->setLink(url_for($internalUri, true), 'self');
    $feed->setLink(url_for($internalUri, true), 'http://schemas.google.com/g/2005#feed', 'application/atom+xml');

    if ($totalCount)
    {
      $offsets = $this->routeObject->getDqlPart('offset');
      $limits = $this->routeObject->getDqlPart('limit');
      $feed->setOpenSearch($totalCount, array_shift($offsets) + 1, array_shift($limits));
    }

    return $feed;
  }

  public function getRequiredParameter($name)
  {
    $result = $this->getParameter($name);

    if (is_null($result))
    {
      throw new RuntimeException(sprintf('The required argument "%s" is not specified.', $name));
    }

    return $result;
  }

  public function generateEntryId($entry)
  {
    return get_class($entry).':'.$entry->getId();
  }

  public function createEntryByInstance(Doctrine_Record $obj, SimpleXMLElement $entry = null)
  {
    $entry = new opAtomPubDocumentEntry($entry);
    $entry->setId($this->generateEntryId($obj));
    $entry->setPublished($obj->getCreatedAt());
    $entry->setUpdated($obj->getUpdatedAt());

    return $entry;
  }

  public function getEntryXMLFromRequestBody()
  {
    $input = file_get_contents('php://input');
    $entry = new opAtomPubDocumentEntry($input, true);
    return $entry->getElements();
  }

  public function setRouteObject($object)
  {
    $this->routeObject = $object;
  }

  public function getRouteObject()
  {
    return $this->routeObject;
  }

  public function setOffsetAndLimitation()
  {
    $this->routeObject->limit($this->getParameter('max-results', 25));
    $this->routeObject->offset($this->getParameter('max-results', 1) - 1);

    return $this;
  }

  public function setOrderBy()
  {
    $rawOrderby = $this->getParameter('orderby', 'published');
    if (!isset($this->orderByTable[$rawOrderby]))
    {
      return $this;
    }

    $orderby = $this->orderByTable[$rawOrderby];

    if ('ascend' === $this->getParameter('sortorder'))
    {
      $sortorder = 'ASC';
    }
    else
    {
      $sortorder = 'DESC';
    }

    $this->routeObject->orderby($orderby.' '.$sortorder);

    return $this;
  }

  public function addConditionPublished()
  {
    if ($this->hasParameter('published-min'))
    {
      $publishedMin = $this->getParameter('published-min');
      $this->routeObject->andWhere('created_at >= ?', date('Y-m-d H:i:s', strtotime($publishedMin)));
    }

    if ($this->hasParameter('published-max'))
    {
      $publishedMax = $this->getParameter('published-max');
      $this->routeObject->andWhere('created_at < ?', date('Y-m-d H:i:s', strtotime($publishedMax)));
    }

    return $this;
  }

  public function addConditionUpdated()
  {
    if ($this->hasParameter('updated-min'))
    {
      $updatedMin = $this->getParameter('updated-min');
      $this->routeObject->andWhere('updated_at >= ?', date('Y-m-d H:i:s', strtotime($updatedMin)));
    }

    if ($this->hasParameter('updated-max'))
    {
      $updatedMax = $this->getParameter('updated-max');
      $this->routeObject->andWhere('updated_at < ?', date('Y-m-d H:i:s', strtotime($updatedMax)));
    }

    return $this;
  }

  public function addConditionAuthor()
  {
    if ($this->hasParameter('author'))
    {
      $author = $this->getParameter('author');
      $memberId = $this->getMemberIdByUrl($author);
      $this->routeObject->andWhere('member_id = ?', $memberId);
    }

    return $this;
  }

  public function getMemberIdByUrl($url)
  {
    $result = '';

    $path = parse_url($url, 'path');
    if ($path)
    {
      $pieces = array_reverse(explode('/', $path));
      if (isset($pieces[1]) && 'member' === $pieces[1])
      {
        $result = (int)array_pop($pieces);
      }
    }

    return $result;
  }
}
