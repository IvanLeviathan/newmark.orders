<?
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
Loc::loadMessages(__FILE__);

class newmark_orders extends CModule{
    public function __construct(){
        if(file_exists(__DIR__."/version.php")){ //descriptions
            $arModuleVersion = array();
            include_once(__DIR__."/version.php");
            $this->MODULE_ID 		   = str_replace("_", ".", get_class($this));
            $this->MODULE_VERSION 	   = $arModuleVersion["VERSION"];
            $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
            $this->MODULE_NAME 		   = Loc::getMessage("MODULE_NAME");
            $this->MODULE_DESCRIPTION  = Loc::getMessage("MODULE_DESCRIPTION");
            $this->PARTNER_NAME 	   = Loc::getMessage("PARTNER_NAME");
            $this->PARTNER_URI  	   = Loc::getMessage("PARTNER_URI");
        }

        return false;
    }

    public function DoInstall(){

        global $APPLICATION;

        if(CheckVersion(ModuleManager::getVersion("main"), "14.00.00")){
            $this->InstallFiles();
            $this->InstallDB();
            ModuleManager::registerModule($this->MODULE_ID);
            // $this->InstallEvents();
			$this->InstallAgents();
        }else{
            $APPLICATION->ThrowException(
                Loc::getMessage("INSTALL_ERROR_VERSION")
            );
        }

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("INSTALL_TITLE")." \"".Loc::getMessage("MODULE_NAME")."\"",
            __DIR__."/step.php"
        );

        return false;
    }


    public function DoUninstall(){

        global $APPLICATION;

        $this->UnInstallFiles();
        $this->UnInstallDB();
        // $this->UnInstallEvents();
		$this->UnInstallAgents();

        ModuleManager::unRegisterModule($this->MODULE_ID);

        $APPLICATION->IncludeAdminFile(
            Loc::getMessage("UNINSTALL_TITLE")." \"".Loc::getMessage("MODULE_NAME")."\"",
            __DIR__."/unstep.php"
        );
        return false;
    }

    //FILES
    public function InstallFiles(){
        CopyDirFiles(
            __DIR__."/assets/scripts",
            Application::getDocumentRoot()."/bitrix/js/".$this->MODULE_ID."/",
            true,
            true
        );
        CopyDirFiles(
            __DIR__."/assets/styles",
            Application::getDocumentRoot()."/bitrix/css/".$this->MODULE_ID."/",
            true,
            true
        );
        CopyDirFiles(
            __DIR__."/assets/images",
            Application::getDocumentRoot()."/bitrix/images/".$this->MODULE_ID."/",
            true,
            true
        );

        return false;
    }
    public function UnInstallFiles(){
        Directory::deleteDirectory(
            Application::getDocumentRoot()."/bitrix/js/".$this->MODULE_ID
        );
        Directory::deleteDirectory(
            Application::getDocumentRoot()."/bitrix/css/".$this->MODULE_ID
        );
        Directory::deleteDirectory(
            Application::getDocumentRoot()."/bitrix/images/".$this->MODULE_ID
        );

        return false;
    }

    //DB
    public function InstallDB(){
        return false;
    }
    public function UnInstallDB(){
        Option::delete($this->MODULE_ID);
        return false;
    }

    //EVENTS
    // public function InstallEvents(){
    //     RegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "Newmark\Replacemarks\Main", "replaceActions");
    //     return false;
    // }
    // public function UnInstallEvents(){
    //     UnRegisterModuleDependences("main", "OnEndBufferContent", $this->MODULE_ID, "Newmark\Replacemarks\Main", "replaceActions");
    //     return false;
    // }

	// AGENTS

    public function InstallAgents(){
        $minutes_to_add = 5;
        $new_day_plus1 = new DateTime(date('Y-m-d H:i:s'));
        $new_day_plus1->add(new DateInterval('PT' . $minutes_to_add . 'M'));
        $firstStartDate = $new_day_plus1->format('d.m.Y H:i:s');


        CAgent::AddAgent(
            "Newmark\Orders\Main::CheckOrders();", // имя функции
            $this->MODULE_ID,                          // идентификатор модуля
            "N",                                  // агент не критичен к кол-ву запусков
            60,                                // интервал запуска - 1 сутки
            $firstStartDate,                // дата первой проверки на запуск
            "Y",                                  // агент активен
            $firstStartDate,                // дата первого запуск
            30
        );


	}
	public function UnInstallAgents(){
        CAgent::RemoveAgent("Newmark\Orders\Main::CheckOrders();", $this->MODULE_ID);
	}
}
