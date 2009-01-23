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
    $entry = $this->createEntryByInstance($diary);
    return $entry->publish();
  }

  public function insert()
  {
    $memberId = $this->getRequiredParameter('member_id');
    $member = MemberPeer::retrieveByPk($memberId);

    $input = file_get_contents('php://input');
    $entry = new opAtomPubDocumentEntry($input, true);
    $elements = $entry->getElements();

    $diary = new Diary();
    $diary->setTitle($elements->title);
    $diary->setBody($elements->content);
    $diary->setMember($member);
    $diary->save();

    $responseEntry = $this->createEntryByInstance($diary);
    return $responseEntry->publish();
  }

  public function update()
  {
    $input = file_get_contents('php://input');
    $entry = new opAtomPubDocumentEntry($input);
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

    $responseEntry = $this->createEntryByInstance($diary);
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

  protected function createEntryByInstance(BaseObject $diary, SimpleXMLElement $entry = null)
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
