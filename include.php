<?
namespace Newmark\Orders;
use Bitrix\Main\Config\Option;

class Main{
    private static $allOptions;
    /**
     * @return mixed
     */
    private static function getModuleId()
    {
        return pathinfo(__DIR__)["basename"];
    }

    /**
     * @param $module_id
     * @return array
     */
    private static function getOptions(){
        if(!empty(self::$allOptions))
            return self::$allOptions;
        $optionsArr = array(
            "switch_on" 	=> Option::get(self::getModuleId(), "switch_on", "N"),
            "time"       => Option::get(self::getModuleId(), "time", "3600"),
            "paysystems"         => Option::get(self::getModuleId(), 'paysystems', ""),
			"action"         => Option::get(self::getModuleId(), 'action', "cancel"),
			"change_status" => Option::get(self::getModuleId(), 'change_status', ""),
            "cancel_comment"         => Option::get(self::getModuleId(), 'cancel_comment', ""),
        );
        self::$allOptions = $optionsArr;
        return $optionsArr;
    }

	public static function CheckOrders(){
        $options = self::getOptions();
        if($options['switch_on'] != 'Y')
            return 'Newmark\Orders\Main::CheckOrders();';

        \CModule::IncludeModule('sale');
        $arFilter = array(
            "PAYED" => "N",
            "CANCELED" => "N"
        );

        if($options['change_status'])
            $arFilter["!STATUS_ID"] = $options['change_status'];

        if($options['paysystems'])
            $arFilter['PAY_SYSTEM_ID'] = explode(',',$options['paysystems']);

        $db_sales = \CSaleOrder::GetList(array("DATE_UPDATE" => "DESC"), $arFilter);

        while ($ar_sales = $db_sales->Fetch()){
            $updateTime = strtotime($ar_sales['DATE_UPDATE']);
            $nowTime = time();
            $min = $options['time'];

            if(($nowTime - $updateTime) >= $min){
                switch ($options['action']){
                    case 'cancel':
                        \CSaleOrder::CancelOrder($ar_sales['ID'], "Y", $options['cancel_comment']);
                        break;
                    case 'change_status':
                        if($options['change_status'])
                            \CSaleOrder::StatusOrder($ar_sales['ID'], $options['change_status']);
                        break;
                    case 'change_cancel':
                        \CSaleOrder::CancelOrder($ar_sales['ID'], "Y", $options['cancel_comment']);
                        if($options['change_status'])
                            \CSaleOrder::StatusOrder($ar_sales['ID'], $options['change_status']);
                        break;
                    case 'delete':
                        \CSaleOrder::Delete($ar_sales['ID']);
                        break;
                }

            }else{
                continue;
            }
        }

        return 'Newmark\Orders\Main::CheckOrders();';
    }
}

?>
