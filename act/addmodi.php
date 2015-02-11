<?php
# add and modify

$formid = "gtbl_add_form";

$hiddenfields = "";

$colsPerRow = 3;
if($_REQUEST['otbl'] != ''){
    $colsPerRow = 2;
}

$out .= "<fieldset style=\"border-color:#5f8ac5;border: 1px solid #5f8ac5;\"><legend><h4>增修记录</h4></legend><form id=\"".$formid."\" name=\"".$formid."\" method=\"post\" action=\"".$url."&act=list-addform\"  ".$gtbl->getJsActionTbl()."><table align=\"center\" width=\"98%\" cellspacing=\"0\" cellpadding=\"0\" border=\"0px\">";
$out .= "<tr><td width=\"11%\">&nbsp;</td>
            <td width=\"22%\">&nbsp;</td>
            <!-- <td width=\"2%\">&nbsp;</td> -->
            <td width=\"11%\">&nbsp;</td>
            <td width=\"22%\">&nbsp;</td>
            <td width=\"11%\">&nbsp;</td>
            <td width=\"22%\">&nbsp;</td>
            </tr>";
			
//print_r($_REQUEST);
			
$hmorig = array(); 
if(startsWith($act, "modify")){
    if($hasid){
        $gtbl->setId($id);
        $hmorig = $gtbl->getBy("*", null);
        $gtbl->setId('');
    }else{
        $fieldargv = "";
        for($hmi=$min_idx; $hmi<=$max_idx; $hmi++){
            $field = $gtbl->getField($hmi);
            if($field == null | $field == '' 
                    || $field == 'id'){
                continue;
            }
            if(array_key_exists($field, $_REQUEST)){
                $gtbl->set($field, $_REQUEST[$field]);
                $fieldargv[] = $field."=?";
            }
        } 
        $hmorig = $gtbl->getBy("*", implode(" and ", $fieldargv));
    }
}
else{
    foreach($_REQUEST as $k=>$v){
        if(startsWith($k,"pnsk")){
            $hmorig[substr($k,4)] = $v;
        }
		else if(startsWith($k, 'parent')){ # Attention! parentid
			$k2 = $v;
			$hmorig[$k2] = $_REQUEST[$k2];	
		}
    }
    for($hmi=$min_idx; $hmi<=$max_idx; $hmi++){
        $field = $gtbl->getField($hmi);
        if($field == null | $field == '' 
                || $field == 'id'){
            continue;
        }
        $fielddf = $gtbl->getDefaultValue($field);
        if($fielddf != ''){
            $tmparr = explode(":", $fielddf);
            if($tmparr[0] == 'request'){ # see xml/hss_info_attachfiletbl.xml
                $hmorig[$field] = $_REQUEST[$tmparr[1]];
            }else{
                $hmorig[$field] = $tmparr[0]; # see xml/hss_tuanduitbl.xml
            }
        }
    } 

}

if($hmorig[0]){
    $hmorig = $hmorig[1][0]; 
}

$closedtr = 1;
$columni = 0;
for($hmi=$min_idx; $hmi<=$max_idx;$hmi++){
    $field = $gtbl->getField($hmi);
    $fieldinputtype = $gtbl->getInputType($field);
    
	if($field == null || $field == ''){
		continue;
	}
	
	if($fieldinputtype == 'hidden'){
        $hiddenfields .= "<input type=\"hidden\" name=\"".$field."\" id=\"".$field."\" value=\"".$hmorig[$field]."\"/>\n";
    }
    if($gtbl->filterHiddenField($field, $opfield,$timefield)){
        continue;
    }
    if($closedtr == 1){
        $out .= "<tr height=\"30px\" valign=\"middle\"  onmouseover=\"javascript:this.style.backgroundColor='".$hlcolor."';\" onmouseout=\"javascript:this.style.backgroundColor='';\">";
        $closedtr = 0;
    }

    if($field == 'password'){
        $hmorig[$field] = '';
    }

    if(!$user->canWrite($field)){
        $out .= "<--NOWRITE--><input type=\"hidden\" id=\"".$field."\" name=\"".$field."\" value=\"".$hmorig[$field]."\" />";
        continue;

    }else if($fieldinputtype == 'select'){
        $out .= "<td nowrap>".$gtbl->getCHN($field).":&nbsp;</td><td>".$gtbl->getSelectOption($field, $hmorig[$field],'',0,$gtbl->getSelectMultiple($field))." <br/> ".$gtbl->getMemo($field)." <input type=\"hidden\" id=\"".$field."_select_orig\" name=\"".$field."_select_orig\" value=\"".$hmorig[$field]."\" />"; 
        $out .= "</td>";

    }else if($fieldinputtype == 'textarea'){

        $hmorig[$field] = str_replace("<br/>", "\n", $hmorig[$field]); 

        if($gtbl->getSingleRow($field) == '1'){
            if($tmpmemo == ''){
                #$tmpmemo = '支持标准HTML: 加粗&lt;b&gt;, 超链&lt;a&gt;, 插入图片如&lt;img src="admin/upld/102913431493_-201210.jpg" /&gt;(图片路径在 综合数据--附件数据 里获取)';
            }
            $out .= "</tr>\n<tr><td style=\"vertical-align:top\">".$gtbl->getCHN($field).":</td><td colspan=\"".($form_cols)."\">
                <div id='".$field."_myeditordiv' style='width:680px;height:450px;display:none'></div>
                <div id='".$field."_mytextdiv' style='width:680px;height:450px;display:block'>
                <textarea id=\"".$field."\" name=\"".$field."\" rows=\"5\" cols=\"65\"  class=\"search\" onclick=\"javascript:openEditor('".$rtvdir."/extra/htmleditor.php?field=".$field."', '".$field."'); parent.switchArea('".$field."_myeditordiv','on'); parent.switchArea('".$field."_mytextdiv','off');\">".$hmorig[$field]."</textarea> </div><br/> ".$tmpmemo." </td></tr><tr>";
            $out .= '';

        }else{
            $out .= "<td>".$gtbl->getCHN($field).":</td><td><textarea id=\"".$field."\" name=\"".$field."\" rows=\"5\" cols=\"30\"  class=\"search\">".$hmorig[$field]."</textarea> <br/> ".$gtbl->getMemo($field)." </td>";
        }

    }else if($fieldinputtype == 'file'){
        if($columni % 2 != 0){
            $out .= "</tr><tr>";
        }
        $isimg = isImg($hmorig[$field]);
        $out .= "<td nowrap>".$gtbl->getCHN($field).":</td><td><input type=\"file\" id=\"".$field."\" name=\"".$field."\" size=\"20\" class=\"noneinput wideinput\" ".$gtbl->getJsAction($field)." /> <input type=\"hidden\" name=\"".$field."_orig\" value=\"".$hmorig[$field]."\" /> <br/> ".($hmorig[$field]==''?'':$hmorig[$field])." ".$gtbl->getMemo($field)."</td>";
        $out .="<td> ".($isimg==1?"<img src=\"".$hmorig[$field]."\" alt=\"-x-\" width=\"118px\" /><br/>".$hmorig[$field]:"")." <script>document.getElementById('".$formid."').enctype='multipart/form-data';</script>  </td></tr><tr>";

    }else if($gtbl->getExtraInput($field, $hmorig) != ''){

            $out .= "</tr><tr><td>".$gtbl->getCHN($field).":</td><td colspan=\"".$form_cols."\"><span id=\"span_".$act."_".$field."\"><input id=\"".$field."\" name=\"".$field."\" class=\"search\" value=\"".$hmorig[$field]."\" /></span> <span id=\"span_".$act."_".$field."_v\"><a href=\"javascript:void(0);\" onclick=\"javascript:doActionEx('".$gtbl->getExtraInput($field, $hmorig)."&act=".$act."&field=".$field."&oldv=".$hmorig[$field]."&otbl=".$tbl."&oid=".$id."','extrainput_".$act."_".$field."_inside');document.getElementById('extrainput_".$act."_".$field."').style.display='block'; document.getElementById('extendicon_${id}_$field').src='./img/minus.gif';\"><img border=\"0\" id=\"extendicon_${id}_$field\" src=\"img/plus.gif\" width=\"15\" height=\"15\"></a></span> <div id=\"extrainput_".$act."_".$field."\" class=\"extrainput\"> ";
            
            $out .= "<table width=\"100%\" ><tr><td width=\"100%\" style=\"text-align:right\"> <b> <a href=\"javascript:void(0);\" onclick=\"javascript:if('".$id."' != ''){ var linkobj=document.getElementById('".$field."'); if(linkobj != null){ document.getElementById('".$field."').value=document.getElementById('linktblframe').contentWindow.sendLinkInfo('','r','".$field."');} } document.getElementById('extrainput_".$act."_".$field."').style.display='none';  document.getElementById('extendicon_${id}_$field').src='./img/plus.gif';\">X</a> </b> &nbsp; </td></tr><tr><td> <div id=\"extrainput_".$act."_".$field."_inside\"></div></td></tr></table> </div>";
            //$out .="  </div>   <br/>".$gtbl->getMemo($field)." </td>  </tr><tr>";
            if($field != "operatelog" && $id != ''){
                $out .= "<script type=\"text/javascript\"> parent.doActionEx('".$gtbl->getExtraInput($field, $hmorig)."&act=".$act."&otbl=".$tbl."&field=".$field."&oldv=".$hmorig[$field]."&oid=".$id."','extrainput_".$act."_".$field."_inside');document.getElementById('extrainput_".$act."_".$field."').style.display='block'; </script>";
            }
            $out .= "   <br/>".$gtbl->getMemo($field)."</td></tr><tr>";

    }else{

		if($gtbl->getSingleRow($field) == '1'){
            $out .= "</tr>\n<tr><td style=\"vertical-align:top\" ".$gtbl->getCss($field).">".$gtbl->getCHN($field).":</td><td colspan=\"".($form_cols)."\"><input type=\"text\" id=\"".$field."\" name=\"".$field."\" class=\"\" style=\"width:600px\" value=\"".$hmorig[$field]."\" ".$gtbl->getJsAction($field).$gtbl->getAccept($field)." ".$gtbl->getReadOnly($field)." /> <br/>   ".$gtbl->getMemo($field)." </td></tr><tr>";
		}
		else{
        	$out .= "<td nowrap ".$gtbl->getCss($field).">".$gtbl->getCHN($field).": </td><td><input type=\"text\" id=\"".$field."\" name=\"".$field."\" class=\"noneinput wideinput\" value=\"".$hmorig[$field]."\" ".$gtbl->getJsAction($field).$gtbl->getAccept($field)." ".$gtbl->getReadOnly($field)." /> <br/> ".$gtbl->getMemo($field)."</td>";
		}

    }

    $out .= $gtbl->getDelayJsAction($field);       

    $columni++;

    if($columni % $colsPerRow == 0){
        $out .= "</tr>";
        $closedtr = 1;
    }

    //print "i:[".$columni."]\n"; 

    if(++$rows % 6 == 0 && $closedtr == 1){
        $out .= "<tr height=\"28px\"><td style=\"border-top: 1px dotted #cccccc; vertical-align:middle;\" colspan=\"".$form_cols."\">  </td> </tr>";
    }

}

$out .= "<tr height=\"10px\"><td style=\"border-top: 1px dotted #cccccc; vertical-align:middle;\" colspan=\"".$form_cols."\">  </td></tr>";
$out .= "<tr><td colspan=\"".$form_cols."\" align=\"center\"><input type=\"submit\" name=\"addsub\" id=\"addsub\" value=\"递交\" onclick=\"javascript:doActionEx(this.form.name,'actarea');\" /> \n";
$out .= "<input type=\"hidden\" id=\"id\" name=\"id\" value=\"".$id."\"/>\n ".$hiddenfields."\n";
$out .= "<input type=\"button\" name=\"cancelbtn\" value=\"取消\" onclick=\"javascript:switchArea('contentarea_outer','off');\" /> </td></tr>";
$out .= "</table> </form>  </fieldset>  <br/>";


?>
