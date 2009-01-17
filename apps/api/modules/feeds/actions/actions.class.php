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
    $this->getResponse()->setHttpHeader('GData-Version', 2);
    $this->getResponse()->setHttpHeader('Content-Type', 'text/xml');

    $request = sfContext::getInstance()->getRequest();
    $model = $request->getParameter('model');

    $className = 'opAPI'.sfInflector::classify($model);
    $this->forward404Unless(class_exists($className));

    $params = $request->getParameterHolder()->getAll();
    $this->api = new $className($params);
  }

 /**
  * Executes retrieveEntries action
  *
  * @param sfRequest $request A request object
  */
  public function executeRetrieveEntries($request)
  {
    $this->result = $this->api->retrieve();
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
