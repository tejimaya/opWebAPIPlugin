<?php

/**
 * This file is part of the OpenPNE package.
 * (c) OpenPNE Project (http://www.openpne.jp/)
 *
 * For the full copyright and license information, please view the LICENSE
 * file and the NOTICE file that were distributed with this source code.
 */

/**
 * APIAllowIPConfig form.
 *
 * @package    OpenPNE
 * @subpackage form
 * @author     Kousuke Ebihara <ebihara@tejimaya.com>
 */
class APIAllowIPConfig extends sfForm
{
  protected $configName = 'op_web_api_plugin_ip_list';

  public function configure()
  {
    $this->setWidgets(array(
      'ip_list' => new sfWidgetFormTextarea(),
    ));
    $this->setValidators(array(
      'ip_list' => new sfValidatorCallback(array('callback' => array($this, 'validate'))),
    ));

    $config = SnsConfigPeer::retrieveByName($this->configName);
    if ($config)
    {
      $this->getWidgetSchema()->setDefault('ip_list', $config->getValue());
    }

    $this->getWidgetSchema()->setNameFormat('api_config[%s]');
  }

  public function save()
  {
    $config = SnsConfigPeer::retrieveByName($this->configName);
    if (!$config)
    {
      $config = new SnsConfig();
      $config->setName($this->configName);
    }
    $config->setValue($this->getValue('ip_list'));
    $config->save();
  }

  public function validate($validator, $value, $arguments = array())
  {
    $value = opToolkit::unifyEOLCharacter($value);

    $list = array_map('trim', explode("\n", $value));
    $list = array_unique($list);

    foreach ($list as $item)
    {
      if (!preg_match('/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/', $item))
      {
        throw new sfValidatorError($validator, 'invalid');
      }
    }

    return implode("\n", $list);
  }
}
