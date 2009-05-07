<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAPICommunityTopicComment
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opAPICommunityTopicComment extends opAPI implements opAPIInterface
{
  public function feed()
  {
    if ($id = $this->getParameter('topic_id'))
    {
      $communityTopicComment = Doctrine::getTable('CommunityTopicComment')->retrieveByCommunityTopicId($id);
    }
    else
    {
      $communityTopicComment = Doctrine::getTable('CommunityTopicComment')->doSelect(new Criteria());
    }

    if (!$communityTopicComment)
    {
      return false;
    }

    $feed = $this->getGeneralFeed('CommunityTopicComments');
    foreach ($communityTopicComment as $comment)
    {
      $entry = $feed->addEntry();
      $this->createEntryByInstance($comment, $entry);
    }
    $feed->setUpdated($communityTopicComment[0]->getCreatedAt());

    return $feed->publish();
  }

  public function entry()
  {
    $id = $this->getRequiredParameter('id');
    $comment = Doctrine::getTable('CommunityTopicComment')->find($id);
    return $comment;
  }

  public function insert(SimpleXMLElement $xml)
  {
    $communityTopicId = $this->getRequiredParameter('topic_id');

    $communityTopic = Doctrine::getTable('CommunityTopic')->find($communityTopicId);
    $member = Doctrine::getTable('Member')->find($xml->author->id);
    if (!$communityTopic || !$member)
    {
      return false;
    }

    $comment = new CommunityTopicComment();
    $comment->setMember($member);
    $comment->setCommunityTopic($communityTopic);
    $comment->setBody($xml->content);
    $comment->save();

    return $comment;
  }

  public function update(SimpleXMLElement $xml)
  {
    $id = $this->getRequiredParameter('id');
    $comment = Doctrine::getTable('CommunityTopicComment')->find($id);
    if (!$comment || $this->generateEntryId($comment) != $xml->id)
    {
      return false;
    }

    $comment->setBody($xml->content);
    $comment->save();

    return $comment;
  }

  public function delete()
  {
    $id = $this->getRequiredParameter('id');
    $comment = Doctrine::getTable('CommunityTopicComment')->find($id);
    if (!$comment)
    {
      return false;
    }

    $comment->delete();
    return true;
  }

  public function createEntryByInstance(Doctrine_Record $comment, SimpleXMLElement $entry = null)
  {
    $entry = parent::createEntryByInstance($comment, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));
    $entry->setTitle($comment->getCommunityTopic()->getName());
    $entry->setContent($comment->getBody());
    $entry->setAuthorByMember($comment->getMember());
    $entry->setLink(app_url_for('pc_frontend', 'communityTopic/detail?id='.$comment->getCommunityTopic()->getId(), true), 'self', 'alternate');

    return $entry;
  }
}
