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
    $list = SnsConfigPeer::get('op_web_api_plugin_ip_list', '127.0.0.1');
    $this->forward404Unless(in_array(@$_SERVER['REMOTE_ADDR'], explode("\n", $list)));

    $this->getResponse()->setHttpHeader('Content-Type', 'text/xml');

    $request = sfContext::getInstance()->getRequest();
    $model = $request->getParameter('model');

    $className = 'opAPI'.sfInflector::classify($model);
    $this->forward404Unless(class_exists($className));

    $params = $request->getParameterHolder()->getAll();
    $this->api = new $className($params);
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
    return $this->renderText($this->result);
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
    return $this->renderText($this->result);
  }

 /**
  * Executes insertEntry action
  *
  * @param sfRequest $request A request object
  */
  public function executeInsertEntry($request)
  {
    $this->result = $this->api->insert();
    $this->forward404Unless($this->result);

    $this->getResponse()->setStatusCode(201);
    return $this->renderText($this->result);
  }

 /**
  * Executes updateEntry action
  *
  * @param sfRequest $request A request object
  */
  public function executeUpdateEntry($request)
  {
    $this->result = $this->api->update();
    $this->forward404Unless($this->result);
    return $this->renderText($this->result);
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