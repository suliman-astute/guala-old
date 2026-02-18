<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Database.class.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'BusinessCentralApi.php';

$logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'scripts';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$dbAlignerLogFile = $logDir . DIRECTORY_SEPARATOR . 'db_aligner_' . date('Y-m-d') . '.log';

function db_aligner_log(string $message, array $context = []): void
{
    global $dbAlignerLogFile;

    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;

    if ($context) {
        $sanitized = [];
        foreach ($context as $key => $value) {
            if (is_string($key) && preg_match('/password|secret|token/i', $key)) {
                $sanitized[$key] = '[HIDDEN]';
            } else {
                $sanitized[$key] = $value;
            }
        }

        $line .= ' ' . json_encode($sanitized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    error_log($line . PHP_EOL, 3, $dbAlignerLogFile);
}

function db_aligner_execute(Database $db, string $operation, array $context = []): bool
{
    $ok = $db->execute();
    db_aligner_log($operation . ($ok ? ' OK' : ' ERROR'), $context);
    return $ok;
}

db_aligner_log('db_aligner start');

/* ============================
 *   CONNESSIONI DB
 * ============================ */
/* mysql */
$db_mysql = new Database("mysql", "127.0.0.1", "guala_app", "guala_usr", "D@787f7nd"); // ONLINE
//$db_mysql = new Database("mysql", "127.0.0.1", "guala_app_test", "guala_usr_test", "D@787f7nd"); //TEST

$db_sqlsrv_50_65          = new Database("sqlsrv", "192.168.50.65",  "mdw",    "b4web", "%eAnZiUh");
$db_sqlsrv_data_wherehouse = new Database("sqlsrv", "192.168.0.84",  "master", "b4web", "%eAnZiUh");
$db_sqlsrv_wms            = new Database("sqlsrv", "192.168.50.245", "AHA_8659_GUALA_ROM_PROD", "as400", "As400Romania");
$db_sqlsrv_stein          = new Database("sqlsrv", "192.168.30.1",   "master", "METODO", "intGest2018");
$db_sqlsrv_incas          = new Database("sqlsrv", "192.168.22.104", "EsBisio74", "DBC", "ErPBc25");

/* ============================
 *   CREAZIONE TABELLE TMP
 * ============================ */
creaTabelle($db_mysql);


/* ============================
 *   RECUPERO I COMMENTI PER GUALA FP
 * ============================ */

$db_sqlsrv_data_wherehouse->prepare("select * from shir.stg_p.[FP-CommentLine]");
$res_comment_fp = $db_sqlsrv_data_wherehouse->fetchAll();

db_aligner_log('Loaded FP comments', [
    'source' => 'shir.stg_p.[FP-CommentLine]',
    'count'  => is_array($res_comment_fp) ? count($res_comment_fp) : null,
]);

$sql_insert_commenti = "INSERT INTO `table_commenti_guala_fp_tmp`(`TableName`, `No`, `LineNo`, `Date`, `Code`, `Comment`, `Company`) 
                    VALUES (:TableName, :No, :LineNo, :Date, :Code, :Comment, :Company)";


foreach($res_comment_fp as $k=>$rcfp){
    
    $db_mysql->prepare($sql_insert_commenti);
    $db_mysql->bind(":TableName", $rcfp["TableName"]);
    $db_mysql->bind(":No", $rcfp["No"]);
    $db_mysql->bind(":LineNo", $rcfp["LineNo"]);
    $db_mysql->bind(":Date", $rcfp["Date"]);
    $db_mysql->bind(":Code", $rcfp["Code"]);
    $db_mysql->bind(":Comment", $rcfp["Comment"]);
    $db_mysql->bind(":Company", $rcfp["Company"]);

    if (db_aligner_execute($db_mysql, 'insert table_commenti_guala_fp_tmp', [
        'No'     => $rcfp["No"] ?? null,
        'LineNo' => $rcfp["LineNo"] ?? null,
    ])) {
        echo "Inserisco il commento in data ".date("Y-m-d H:i:s")." con id: ".$db_mysql->lastInsertId()." per elemento ".$rcfp["No"]."\n";
    }
}


$db_mysql->prepare("DROP TABLE IF EXISTS `table_commenti_guala_fp`"); $db_mysql->execute();
$db_mysql->prepare("RENAME TABLE `table_commenti_guala_fp_tmp` TO `table_commenti_guala_fp`"); $db_mysql->execute();

/* ============================
 *   STAIN - BISIO
 * ============================ */
echo "\n\nINIZIO PARTE PER STAIN - BISIO \n\n";

$db_sqlsrv_stein->prepare("
SELECT 
    t2.nome,
    t2.DescrMacchinaEstesa,
    stato.StatoOperazione,
    stato.nrordinesap,
    stato.codarticolo,
    stato.DescrizioneArticolo
FROM [BisioProgetti_STAINPlus].dbo.tb_cm_cfg_Macchine t2
OUTER APPLY (
    SELECT TOP 1 
        t1.StatoOperazione,
        t1.IDOrdine,
        t3.nrordinesap,
        t4.CodArticolo,
        t5.DescrizioneArticolo
    FROM [BisioProgetti_STAINPlus].dbo.tb_odp_OrdiniOperazioni t1
    JOIN [BisioProgetti_STAINPlus].dbo.tb_odp_Ordinitestata t3  ON t1.idordine=t3.idordine
    JOIN [BisioProgetti_STAINPlus].dbo.tb_odp_Ordiniprodotto t4 ON t1.idordine=t4.idordine
    JOIN [BisioProgetti_STAINPlus].dbo.tb_ana_AnagraficaArticoli t5 ON t4.codarticolo=t5.codarticolo
    WHERE t1.CentroDiLavoro = t2.DescrMacchinaEstesa
      AND t1.statooperazione IS NOT NULL
    ORDER BY t1.LastUpdateDate DESC
) AS stato
WHERE stato.StatoOperazione IS NOT NULL 
ORDER BY t2.nome;
");
$res_stain = $db_sqlsrv_stein->fetchAll();

db_aligner_log('Loaded STAIN/BISIO rows', [
    'source' => 'BisioProgetti_STAINPlus',
    'count'  => is_array($res_stain) ? count($res_stain) : null,
]);

$sql_stain = "
INSERT INTO `bisio_progetti_stain_tmp`
(`nome`,`DescrMacchinaEstesa`,`StatoOperazione`,`nrordinesap`,`codarticolo`,`DescrizioneArticolo`)
VALUES (:nome,:DescrMacchinaEstesa,:StatoOperazione,:nrordinesap,:codarticolo,:DescrizioneArticolo)
";
foreach ($res_stain as $r) {
    $db_mysql->prepare($sql_stain);
    $db_mysql->bind(":nome",                $r["nome"]);
    $db_mysql->bind(":DescrMacchinaEstesa", $r["DescrMacchinaEstesa"]);
    $db_mysql->bind(":StatoOperazione",     $r["StatoOperazione"]);
    $db_mysql->bind(":nrordinesap",         $r["nrordinesap"]);
    $db_mysql->bind(":codarticolo",         $r["codarticolo"]);
    $db_mysql->bind(":DescrizioneArticolo", $r["DescrizioneArticolo"]);
    if (db_aligner_execute($db_mysql, 'insert bisio_progetti_stain_tmp', [
        'nome'   => $r["nome"] ?? null,
        'ordine' => $r["nrordinesap"] ?? null,
    ])) {
        echo "Inserito in stain id: ".$db_mysql->lastInsertId()."\n";
    }
}
$db_mysql->prepare("DROP TABLE IF EXISTS `bisio_progetti_stain`"); $db_mysql->execute();
$db_mysql->prepare("RENAME TABLE `bisio_progetti_stain_tmp` TO `bisio_progetti_stain`"); $db_mysql->execute();

/* ============================
 *   LOTTI DI LAVORO - INCAS
 * ============================ */
echo "\n\nINIZIO PARTE PER LOTTI DI LAVORO - INCAS\n\n";

$db_sqlsrv_incas->prepare("SELECT * FROM view_OrdineLavoroLotto");
$res_incas = $db_sqlsrv_incas->fetchAll();

db_aligner_log('Loaded INCAS rows', [
    'source' => 'view_OrdineLavoroLotto',
    'count'  => is_array($res_incas) ? count($res_incas) : null,
]);

$sql_incas = "
INSERT INTO `ordini_lavoro_lotti_tmp`
(`Ordine`,`Lotto`,`ArticoloCodice`,`ArticoloDescrizione`,`ClienteCodice`,`ClienteDescrizione`,`QtaPrevOrdin`)
VALUES (:Ordine,:Lotto,:ArticoloCodice,:ArticoloDescrizione,:ClienteCodice,:ClienteDescrizione,:QtaPrevOrdin)
";
foreach ($res_incas as $r) {
    $db_mysql->prepare($sql_incas);
    $db_mysql->bind(":Ordine",              $r["Ordine"]);
    $db_mysql->bind(":Lotto",               $r["Lotto"] ?? "");
    $db_mysql->bind(":ArticoloCodice",      $r["ArticoloCodice"] ?? "");
    $db_mysql->bind(":ArticoloDescrizione", $r["ArticoloDescrizione"] ?? "");
    $db_mysql->bind(":ClienteCodice",       $r["ClienteCodice"] ?? "");
    $db_mysql->bind(":ClienteDescrizione",  $r["ClienteDescrizione"] ?? "");
    $db_mysql->bind(":QtaPrevOrdin",        $r["QtaPrevOrdin"] ?? "");
    if (db_aligner_execute($db_mysql, 'insert ordini_lavoro_lotti_tmp', [
        'Ordine' => $r["Ordine"] ?? null,
    ])) {
        echo "Inserito in incas id: ".$db_mysql->lastInsertId()."\n";
    }
}
$db_mysql->prepare("DROP TABLE IF EXISTS `ordini_lavoro_lotti`"); $db_mysql->execute();
$db_mysql->prepare("RENAME TABLE `ordini_lavoro_lotti_tmp` TO `ordini_lavoro_lotti`"); $db_mysql->execute();

/* ============================
 *   ORDINI PRODOTTI ROMANIA (WMS)
 * ============================ */
echo "\n\nINIZIO PARTE ORDINI PRODOTTI IN ROMANIA\n\n";

$db_sqlsrv_wms->prepare("
SELECT 
    codice_udc, sku, LEFT(producttype,2) AS productype,
    UT.Id_Unita_Misura AS UM, UD.Quantita_Pezzi AS Quantita, Stato_udc
FROM [AwmConfig].[vUdcTestata] UT
JOIN udc_dettaglio UD ON UT.Id_udc = UD.id_udc
WHERE partizione LIKE '1%' OR UT.Guala_Location IN ('INBOUND','SELE')
");
$rows_db_sqlsrv_wms = $db_sqlsrv_wms->fetchAll();

db_aligner_log('Loaded WMS rows', [
    'source' => 'AwmConfig.vUdcTestata + udc_dettaglio',
    'count'  => is_array($rows_db_sqlsrv_wms) ? count($rows_db_sqlsrv_wms) : null,
]);

$sql_qta = "
INSERT INTO `qta_guala_pro_rom_tmp`
(`codice_udc`,`sku`,`Quantita`,`Stato_udc`,`productype`,`UM`)
VALUES (:codice_udc,:sku,:Quantita,:Stato_udc,:productype,:UM)
";
foreach ($rows_db_sqlsrv_wms as $rows) {
    $db_mysql->prepare($sql_qta);
    $db_mysql->bind(":codice_udc", $rows["codice_udc"]);
    $db_mysql->bind(":sku",        $rows["sku"]);
    $db_mysql->bind(":Quantita",   $rows["Quantita"]);
    $db_mysql->bind(":Stato_udc",  $rows["Stato_udc"]);
    $db_mysql->bind(":productype", $rows["productype"]);
    $db_mysql->bind(":UM",         $rows["UM"]);
    if (db_aligner_execute($db_mysql, 'insert qta_guala_pro_rom_tmp', [
        'codice_udc' => $rows["codice_udc"] ?? null,
        'sku'        => $rows["sku"] ?? null,
    ])) {
        echo "Inserisco in qta_guala_pro_rom ".date("Y-m-d H:i:s")." - id: ".$db_mysql->lastInsertId()."\n";
    }
}
$db_mysql->prepare("DROP TABLE IF EXISTS `qta_guala_pro_rom`"); $db_mysql->execute();
$db_mysql->prepare("RENAME TABLE `qta_guala_pro_rom_tmp` TO `qta_guala_pro_rom`"); $db_mysql->execute();

/* ============================
 *   MACHINE CENTER
 * ============================ */
echo "\n\nINIZIO PARTE DI MACHINE CENTER\n\n";

$db_sqlsrv_data_wherehouse->prepare("SELECT No, GUAPosition, name, GUAMachineCenterType, Company FROM shir.stg_p.MachineCenter");
$rows_mc1 = $db_sqlsrv_data_wherehouse->fetchAll();

db_aligner_log('Loaded MachineCenter rows', [
    'source' => 'shir.stg_p.MachineCenter',
    'count'  => is_array($rows_mc1) ? count($rows_mc1) : null,
]);

$db_mysql->prepare("SET FOREIGN_KEY_CHECKS = 0"); $db_mysql->execute();
$db_mysql->prepare("DELETE FROM `machine_center`"); $db_mysql->execute();
$db_mysql->prepare("ALTER TABLE `machine_center` AUTO_INCREMENT = 1"); $db_mysql->execute();

$sql_mc = "
INSERT INTO `machine_center`
(`GUAPosition`,`name`,`no`,`GUAMachineCenterType`,`Company`,`GUA_schedule`)
VALUES (:GUAPosition,:name,:no,:GUAMachineCenterType,:Company,:GUA_schedule)
";
$db_mysql->prepare($sql_mc);
foreach ($rows_mc1 as $r) {
    $db_mysql->bind(":GUAPosition",         $r["GUAPosition"]);
    $db_mysql->bind(":name",                $r["name"]);
    $db_mysql->bind(":no",                  $r["No"]);
    $db_mysql->bind(":GUAMachineCenterType",$r["GUAMachineCenterType"]);
    $db_mysql->bind(":Company",             $r["Company"]);
    $db_mysql->bind(":GUA_schedule",        null);
    db_aligner_execute($db_mysql, 'insert machine_center (first source)', [
        'no'          => $r["No"] ?? null,
        'GUAPosition' => $r["GUAPosition"] ?? null,
    ]);
}
$db_sqlsrv_data_wherehouse->prepare("
SELECT No, GUAPosition, name, GUAMachineCenterType, Company, GUAUsageFrom
FROM [shir].[stg_p].[FP-MachineCenter]
");
$rows_mc2 = $db_sqlsrv_data_wherehouse->fetchAll();

db_aligner_log('Loaded FP-MachineCenter rows', [
    'source' => 'shir.stg_p.FP-MachineCenter',
    'count'  => is_array($rows_mc2) ? count($rows_mc2) : null,
]);

$db_mysql->prepare($sql_mc);
foreach ($rows_mc2 as $r) {
    $db_mysql->bind(":GUAPosition",         $r["GUAPosition"]);
    $db_mysql->bind(":name",                $r["name"]);
    $db_mysql->bind(":no",                  $r["No"]);
    $db_mysql->bind(":GUAMachineCenterType",$r["GUAMachineCenterType"]);
    $db_mysql->bind(":Company",             $r["Company"]);
    $db_mysql->bind(":GUA_schedule",        $r["GUAUsageFrom"]);
    db_aligner_execute($db_mysql, 'insert machine_center (FP source)', [
        'no'          => $r["No"] ?? null,
        'GUAPosition' => $r["GUAPosition"] ?? null,
    ]);
}
$db_mysql->prepare("SET FOREIGN_KEY_CHECKS = 1"); $db_mysql->execute();

/* ============================
 *   ORDINI MES (ROMANIA)
 * ============================ */
echo "\n\nINIZIO PARTE DI ORDINI MES\n\n";
$db_sqlsrv_50_65->prepare("SELECT * FROM orderfrommes");
$rows_mes = $db_sqlsrv_50_65->fetchAll();

db_aligner_log('Loaded MES orders', [
    'source' => 'orderfrommes',
    'count'  => is_array($rows_mes) ? count($rows_mes) : null,
]);

$sql_ofm = "INSERT INTO `orderfrommes_tmp`(`ordernane`,`messtatus`) VALUES (:ordernane,:messtatus)";
foreach ($rows_mes as $r) {
    $db_mysql->prepare($sql_ofm);
    $db_mysql->bind(":ordernane", $r["ordernane"]);
    $db_mysql->bind(":messtatus", $r["messtatus"]);
    if (db_aligner_execute($db_mysql, 'insert orderfrommes_tmp', [
        'ordernane' => $r["ordernane"] ?? null,
    ])) {
        echo "Inserisco in orderfrommes ".date("Y-m-d H:i:s")." - id: ".$db_mysql->lastInsertId()."\n";
    }
}
$db_mysql->prepare("DROP TABLE IF EXISTS `orderfrommes`"); $db_mysql->execute();
$db_mysql->prepare("RENAME TABLE `orderfrommes_tmp` TO `orderfrommes`"); $db_mysql->execute();

/* ============================
 *   BUSINESS CENTRAL (ROProduction)
 * ============================ */
echo "\n\nINIZIO PARTE DI ITEM DI PRODUZIONE\n\n";

$bc = new BusinessCentralApi(
    '107d0701-2f60-45c2-8784-da5926a223fd',
    'Dbp8Q~dMltxo7sZ_Xt82wxHhHaqo58cMWMjgnc6f',
    'https://api.businesscentral.dynamics.com/.default',
    'acb6aa33-e9bf-4632-8118-5e4ad89beea4'
);

$response_guaItemsInProduction = $bc->get('https://api.businesscentral.dynamics.com/v2.0/acb6aa33-e9bf-4632-8118-5e4ad89beea4/ROProduction/api/eos/guaa/v2.0/companies(a16f81ea-4219-ee11-9cc3-6045bdaccbcb)/guaItemsInProduction');
$response_guamesprodorders    = $bc->get('https://api.businesscentral.dynamics.com/v2.0/acb6aa33-e9bf-4632-8118-5e4ad89beea4/ROProduction/api/eos/guaa/v2.0/companies(a16f81ea-4219-ee11-9cc3-6045bdaccbcb)/guaMESProdOrders');
//$response_guamesprodorders    = $bc->get('https://api.businesscentral.dynamics.com/v2.0/acb6aa33-e9bf-4632-8118-5e4ad89beea4/ROProduction/api/eos/guaa/v2.0/companies(a16f81ea-4219-ee11-9cc3-6045bdaccbcb)/guaprodorders');

db_aligner_log('BC ROProduction responses', [
    'guaItemsInProduction_type' => gettype($response_guaItemsInProduction),
    'guaItemsInProduction_count' => is_array($response_guaItemsInProduction) ? count($response_guaItemsInProduction) : null,
    'guaMESProdOrders_type' => gettype($response_guamesprodorders),
    'guaMESProdOrders_count' => is_array($response_guamesprodorders) ? count($response_guamesprodorders) : null,
]);

$sql_items_tmp = "
INSERT INTO `table_gua_items_in_producion_tmp`
(`entryNo`,`componentNo`,`parentitemNo`,`compDescription`,`levelCode`,`qty`,`unitOfMeasure`,`prodorderno`,`mesOrderNo`,`commento`)
VALUES (:entryNo,:componentNo,:parentitemNo,:compDescription,:levelCode,:qty,:unitOfMeasure,:prodorderno,:mesOrderNo,'')
";
foreach ($response_guaItemsInProduction as $value) {
    if (!is_array($value)) continue;
    foreach ($value as $v) {
        $db_mysql->prepare($sql_items_tmp);
        $db_mysql->bind(":entryNo",       $v["entryNo"]);
        $db_mysql->bind(":componentNo",   $v["componentNo"]);
        $db_mysql->bind(":parentitemNo",  $v["parentitemNo"]);
        $db_mysql->bind(":compDescription",$v["compDescription"]);
        $db_mysql->bind(":levelCode",     $v["levelCode"]);
        $db_mysql->bind(":qty",           $v["qty"]);
        $db_mysql->bind(":unitOfMeasure", $v["unitOfMeasure"]);
        $db_mysql->bind(":prodorderno",   $v["prodorderno"]);
        $db_mysql->bind(":mesOrderNo",    $v["mesOrderNo"]);
        if (db_aligner_execute($db_mysql, 'insert table_gua_items_in_producion_tmp', [
            'entryNo'     => $v["entryNo"] ?? null,
            'componentNo' => $v["componentNo"] ?? null,
        ])) {
            echo "Inserisco in guaitemsinproduction ".date("Y-m-d H:i:s")." - id: ".$db_mysql->lastInsertId()."\n";
        }
    }
}
$db_mysql->prepare("DROP TABLE IF EXISTS `table_gua_items_in_producion`"); $db_mysql->execute();
$db_mysql->prepare("RENAME TABLE `table_gua_items_in_producion_tmp` TO `table_gua_items_in_producion`"); $db_mysql->execute();

/* ORDINI DI PRODUZIONE (ROProduction) */
echo "\n\nINIZIO PARTE DI ORDINE DI PRODUZIONE\n\n";

$db_mysql->prepare("SELECT `codici`, `oggetto` FROM `codici_oggetti`");
$x = $db_mysql->fetchALl(); 
      
foreach($x as $t){
    $cod_ogg[$t["codici"]] = $t["oggetto"];
}

$sql_orders_tmp = "
INSERT INTO `table_gua_mes_prod_orders_tmp`
(`mesOrderNo`,`mesStatus`,`itemNo`,`itemDescription`,`machineSatmp`,`machinePress`,`machinePressDesc`,`guaCustomerNo`,`guaCustomName`,`guaCustomerOrder`,`quantity`,`relSequence`,`family`,`quantita_prodotta`,`startingdatetime`,`no`)
VALUES (:mesOrderNo,:mesStatus,:itemNo,:itemDescription,:machineSatmp,:machinePress,:machinePressDesc,:guaCustomerNo,:guaCustomName,:guaCustomerOrder,:quantity,:relSequence,:family,:quantita_prodotta,:startingdatetime,:no)
";
foreach ($response_guamesprodorders as $value) {
    if (!is_array($value)) continue;
    foreach ($value as $v) {
        // family + produced qty
        if (strpos($v["mesOrderNo"], "AS") !== false) {
            $v["family"] = getFamily($v["itemNo"], $cod_ogg);
            $db_sqlsrv_50_65->prepare("
                SELECT ROUND(SUM(value),0) AS totale
                FROM bm20.IndicatorValueEmulation t1 WITH (NOLOCK)
                JOIN bm20.OperationExecution t3 ON t1.OperationExecutionId=t3.OperationExecutionId
                JOIN bm20.Indicator ind ON ind.IndicatorId=t1.Indicatorid
                WHERE t1.IndicatorTypeId=2 AND t1.value < 1000
                  AND ind.IndicatorKey LIKE 'Good%'
                  AND t3.OperationExecutionkey = '{$v["mesOrderNo"]}'
            ");
        } else {
            $v["family"] = "";
            $db_sqlsrv_50_65->prepare("
                SELECT SUM(good) AS totale
                FROM GoodQuantityMonitoring WITH (NOLOCK)
                WHERE ordername='{$v["mesOrderNo"]}'
            ");
        }
        $good  = $db_sqlsrv_50_65->fetch();
        $qtyOk = isset($good["totale"]) ? $good["totale"] : 0;

        $db_mysql->prepare($sql_orders_tmp);
        $db_mysql->bind(":mesOrderNo",      $v["mesOrderNo"]);
        $db_mysql->bind(":mesStatus",       $v["mesStatus"]);
        $db_mysql->bind(":itemNo",          $v["itemNo"]);
        $db_mysql->bind(":itemDescription", $v["itemDescription"]);
        // ATTENZIONE: colonna si chiama machineSatmp nello schema tmp (poi rinominerai a machineStamp se vuoi)
        $db_mysql->bind(":machineSatmp",    $v["machineStamp"] ?? "");
        $db_mysql->bind(":machinePress",    $v["machinePress"]);
        $db_mysql->bind(":machinePressDesc",$v["machinePressDesc"] ?? "");
        $db_mysql->bind(":guaCustomerNo",   $v["guaCustomerNo"]);
        $db_mysql->bind(":guaCustomName",   $v["guaCustomerName"] ?? "");
        $db_mysql->bind(":guaCustomerOrder",$v["guaCustomerOrder"]);
        $db_mysql->bind(":quantity",        $v["quantity"]);
        $db_mysql->bind(":relSequence",     $v["relSequence"]);
        $db_mysql->bind(":family",          $v["family"]);
        $db_mysql->bind(":quantita_prodotta",$qtyOk);
        $db_mysql->bind(":startingdatetime", str_replace("Z","", str_replace("T"," ",$v["startingdatetime"])));
        $db_mysql->bind(":no",              $v["no"] ?? null);
        if (db_aligner_execute($db_mysql, 'insert table_gua_mes_prod_orders_tmp', [
            'mesOrderNo' => $v["mesOrderNo"] ?? null,
            'itemNo'     => $v["itemNo"] ?? null,
        ])) {
            echo "Inserisco in guamesprodorders ".date("Y-m-d H:i:s")." - id: ".$db_mysql->lastInsertId()."\n";
        }
    }
}

/* ============================
 *   STAMPAGGIO → BOM EXPLOSION
 * ============================ */
echo "\n\nINIZIO PARTE DI STAMPAGGIO\n\n";

$sql_st = "SELECT `id`,`mesOrderNo`,`itemNo` FROM `table_gua_mes_prod_orders_tmp` WHERE `mesOrderNo` LIKE '%ST%'";
$db_mysql->prepare($sql_st);
if ($db_mysql->execute()) {
    $res = $db_mysql->fetchAll();
    $sql_bom_ins = "
    INSERT INTO `bom_explosion_tmp`
    (`xLevel`,`productionBOMNo`,`BOMReplSystem`,`BOMInvPostGr`,`No`,`ReplSystem`,`InvPostGr`,`UoM`,`QtyPer`,`PercScarti`,`PathString`,`PathLength`,`StartingDate`,`Company`)
    VALUES (:xLevel,:productionBOMNo,:BOMReplSystem,:BOMInvPostGr,:No,:ReplSystem,:InvPostGr,:UoM,:QtyPer,:PercScarti,:PathString,:PathLength,:StartingDate,:Company)
    ";

    foreach ($res as $r) {
        $strToSearch = "RO-".$r["itemNo"];
        $db_sqlsrv_data_wherehouse->prepare("
            SELECT xLevel, productionBOMNo, BOMReplSystem, BOMInvPostGr, [No], ReplSystem, InvPostGr, UoM, QtyPer, PercScarti, PathString, PathLength, StartingDate, Company
            FROM shir.dwh.BOMExplosion
            WHERE productionBOMNo = '{$strToSearch}' AND xlevel = 1
        ");
        $boms = $db_sqlsrv_data_wherehouse->fetchAll();

        foreach ($boms as $b) {
            $db_mysql->prepare($sql_bom_ins);
            $db_mysql->bind(":xLevel",         $b["xLevel"]);
            $db_mysql->bind(":productionBOMNo",$b["productionBOMNo"]);
            $db_mysql->bind(":BOMReplSystem",  $b["BOMReplSystem"]);
            $db_mysql->bind(":BOMInvPostGr",   $b["BOMInvPostGr"]);
            $db_mysql->bind(":No",             $b["No"]);
            $db_mysql->bind(":ReplSystem",     $b["ReplSystem"]);
            $db_mysql->bind(":InvPostGr",      $b["InvPostGr"]);
            $db_mysql->bind(":UoM",            $b["UoM"]);
            $db_mysql->bind(":QtyPer",         $b["QtyPer"]);
            $db_mysql->bind(":PercScarti",     $b["PercScarti"]);
            $db_mysql->bind(":PathString",     $b["PathString"]);
            $db_mysql->bind(":PathLength",     $b["PathLength"]);
            $db_mysql->bind(":StartingDate",   $b["StartingDate"]);
            $db_mysql->bind(":Company",        $b["Company"]);
            if (db_aligner_execute($db_mysql, 'insert bom_explosion_tmp', [
                'productionBOMNo' => $b["productionBOMNo"] ?? null,
                'No'              => $b["No"] ?? null,
            ])) {
                echo "Inserisco in bom_explosion ".date("Y-m-d H:i:s")." - id: ".$db_mysql->lastInsertId()."\n";
            }
        }
    }
}
$db_mysql->prepare("DROP TABLE IF EXISTS `table_gua_mes_prod_orders`"); $db_mysql->execute();
$db_mysql->prepare("RENAME TABLE `table_gua_mes_prod_orders_tmp` TO `table_gua_mes_prod_orders`"); $db_mysql->execute();
$db_mysql->prepare("DROP TABLE IF EXISTS `bom_explosion`"); $db_mysql->execute();
$db_mysql->prepare("RENAME TABLE `bom_explosion_tmp` TO `bom_explosion`"); $db_mysql->execute();

/* ============================
 *   PIOVAN
 * ============================ */
echo "\n\nINIZIO PARTE DI PIOVAN PER LOTTI E MATERIALE\n\n";

$db_mysql->prepare("SELECT `id_piovan`, `ingressi_usati`,`id_mes` FROM `tabella_appoggio_macchine` WHERE `id_piovan`!=''");
$presse = $db_mysql->fetchAll();

$piovan = [];
foreach ($presse as $p) {
    for ($i=1; $i <= $p["ingressi_usati"]; $i++) {
        $deviceFmid = $p['id_piovan'];

        $material = curlCall(soapXML($deviceFmid, 'I_'.$i.'.MATERIAL'));
        $materjob = curlCall(soapXML($deviceFmid, 'I_'.$i.'.MATERJOB'));

        $piovan[$p["id_piovan"]][$i] = [
            "id_mes"  => $p["id_piovan"],
            "material"=> $material,
            "lotto"   => $materjob,
        ];
    }
}
$sql_piovan = "
INSERT INTO `table_piovan_import_tmp`(`id_mes`,`material`,`lotto`,`created_at`,`updated_at`)
VALUES (:id_mes,:material,:lotto,CURRENT_TIMESTAMP(),CURRENT_TIMESTAMP())
";
foreach ($presse as $p) {
    foreach ($piovan[$p["id_piovan"]] as $pio) {
        $db_mysql->prepare($sql_piovan);
        $db_mysql->bind(":id_mes",  $pio["id_mes"]);
        $db_mysql->bind(":material",$pio["material"]);
        $db_mysql->bind(":lotto",   $pio["lotto"]);
        if (db_aligner_execute($db_mysql, 'insert table_piovan_import_tmp', [
            'id_mes'  => $pio["id_mes"] ?? null,
            'lotto'   => $pio["lotto"] ?? null,
        ])) {
            echo "Inserito record in table_import_piovan id ".$db_mysql->lastInsertId()."\n";
        }
    }
}
$db_mysql->prepare("DROP TABLE IF EXISTS `table_piovan_import`"); $db_mysql->execute();
$db_mysql->prepare("RENAME TABLE `table_piovan_import_tmp` TO `table_piovan_import`"); $db_mysql->execute();

/* ============================
 *   FP-FULLTEST
 * ============================ */
$bc = new BusinessCentralApi(
    '107d0701-2f60-45c2-8784-da5926a223fd',
    'Dbp8Q~dMltxo7sZ_Xt82wxHhHaqo58cMWMjgnc6f',
    'https://api.businesscentral.dynamics.com/.default',
    'acb6aa33-e9bf-4632-8118-5e4ad89beea4',
    'FPFullTest'
);
//https://api.businesscentral.dynamics.com/v2.0/acb6aa33-e9bf-4632-8118-5e4ad89beea4/Production/api/eos/guaa/v2.0/companies(9d717266-4eae-f011-bbd0-7ced8d422850)/guaprodorders
$response_guaItemsInProduction_fp = $bc->get('https://api.businesscentral.dynamics.com/v2.0/acb6aa33-e9bf-4632-8118-5e4ad89beea4/Production/api/eos/guaa/v2.0/companies(9d717266-4eae-f011-bbd0-7ced8d422850)/guaItemsInProduction');
$response_guamesprodorders_fp    = $bc->get('https://api.businesscentral.dynamics.com/v2.0/acb6aa33-e9bf-4632-8118-5e4ad89beea4/Production/api/eos/guaa/v2.0/companies(9d717266-4eae-f011-bbd0-7ced8d422850)/guaMESProdOrders');
//$response_guamesprodorders_fp    = $bc->get('https://api.businesscentral.dynamics.com/v2.0/acb6aa33-e9bf-4632-8118-5e4ad89beea4/Production/api/eos/guaa/v2.0/companies(9d717266-4eae-f011-bbd0-7ced8d422850)/guaprodorders');
$response_guaprodrouting         = $bc->get('https://api.businesscentral.dynamics.com/v2.0/acb6aa33-e9bf-4632-8118-5e4ad89beea4/Production/api/eos/guaa/v2.0/companies(9d717266-4eae-f011-bbd0-7ced8d422850)/guaprodrouting');

$sql_items_tmp_fp = $sql_items_tmp;
foreach ($response_guaItemsInProduction_fp as $value) {
    if (!is_array($value)) continue;
    foreach ($value as $v) {
        $db_mysql->prepare($sql_items_tmp_fp);
        $db_mysql->bind(":entryNo",       $v["entryNo"]);
        $db_mysql->bind(":componentNo",   $v["componentNo"]);
        $db_mysql->bind(":parentitemNo",  $v["parentitemNo"]);
        $db_mysql->bind(":compDescription",$v["compDescription"]);
        $db_mysql->bind(":levelCode",     $v["levelCode"]);
        $db_mysql->bind(":qty",           $v["qty"]);
        $db_mysql->bind(":unitOfMeasure", $v["unitOfMeasure"]);
        $db_mysql->bind(":prodorderno",   $v["prodorderno"]);
        $db_mysql->bind(":mesOrderNo",    $v["mesOrderNo"]);
        if (db_aligner_execute($db_mysql, 'insert table_gua_items_in_producion_tmp_fp', [
            'entryNo'     => $v["entryNo"] ?? null,
            'componentNo' => $v["componentNo"] ?? null,
        ])) {
            echo "Inserisco in guaitemsinproduction_fp ".date("Y-m-d H:i:s")." - id: ".$db_mysql->lastInsertId()."\n";
        }
    }
}

/* Ordini produzione FP */
$sql_orders_fp = "
INSERT INTO `table_gua_mes_prod_orders`
(`mesOrderNo`,`mesStatus`,`itemNo`,`itemDescription`,`machineSatmp`,`machinePress`,`machinePressDesc`,`guaCustomerNo`,`guaCustomName`,`guaCustomerOrder`,`quantity`,`relSequence`,`quantita_prodotta`,`startingdatetime`,`no`)
VALUES (:mesOrderNo,:mesStatus,:itemNo,:itemDescription,:machineSatmp,:machinePress,:machinePressDesc,:guaCustomerNo,:guaCustomName,:guaCustomerOrder,:quantity,:relSequence,:quantita_prodotta,:startingdatetime,:no)
";
foreach ($response_guamesprodorders_fp as $value) {
    if (!is_array($value)) continue;
    foreach ($value as $v) {
        $db_mysql->prepare($sql_orders_fp);
        $db_mysql->bind(":mesOrderNo",      $v["mesOrderNo"]);
        $db_mysql->bind(":mesStatus",       $v["mesStatus"]);
        $db_mysql->bind(":itemNo",          $v["itemNo"]);
        $db_mysql->bind(":itemDescription", $v["itemDescription"]);
        $db_mysql->bind(":machineSatmp",    $v["machineStamp"] ?? "");
        $db_mysql->bind(":machinePress",    $v["machinePress"]);
        $db_mysql->bind(":machinePressDesc",$v["machinePressDesc"] ?? "");
        $db_mysql->bind(":guaCustomerNo",   $v["guaCustomerNo"]);
        $db_mysql->bind(":guaCustomName",   $v["guaCustomerName"] ?? "");
        $db_mysql->bind(":guaCustomerOrder",$v["guaCustomerOrder"]);
        $db_mysql->bind(":quantity",        $v["quantity"]);
        $db_mysql->bind(":relSequence",     $v["relSequence"]);
        $db_mysql->bind(":quantita_prodotta", 0);
        $db_mysql->bind(":startingdatetime", str_replace("Z","", str_replace("T"," ",$v["startingdatetime"])));
        $db_mysql->bind(":no",              $v["no"] ?? null);
        if (db_aligner_execute($db_mysql, 'insert table_gua_mes_prod_orders_fp', [
            'mesOrderNo' => $v["mesOrderNo"] ?? null,
            'itemNo'     => $v["itemNo"] ?? null,
        ])) {
            echo "Inserisco in guamesprodorders_fp ".date("Y-m-d H:i:s")." - id: ".$db_mysql->lastInsertId()."\n";
        }
    }
}

/* guaprodrouting FP */
$sql_routing_tmp = "
INSERT INTO `table_guaprodrouting_tmp`
(`status`,`prodOrderNo`,`routingReferenceNo`,`routingNo`,`operationNo`,`type`,`no`,`created_at`,`updated_at`)
VALUES (:status,:prodOrderNo,:routingReferenceNo,:routingNo,:operationNo,:type,:no,CURRENT_TIMESTAMP(),CURRENT_TIMESTAMP())
";
foreach ($response_guaprodrouting["value"] as $gr) {
    if ($gr["no"] == "WMS" || $gr["no"] == "IMPA.LOG RIEMP") continue;
    $db_mysql->prepare($sql_routing_tmp);
    $db_mysql->bind(":status",            $gr["status"]);
    $db_mysql->bind(":prodOrderNo",       $gr["prodOrderNo"]);
    $db_mysql->bind(":routingReferenceNo",$gr["routingReferenceNo"]);
    $db_mysql->bind(":routingNo",         $gr["routingNo"]);
    $db_mysql->bind(":operationNo",       $gr["operationNo"]);
    $db_mysql->bind(":type",              $gr["type"]);
    $db_mysql->bind(":no",                $gr["no"]);
    if (db_aligner_execute($db_mysql, 'insert table_guaprodrouting_tmp', [
        'prodOrderNo'   => $gr["prodOrderNo"] ?? null,
        'operationNo'   => $gr["operationNo"] ?? null,
    ])) {
        echo "Inserisco in table_guaprodrouting_tmp ".date("Y-m-d H:i:s")." - id: ".$db_mysql->lastInsertId()."\n";
    }
}
$db_mysql->prepare("DROP TABLE IF EXISTS `table_guaprodrouting`"); $db_mysql->execute();
$db_mysql->prepare("RENAME TABLE `table_guaprodrouting_tmp` TO `table_guaprodrouting`"); $db_mysql->execute();

/* Update quantità buoni da STAIN */
$db_mysql->prepare("SELECT * FROM `table_guaprodrouting`");
$rr = $db_mysql->fetchAll();
foreach ($rr as $r) {
    $db_sqlsrv_stein->prepare("
        SELECT Q.TotaleQtaProdottaBuoni, Q.TotaleQtaProdottaScarti
        FROM [BisioProgetti_STAINplus].[dbo].[tb_odp_QuantitaProdotteOperazioni] AS Q
        INNER JOIN [BisioProgetti_STAINplus].[dbo].[tb_odp_OrdiniOperazioni] AS OO ON Q.IDOperazioneOdp = OO.IDOperazioneOdp
        INNER JOIN [BisioProgetti_STAINplus].[dbo].[tb_odp_OrdiniTestata] AS T ON OO.IDOrdine = T.IDOrdine
        WHERE T.NrOrdineSAP = '".$r["prodOrderNo"]."'
          AND OO.OperazioneCiclo = '".$r["operationNo"]."'
    ");
    $result = $db_sqlsrv_stein->fetch();
    if (!empty($result)) {
        $db_mysql->prepare("UPDATE `table_guaprodrouting` SET `TotaleQtaProdottaBuoni`=:q WHERE `id`=:id");
        $db_mysql->bind(":q",  $result["TotaleQtaProdottaBuoni"]);
        $db_mysql->bind(":id", $r["id"]);
        db_aligner_execute($db_mysql, 'update table_guaprodrouting TotaleQtaProdottaBuoni', [
            'id'          => $r["id"] ?? null,
            'prodOrderNo' => $r["prodOrderNo"] ?? null,
            'operationNo' => $r["operationNo"] ?? null,
        ]);
    }
}

$db_mysql->prepare("SELECT * FROM `table_guaprodrouting`");
foreach ($rr as $r) {
    $db_sqlsrv_stein->prepare("select StatoOperazione
        from [BisioProgetti_STAINplus].[dbo].[tb_odp_OrdiniTestata] T
        inner join [BisioProgetti_STAINplus].[dbo].[tb_odp_OrdiniOperazioni] O on T.IdOrdine = O.IdOrdine
        where T.NrOrdineSAP ='".$r["prodOrderNo"]."'
        and O.OperazioneCiclo ='".$r["operationNo"]."' 
    ");
    $result = $db_sqlsrv_stein->fetch();

    if (!empty($result)) {
        $db_mysql->prepare("UPDATE `table_guaprodrouting` SET `StatoOperazione`=:q WHERE `id`=:id");
        $db_mysql->bind(":q",  $result["StatoOperazione"]);
        $db_mysql->bind(":id", $r["id"]);
        db_aligner_execute($db_mysql, 'update table_guaprodrouting StatoOperazione', [
            'id'          => $r["id"] ?? null,
            'prodOrderNo' => $r["prodOrderNo"] ?? null,
            'operationNo' => $r["operationNo"] ?? null,
        ]);
    }
}

/* ============================
 *   FUNZIONI DI SUPPORTO
 * ============================ */
function getFamily($itemNo, $family){
    if (substr($itemNo,0,3)==='601' || substr($itemNo,0,3)==='600') return $family["400100"];
    if (substr($itemNo,0,3)==='602') return $family["400300"];
    if (substr($itemNo,0,1)==='3' || substr($itemNo,0,1)==='8') return $family["100100"];
    if (substr($itemNo,0,1)==='S'){
        if (substr($itemNo,0,2)==='S1') return $family["100100"];
        elseif (substr($itemNo,0,3)==='S3U') return $family["300270"];
        else return $family["300900"];
    }
    return "";
}

function soapXML($deviceFmid, $varName){
    $df = htmlspecialchars($deviceFmid, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    $vn = htmlspecialchars($varName,    ENT_XML1 | ENT_COMPAT, 'UTF-8');
    return <<<XML
<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:web="http://webservice.galileo.piovan.com/">
  <soapenv:Header/>
  <soapenv:Body>
    <web:readStringVariable>
      <deviceFmId>{$df}</deviceFmId>
      <varName>{$vn}</varName>
    </web:readStringVariable>
  </soapenv:Body>
</soapenv:Envelope>
XML;
}

function curlCall($xml){
    $db = new Database("mysql", "127.0.0.1", "guala_app", "guala_usr", "D@787f7nd"); // se serve usare *_test cambiare qui
    $db->prepare("SELECT endpoint, chiamata_soap FROM enpoint_piovan");
    $res = $db->fetch();

    $endpoint   = $res["endpoint"];
    $soapAction = $res["chiamata_soap"];

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $xml,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: text/xml; charset=utf-8',
            'SOAPAction: "'.$soapAction.'"',
            'Content-Length: '.strlen($xml),
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 60,
    ]);
    $body   = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err    = curl_error($ch);
    curl_close($ch);
    if ($err) die("cURL error: $err (HTTP $status)\n");

    $xmlObj = @simplexml_load_string($body);
    if ($xmlObj === false) { echo "HTTP $status\n$body\n"; exit; }

    $fault = $xmlObj->xpath('/*[local-name()="Envelope"]/*[local-name()="Body"]/*[local-name()="Fault"]');
    if ($fault && isset($fault[0])) {
        $faultcode   = (string)($fault[0]->faultcode ?? '');
        $faultstring = (string)($fault[0]->faultstring ?? '');
        echo "SOAP Fault [$faultcode]: $faultstring\n";
        return null;
    }
    $nodes = $xmlObj->xpath('/*[local-name()="Envelope"]/*[local-name()="Body"]/*[local-name()="readStringVariableResponse"]/*[local-name()="return"]');
    return ($nodes && isset($nodes[0])) ? (string)$nodes[0] : null;
}

/* ============================
 *   CREAZIONE TABELLE TMP
 * ============================ */
function creaTabelle($db){
    $ddl = [
        "CREATE TABLE IF NOT EXISTS `bisio_progetti_stain_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `nome` VARCHAR(255) NOT NULL,
            `DescrMacchinaEstesa` VARCHAR(255) NOT NULL,
            `StatoOperazione` VARCHAR(255) NOT NULL,
            `nrordinesap` VARCHAR(255) NOT NULL,
            `codarticolo` VARCHAR(255) NOT NULL,
            `DescrizioneArticolo` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `bom_explosion_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `xLevel` INT NOT NULL,
            `productionBOMNo` VARCHAR(255) NOT NULL,
            `BOMReplSystem` VARCHAR(255) NOT NULL,
            `BOMInvPostGr` VARCHAR(255) NOT NULL,
            `No` VARCHAR(255) NOT NULL,
            `ReplSystem` VARCHAR(255) NOT NULL,
            `InvPostGr` VARCHAR(255) NOT NULL,
            `UoM` VARCHAR(255) NOT NULL,
            `QtyPer` DOUBLE NOT NULL,
            `PercScarti` DOUBLE NOT NULL,
            `PathString` VARCHAR(255) NOT NULL,
            `PathLength` INT NOT NULL,
            `StartingDate` DATE DEFAULT NULL,
            `Company` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `machine_center_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `GUAPosition` VARCHAR(255) DEFAULT NULL,
            `name` VARCHAR(255) DEFAULT NULL,
            `no` VARCHAR(255) DEFAULT NULL,
            `id_piovan` VARCHAR(255) DEFAULT NULL,
            `azienda` ENUM('','Guala Dispensing','Bisio','Messico','Romania') NOT NULL DEFAULT '',
            `GUAMachineCenterType` VARCHAR(255) DEFAULT NULL,
            `Company` VARCHAR(255) DEFAULT NULL,
            `GUA_schedule` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `orderfrommes_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ordernane` VARCHAR(255) NOT NULL,
            `messtatus` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `ordini_lavoro_lotti_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `Ordine` VARCHAR(255) NOT NULL,
            `Lotto` VARCHAR(255) NOT NULL,
            `ArticoloCodice` VARCHAR(255) NOT NULL,
            `ArticoloDescrizione` VARCHAR(255) NOT NULL,
            `ClienteCodice` VARCHAR(255) NOT NULL,
            `ClienteDescrizione` VARCHAR(255) NOT NULL,
            `QtaPrevOrdin` VARCHAR(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `qta_guala_pro_rom_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `codice_udc` VARCHAR(255) NOT NULL,
            `sku` VARCHAR(255) NOT NULL,
            `Quantita` DOUBLE NOT NULL,
            `Stato_udc` VARCHAR(255) NOT NULL,
            `productype` VARCHAR(255) DEFAULT NULL,
            `UM` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `table_guaprodrouting_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `status` VARCHAR(255) DEFAULT NULL,
            `prodOrderNo` VARCHAR(255) DEFAULT NULL,
            `routingReferenceNo` VARCHAR(255) DEFAULT NULL,
            `routingNo` VARCHAR(255) DEFAULT NULL,
            `operationNo` VARCHAR(255) DEFAULT NULL,
            `type` VARCHAR(255) DEFAULT NULL,
            `no` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            `TotaleQtaProdottaBuoni` INT UNSIGNED NOT NULL DEFAULT 0,
            `StatoOperazione` INT(5) NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `table_gua_items_in_producion_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `entryNo` INT NOT NULL,
            `componentNo` VARCHAR(255) NOT NULL,
            `parentitemNo` VARCHAR(255) NOT NULL,
            `compDescription` TEXT NOT NULL,
            `levelCode` INT NOT NULL,
            `qty` INT NOT NULL,
            `unitOfMeasure` VARCHAR(255) NOT NULL,
            `prodorderno` VARCHAR(255) NOT NULL,
            `mesOrderNo` VARCHAR(255) NOT NULL,
            `commento` TEXT DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `table_gua_mes_prod_orders_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `startingdatetime` DATETIME DEFAULT NULL,
            `mesOrderNo` VARCHAR(255) NOT NULL,
            `mesStatus` VARCHAR(255) NOT NULL,
            `itemNo` VARCHAR(255) NOT NULL,
            `itemDescription` TEXT NOT NULL,
            `machineSatmp` VARCHAR(255) NOT NULL,      -- rimane così per compatibilità inserimenti
            `machinePress` VARCHAR(255) NOT NULL,
            `machinePressDesc` VARCHAR(255) NOT NULL,
            `guaCustomerNo` VARCHAR(255) NOT NULL,     -- uniformato il nome
            `guaCustomName` VARCHAR(255) NOT NULL,
            `guaCustomerOrder` VARCHAR(255) NOT NULL,
            `quantity` INT NOT NULL,
            `relSequence` INT NOT NULL,
            `quantita_prodotta` VARCHAR(255) DEFAULT NULL,
            `family` VARCHAR(255) DEFAULT NULL,
            `commento` VARCHAR(255) DEFAULT NULL,
            `no` VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `table_piovan_import_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_mes` VARCHAR(255) DEFAULT NULL,
            `material` VARCHAR(255) DEFAULT NULL,
            `lotto` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NULL DEFAULT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

        "CREATE TABLE IF NOT EXISTS `table_commenti_guala_fp_tmp` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `TableName` varchar(50) NOT NULL,
            `No` varchar(255) NOT NULL,
            `LineNo` varchar(255) NOT NULL,
            `Date` date NOT NULL,
            `Code` varchar(255) DEFAULT NULL,
            `Comment` varchar(255) NOT NULL,
            `Company` varchar(255) NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($ddl as $sql) {
        $db->prepare($sql);
        $db->execute();
    }
}

/* ============================ */
