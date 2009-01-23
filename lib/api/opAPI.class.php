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
    if (!isset($this->parameters[$name]))
    {
      return $default;
    }

    return $this->parameters[$name];
  }

  public function getGeneralFeed($title)
  {
    sfContext::getInstance()->getConfiguration()->loadHelpers(array('Url'));
    $internalUri = sfContext::getInstance()->getRouting()->getCurrentInternalUri();

    $feed = new opAtomPubDocumentFeed();
    $feed->setTitle($title.' - '.opConfig::get('sns_name'));
    $feed->setId(md5($internalUri));
    $feed->setLink(url_for($internalUri, true), 'self');

    return $feed;
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
}
