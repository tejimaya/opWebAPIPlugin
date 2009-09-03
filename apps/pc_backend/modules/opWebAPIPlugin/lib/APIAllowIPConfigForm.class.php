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
  protected $configs = array(
    'auth_type'   => 'op_web_api_plugin_auth_type',
    'ip_list'     => 'op_web_api_plugin_ip_list',
    'using_cdata' => 'op_web_api_plugin_using_cdata',
  );

  public function configure()
  {
    $authTypeChoices = array('IP Address', 'OAuth');
    $usingCDataChoices = array('使用しない', '使用する');

    $this->setWidgets(array(
      'auth_type' => new sfWidgetFormSelect(array('choices' => $authTypeChoices)),
      'ip_list' => new sfWidgetFormTextarea(),
      'using_cdata' => new sfWidgetFormSelect(array('choices' => $usingCDataChoices)),
    ));
    $this->setValidators(array(
      'auth_type' => new sfValidatorChoice(array('choices' => array_keys($authTypeChoices))),
      'ip_list' => new sfValidatorCallback(array('callback' => array($this, 'validate'))),
      'using_cdata' => new sfValidatorChoice(array('choices' => array_keys($usingCDataChoices))),
    ));

    $this->widgetSchema->setHelp('ip_list', 'API へのアクセスを許可する IP アドレスを入力してください。<br />※改行区切りで複数の IP アドレスを入力することができます。');

    foreach ($this->configs as $k => $v)
    {
      $config = Doctrine::getTable('SnsConfig')->retrieveByName($v);
      if ($config)
      {
        $this->getWidgetSchema()->setDefault($k, $config->getValue());
      }
    }

    $this->getWidgetSchema()->setNameFormat('api_config[%s]');
  }

  public function save()
  {
    foreach ($this->getValues() as $k => $v)
    {
      if (!isset($this->configs[$k]))
      {
        continue;
      }

      $config = Doctrine::getTable('SnsConfig')->retrieveByName($this->configs[$k]);
      if (!$config)
      {
        $config = new SnsConfig();
        $config->setName($this->configs[$k]);
      }
      $config->setValue($v);
      $config->save();
    }
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
