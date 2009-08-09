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
  protected
    $collectionUriRule = '/feeds/:model',
    $memberUriRule = '/feeds/:model/:id',

    $templates = array(
      'retrieve_feed' => array(
        'uriType' => opWebAPIRoute::URI_TYPE_COLLECTION,
        'action'  => 'feedEntries',
        'method'  => 'get',
      ),

      'retrieve_resource' => array(
        'uriType' => opWebAPIRoute::URI_TYPE_MEMBER,
        'action'  => 'retrieveEntry',
        'method'  => 'get',
      ),

      'insert_resource' => array(
        'uriType' => opWebAPIRoute::URI_TYPE_COLLECTION,
        'action'  => 'insertEntry',
        'method'  => 'post',
      ),

      'update_resource' => array(
        'uriType' => opWebAPIRoute::URI_TYPE_MEMBER,
        'action'  => 'updateEntry',
        'method'  => 'put',
      ),

      'delete_resource' => array(
        'uriType' => opWebAPIRoute::URI_TYPE_MEMBER,
        'action'  => 'deleteEntry',
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

    $apis = array('retrieve_feed', 'retrieve_resource', 'insert_resource', 'update_resource', 'delete_resource');
    if (!empty($options['apis']))
    {
      $apis = (array)$options['apis'];
    }

    $model = $options['model'];
    if (!class_exists(ucfirst($model)))
    {
      return false;
    }

    $parentModel = '';
    $prefix = 'feeds_'.sfInflector::underscore($model).'_';

    if (!empty($options['parent_model']))
    {
      $parentModel = $options['parent_model'];
      if (!class_exists(ucfirst($parentModel)))
      {
        return false;
      }
    }

    foreach ($this->templates as $name => $template)
    {
      if (!in_array($name, $apis))
      {
        continue;
      }

      $uris = array();
      $action = array('module' => 'feeds', 'action' => $template['action']);
      $requirements = array('model' => $model, 'sf_method' => $template['method']);
      $routeOption = array('model' => ucfirst($model), 'type' => 'object', 'uriType' => $template['uriType']);

      $routeOption['api_name'] = 'webapi_'.sfInflector::underscore($model).'_'.$template['method'];
      $routeOption['api_caption'] = ucfirst($template['method']).' '.sfInflector::humanize($model).' item(s)';

      if ($template['uriType'] === opWebAPIRoute::URI_TYPE_COLLECTION)
      {
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
        $uris['normal'] = $this->memberUriRule;
      }

      foreach ($uris as $key => $value)
      {
        $this->routes[$prefix.$name.'_'.$key] = new opWebAPIRoute(
          $value,
          array_merge($action, array('sf_format' => 'atom')),
          $requirements,
          $routeOption
        );
      }
    }
  }
}
