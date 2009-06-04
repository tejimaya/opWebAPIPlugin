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
    $this
      ->addConditionPublished()
      ->addConditionUpdated()
      ->setOrderBy()
      ->setOffsetAndLimitation();

    $communityTopicComments = $this->getQuery()->execute();
    if (!$communityTopicComments->count())
    {
      return false;
    }

    $feed = $this->getGeneralFeed($this->getParentObject()->getName(), $this->getTotalCount());
    foreach ($communityTopicComments as $key => $comment)
    {
      $entry = $feed->addEntry();
      $this->createEntryByInstance($comment, $entry);
    }
    $feed->setUpdated($communityTopicComments->getFirst()->getCreatedAt());

    return $feed->publish();
  }

  public function entry()
  {
    return $this->getRouteObject()->fetchOne();
  }

  public function insert(SimpleXMLElement $xml)
  {
    $member = Doctrine::getTable('Member')->find($this->getMemberIdByUrl((string)$xml->author->uri));
    if (!$member)
    {
      return false;
    }

    $comment = new CommunityTopicComment();
    $comment->setMember($member);
    $comment->setCommunityTopic($this->getParentObject());
    $comment->setBody((string)$xml->content);
    $comment->save();

    return $comment;
  }

  public function update(SimpleXMLElement $xml)
  {
    return false;
  }

  public function delete()
  {
    $comment = $this->getRouteObject()->fetchOne();
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
    $entry->setContent($comment->getBody());
    $entry->setAuthorByMember($comment->getMember());
    $entry->setLink(url_for('@feeds_community_topic_comment_retrieve_resource_normal?model=communityTopic&id='.$comment->getId()), 'self', 'application/atom+xml');

    return $entry;
  }
}
