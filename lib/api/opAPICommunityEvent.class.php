<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAPICommunityTopic
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opAPICommunityEvent extends opAPICommunityTopic
{
  public function insert(SimpleXMLElement $xml)
  {
    $member = Doctrine::getTable('Member')->find($this->getMemberIdByUrl((string)$xml->author->uri));
    if (!$member)
    {
      return false;
    }

    $event = new CommunityEvent();
    $event->setMember($member);
    $event->setCommunity($this->getParentObject());
    $event->setName((string)$xml->title);
    $event->setBody((string)$xml->content);
    $event->save();

    return $event;
  }

  public function generateEntryId($entry)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));

    return app_url_for('pc_frontend', 'communityEvent/detail?id='.$entry->getId(), true);
  }

  public function createEntryByInstance(Doctrine_Record $event, SimpleXMLElement $entry = null)
  {
    $entry = parent::createEntryByInstance($event, $entry);
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url', 'opUtil'));

    $entry->setTitle($event->getName());
    $entry->setContent($event->getBody());
    $entry->setAuthorByMember($event->getMember());

    $entry->setLink(url_for('@feeds_community_event_retrieve_resource_normal?model=communityEvent&id='.$event->getId()), 'self', 'application/atom+xml');
    $entry->setLink(app_url_for('pc_frontend', 'communityEvent/detail?id='.$event->getId(), true), 'alternate', 'text/html');
    $entry->setLink(app_url_for('mobile_frontend', 'communityEvent/detail?id='.$event->getId(), true), 'alternate');

    return $entry;
  }
}
