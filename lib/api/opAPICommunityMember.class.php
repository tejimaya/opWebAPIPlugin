<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAPICommunityMember
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opAPICommunityMember extends opAPI implements opAPIInterface
{
  public function feed()
  {
    $this
      ->addConditionPublished()
      ->addConditionUpdated()
      ->setOrderBy()
      ->setOffsetAndLimitation();

    $communities = $this->getQuery()->execute();
    if (!$communities->count())
    {
      return false;
    }

    $feed = $this->getGeneralFeed($this->getParentObject()->getName(), $this->getTotalCount());
    foreach ($communities as $key => $community)
    {
      $entry = $feed->addEntry();
      $this->createEntryByInstance($community, $entry);
    }
    $feed->setUpdated($communities->getFirst()->getCreatedAt());

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

    return app_url_for('pc_frontend', 'member/profile?id='.$entry->getMember()->getId(), true);
  }

  public function createEntryByInstance(Doctrine_Record $communityMember, SimpleXMLElement $entry = null)
  {
    $member = $communityMember->getMember();

    $entry = parent::createEntryByInstance($communityMember, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));
    $entry->setTitle($member->getName());

    $content = $entry->getElements()->addChild('content');
    $content->addAttribute('type', 'xhtml');
    $profiles = $content->addChild('div');
    $profiles->addAttribute('xmlns', 'http://www.w3.org/1999/xhtml');

    foreach ($member->getProfiles() as $profile)
    {
      $value = (string)$profile;
      $child = $profiles->addChild('div', $value);
      $child->addAttribute('id', $profile->getName());
    }

    $entry->setLink(url_for('@feeds_member_retrieve_resource_normal?model=community&id='.$member->getId()), 'self', 'application/atom+xml');
    $entry->setLink(app_url_for('pc_frontend', 'member/profile?id='.$member->getId(), true), 'alternate', 'text/html');
    $entry->setLink(app_url_for('mobile_frontend', 'member/profile?id='.$member->getId(), true), 'alternate');

    return $entry;
  }
}
