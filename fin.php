<?php

$balance = 0;
$list = array();

if (count($argv) >= 2) {

    switch ($argv[1]) {

        case '--add':
            if (count($argv) >= 3) {
                $balance += $argv[2];
                
                setFile("Added",$balance);
                echo "Added ".$balance ." to your balance" ;
                echo "\n";
            } else {
                echo "Amount not provided for --add\n";
            }
            break;

        case '--sub':
            if (count($argv) >= 3) {
                $balance = $argv[2];
                setFile("Used",$balance);
                echo "Removed " .$balance. " from your balance \n";
            } else {
                echo "Amount not provided for --sub\n";
            }
            break;
        case '--show-balance':
            total();
            break;

        case '--show-history':
                getFile();
            break;

        default:
            echo "Invalid argument\n";
            break;
    }

} else {
    echo "Not enough arguments provided\n";
}


function setFile($argue,$balance){
    $fp = fopen('file.csv', 'a');
    fputcsv($fp, array(date("Y-m-d H:i:s"),$argue,$balance,"€"));
    fclose($fp);
}

function getFile(){
    $row = 1;
    if (($handle = fopen("file.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
            $num = count($data);
            $row++;
            for ($c=0; $c < $num; $c++) {
                echo $data[$c] . " ";
            }
            echo "\n";
        }
        fclose($handle);
    }
}

    function total() {
        $total = 0;
        $row = 1;
    
        if (($handle = fopen("file.csv", "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $num = count($data);
                $row++;
                for ($c = 0; $c < $num; $c++) {
                    if ($c > 0 && $data[$c - 1] == "Added" && $c == 2) {
                        $total += (int)$data[$c];
                    }
                    if($c > 0 && $data[$c - 1] == "Used" && $c == 2){
                        $total = $total - (int)$data[$c];

                    }
                }
            }
    
            fclose($handle);
    
            // Display the total after processing all rows
            echo "Υour balance is ". $total."€\n";
        }
    }

