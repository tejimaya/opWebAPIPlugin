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
class opWebAPIRoute extends sfObjectRoute
{
  protected function getObjectForParameters($parameters)
  {
    $query = Doctrine::getTable($this->options['model'])->createQuery();

    if ('object' === $this->options['type'])
    {
      return $query->where('id = ?', $parameters['id']);
    }
    elseif ('list' === $this->options['type'] && isset($parameters['parent_model']))
    {
      return $query->where($parameters['parent_model'].'_id = ?', $parameters['parent_id']);
    }
  }
}
