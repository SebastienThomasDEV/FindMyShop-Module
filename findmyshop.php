<?php 

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;


class FindMyShop extends Module implements WidgetInterface {

    private $templateFile;

    public function __construct() {
        $this->name =  'findmyshop';
        $this->version = '1.0.0';
        $this->author = 'ST';
        $this->need_instance = 1;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->trans("Find my Shop !", [], 'Modules.WhereAreWe.Admin');
        $this->description = $this->trans("Affiche une carte avec toutes vos boutiques !", [], 'Modules.WhereAreWe.Admin');
        $this->confirmUninstall = $this->trans('Vous desinstaller le module Find My Shop : êtes-vous sûr de votre choix ? ', [], 'Modules.WhereAreWe.Admin');
        $this->templateFile = 'module:findmyshop/views/template/front.tpl';
    }
    

    public function install() {
        $sql = "CREATE TABLE IF NOT EXISTS " . _DB_PREFIX_ ."MyShops (
        id INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
        myShopName VARCHAR(255) NOT NULL,
        myShopAddress VARCHAR(255) NOT NULL,
        myShopTel VARCHAR(255) NOT NULL,
        myShopLat VARCHAR(255) NOT NULL,
        myShopLong VARCHAR(255) NOT NULL
        ) ENGINE=INNODB  DEFAULT CHARSET=utf8";
        Db::getInstance()->execute($sql);
        return parent::install()
        && $this->registerHook('displayHome')
        && $this->registerHook('header');
    }

    public function uninstall() {
        $sql = "DROP TABLE IF EXISTS " . _DB_PREFIX_ ."MyShops;";
        Db::getInstance()->execute($sql);
        return parent::uninstall()
        && $this->unregisterHook('displayHome');
    }

    

    public function hookHeader() {
        $this->context->controller->registerStylesheet(
            'module-findmyshop-style',
            'modules/'.$this->name.'/views/template/assets/main.css',
            [
                'media' => 'all',
                'priority' => 200
            ]
        );

        $this->context->controller->registerJavascript(
            'module-leaflet-js',
            'modules/'.$this->name.'/views/template/assets/leaflet.js',
            [
                'position' => 'bottom',
                'media' => 'all',
                'priority' => 0
            ]
        );
        $this->context->controller->registerJavascript(
            'module-findmyshop-js',
            'modules/'.$this->name.'/views/template/assets/main.js',
            [
                'position' => 'bottom',
                'media' => 'all',
            ]
        );

    }


    public function renderWidget($hookName, array $configuration) {
        $templateVars = $this->getWidgetVariables($hookName, $configuration);
        $this->smarty->assign($templateVars);
        return $this->fetch($this->templateFile);
    }

    public function getWidgetVariables($hookName, array $configuration) {
        $db = Db::getInstance();
        $request = 'SELECT * FROM ' . _DB_PREFIX_ . 'MyShops;';
        $result = $db->executeS($request);
        return [
            'link' => Context::getContext()->link->getModuleLink('findmyshop', 'pagefind'),
            'test' => $result
        ];
    }

    public function getContent() {
        $output = $this->post_validate();
        return $output.$this->renderForm();
    }

    private function post_validate() {
        $output = "";
        $errors = [];
        $name = Tools::getValue('name');
        $rawAddress = explode(" ", trim(Tools::getValue('address')));
        $address = [];
        foreach ($rawAddress as $value) {
            $value = trim($value);
            if ($value !== "") {
                $address[] = $value;
            }
        }
        $address = implode("%20", $address);
        $fullAddress = implode("%20", [trim(Tools::getValue('num')), $address, trim(Tools::getValue('ville')), trim(Tools::getValue('zipcode'))]);

        $tel = Tools::getValue('tel');
        if (Tools::isSubmit('submit_find')) {
            if ($name === '') {
                $errors[] = 'le champs nom est obligatoire';
            }

            if (!Tools::getValue('num')) {
                $errors[] = 'le champs numero est obligatoire';
            }
            if (!Tools::getValue('address')) {
                $errors[] = 'le champs addresse est obligatoire';
            }
            if (!Tools::getValue('ville')) {
                $errors[] = 'le champs ville est obligatoire';
            }
            if (!Tools::getValue('zipcode')) {
                $errors[] = 'le champs code postal est obligatoire';
            }
            if (!Tools::getValue('tel')) {
                $errors[] = 'le champs telephone est obligatoire';
            }
            if (count($errors) > 0) {
                $output = $this->displayError(implode('<br>', $errors));
                
            } else {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.geoapify.com/v1/geocode/search?text='. $fullAddress .'&format=json&apiKey=0b650e024b3942279581fbc30b1bfc56',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_CONTENT_DECODING => false,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET'));
                $response = curl_exec($curl);
                $response = json_decode($response, true);
            
                if (count($response['results']) === 1) {
                    $lat =  $response['results'][0]['lat'];
                    $lon = $response['results'][0]['lon'];
                    $fullAddress = explode("%20", $fullAddress);
                    $fullAddress = implode(" ", $fullAddress);
                    
                    $slq = Db::getInstance()->insert('MyShops', [
                        'myShopName' => htmlspecialchars($name),
                        'myShopaddress' => htmlspecialchars($fullAddress),
                        'myShoptel' => htmlspecialchars($tel),
                        'myShopLat' => htmlspecialchars($lat),
                        'myShopLong' => htmlspecialchars($lon)
                    ]);
                    curl_close($curl);
                    $output = $this->displayConfirmation('le formulaire est enregistré');
                } else {
                    $output = $this->displayError("L'adresse n'a pas pu être trouvé, entrez une adresse valide");
                }
            }
        }
        return $output;
    }

    private function renderForm() {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Settings', [], 'Admin.Global'),
                    'icon' => 'icon-cogs',
                ],
                'description' => $this->trans('Enter a new shop details', [], 'Modules.WhereAreWe.Admin'),
                'input' => [
                    [
                        'type' => 'text',
                        'name' => 'name',
                        'label' => $this->trans('Nom de la boutique', [], 'Modules.WhereAreWe.Admin'),
                        'required' => 1
                    ],
                    [
                        'type' => 'text',
                        'name' => 'num',
                        'label' => $this->trans('Numero de la rue', [], 'Modules.WhereAreWe.Admin'),
                        'required' => 1
                    ],
                    [
                        'type' => 'text',
                        'name' => 'address',
                        'label' => $this->trans('Adresse', [], 'Modules.WhereAreWe.Admin'),
                        'required' => 1
                    ],
                    [
                        'type' => 'text',
                        'name' => 'ville',
                        'label' => $this->trans('Ville', [], 'Modules.WhereAreWe.Admin'),
                        'required' => 1
                    ],
                    [
                        'type' => 'text',
                        'name' => 'zipcode',
                        'label' => $this->trans('Code postal', [], 'Modules.WhereAreWe.Admin'),
                        'required' => 1
                    ],
                    [
                        'type' => 'text',
                        'name' => 'tel',
                        'label' => $this->trans('Telephone', [], 'Modules.WhereAreWe.Admin'),
                        'required' => 1
                    ]
                ],
                'submit' => [
                    'title' => $this->trans('Ajouter la boutique', [], 'Admin.Actions'),
                ]
            ]
        ];

        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit_find';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValue(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];
        return $helper->generateForm([$fields_form]);
    }

    private function getConfigFieldsValue() {
        return [
            'name' => "",
            'num' => "",
            'address' => "",
            'ville' => "",
            'zipcode' => "",
            'tel' => "",
        ];
    }
    
}   