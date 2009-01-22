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
  public function retrieve()
  {
    $comments = CommunityTopicCommentPeer::retrieveByPks($this->search());
    if (!$comments)
    {
      return false;
    }

    sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfImage', 'Asset'));

    $feed = $this->createFeedBySNS();
    $feed->setId('http://example.com/');

    foreach ($comments as $comment)
    {
      $entry = $feed->addEntry();
      $this->createEntryByTopicComment($comment, $entry);
    }

    $feed->setUpdated($comment->getUpdatedAt());

    return $feed->publish();
  }

  public function insert()
  {
    $memberId = $this->getRequiredParameter('member_id');
    $member = MemberPeer::retrieveByPk($memberId);

    $communityTopicId = $this->getRequiredParameter('topic_id');
    $communityTopic = CommunityTopicPeer::retrieveByPk($communityTopicId);

    $input = file_get_contents('php://input');
    $entry = new opGDataDocumentEntry($input, true);
    $elements = $entry->getElements();

    $comment = new CommunityTopicComment();
    $comment->setMember($member);
    $comment->setCommunityTopic($communityTopic);
    $comment->setBody($elements->content);
    $comment->save();

    $responseEntry = $this->createEntryByTopicComment($comment);
    return $responseEntry->publish();
  }

  public function update()
  {
    $input = file_get_contents('php://input');
    $entry = new opGDataDocumentEntry($input);
    $elements = $entry->getElements();

    $id = $this->getRequiredParameter('id');
    $comment = CommunityTopicCommentPeer::retrieveByPk($id);
    if (!$comment || $id != $elements->id)
    {
      return false;
    }

    $comment->setBody($elements->content);
    $comment->save();

    $responseEntry = $this->createEntryByTopicComment($comment);
    return $responseEntry->publish();
  }

  public function delete()
  {
    $id = $this->getRequiredParameter('id');
    $comment = CommunityTopicCommentPeer::retrieveByPk($id);
    if (!$comment)
    {
      return false;
    }

    $comment->delete();
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

  protected function createEntryByTopicComment(CommunityTopicComment $comment, SimpleXMLElement $entry = null)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));

    $member = $comment->getMember();
    $mailAddress = $member->getConfig('pc_address');
    if (!$mailAddress)
    {
      $mailAddress = $member->getConfig('mobile_address');
    }

    $entry = new opGDataDocumentEntry($entry);
    $entry->setTitle($comment->getCommunityTopic()->getName());
    $entry->setContent($comment->getBody());
    $entry->setId($comment->getId());
    $entry->setAuthor($member->getName(), $mailAddress);
    $entry->setPublished($comment->getCreatedAt());
    $entry->setUpdated($comment->getUpdatedAt());
    $entry->setLink(url_for('@feeds_update_entry?model=communityTopicComment&id='.$comment->getId(), true), 'edit');

    return $entry;
  }
}
