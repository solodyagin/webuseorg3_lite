<?php

/* 
 * (с) 2011-2015 Грибов Павел
 * http://грибовы.рф * 
 * Если исходный код найден в сети - значит лицензия GPL v.3 * 
 * В противном случае - код собственность ГК Яртелесервис, Мультистрим, Телесервис, Телесервис плюс * 
 */

include_once ("inc/lbfunc.php");                    // загружаем функции LB

// Роли:  
//            1="Полный доступ"
//            2="Просмотр финансовых отчетов"
//            3="Просмотр количественных отчетов"
//            4="Добавление"
//            5="Редактирование"
//            6="Удаление"

if ($user->TestRoles("1,3")==true){
    
?>
<div class="row-fluid">

        <?php 
            $period=false;
            $seconddate=false;
            $agent=false;
            $btrep=false;
            $period=false;
            $fill=false;
            include("controller/client/themes/bootstrap/lanbilling/reports/head.php");                       
        ?>

        <div class="well" id="report">
            <table id="list2"></table>
            <div id="pager2"></div>                    
            
        <div id="dialog-load" title="Загрузка списка телефонов и текста СМС">
          <p>                  
          <label>Вставьте список:</label>
            <textarea rows="8" class="span8" name="smstext" id="smstext"></textarea>
            <div id="message_send"></div>
        </div>
        <div id="dialog-confirm" title="Разослать СМС по списку?">
          <p><span class="ui-icon ui-icon-alert" style="float:left; margin:0 7px 20px 0;"></span>После нажатия кнопки "ДА" произойдет рассылка СМС по всем телефонам находящимся в таблице.</br><strong>Вы уверены?</strong></p>
        </div>            
            
        </div>            
</div>        

<script type="text/javascript" src="controller/client/js/smscenter/smsbylist.js"></script>
<?php
} else {
echo '<div class="alert alert-error">
  У вас нет доступа в данный раздел! Не назначены роли!
</div>';        
};
?>