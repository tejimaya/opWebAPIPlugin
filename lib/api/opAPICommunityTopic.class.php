<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAPICommunityTopic
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opAPICommunityTopic extends opAPI implements opAPIInterface
{
  public function getSearchableFields()
  {
    return array('name', 'body');
  }

  public function feed()
  {
    $this
      ->addConditionSearchQuery()
      ->addConditionPublished()
      ->addConditionUpdated()
      ->setOrderBy()
      ->setOffsetAndLimitation();

    $communityTopicList = $this->getQuery()->execute();
    if (!$communityTopicList->count())
    {
      return false;
    }

    if ($this->member)
    {
      $acl = $this->getCollectionAcl($this->getParentObject(), $this->member);
      if (!$acl || !$acl->isAllowed($this->member->id, null, 'view'))
      {
        return false;
      }
    }

    $feed = $this->getGeneralFeed($this->getParentObject()->getName(), $this->getTotalCount());
    foreach ($communityTopicList as $key => $topic)
    {
      $entry = $feed->addEntry();
      $this->createEntryByInstance($topic, $entry);
    }
    $feed->setUpdated($communityTopicList->getFirst()->getCreatedAt());

    return $feed->publish();
  }

  public function entry()
  {
    $topic = $this->getRouteObject()->fetchOne();

    if ($this->member && $topic)
    {
      $acl = $this->getResourceAcl($topic, $this->member);
      if (!$acl || !$acl->isAllowed($this->member->id, null, 'view'))
      {
        return false;
      }
    }

    return $topic;
  }

  public function insert(SimpleXMLElement $xml)
  {
    $member = Doctrine::getTable('Member')->find($this->getMemberIdByUrl((string)$xml->author->uri));
    if (!$member)
    {
      return false;
    }

    if ($this->member)
    {
      $acl = $this->getCollectionAcl($this->getParentObject(), $this->member);
      if (!$acl || !$acl->isAllowed($this->member->id, null, 'add'))
      {
        return false;
      }
    }

    $topic = new CommunityTopic();
    $topic->setMember($member);
    $topic->setCommunity($this->getParentObject());
    $topic->setName((string)$xml->title);
    $topic->setBody((string)$xml->content);
    $topic->save();

    return $topic;
  }

  public function update(SimpleXMLElement $xml)
  {
    $topic = $this->getRouteObject()->fetchOne();
    if (!$topic)
    {
      return false;
    }

    if ($this->member && $topic)
    {
      $acl = $this->getResourceAcl($topic, $this->member);
      if (!$acl || !$acl->isAllowed($this->member->id, null, 'edit'))
      {
        return false;
      }
    }

    $topic->setName($xml->title);
    $topic->setName((string)$xml->title);
    $topic->setBody((string)$xml->content);
    $topic->save();

    return $topic;
  }

  public function delete()
  {
    $topic = $this->getRouteObject()->fetchOne();
    if (!$topic)
    {
      return false;
    }

    if ($this->member && $topic)
    {
      $acl = $this->getResourceAcl($topic, $this->member);
      if (!$acl || !$acl->isAllowed($this->member->id, null, 'edit'))
      {
        return false;
      }
    }

    $topic->delete();
    return true;
  }

  public function generateEntryId($entry)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));

    return app_url_for('pc_frontend', 'communityTopic/detail?id='.$entry->getId(), true);
  }

  public function createEntryByInstance(Doctrine_Record $topic, SimpleXMLElement $entry = null)
  {
    $entry = parent::createEntryByInstance($topic, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));

    $entry->setTitle($topic->getName());
    $entry->setContent($topic->getBody());
    $entry->setAuthorByMember($topic->getMember());

    $entry->setLink(url_for('@feeds_community_topic_retrieve_resource_normal?model=communityTopic&id='.$topic->getId()), 'self', 'application/atom+xml');
    $entry->setLink(app_url_for('pc_frontend', 'communityTopic/detail?id='.$topic->getId(), true), 'alternate', 'text/html');
    $entry->setLink(app_url_for('mobile_frontend', 'communityTopic/detail?id='.$topic->getId(), true), 'alternate');

    return $entry;
  }

  protected function getCollectionAcl($community, $member)
  {
    return opCommunityTopicAclBuilder::buildCollection($community, array($member));
  }

  protected function getResourceAcl($communityTopic, $member)
  {
    return opCommunityTopicAclBuilder::buildResource($communityTopic, array($member));
  }
}
