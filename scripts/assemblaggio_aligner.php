<?php
require_once("C:/inetpub/vhosts/gualapps.sede.gualadispensing.italia.com/httpdocs/classes/Database.class.php");
require_once ("C:/inetpub/vhosts/gualapps.sede.gualadispensing.italia.com/httpdocs/api/BusinessCentralApi.php");

/* mysql */

$db_mysql = new Database("mysql", "127.0.0.1", "guala_app", "guala_usr", "D@787f7nd");

/* sqlsrv */
$db_sqlsrv_50_65 = new Database("sqlsrv", "192.168.50.65", "mdw", "b4web", "%eAnZiUh");

$sql = "SELECT `id`, `mesOrderNo`, `itemNo` FROM `table_gua_mes_prod_orders` WHERE `mesOrderNo` LIKE '%AS%'";
$db_mysql->prepare($sql);
$res = $db_mysql->fetchAll();

foreach($res as $r){
    if (str_contains($r["mesOrderNo"], 'AS')) {
        $db_sqlsrv_50_65 ->prepare("SELECT round(sum(value),0) from  bm20.IndicatorValueEmulation t1 with (nolock) join bm20.OperationExecution t3 on t1.OperationExecutionId=t3.OperationExecutionId inner join bm20.Indicator ind on ind.IndicatorId=T1.Indicatorid where  T1.IndicatorTypeId = 2 and t1.value<1000 and ind.IndicatorKey like 'Good%' and t3.OperationExecutionkey = '{$r["mesOrderNo"]}'");
        $good = $db_sqlsrv_50_65->fetch();
        foreach($good as $g){
            //$value = array_values($g)[0]; // Prendi il primo valore numerico (es. "round(sum(value), 0)")
            $db_mysql->prepare("UPDATE `table_gua_mes_prod_orders` SET `quantita_prodotta`=:quantita_prodotta WHERE `id`=:id");
            $db_mysql->bind(":id", $r["id"]);
            $db_mysql->bind(":quantita_prodotta", $g);

            if($db_mysql->execute()){
                echo "bomexplosion ".date("Y-m-d H:m:s")." - Aggiornato id : ".$r["id"]."\n";
            }
        }
        
    }
    
}