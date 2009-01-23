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
  public function feed()
  {
    if ($id = $this->getParameter('community_id'))
    {
      $communityTopic = CommunityTopicPeer::retrieveByCommunityId($id);
    }
    else
    {
      $communityTopic = CommunityTopicPeer::doSelect(new Criteria());
    }

    if (!$communityTopic)
    {
      return false;
    }

    $feed = $this->getGeneralFeed('CommunityTopics');
    foreach ($communityTopic as $topic)
    {
      $entry = $feed->addEntry();
      $this->createEntryByTopic($topic, $entry);
    }
    $feed->setUpdated($communityTopic[0]->getCreatedAt());

    return $feed->publish();
  }

  public function entry()
  {
    $id = $this->getRequiredParameter('id');
    $topic = CommunityTopicPeer::retrieveByPk($id);
    $entry = $this->createEntryByTopic($topic);
    return $entry->publish();
  }

  public function insert()
  {
    $memberId = $this->getRequiredParameter('member_id');
    $member = MemberPeer::retrieveByPk($memberId);

    $communityId = $this->getRequiredParameter('community_id');
    $community = CommunityPeer::retrieveByPk($communityId);

    $input = file_get_contents('php://input');
    $entry = new opAtomPubDocumentEntry($input, true);
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
    $entry = new opAtomPubDocumentEntry($input);
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

  protected function createEntryByTopic(CommunityTopic $topic, SimpleXMLElement $entry = null)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));

    $member = $topic->getMember();
    $mailAddress = $member->getConfig('pc_address');
    if (!$mailAddress)
    {
      $mailAddress = $member->getConfig('mobile_address');
    }

    $entry = new opAtomPubDocumentEntry($entry);
    $entry->setTitle($topic->getName());
    $entry->setId(md5(CommunityTopic::PEER.$topic->getId()));
    $entry->setAuthor($topic->getName(), $mailAddress);
    $entry->setPublished($topic->getCreatedAt());
    $entry->setUpdated($topic->getUpdatedAt());
    $entry->setLink(app_url_for('pc_frontend', 'communityTopic/detail?id='.$topic->getId(), true), 'self', 'alternate');

    return $entry;
  }
}
