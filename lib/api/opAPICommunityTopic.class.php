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
  public function retrieve()
  {
    $topics = CommunityTopicPeer::retrieveByPks($this->search());
    if (!$topics)
    {
      return false;
    }

    sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfImage', 'Asset'));

    $feed = $this->createFeedBySNS();
    $feed->setId('http://example.com/');

    foreach ($topics as $topic)
    {
      $entry = $feed->addEntry();
      $this->createEntryByTopic($topic, $entry);
    }

    $feed->setUpdated($topic->getUpdatedAt());

    return $feed->publish();
  }

  public function insert()
  {
    $memberId = $this->getRequiredParameter('member_id');
    $member = MemberPeer::retrieveByPk($memberId);

    $communityId = $this->getRequiredParameter('community_id');
    $community = CommunityPeer::retrieveByPk($communityId);

    $input = file_get_contents('php://input');
    $entry = new opGDataDocumentEntry($input, true);
    $elements = $entry->getElements();

    $topic = new CommunityTopic();
    $topic->setMember($member);
    $topic->setCommunity($community);
    $topic->setName($elements->title);
    $topic->save();

    $responseEntry = $this->createEntryByTopic($topic);
    return $responseEntry->publish();
  }

  public function update()
  {
    $input = file_get_contents('php://input');
    $entry = new opGDataDocumentEntry($input);
    $elements = $entry->getElements();

    $id = $this->getRequiredParameter('id');
    $topic = CommunityTopicPeer::retrieveByPk($id);
    if (!$topic || $id != $elements->id)
    {
      return false;
    }

    $topic->setName($elements->title);
    $topic->save();

    $responseEntry = $this->createEntryByTopic($topic);
    return $responseEntry->publish();
  }

  public function delete()
  {
    $id = $this->getRequiredParameter('id');
    $topic = CommunityTopicPeer::retrieveByPk($id);
    if (!$topic)
    {
      return false;
    }

    $topic->delete();
    return true;
  }

  public function search()
  {
    $query = $this->parseSearchQueries();
    if (isset($query['id']))
    {
      return array($query['id']);
    }

    $result = array();

    if (false !== $ids = $this->fullTextSearch($query['fulltext']))
    {
      $result = array_merge($result, $ids);
    }

    return $result;
  }

  public function fullTextSearch($query)
  {
    return false;
  }

  protected function createEntryByTopic(CommunityTopic $topic, SimpleXMLElement $entry = null)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));

    $member = $topic->getMember();
    $mailAddress = $member->getConfig('pc_address');
    if (!$mailAddress)
    {
      $mailAddress = $member->getConfig('mobile_address');
    }

    $entry = new opGDataDocumentEntry($entry);
    $entry->setTitle($topic->getName());
    $entry->setContent($topic->getName());
    $entry->setId($topic->getId());
    $entry->setAuthor($topic->getName(), $mailAddress);
    $entry->setPublished($topic->getCreatedAt());
    $entry->setUpdated($topic->getUpdatedAt());
    $entry->setLink(url_for('@feeds_update_entry?model=topic&id='.$topic->getId(), true), 'edit');

    return $entry;
  }
}
