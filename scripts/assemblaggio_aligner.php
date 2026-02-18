<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'Database.class.php';
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'api' . DIRECTORY_SEPARATOR . 'BusinessCentralApi.php';

$logDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'scripts';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

$assemblaggioLogFile = $logDir . DIRECTORY_SEPARATOR . 'assemblaggio_aligner_' . date('Y-m-d') . '.log';

function assemblaggio_log(string $message, array $context = []): void
{
    global $assemblaggioLogFile;

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

    error_log($line . PHP_EOL, 3, $assemblaggioLogFile);
}

assemblaggio_log('assemblaggio_aligner start');

// $db_mysql = new Database("mysql", "127.0.0.1", "guala_app", "guala_usr", "D@787f7nd"); // ONLINE
$db_mysql = new Database("mysql", "127.0.0.1", "guala_app_v1", "root", "", 3307); //TEST
$db_sqlsrv_50_65 = new Database("sqlsrv", "192.168.50.65", "mdw", "b4web", "%eAnZiUh");

$sql = "SELECT `id`, `mesOrderNo`, `itemNo` FROM `table_gua_mes_prod_orders` WHERE `mesOrderNo` LIKE '%AS%'";
$db_mysql->prepare($sql);
$res = $db_mysql->fetchAll();

assemblaggio_log('Loaded assembly orders', [
    'table' => 'table_gua_mes_prod_orders',
    'pattern' => '%AS%',
    'count' => is_array($res) ? count($res) : null,
]);

foreach($res as $r){
    if (str_contains($r["mesOrderNo"], 'AS')) {
        $db_sqlsrv_50_65 ->prepare("SELECT round(sum(value),0) from  bm20.IndicatorValueEmulation t1 with (nolock) join bm20.OperationExecution t3 on t1.OperationExecutionId=t3.OperationExecutionId inner join bm20.Indicator ind on ind.IndicatorId=T1.Indicatorid where  T1.IndicatorTypeId = 2 and t1.value<1000 and ind.IndicatorKey like 'Good%' and t3.OperationExecutionkey = '{$r["mesOrderNo"]}'");
        $good = $db_sqlsrv_50_65->fetch();

        assemblaggio_log('Fetched MES good quantity', [
            'mesOrderNo' => $r["mesOrderNo"] ?? null,
            'result'     => $good,
        ]);

        foreach($good as $g){
            $db_mysql->prepare("UPDATE `table_gua_mes_prod_orders` SET `quantita_prodotta`=:quantita_prodotta WHERE `id`=:id");
            $db_mysql->bind(":id", $r["id"]);
            $db_mysql->bind(":quantita_prodotta", $g);

            $ok = $db_mysql->execute();
            assemblaggio_log($ok ? 'Updated quantita_prodotta OK' : 'Updated quantita_prodotta ERROR', [
                'id'         => $r["id"] ?? null,
                'mesOrderNo' => $r["mesOrderNo"] ?? null,
                'value'      => $g,
            ]);

            if($ok){
                echo "bomexplosion ".date("Y-m-d H:m:s")." - Aggiornato id : ".$r["id"]."\n";
            }
        }
    }
}
