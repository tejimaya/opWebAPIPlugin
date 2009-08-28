<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAPICommunity
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opAPICommunity extends opAPI implements opAPIInterface
{
  protected $orderByTable = array(
      'published' => 'created_at',
      'updated'   => 'updated_at',
      'member'    => 'num_member',
    );

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
      ->setOffsetAndLimitation()
      ->setOrderBy();

    $communities = $this->getRouteObject()->execute();
    if (!$communities->count())
    {
      return false;
    }

    $feed = $this->getGeneralFeed('Communities', $this->getTotalCount());
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
    $member = Doctrine::getTable('Member')->find($this->getMemberIdByUrl((string)$xml->author->uri));
    if (!$member)
    {
      return false;
    }

    $community = new Community();
    $community->setName((string)$xml->title);
    $community->save();

    $admin = new CommunityMember();
    $admin->setPosition('admin');
    $admin->setMember($member);
    $admin->setCommunity($community);
    $admin->save();

    $config = new CommunityConfig();
    $config->setName('description');
    $config->setValue((string)$xml->content);
    $config->setCommunity($community);
    $config->save();

    return $community;
  }

  public function update(SimpleXMLElement $xml)
  {
    $community = $this->getRouteObject()->fetchOne();
    if (!$community)
    {
      return false;
    }

    if ($this->member && !$community->isAllowed($this->member, 'edit'))
    {
      return false;
    }

    $community->setName((string)$xml->title);
    $community->save();

    $config = Doctrine::getTable('CommunityConfig')->retrieveByNameAndCommunityId('description', $community->getId());
    $config->setValue((string)$xml->content);
    $config->save();

    return $community;
  }

  public function delete()
  {
    $community = $this->getRouteObject()->fetchOne();
    if (!$community)
    {
      return false;
    }

    if ($this->member && !$community->isAllowed($this->member, 'edit'))
    {
      return false;
    }

    $community->delete();

    return true;
  }

  public function generateEntryId($entry)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));

    return app_url_for('pc_frontend', 'community/home?id='.$entry->getId(), true);
  }

  public function createEntryByInstance(Doctrine_Record $community, SimpleXMLElement $entry = null)
  {
    $entry = parent::createEntryByInstance($community, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));
    $entry->setTitle($community->getName());
    $entry->setContent($community->getConfig('description'));

    $entry->setAuthorByMember($community->getAdminMember());

    $entry->setLink(url_for('@feeds_community_retrieve_resource_normal?model=community&id='.$community->getId()), 'self', 'application/atom+xml');
    $entry->setLink(app_url_for('pc_frontend', 'community/home?id='.$community->getId(), true), 'alternate', 'text/html');
    $entry->setLink(app_url_for('mobile_frontend', 'community/home?id='.$community->getId(), true), 'alternate');

    return $entry;
  }

  public function setOrderBy()
  {
    $this->query
      ->select('Community.*, COUNT(Community.CommunityMember.id) AS num_member')
      ->groupBy('Community.CommunityMember.community_id');

    return parent::setOrderBy();
  }
}
