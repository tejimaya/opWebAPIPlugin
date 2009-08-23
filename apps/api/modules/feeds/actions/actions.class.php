<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * feeds actions.
 *
 * @package    OpenPNE
 * @subpackage feeds
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class feedsActions extends sfActions
{
  public function preExecute()
  {
    $this->getResponse()->setHttpHeader('Content-Type', 'application/atom+xml');

    $request = sfContext::getInstance()->getRequest();
    $model = $request->getParameter('model');

    $className = 'opAPI'.sfInflector::classify($model);
    $this->forward404Unless(class_exists($className));

    $params = $request->getParameterHolder()->getAll();
    $this->api = new $className($params, $this->getRoute());

    if ($this->getUser()->hasAttribute('member_id'))
    {
      $this->api->setMemberId($this->getUser()->getAttribute('member_id'));
    }

    $this->dispatcher->notify(new sfEvent($this, 'feeds_action.pre_execute'));
  }

 /**
  * Executes feedEntries action
  *
  * @param sfRequest $request A request object
  */
  public function executeFeedEntries($request)
  {
    $this->result = $this->api->feed();
    $this->forward404Unless($this->result);
    return $this->renderText($this->api->convertEmojiForAPI($this->result));
  }

 /**
  * Executes retrieveEntry action
  *
  * @param sfRequest $request A request object
  */
  public function executeRetrieveEntry($request)
  {
    $this->result = $this->api->entry();
    $this->forward404Unless($this->result);
    $entry = $this->api->createEntryByInstance($this->result);
    return $this->renderText($this->api->convertEmojiForAPI($entry->publish()));
  }

 /**
  * Executes insertEntry action
  *
  * @param sfRequest $request A request object
  */
  public function executeInsertEntry($request)
  {
    $this->result = $this->api->insert($this->api->getEntryXMLFromRequestBody());
    $this->forward404Unless($this->result);

    $params = array(
      'model' => $request->getParameter('model'),
      'id' => $this->result->getId(),
    );
    $url = $this->generateUrl('feeds_community_retrieve_resource_normal', $params, true);

    $this->getResponse()->setStatusCode(201);
    $this->getResponse()->setHttpHeader('Location', $url);
    $entry = $this->api->createEntryByInstance($this->result);
    return $this->renderText($this->api->convertEmojiForAPI($entry->publish()));
  }

 /**
  * Executes updateEntry action
  *
  * @param sfRequest $request A request object
  */
  public function executeUpdateEntry($request)
  {
    $this->result = $this->api->update($this->api->getEntryXMLFromRequestBody());
    $this->forward404Unless($this->result);
    $entry = $this->api->createEntryByInstance($this->result);
    return $this->renderText($this->api->convertEmojiForAPI($entry->publish()));
  }

 /**
  * Executes deleteEntry action
  *
  * @param sfRequest $request A request object
  */
  public function executeDeleteEntry($request)
  {
    $this->result = $this->api->delete();
    $this->forward404Unless($this->result);
    return sfView::NONE;
  }
}
