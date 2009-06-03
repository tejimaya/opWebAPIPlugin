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
  public function feed()
  {
    $this
      ->addConditionPublished()
      ->addConditionUpdated()
      ->setOrderBy()
      ->setOffsetAndLimitation();

    $members = $this->getRouteObject()->execute();
    if (!$members->count())
    {
      return false;
    }

    $feed = $this->getGeneralFeed('Members', $this->getTotalCount());
    foreach ($members as $key => $member)
    {
      $entry = $feed->addEntry();
      $this->createEntryByInstance($member, $entry);
    }
    $feed->setUpdated($members->getFirst()->getCreatedAt());

    return $feed->publish();
  }

  public function entry()
  {
    return $this->getRouteObject()->fetchOne();
  }

  public function insert(SimpleXMLElement $xml)
  {
    return false;
  }

  public function update(SimpleXMLElement $xml)
  {
    return false;
  }

  public function delete()
  {
    return false;
  }

  public function generateEntryId($entry)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));

    return app_url_for('pc_frontend', 'member/profile?id='.$entry->getId(), true);
  }

  public function createEntryByInstance(Doctrine_Record $member, SimpleXMLElement $entry = null)
  {
    $entry = parent::createEntryByInstance($member, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));
    $entry->setTitle($member->getName());

    $content = $entry->getElements()->addChild('content');
    $content->addAttribute('type', 'xhtml');
    $profiles = $content->addChild('div');
    $profiles->addAttribute('xmlns', 'http://www.w3.org/1999/xhtml');

    foreach ($member->getProfiles() as $profile)
    {
      $child = $profiles->addChild('div', $profile);
      $child->addAttribute('id', $profile->getName());
    }

    $entry->setLink(url_for('@feeds_member_retrieve_resource_normal?model=member&id='.$member->getId()), 'self', 'application/atom+xml');
    $entry->setLink(app_url_for('pc_frontend', 'member/profile?id='.$member->getId(), true), 'alternate', 'text/html');
    $entry->setLink(app_url_for('mobile_frontend', 'member/profile?id='.$member->getId(), true), 'alternate');

    return $entry;
  }
}
