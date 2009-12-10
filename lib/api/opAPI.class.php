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
    $member = null,

    $parameters = array(),
    $totalCount = 0,

    $orderByTable = array(
      'published' => 'created_at',
      'updated'   => 'updated_at',
    ),

    $query = null,
    $object = null,
    $parentObject = null,

    $emojiList = null;

  public function __construct($parameters = array(), $route)
  {
    $this->parameters = $parameters;

    $this->query = $route->getObject();
    $this->parentObject = $route->getParentObject();

    $this->emojiList = new OpenPNE_KtaiEmoji();
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
      $offsets = $this->query->getDqlPart('offset');
      $limits = $this->query->getDqlPart('limit');
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
    $entry = new opGDataDocumentEntry($entry);
    $entry->setId($this->generateEntryId($obj));
    $entry->setPublished($obj->getCreatedAt());
    $entry->setUpdated($obj->getUpdatedAt());

    return $entry;
  }

  public function getEntryXMLFromRequestBody()
  {
    $input = file_get_contents('php://input');
    $entry = new opGDataDocumentEntry($input, true);
    return $entry->getElements();
  }

 /**
  * @deprecated
  */
  public function setRouteObject($object)
  {
    $this->query = $object;
  }

 /**
  * @deprecated
  */
  public function getRouteObject()
  {
    return $this->getQuery();
  }

  public function getQuery()
  {
    return $this->query;
  }

  public function getObject()
  {
    if ($this->object)
    {
      return $this->object;
    }

    return $this->getQuery()->fetchOne();
  }

  public function getParentObject()
  {
    return $this->parentObject;
  }

  public function setOffsetAndLimitation()
  {
    $q = clone $this->getQuery();
    $this->totalCount = $q->select('COUNT(DISTINCT id)')->execute(array(), Doctrine::HYDRATE_SINGLE_SCALAR);
    $this->getQuery()->setHydrationMode(Doctrine::HYDRATE_RECORD);

    $this->query->limit($this->getParameter('max-results', 25));
    $this->query->offset($this->getParameter('start', 1) - 1);

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

    $this->query->orderby($orderby.' '.$sortorder);

    return $this;
  }

  public function addConditionPublished()
  {
    if ($this->hasParameter('published-min'))
    {
      $publishedMin = $this->getParameter('published-min');
      $this->query->andWhere('created_at >= ?', date('Y-m-d H:i:s', strtotime($publishedMin)));
    }

    if ($this->hasParameter('published-max'))
    {
      $publishedMax = $this->getParameter('published-max');
      $this->query->andWhere('created_at < ?', date('Y-m-d H:i:s', strtotime($publishedMax)));
    }

    return $this;
  }

  public function addConditionUpdated()
  {
    if ($this->hasParameter('updated-min'))
    {
      $updatedMin = $this->getParameter('updated-min');
      $this->query->andWhere('updated_at >= ?', date('Y-m-d H:i:s', strtotime($updatedMin)));
    }

    if ($this->hasParameter('updated-max'))
    {
      $updatedMax = $this->getParameter('updated-max');
      $this->query->andWhere('updated_at < ?', date('Y-m-d H:i:s', strtotime($updatedMax)));
    }

    return $this;
  }

  public function addConditionAuthor()
  {
    if ($this->hasParameter('author'))
    {
      $author = $this->getParameter('author');
      $memberId = $this->getMemberIdByUrl($author);
      $this->query->andWhere('member_id = ?', $memberId);
    }

    return $this;
  }

  public function getSearchableFields()
  {
    return array();
  }

  public function addConditionSearchQuery()
  {
    if (!$this->getSearchableFields())
    {
      return $this;
    }

    if ($this->hasParameter('q'))
    {
      $q = $this->getParameter('q');
      if ('"' === $q[0] && '"' === $q[strlen($q)-1])
      {
        $q = substr($q, 1, strlen($q)-2);
      }

      $token = strtok($q, ' ');
      while (false !== $token)
      {
        $isIgnore = false;
        if ('-' === $token[0])
        {
          $token = substr($token, 1);
          $isIgnore = true;
        }

        $queryString = '(';

        foreach ($this->getSearchableFields() as $i => $field)
        {
          if ($i)
          {
            $queryString .= ' OR ';
          }

          $queryString .= $field;

          if ($isIgnore)
          {
            $queryString .= ' NOT';
          }

          $queryString .= ' LIKE ?';
        }

        $queryString .= ')';

        $this->query->andWhere($queryString, array_fill(0, count($this->getSearchableFields()), '%'.$token.'%'));

        $token = strtok(' ');
      }
   }

    return $this;
  }

  public function getMemberIdByUrl($url)
  {
    $result = '';

    if ($this->member)
    {
      return $this->member->id;
    }

    $path = parse_url($url, PHP_URL_PATH);
    if ($path)
    {
      if (false !== strpos($path, 'member'))
      {
        $pieces = explode('/', $path);
        $result = (int)array_pop($pieces);
      }
    }

    return $result;
  }

  public function getTotalCount()
  {
    return $this->totalCount;
  }

  public function getCategoryByRequestParameter()
  {
    if (!$this->hasParameter('category'))
    {
      return array();
    }

    $category = $this->getParameter('category');
    $categories = explode(',', $category);

    if ($categories)
    {
      return $categories;
    }

    return array();
  }

  public function convertEmojiForAPI($str)
  {
    $pattern = '/\[([ies]:[0-9]{1,3})\]/';
    return preg_replace_callback($pattern, array($this, 'convertEmojiForAPICallback'), $str);
  }

  public function convertEmojiForAPICallback($matches)
  {
    $o_code = $matches[1];
    $o_carrier = $o_code[0];
    $o_id = substr($o_code, 2);

    if ('i' === $o_carrier)
    {
      return $matches[0];
    }

    return $this->emojiList->relation_list[$o_carrier]['i'][$o_id];
  }

  public function setMemberId($id)
  {
    $this->member = Doctrine::getTable('Member')->find($id);
  }

  public function getAcl($model)
  {
    if (!$this->member)
    {
      return null;
    }

    $builderName = 'op'.get_class($model).'AclBuilder';

    return call_user_func($builderName.'::buildResource', $model, array($this->member));
  }
}
