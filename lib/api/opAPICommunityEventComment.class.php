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
class opAPICommunityEventComment extends opAPICommunityTopicComment
{
  public function insert(SimpleXMLElement $xml)
  {
    $member = Doctrine::getTable('Member')->find($this->getMemberIdByUrl((string)$xml->author->uri));
    if (!$member)
    {
      return false;
    }

    $comment = new CommunityEventComment();
    $comment->setMember($member);
    $comment->setCommunityEvent($this->getParentObject());
    $comment->setBody((string)$xml->content);
    $comment->save();

    return $comment;
  }

  public function createEntryByInstance(Doctrine_Record $comment, SimpleXMLElement $entry = null)
  {
    $entry = parent::createEntryByInstance($comment, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));
    $entry->setContent($comment->getBody());
    $entry->setAuthorByMember($comment->getMember());
    $entry->setLink(url_for('@feeds_community_event_comment_retrieve_resource_normal?model=communityTopic&id='.$comment->getId()), 'self', 'application/atom+xml');

    return $entry;
  }
}
