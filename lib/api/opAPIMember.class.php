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
    return false;
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

  public function createEntryByInstance(Doctrine_Record $member, SimpleXMLElement $entry = null)
  {
    $entry = parent::createEntryByInstance($member, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));
    $entry->setTitle($member->getName());
    $entry->setLink(app_url_for('pc_frontend', 'member/profile?id='.$member->getId(), true), 'self', 'alternate');

    return $entry;
  }
}
