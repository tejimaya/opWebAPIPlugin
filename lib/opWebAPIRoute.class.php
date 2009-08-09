<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opWebAPIPlugin route
 *
 * @package    opWebAPIPlugin
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class opWebAPIRoute extends sfObjectRoute implements opAPIRouteInterface
{
  const
    URI_TYPE_COLLECTION = 'collection',
    URI_TYPE_MEMBER = 'member';

  protected $parentObject = false;

  public function getAPIName()
  {
    return $this->options['api_name'];
  }

  public function getAPICaption()
  {
    return $this->options['api_caption'];
  }

  protected function getObjectForParameters($parameters)
  {
    $query = Doctrine::getTable($this->options['model'])->createQuery();

    if (self::URI_TYPE_MEMBER === $this->options['uriType'])
    {
      return $query->where('id = ?', $parameters['id']);
    }
    elseif (self::URI_TYPE_COLLECTION === $this->options['uriType'] && isset($parameters['parent_model']))
    {
      return $query->where(sfInflector::underscore($parameters['parent_model']).'_id = ?', $parameters['parent_id']);
    }

    return $query;
  }

  public function getParentObject()
  {
    $requirements = $this->getRequirements();
    if (!isset($requirements['parent_model']))
    {
      return false;
    }

    if (false !== $this->parentObject)
    {
      return $this->parentObject;
    }

    $object = Doctrine::getTable(ucfirst($this->parameters['parent_model']))->find($this->parameters['parent_id']);
    if ($object)
    {
      $this->parentObject = $object;

      return $object;
    }

    throw new sfError404Exception(sprintf('Unable to find the %s parent object with the following parameters "%s").', $requirements['parent_model'], str_replace("\n", '', var_export($this->filterParameters($this->parameters), true))));
  }

  protected function parseStarParameter($star)
  {
    $include = array();
    $exclude = array();

    $categories = explode('/', $star);
    foreach ($categories as $category)
    {
      if ('-' === $category[0])
      {
        $exclude[] = substr($category, 1);
      }
      else
      {
        $include[] = explode('|', $category);
      }
    }

    return array('include_category' => $include, 'exclude_category' => $exclude);
  }
}
