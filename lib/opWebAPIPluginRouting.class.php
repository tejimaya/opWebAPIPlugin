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
      'member'                => new opWebAPIRouteCollection(array('model' => 'member')),
      'diary'                 => new opWebAPIRouteCollection(array('model' => 'diary')),
      'community'             => new opWebAPIRouteCollection(array('model' => 'community')),
      'communityMember'       => new opWebAPIRouteCollection(array('model' => 'communityMember', 'parent_model' => 'community')),
      'communityTopic'        => new opWebAPIRouteCollection(array('model' => 'communityTopic', 'parent_model' => 'community')),
      'communityTopicComment' => new opWebAPIRouteCollection(array('model' => 'communityTopicComment', 'parent_model' => 'communityTopic')),
      'communityEvent'        => new opWebAPIRouteCollection(array('model' => 'communityEvent', 'parent_model' => 'community')),
      'communityEventComment' => new opWebAPIRouteCollection(array('model' => 'communityEventComment', 'parent_model' => 'communityEvent')),

      'feeds_nodefaults' => new sfRoute(
        '/feeds/*',
        array('module' => 'default', 'action' => 'error', 'sf_format' => 'atom')
      ),
    );

    $routes = array_reverse($routes);
    foreach ($routes as $name => $route)
    {
      $routing->prependRoute($name, $route);
    }
  }
}
