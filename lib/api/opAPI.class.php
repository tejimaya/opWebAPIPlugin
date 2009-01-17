<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * opAPI
 *
 * @package    OpenPNE
 * @subpackage api
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
abstract class opAPI
{
  protected $parameters = array();

  public function __construct($parameters = array())
  {
    $this->parameters = $parameters;
  }

  public function getParameter($name, $default = null)
  {
    if (!isset($this->parameters))
    {
      return $default;
    }

    return $this->parameters[$name];
  }

  public function getRequiredParameter($name)
  {
    $result = $this->getParameter($name);

    if (is_null($result))
    {
      throw new RuntimeException(sprintf('The required argument "%s" is not specified.', $name));
    }

    return $result;
  }

  public function createFeedBySNS()
  {
    $feed = new opGDataDocumentFeed();
    $feed->setTitle(opConfig::get('sns_name'));
    $feed->setAuthor(opConfig::get('admin_email'), opConfig::get('admin_email'));

    return $feed;
  }

  public function parseSearchQueries()
  {
    $result = array();

    if (isset($this->parameters['id']))
    {
      $result['id'] = $this->parameters['id'];
    }

    if (isset($this->parameters['q']))
    {
      $fulltext = array('include' => array(), 'exclude' => array());

      $extracted = opToolkit::extractEnclosedStrings($this->parameters['q']);
      $words = $extracted['enclosed'];
      $queryString = $extracted['base'];

      $words = array_merge($words, explode(' ', $queryString));
      foreach ($words as $value)
      {
        if ($value[0] === '-')
        {
          $fulltext['exclude'][] = $value;
        }
        else
        {
          $fulltext['include'][] = $value;
        }
      }

      $result['fulltext'] = $fulltext;
    }

    return $result;
  }
}
