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

    if ($this->member)
    {
      $acl = $this->getCollectionAcl($this->getParentObject(), $this->member);
      if (!$acl || !$acl->isAllowed($this->member->id, null, 'add'))
      {
        return false;
      }
    }

    $gd = $xml->children(opGDataDocument::GDATA_NAMESPACE);
    if (!$gd)
    {
      return false;
    }

    $when = $gd->when[0]->attributes();
    $where = $gd->where[0]->attributes();

    $event = new CommunityEvent();
    $event->setMember($member);
    $event->setCommunity($this->getParentObject());
    $event->setName((string)$xml->title);
    $event->setBody((string)$xml->content);
    $event->setOpenDate((string)$when['startTime']);
    $event->setOpenDateComment('');
    $event->setArea((string)$when['valueString']);
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

    $this->addWhenElement($entry->getElements(), date('c', strtotime($event->getOpenDate())));
    $this->addWhereElement($entry->getElements(), $event->getArea());

    $entry->setLink(url_for('@feeds_community_event_retrieve_resource_normal?model=communityEvent&id='.$event->getId()), 'self', 'application/atom+xml');
    $entry->setLink(app_url_for('pc_frontend', 'communityEvent/detail?id='.$event->getId(), true), 'alternate', 'text/html');
    $entry->setLink(app_url_for('mobile_frontend', 'communityEvent/detail?id='.$event->getId(), true), 'alternate');

    return $entry;
  }

  public function addWhenElement($entry, $startTime)
  {
    $child = $entry->addChild('when', '', opGDataDocument::GDATA_NAMESPACE);
    $child->addAttribute('startTime', $startTime);
  }

  public function addWhereElement($entry, $valueString)
  {
    $child = $entry->addChild('where', '', opGDataDocument::GDATA_NAMESPACE);
    $child->addAttribute('valueString', $valueString);
  }
}
