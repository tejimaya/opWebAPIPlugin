<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opWebAPIPlugin routing.
 *
 * @package    OpenPNE
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opWebAPIPluginRouting
{
  static public function listenToRoutingLoadConfigurationEvent(sfEvent $event)
  {
    $routing = $event->getSubject();

    $routes = array(
      'feeds_feed' => new sfRequestRoute(
        '/feeds/:model/feed/*',
        array('module' => 'feeds', 'action' => 'feedEntries'),
        array('sf_method' => array('get'))
      ),

      'feeds_entry' => new sfRequestRoute(
        '/feeds/:model/id/:id',
        array('module' => 'feeds', 'action' => 'retrieveEntry'),
        array('sf_method' => array('get'))
      ),

      'feeds_insert_entry' => new sfRequestRoute(
        '/feeds/:model/*',
        array('module' => 'feeds', 'action' => 'insertEntry'),
        array('sf_method' => array('post'))
      ),

      'feeds_update_entry' => new sfRequestRoute(
        '/feeds/:model/id/:id/*',
        array('module' => 'feeds', 'action' => 'updateEntry'),
        array('sf_method' => array('put'))
      ),

      'feeds_delete_entry' => new sfRequestRoute(
        '/feeds/:model/id/:id/*',
        array('module' => 'feeds', 'action' => 'deleteEntry'),
        array('sf_method' => array('delete'))
      ),

      'feeds_nodefaults' => new sfRoute(
        '/feeds/*',
        array('module' => 'default', 'action' => 'error')
      ),
    );

    $routes = array_reverse($routes);
    foreach ($routes as $name => $route)
    {
      $routing->prependRoute($name, $route);
    }
  }
}
