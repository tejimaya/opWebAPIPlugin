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
  public function getSearchableFields()
  {
    return array('name');
  }

  public function feed()
  {
    $this
      ->addConditionSearchQuery()
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
    $entry = $this->getRouteObject()->fetchOne();

    if ($this->member && !$entry->isAllowed($this->member, 'view'))
    {
      return false;
    }

    return $entry;
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
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil', 'sfImage', 'Asset', 'Tag'));
    $entry->setTitle($member->getName());

    if (!$this->member || $member->id === $this->member->id)
    {
      $entry->setAuthor(null, null, $member->getEmailAddress());
    }

    $content = $entry->getElements()->addChild('content');
    $content->addAttribute('type', 'xhtml');
    $profiles = $content->addChild('div');
    $profiles->addAttribute('xmlns', 'http://www.w3.org/1999/xhtml');

    $ids = array();
    foreach ($member->getProfiles() as $profile)
    {
      if (in_array($profile->getName(), $ids))
      {
        continue;
      }

      if ($this->member && !$profile->isAllowed($this->member, 'view'))
      {
        continue;
      }

      $ids[] = $profile->getName();

      if ($profile->getProfile()->isPreset())
      {
        $i18n = sfContext::getInstance()->getI18N();
        $child = $profiles->addChild('div');
        $entry->addValidStringToNode($child, $i18n->__((string)$profile));
      }
      else
      {
        $child = $profiles->addChild('div');
        $entry->addValidStringToNode($child, $profile);
      }
      $child->addAttribute('id', $profile->getName());
    }

    $entry->setLink(url_for('@feeds_member_retrieve_resource_normal?model=member&id='.$member->getId()), 'self', 'application/atom+xml');
    $entry->setLink(app_url_for('pc_frontend', 'member/profile?id='.$member->getId(), true), 'alternate', 'text/html');
    $entry->setLink(app_url_for('mobile_frontend', 'member/profile?id='.$member->getId(), true), 'alternate');

    $image = $member->getImage();
    if ($image)
    {
      $entry->setLink(sf_image_path($member->getImageFileName(), array(), true), 'enclosure', $member->getImage()->getFile()->getType());
    }

    return $entry;
  }
}
