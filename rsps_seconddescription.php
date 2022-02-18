<?php
/**
* 2007-2022 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2022 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;
use PrestaShopBundle\Form\Admin\Type\FormattedTextareaType;
use PrestaShopBundle\Form\Admin\Type\TranslateType;

class Rsps_seconddescription extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'rsps_seconddescription';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Szilamer';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Resolution Studio Second Description');
        $this->description = $this->l('Add a new description for the categories');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall this module?');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        Configuration::updateValue('RESOLUTIONDESCRIPTION_LIVE_MODE', false);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('displayBackOfficeCategory') &&
            $this->registerHook('actionAdminCategoriesFormModifier')  &&
            $this->registerHook('actionCategoryFormBuilderModifier') &&
            $this->registerHook('actionAdminCategoriesControllerSaveAfter') &&
            $this->registerHook('actionAfterCreateCategoryFormHandler') &&
            $this->registerHook('actionAfterUpdateCategoryFormHandler') ;
            
    }

    public function uninstall()
    {
        Configuration::deleteByName('RESOLUTIONDESCRIPTION_LIVE_MODE');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitResolutiondescriptionModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');

        return $output.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitResolutiondescriptionModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'RESOLUTIONDESCRIPTION_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'RESOLUTIONDESCRIPTION_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'RESOLUTIONDESCRIPTION_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'RESOLUTIONDESCRIPTION_LIVE_MODE' => Configuration::get('RESOLUTIONDESCRIPTION_LIVE_MODE', true),
            'RESOLUTIONDESCRIPTION_ACCOUNT_EMAIL' => Configuration::get('RESOLUTIONDESCRIPTION_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'RESOLUTIONDESCRIPTION_ACCOUNT_PASSWORD' => Configuration::get('RESOLUTIONDESCRIPTION_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();

        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    /**
    * Add the CSS & JavaScript files you want to be loaded in the BO.
    */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path.'views/js/back.js');
            $this->context->controller->addCSS($this->_path.'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayBackOfficeCategory()
    {
        /* Place your code here. */
    }
    public function hookActionAdminCategoriesFormModifier($params) {
        $fieldsForm = &$params['fields'];
        
//var_export($fieldsForm[0]['form']['input']);
        $fieldsForm[0]['form']['input'][] = array(
            'type' => 'textarea',
            'label' => $this->l('Additional description'),
            'name' => 'additional_description1',
            'autoload_rte' => true,
            'lang' => true
        );
        $fieldsValue = &$params['fields_value'];
        $fieldsValue['additional_description1'] = $this->getFieldsValues();
    }

    public function hookActionAdminCategoriesControllerSaveAfter($params) {
        
        $languages = Language::getLanguages(false);
        $shopId = $this->context->shop->id;
        
        $categoryId = (int) Tools::getValue('id_category');
        foreach ($languages as $lang) {
            $langId = $lang['id_lang'];
            $desc = Tools::getValue('additional_description1_' . $langId);
            $this->storeDescription($shopId, $langId, $categoryId, $desc);
        }
    }

    protected function storeDescription($shopId, $langId, $categoryId, $desc) {
        if ($this->exists($categoryId, $shopId, $langId)) {
            Db::getInstance()->update('resolutiondescription', array('description' => pSQL($desc, true)),
                    '`id_category` = ' . $categoryId . ' '
                    . ' AND `id_shop` = ' . $shopId . ' AND `id_lang` = ' . $langId);
        } else {
            Db::getInstance()->insert('resolutiondescription',
                    array(
                        'description' => pSQL($desc, true),
                        'id_category' => $categoryId,
                        'id_shop' => $shopId,
                        'id_lang' => $langId
            ));
        }
    }

    protected function exists($categoryId, $shopId, $langId) {
        return ($this->getDescription($categoryId, $shopId, $langId) !== false );
    }

    public function getDescription($categoryId, $shopId, $langId) {
        if ((int) $categoryId) {
            $result = Db::getInstance()->getValue('SELECT `description` FROM `' . _DB_PREFIX_ . 'resolutiondescription` WHERE `id_category` = ' . $categoryId . ' '
                    . ' AND `id_shop` = ' . $shopId . ' AND `id_lang` = ' . $langId);
            return $result;
        }
        return false;
    }

    protected function getFieldsValues() {
        $categoryId = (int) Tools::getValue('id_category');
        $languages = Language::getLanguages(false);
        $shopId = $this->context->shop->id;
        $fieldsValues = array();
        foreach ($languages as $lang) {
            $langId = $lang['id_lang'];
            $fieldsValues[$langId] = $this->getDescription($categoryId, $shopId, $langId);
        }
        return $fieldsValues;
    }

    public function renderWidget($hookName, array $configuration) {
        
        $shopId = $this->context->shop->id;
        $langId = $this->context->language->id;
        $categoryId = (int) Tools::getValue('id_category');
        $p = (int) Tools::getValue('page');
        if ($p > 1) {
            return false;
        }
        $cacheId = $this->name . '|' . $categoryId . '|' . $shopId . '|' . $langId;
        if (!$this->isCached($this->templateFile, $cacheId)) {
            $variables = $this->getWidgetVariables($hookName, $configuration);
            if (empty($variables)) {
                return false;
            }
            $this->smarty->assign($variables);
        }
        return $this->fetch($this->templateFile, $cacheId);
    }

    public function getWidgetVariables($hookName, array $configuration) {
        $shopId = $this->context->shop->id;
        $langId = $this->context->language->id;
        $categoryId = (int) Tools::getValue('id_category');
        if ($categoryId > 0) {
            $desc = $this->getDescription($categoryId, $shopId, $langId);
            if (strlen($desc) > 0) {
                return array(
                    'additionalDescription' => $desc
                );
            }
        }
        return false;
    }

    public function hookActionCategoryFormBuilderModifier(array $params) {
       
        $shopId = $this->context->shop->id;
        $categoryId = $params['id'];
        $formBuilder = $params['form_builder'];
        $locales = $this->get('prestashop.adapter.legacy.context')->getLanguages();

        $formBuilder->add('new_description', TranslateType::class, [
            'type' => FormattedTextareaType::class,
            'label' => $this->getTranslator()->trans('New description for categories', [], 'Modules.resolutiondescription.Admin'),
            'locales' => $locales,
            'hideTabs' => false,
            'required' => false]);
           
        foreach ($locales as $locale) {
            $langId = $locale['id_lang'];
            $params['data']['new_description'][$langId] = $this->getDescription($categoryId, $shopId, $langId);

        }
        $formBuilder->setData($params['data']);
        
    }

    public function hookActionAfterUpdateCategoryFormHandler(array $params) {
        $this->updateSecondDescription($params);
    }

    public function hookActionAfterCreateCategoryFormHandler(array $params) {
        $this->updateSecondDescription($params);
    }

    private function updateSecondDescription(array $params) {
        $categoryId = $params['id'];
        $formData = $params['form_data'];
        $shopId = $this->context->shop->id;
        $locales = $this->get('prestashop.adapter.legacy.context')->getLanguages();
        foreach ($locales as $locale) {
            $langId = $locale['id_lang'];
            $desc = $formData['new_description'][$langId];
            $this->storeDescription($shopId, $langId, $categoryId, $desc);
        }
    }


}
