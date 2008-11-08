<?php

/**
 * member entry action
 *
 * @package    OpenPNE
 * @subpackage member
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class entryAction extends sfAction
{
 /**
  * Executes this action
  *
  * @param sfRequest $request A request object
  */
  public function execute($request)
  {
    $this->member = MemberPeer::retrieveByPk($request->getParameter('id'));
    $this->forward404Unless($this->member);

    return sfView::SUCCESS;
  }
}
