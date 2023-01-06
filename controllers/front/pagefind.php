<?php 

class FindMyShopPageFindModuleFrontController extends ModuleFrontController {


    public function __construct() {
        parent::__construct();
    }

    public function initContent() {
        $request = 'SELECT * FROM ' . _DB_PREFIX_ . 'MyShops';
        $result = Db::getInstance()->executeS($request);

        $tpl_vars = [
            'link' => Context::getContext()->link->getModuleLink('findmyshop', 'pagefind'),
            'shops' => $result
        ];
        
        $module = 'module:findmyshop/views/front/front.tpl';
            

        parent::initContent();
        $this->context->smarty->assign($tpl_vars);
        $this->setTemplate($module);
    }


}