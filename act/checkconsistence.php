<?php

# embedded in jdo.php?act=list-dodelete

$hmdelete = $gtbl->getBy("*", implode(" and ", $fieldargv));
if($hmdelete[0]){
    $hmdelete = $hmdelete[1][0];
}
#print __FILE__.":hmdelete: ";
#print_r($hmdelete);

for($hmi=$min_idx; $hmi<=$max_idx; $hmi++){
    $field = $gtbl->getField($hmi);
    if($field == null | $field == '' 
            || $field == 'id'){
        continue;
    }

    $inputtype = $gtbl->getInputType($field);
    if($inputtype == 'file'){
        if($hmdelete[$field] != ''){
           unlink($appdir."/".$hmdelete[$field]); 
           $out .= __FILE__.": file:[".$appdir."/".$hmdelete[$field]."] has been deleted.";
        }
    }

} 

?>
