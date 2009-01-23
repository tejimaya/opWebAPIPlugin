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
    if (!$topic)
    {
      return false;
    }

    $entry = $this->createEntryByInstance($topic);
    return $entry->publish();
  }

  public function insert()
  {
    $elements = $this->getEntryXMLFromRequestBody();
    $communityId = $this->getRequiredParameter('community_id');

    $community = CommunityPeer::retrieveByPk($communityId);
    $member = MemberPeer::retrieveByPk($elements->author->id);
    if (!$community || !$member)
    {
      return false;
    }

    $topic = new CommunityTopic();
    $topic->setMember($member);
    $topic->setCommunity($community);
    $topic->setName($elements->title);
    $topic->save();

    $responseEntry = $this->createEntryByInstance($topic);
    return $responseEntry->publish();
  }

  public function update()
  {
    $elements = $this->getEntryXMLFromRequestBody();

    $id = $this->getRequiredParameter('id');
    $topic = CommunityTopicPeer::retrieveByPk($id);

    if (!$topic || $this->generateEntryId($topic) != $elements->id)
    {
      return false;
    }

    $topic->setName($elements->title);
    $topic->save();

    $responseEntry = $this->createEntryByInstance($topic);
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

  protected function createEntryByInstance(BaseObject $topic, SimpleXMLElement $entry = null)
  {
    $entry = parent::createEntryByInstance($topic, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));
    $entry->setTitle($topic->getName());
    $entry->setAuthorByMember($topic->getMember());
    $entry->setLink(app_url_for('pc_frontend', 'communityTopic/detail?id='.$topic->getId(), true), 'self', 'alternate');

    return $entry;
  }
}
