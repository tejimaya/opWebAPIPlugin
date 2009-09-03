<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opCheckAllowedAccessWebAPI
 *
 * @package    OpenPNE
 * @subpackage filter
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opCheckAllowedAccessWebAPI extends opCheckOAuthAccessTokenFilter
{
  public function execute($filterChain)
  {
    if (0 == Doctrine::getTable('SnsConfig')->get('op_web_api_plugin_auth_type', 0))
    {
      $list = Doctrine::getTable('SnsConfig')->get('op_web_api_plugin_ip_list', '127.0.0.1');
      if (in_array(@$_SERVER['REMOTE_ADDR'], explode("\n", $list)))
      {
        sfContext::getInstance()->getUser()->setAuthenticated(true);
      }

      $filterChain->execute();
    }
    else
    {
      parent::execute($filterChain);
    }
  }
}
