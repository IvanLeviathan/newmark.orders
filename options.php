<?
use Bitrix\Main\Localization\Loc;
use	Bitrix\Main\HttpApplication;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;


Loc::loadMessages(__FILE__);

$request = HttpApplication::getInstance()->getContext()->getRequest();

$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);

Loader::includeModule($module_id);
Loader::includeModule('sale');

$paySystemSelect = array();
$paySystemResult = \Bitrix\Sale\PaySystem\Manager::getList();
while($paySystem = $paySystemResult->Fetch()){
	$paySystemSelect[$paySystem['ID']] = '['.$paySystem['ID'].'] '.$paySystem['NAME'];
}

$statusesSelect = array();
$statusResult = \Bitrix\Sale\Internals\StatusLangTable::getList(array(
    'order' => array('STATUS.SORT'=>'ASC'),
    'filter' => array('STATUS.TYPE'=>'O','LID'=>LANGUAGE_ID),
    'select' => array('STATUS_ID','NAME','DESCRIPTION'),

));
while($status=$statusResult->fetch()){
	$statusesSelect[$status['STATUS_ID']] = '['.$status['STATUS_ID'].'] '.$status['NAME'];
}


$aTabs = array(
    array(
        "DIV" 	  => "edit1",
        "TAB" 	  => Loc::getMessage("OPTIONS_TAB_NAME"),
        "TITLE"   => Loc::getMessage("OPTIONS_TAB_TITLE"),
        "OPTIONS" => array(
            Loc::getMessage("OPTIONS_TAB_COMMON"),
            array(
                "switch_on",
                Loc::getMessage("OPTIONS_TAB_SWITCH_ON"),
                "N",
                array("checkbox")
            ),
            Loc::getMessage("OPTIONS_TAB_ACTION"),
			array(
                "time",
                Loc::getMessage("OPTIONS_TAB_TIME"),
                "3600",
                array("text", 5)
            ),
			array(
                "paysystems",
                Loc::getMessage("OPTIONS_TAB_PAYSYSTEMS"),
                "",
                array("multiselectbox",
					$paySystemSelect,
				)
            ),
			array(
                "action",
                Loc::getMessage("OPTIONS_TAB_ACTION"),
                "cancel",
                array("selectbox",
					array(
						"cancel" => Loc::getMessage("OPTIONS_TAB_ACTION_CANCEL"),
						"change_status" => Loc::getMessage("OPTIONS_TAB_ACTION_CHANGE"),
						"change_cancel" => Loc::getMessage("OPTIONS_TAB_ACTION_CANCEL_CHANGE"),
						"delete" => Loc::getMessage("OPTIONS_TAB_ACTION_DELETE")
					),
				)
            ),
            array(
                "cancel_comment",
                Loc::getMessage("OPTIONS_TAB_COMMENT"),
                "",
                array("textarea", 5, 40)
            ),
			array(
                "change_status",
                Loc::getMessage("OPTIONS_TAB_STATUS"),
                "",
                array("selectbox",
					$statusesSelect,
				),
            ),

        )
    )
);

if($request->isPost() && check_bitrix_sessid()){

    foreach($aTabs as $aTab){

        foreach($aTab["OPTIONS"] as $arOption){

            if(!is_array($arOption)){

                continue;
            }

            if($arOption["note"]){

                continue;
            }

            if($request["apply"]){

                $optionValue = $request->getPost($arOption[0]);

                if($arOption[0] == "switch_on"){

                    if($optionValue == ""){

                        $optionValue = "N";
                    }
                }

                Option::set($module_id, $arOption[0], is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
            }elseif($request["default"]){
                Option::set($module_id, $arOption[0], $arOption[2]);
            }
        }
    }

    LocalRedirect($APPLICATION->GetCurPage()."?mid=".$module_id."&lang=".LANG."&mid_menu=1");
}

$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);

$tabControl->Begin();

?>
<form action="<? echo($APPLICATION->GetCurPage()); ?>?mid=<? echo($module_id); ?>&lang=<? echo(LANG); ?>" method="post">
    <?
    foreach($aTabs as $aTab){

        if($aTab["OPTIONS"]){
            $tabControl->BeginNextTab();
            __AdmSettingsDrawList($module_id, $aTab["OPTIONS"]);
        }
    }

    $tabControl->Buttons();
    ?>

    <input type="submit" name="apply" value="<? echo(Loc::GetMessage("OPTIONS_INPUT_APPLY")); ?>" class="adm-btn-save" />
    <input type="submit" name="default" value="<? echo(Loc::GetMessage("OPTIONS_INPUT_DEFAULT")); ?>" />
    <div style="text-align:right;">
        <a href="https://nmark.ru/" target="_blank" style="display:inline-block;">
            <img src="/bitrix/images/<?=$module_id?>/nmlogo.png"/>
        </a>
    </div>


    <?
    echo(bitrix_sessid_post());
    ?>

</form>
<script>

	function toggleStatuses(actionSelect, statusSelect){
		var value = actionSelect.value,
			statusSelectParent = statusSelect.closest('tr');

		if(value.search('change') >= 0) //change status
			statusSelectParent.style.display = '';
		else
			statusSelectParent.style.display = 'none';

	}
	function toggleComment(actionSelect, commentText){
        var value = actionSelect.value,
            commentTextParent = commentText.closest('tr');

        if(value.search('cancel') >= 0)//cancel
            commentTextParent.style.display = '';
        else
            commentTextParent.style.display = 'none';
    }
	var actionSelect = document.getElementsByName("action")[0];
	var statusSelect = document.getElementsByName('change_status')[0];
	var commentText = document.getElementsByName('cancel_comment')[0];

	toggleStatuses(actionSelect, statusSelect);
    toggleComment(actionSelect, commentText);

	actionSelect.addEventListener("change", function() {
		toggleStatuses(actionSelect, statusSelect);
		toggleComment(actionSelect, commentText);
	});

</script>
<?$tabControl->End();?>
