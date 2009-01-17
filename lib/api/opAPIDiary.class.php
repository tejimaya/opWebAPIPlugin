<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAPIDiary
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opAPIDiary extends opAPI implements opAPIInterface
{
  public function retrieve()
  {
    $diaries = DiaryPeer::retrieveByPks($this->search());
    if (!$diaries)
    {
      return false;
    }

    sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfImage', 'Asset'));

    $feed = $this->createFeedBySNS();
    $feed->setId('http://example.com/');

    foreach ($diaries as $diary)
    {
      $entry = $feed->addEntry();
      $this->createEntryByDiary($diary, $entry);
    }

    $feed->setUpdated($diary->getUpdatedAt());

    return $feed->publish();
  }

  public function insert()
  {
    $memberId = $this->getRequiredParameter('member_id');
    $member = MemberPeer::retrieveByPk($memberId);

    $input = file_get_contents('php://input');
    $entry = new opGDataDocumentEntry($input, true);
    $elements = $entry->getElements();

    $diary = new Diary();
    $diary->setTitle($elements->title);
    $diary->setBody($elements->content);
    $diary->setMember($member);
    $diary->save();

    $responseEntry = $this->createEntryByDiary($diary);
    return $responseEntry->publish();
  }

  public function update()
  {
    $input = file_get_contents('php://input');
    $entry = new opGDataDocumentEntry($input);
    $elements = $entry->getElements();

    $id = $this->getRequiredParameter('id');
    $diary = DiaryPeer::retrieveByPk($id);
    if (!$diary || $id != $elements->id)
    {
      return false;
    }

    $diary->setTitle($elements->title);
    $diary->setBody($elements->content);
    $diary->save();

    $responseEntry = $this->createEntryByDiary($diary);
    return $responseEntry->publish();
  }

  public function delete()
  {
    $id = $this->getRequiredParameter('id');
    $diary = DiaryPeer::retrieveByPk($id);
    if (!$diary)
    {
      return false;
    }

    $diary->delete();
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

  protected function createEntryByDiary(Diary $diary, SimpleXMLElement $entry = null)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));

    $member = $diary->getMember();
    $mailAddress = $member->getConfig('pc_address');
    if (!$mailAddress)
    {
      $mailAddress = $member->getConfig('mobile_address');
    }

    $entry = new opGDataDocumentEntry($entry);
    $entry->setTitle($diary->getTitle());
    $entry->setContent($diary->getBody());
    $entry->setId($diary->getId());
    $entry->setAuthor($member->getName(), $mailAddress);
    $entry->setPublished($diary->getCreatedAt());
    $entry->setUpdated($diary->getUpdatedAt());
    $entry->setLink(url_for('@feeds_update_entry?model=diary&id='.$member->getId(), true), 'edit');

    return $entry;
  }
}
