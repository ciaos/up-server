<?php

// Define a folder to upload
$targetFolder = '/uploads'; // Relative to the root

function array_sort($arr) {
    $karr = array();
    for($i=0;$i<count($arr);$i++){
        $karr[$arr[$i][0]] = $arr[$i][1];
    }
    ksort($karr);
    $arr = array();
    foreach($karr as $k => $v){
        $arr[] = array($k,$v);
    }
    return $arr;
}
/**
* Update range of uploaded segments of file
*/
function updateRange($rangefile, $start, $end, $totalsize){
    if(file_exists($rangefile)){
        $data = json_decode(file_get_contents($rangefile));
    }
    
    if(is_null($data) or is_null($data->range) or count($data->range) == 0) {
        $data->range = array(array($start, $end));//create a record if not exists
        $data->totalsize = $totalsize;
    } else {
        $range = array_sort($data->range);
        $c = count($range);
        for($i=0;$i<$c;$i++){
            if($range[$i][0] > $end + 1) {// insert before $i
                $range[] = array($start,$end);
                break;
            } else if($range[$i][1] + 1< $start){
                if($i == $c - 1){// insert end of all
                    $range[] = array($start,$end);
                    break;
                } else {
                    continue;
                }
            } else{
                $range[$i][0] = $range[$i][0] < $start?$range[$i][0]:$start;
                for($j = $i;$j < $c;$j ++){
                    if($j == $c - 1){
                        $range[$i][1] = $range[$j][1] > $end?$range[$j][1]:$end;
                        if($i < $j){//combine segments
                            unset($range[$j]);
                        }
                        break;
                    } else {
                        if($range[$j+1][0] > $end + 1){
                            $range[$i][1] = $range[$j][1] > $end?$range[$j][1]:$end;
                            if($j > $i){//combine segments
                                unset($range[$j]);
                            }
                            break;
                        }else{
                            if($j > $i){//combine segments
                                unset($range[$j]);
                            } else {
                                continue;
                            }
                        }
                    }
                }
                break;
            }
        }
        $data->range = $range;
    }
    //save status
    file_put_contents($rangefile,json_encode($data));
}
 
/**
* Lookup Next Range for upload
*/
function lookupRange($rangefile){
    if(file_exists($rangefile)){
        $data = json_decode(file_get_contents($rangefile));
    }else{
        return array("Not Found","","");
    }
    if(is_null($data) or is_null($data->range) or count($data->range) == 0) {
        return array("Not Found","","");
    } else {
        $range = array_sort($data->range);
        $c = count($range);
    if($c == 1 && $range[0][0] == 0 && $range[0][1]+1 == $data->totalsize){
        return array("Created","","");
    }
    $start = 0;
    $end = $data->totalsize-1;
        for($i=0;$i<$c;$i++){
        if($range[$i][0]<=$start){
            $start = $range[$i][1];
            if($i+1<$c){
                $end = $range[$i+1][0]-1;
            }
        } else{
            $end = $range[$i][0]-1;
        }
        break;
    }
    return array($start,$end,$data->totalsize);
    }
}

if(!is_null(@$_SERVER['REQUEST_METHOD']) && @$_SERVER['REQUEST_METHOD'] == "HEAD"){
    if(is_null($_SERVER["HTTP_FILENAME"])){
        header("HTTP/1.0 400 Bad Request");
    exit(-1);
    }
    $filename = $_SERVER["HTTP_FILENAME"];
    list($start,$end,$size) = lookupRange(rtrim($_SERVER['DOCUMENT_ROOT'] . $targetFolder,'/')."/".$filename.".range");

    if("Not Found" === $start){
           header("HTTP/1.0 404 File Not Found");
    } else if("Created" === $start){
           header("HTTP/1.0 201 Created");
    } else{ 
       header("HTTP/1.0 202 Accepted");
       header("Range: bytes=$start-$end/$size");
    }
    exit(-1);
}

if(!is_null(@$_SERVER['REQUEST_METHOD']) && @$_SERVER['REQUEST_METHOD'] != "POST"){
    header("HTTP/1.0 400 Bad Request");
    exit(-1);
}

$response = "";
if (!empty($_FILES) && is_array($_FILES)) {
    foreach($_FILES as $k => $v){
        $tempFile = $v['tmp_name'];
        $targetPath = $_SERVER['DOCUMENT_ROOT'] . $targetFolder;
        $targetFile = rtrim($targetPath,'/') . '/' . $v['name'];
        
        if(is_null($_SERVER["HTTP_RANGE"])){
            move_uploaded_file($tempFile,iconv("UTF-8","gb2312", $targetFile));
            $size = $v['size'];
            updateRange($targetFile.".range",0,$size-1,$size);
            header("HTTP/1.0 201 Created");
            $response = "Upload OK";
        } else {
            $pos = $_SERVER["HTTP_RANGE"];
            $range = ltrim($pos, "bytes=");
            list($start,$end,$size) = split('[-/]',$range);
            $pos = $start;
            $r_fp = fopen($tempFile, 'r');
            if(file_exists($targetFile)){
                $w_fp = fopen($targetFile, 'r+');
            }else{
                $w_fp = fopen($targetFile, 'w+');
            }
            while($data = fread($r_fp, 4092)){
                flock($w_fp,LOCK_EX);
                fseek($w_fp, $pos, SEEK_SET);
                fwrite($w_fp, $data);
                flock($w_fp,LOCK_UN);
                $pos += strlen($data);
            }
    
            fclose($r_fp);
            fclose($w_fp);
            updateRange($targetFile.".range",$start,$end,$size);
            list($start,$end,$size) = lookupRange($targetFile.".range");
    
            if("Not Found" === $start){
                header("HTTP/1.0 500 Internal Server Error");
                echo "Upload Failed";
                exit(-1);
            } else if("Created" === $start){
                header("HTTP/1.0 201 Created");
                $response = "Upload OK";
            } else {
                header("HTTP/1.0 202 Accepted");
                header("Range: bytes=$start-$end/$size");
                $response = "Upload Continue";
            }
        }
    }
    echo $response;
}
?>
