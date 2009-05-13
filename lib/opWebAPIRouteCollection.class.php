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
 * @package    opWebAPIPlugin
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opWebAPIRouteCollection extends sfRouteCollection
{
  const
    URI_TYPE_COLLECTION = 'collection',
    URI_TYPE_MEMBER = 'member';

  protected
    $collectionUriRule = '/feeds/:model',
    $memberUriRule = '/feeds/:model/:id',

    $templates = array(
      'retrieve_feed' => array(
        'uriType' => self::URI_TYPE_COLLECTION,
        'action'  => 'feedEntries',
        'method'  => 'get',
      ),

      'retrieve_resource' => array(
        'uriType' => self::URI_TYPE_MEMBER,
        'action'  => 'retrieveEntry',
        'method'  => 'get',
      ),

      'insert_resource' => array(
        'uriType' => self::URI_TYPE_COLLECTION,
        'action'  => 'insertEntry',
        'method'  => 'post',
      ),

      'update_resource' => array(
        'uriType' => self::URI_TYPE_MEMBER,
        'action'  => 'updateEntry',
        'method'  => 'put',
      ),

      'delete_resource' => array(
        'uriType' => self::URI_TYPE_MEMBER,
        'action'  => 'updateEntry',
        'method'  => 'delete',
      ),
  );

  public function __construct($options)
  {
    if (empty($options['rules']))
    {
      $options['rules'] = array_keys($this->templates);
    }

    if (empty($options['model']))
    {
      throw new InvalidArgumentException('You must pass a "model" option.');
    }

    $options['name'] = $options['model'];
    parent::__construct($options);

    $this->generateRoutes();
  }

  protected function generateRoutes()
  {
    $options = $this->getOptions();

    $model = $options['model'];
    $parentModel = '';
    $prefix = 'feeds_'.sfInflector::underscore($model).'_';

    if (!empty($options['parent_model']))
    {
      $parentModel = $options['parent_model'];
    }

    foreach ($this->templates as $name => $template)
    {
      $uris = array();
      $action = array('module' => 'feeds', 'action' => $template['action']);
      $requirements = array('model' => $model);
      $routeOption = array('model' => $model);

      if ($template['uriType'] === self::URI_TYPE_COLLECTION)
      {
        $routeOption['type'] = 'list';
        $uri = $this->collectionUriRule;
        if ($parentModel)
        {
          $uri .= '/:parent_model/:parent_id';
          $requirements['parent_model'] = $parentModel;
        }
        $uris['normal'] = $uri;
        $uris['category'] = $uri.'/-/*';
      }
      else
      {
        $routeOption['type'] = 'object';
        $uris['normal'] = $this->memberUriRule;
      }

      foreach ($uris as $key => $value)
      {
        $this->routes[$prefix.'_'.$name.'_'.$key] = new opWebAPIRoute(
          $value,
          $action,
          $requirements,
          $routeOption
        );
      }
    }
  }
}
