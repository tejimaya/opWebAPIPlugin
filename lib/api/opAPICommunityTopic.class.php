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
      $this->createEntryByInstance($topic, $entry);
    }
    $feed->setUpdated($communityTopic[0]->getCreatedAt());

    return $feed->publish();
  }

  public function entry()
  {
    $id = $this->getRequiredParameter('id');
    $topic = CommunityTopicPeer::retrieveByPk($id);
    return $topic;
  }

  public function insert(SimpleXMLElement $xml)
  {
    $communityId = $this->getRequiredParameter('community_id');

    $community = CommunityPeer::retrieveByPk($communityId);
    $member = MemberPeer::retrieveByPk($xml->author->id);
    if (!$community || !$member)
    {
      return false;
    }

    $topic = new CommunityTopic();
    $topic->setMember($member);
    $topic->setCommunity($community);
    $topic->setName($xml->title);
    $topic->save();

    return $topic;
  }

  public function update(SimpleXMLElement $xml)
  {
    $id = $this->getRequiredParameter('id');
    $topic = CommunityTopicPeer::retrieveByPk($id);

    if (!$topic || $this->generateEntryId($topic) != $xml->id)
    {
      return false;
    }

    $topic->setName($xml->title);
    $topic->save();

    return $topic;
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

  public function createEntryByInstance(BaseObject $topic, SimpleXMLElement $entry = null)
  {
    $entry = parent::createEntryByInstance($topic, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));
    $entry->setTitle($topic->getName());
    $entry->setAuthorByMember($topic->getMember());
    $entry->setLink(app_url_for('pc_frontend', 'communityTopic/detail?id='.$topic->getId(), true), 'self', 'alternate');

    return $entry;
  }
}
