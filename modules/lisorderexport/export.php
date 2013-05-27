<?php
/**
 * @copyright Copyright (C) 2013 land in sicht AG All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2
 */

require_once 'kernel/classes/ezorder.php';
require_once 'lib/ezutils/classes/ezsys.php';

$cryptName = sha1("orderExport")."csv";
$filenameLastExport = eZSys::rootDir()."/var/lisorderexport.last";
$lastDate = getLastExportDate($filenameLastExport);

$addhtml = '';
if ($lastDate !== false){
    $addhtml .= 'letzter Export: '.date("d.m.Y", $lastDate)."<br/>";
} else {
	$addhtml .= 'noch kein Export vorhanden';
}

if (isset($_GET['export']))
{
    $orders = eZOrder::active();
    
    $csv =array();
    $csvHeader = array("OrderNummer","benutzerID","Vorname","Nachname","Email","strasse1","strasse2","plz","Ort","Staat","Land","Kommentar","Erstellungsdatum","Gesamtpreis","Produktnummer(ObjectID)","Anzahl");
    $csv[] = implode(";",$csvHeader);
    
    if (count($orders))
    {
        foreach ($orders as $order)
        {
            if ($lastDate == false || $lastDate <= strtotime($order->attribute("created")))
            {
	        	$line = array();
	            $line[] = $order->attribute('order_nr');
	            $line[] = $order->attribute('user_id');
	            $customer = $order->accountInformation();
	            $line[] = utf8_decode($customer['first_name']);
	            $line[] = utf8_decode($customer['last_name']);
	            $line[] = utf8_decode($customer['email']);
	            $line[] = utf8_decode($customer['street1']);
	            $line[] = utf8_decode($customer['street2']);
	            $line[] = utf8_decode($customer['zip']);
	            $line[] = utf8_decode($customer['place']);
	            $line[] = utf8_decode($customer['state']);
	            $line[] = utf8_decode($customer['country']);
	            $line[] = utf8_decode($customer['comment']);
	            
	            $line[] = $order->attribute("created");
	            $line[] = $order->totalIncVAT();
	            
	            $productItems = $order->productItems();
	            if (count($productItems))
	            {
	                foreach ($productItems as $product)
	                {
	                    $line[] = $product['item_object']->attribute("contentobject_id");
	                    $line[] = $product['item_count'];
	                }
	            }        
	            $csv[] = implode(";",$line);
            }
        }
    }
    $data =  implode("\n",$csv);
        $filename = eZSys::rootDir()."/var/$cryptName";
    $f = fopen ($filename, "w");
    fwrite($f, $data);      
    
    //lastmodified date speichern.
    $lf = fopen($filenameLastExport, "w");
    fwrite($lf,time());
    
    $addhtml = '<p>export.csv ('. date("d.m.Y",filemtime($filename)).')>> <a href="?download=1" target="_blank">[Datei downloaden]</a></p>';
}

if (isset($_GET['download']) && $_GET['download'] == 1)
{
    $data = file_get_contents(eZSys::rootDir()."/var/".$cryptName);
    header("Content-Type: application/x-octetstream");
    header("content-length: ".strlen($data));
    header('content-disposition: attachment; filename="export.csv"') ;   
    echo $data;
    eZExecution::cleanExit();
}

$html = '<h1>lisOrderExport</h1><form method="GET">
<input type="submit" name="export" value="Exportieren" />
</form>';

$html .= $addhtml;

$Result = array();
$Result['path'] = array( array( 'text' => ezpI18n::tr( 'kernel/shop', 'OrderExport' ),
                                'url' => false ) );

$Result['content'] = $html;

function getLastExportDate($filename)
{
	//checken ob file existiert
	if (!is_file($filename))
	{
		return false;
	} else {
		$time = file_get_contents($filename);
	}
	return $time;
}