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
  public function feed()
  {
    if ($id = $this->getParameter('member_id'))
    {
      $diaries = DiaryPeer::getMemberDiaryPager($id, $this->getParameter('_pg', 1));
    }
    else
    {
      $diaries = DiaryPeer::getDiaryPager($this->getParameter('_pg', 1));
    }

    if (!$result = $diaries->getResults())
    {
      return false;
    }

    $feed = $this->getGeneralFeed('Diaries');
    foreach ($result as $diary)
    {
      $entry = $feed->addEntry();
      $this->createEntryByInstance($diary, $entry);
    }
    $feed->setUpdated($result[0]->getCreatedAt());

    return $feed->publish();
  }

  public function entry()
  {
    $id = $this->getRequiredParameter('id');
    $diary = DiaryPeer::retrieveByPk($id);
    return $diary;
  }

  public function insert(SimpleXMLElement $xml)
  {
    $member = MemberPeer::retrieveByPk($xml->author->id);
    if (!$member)
    {
      return false;
    }

    $diary = new Diary();
    $diary->setTitle($xml->title);
    $diary->setBody($xml->content);
    $diary->setMember($member);
    $diary->save();

    return $diary;
  }

  public function update(SimpleXMLElement $xml)
  {
    $id = $this->getRequiredParameter('id');
    $diary = DiaryPeer::retrieveByPk($id);
    if (!$diary || $this->generateEntryId($diary) != $xml->id)
    {
      return false;
    }

    $diary->setTitle($xml->title);
    $diary->setBody($xml->content);
    $diary->save();

    return $diary;
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

  public function createEntryByInstance(BaseObject $diary, SimpleXMLElement $entry = null)
  {
    $entry = parent::createEntryByInstance($diary, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));
    $entry->setTitle($diary->getTitle());
    $entry->setContent($diary->getBody());
    $entry->setAuthorByMember($diary->getMember());
    $entry->setLink(app_url_for('pc_frontend', '@diary_show?id='.$diary->getId(), true), 'self', 'alternate');
    $entry->setLink(app_url_for('mobile_frontend', '@diary_show?id='.$diary->getId(), true), 'self', 'alternate');

    return $entry;
  }
}
