<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAPIMember
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opAPIMember extends opAPI implements opAPIInterface
{
  public function retrieve()
  {
    $members = MemberPeer::retrieveByPks($this->search());
    if (!$members)
    {
      return false;
    }

    sfContext::getInstance()->getConfiguration()->loadHelpers(array('sfImage', 'Asset'));

    $feed = $this->createFeedBySNS();
    $feed->setId('http://example.com/');

    foreach ($members as $member)
    {
      $entry = $feed->addEntry();
      $this->createEntryByMember($member, $entry);
    }

    $feed->setUpdated($member->getUpdatedAt());

    return $feed->publish();
  }

  public function insert()
  {
    $input = file_get_contents('php://input');
    $entry = new opGDataDocumentEntry($input, true);
    $elements = $entry->getElements();

    $member = new Member();
    $member->setName($elements->title);
    $member->setIsActive(true);
    $member->save();

    $profile = ProfilePeer::retrieveByName('self_intro');
    if ($profile)
    {
      $memberProfile = MemberProfilePeer::makeRoot($member->getId(), $profile->getId());
      $memberProfile->setValue($elements->content);
      $memberProfile->save();
    }

    $responseEntry = $this->createEntryByMember($member);
    return $responseEntry->publish();
  }

  public function update()
  {
    $input = file_get_contents('php://input');
    $entry = new opGDataDocumentEntry($input);
    $elements = $entry->getElements();

    $id = $this->getRequiredParameter('id');
    $member = MemberPeer::retrieveByPk($id);
    if (!$member || $id != $elements->id)
    {
      return false;
    }
    $member = MemberPeer::retrieveByPk($id);
    $member->setName($elements->title);
    $member->save();
    if ($intro = $member->getProfile('self_intro'))
    {
      $intro->setValue($elements->content);
      $intro->save();
    }

    $responseEntry = $this->createEntryByMember($member);
    return $responseEntry->publish();
  }

  public function delete()
  {
    $id = $this->getRequiredParameter('id');
    $member = MemberPeer::retrieveByPk($id);
    if (!$member)
    {
      return false;
    }

    $member->delete();
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

  protected function createEntryByMember(Member $member, SimpleXMLElement $entry = null)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));

    $mailAddress = $member->getConfig('pc_address');
    if (!$mailAddress)
    {
      $mailAddress = $member->getConfig('mobile_address');
    }

    $entry = new opGDataDocumentEntry($entry);
    $entry->setTitle($member->getName());
    $entry->setId($member->getId());
    $entry->setAuthor($member->getName(), $mailAddress);
    $entry->setPublished($member->getCreatedAt());
    $entry->setUpdated($member->getUpdatedAt());
    $entry->setLink(url_for('@feeds_update_entry?model=member&id='.$member->getId(), true), 'edit');

    $selfIntro = '';
    if ($intro = $member->getProfile('self_intro'))
    {
      $selfIntro = $intro->getValue();
    }
    $entry->setContent($selfIntro);

    return $entry;
  }
}
