<?php
# 
# Mon Jul 28 15:38:57 CST 2014

$isoput = false;
require("./comm/header.inc.php");

# read table config, IMPORTANT
require("./comm/tblconf.php");

# check tbl action
require("./act/tblcheck.php");

$jdo = mkUrl($jdo, $_REQUEST, $gtbl);
$list_disp_limit = 38;

# act handler
if(startsWith($act,'add') || startsWith($act, "modify")){
    include("./act/addmodi.php");
}
else if(startsWith($act, "list")){
    if(startsWith($act, "list-addform")){
       include("./act/doaddmodi.php"); 
    }
    if(startsWith($act, "list-dodelete")){
		$origId = $id;
       include("./act/dodelete.php"); 
	   $jdo = str_replace('&'.$gtbl->getMyId().'=', '&xoid=', $jdo); 
		if($fmt != ''){ 
		   $data['respobj']['resultobj'] = array('resultcode'=>'0',  # 0 stands for success
		   		'resulttrace'=>'1511242124', 'targetid'=>$origId); # unique trace id
		   }
    }
	if(isset($data['respobj']['resultobj'])){
		# json, xml, Tue Nov 24 21:31:23 CST 2015
	}
	else{
		# html
    $navi = new PageNavi();
    $orderfield = $navi->getOrder();
    if($orderfield == ''){
        $orderfield = $gtbl->getOrderBy();
        if($orderfield == '' && $hasid){
            $orderfield = $gtbl->getMyId();
        } 
        $orderfield = $orderfield=='' ? '1 ' : $orderfield;
        if(strpos($orderfield, ' ') !== false){
            $tmpArr = explode(' ', $orderfield);
            $orderfield = $tmpArr[0];
            $navi->set('isasc', ($tmpArr[1]=='desc' ? 1 : 0));
        }
        else{
            # @todo?
        }   
        if($orderfield == $gtbl->getMyId()){
            $navi->set('isasc', 1);
        }
    }   
    else{   
        #       
    }
	#debug("orderfield:$orderfield hasid:$hasid myid:".$gtbl->getMyId());
    $gtbl->set("pagesize", $navi->get('pnps'));
    $gtbl->set("pagenum", $navi->get('pnpn'));
    $gtbl->set("orderby", $orderfield." ".($navi->getAsc()==1?"desc":"asc"));
    if($_REQUEST['pntc'] == '' || $_REQUEST['pntc'] == '0' || $navi->get('neednewpntc') == 1){
		$pagenum = $gtbl->get('pagenum');
		$gtbl->set('pagenum', 1);
        $hm = $gtbl->getBy("count(*) as totalcount", $navi->getCondition($gtbl, $user));
		#print "get pntc:";
		#print_r($hm);
        if($hm[0]){
            $hm = $hm[1][0];
            $navi->set('totalcount',$hm['totalcount']);
        }
		$gtbl->set('pagenum', $pagenum);
    }
    # list start
    $listid = array();
    $out .= "<table width=\"98%\" cellspacing=\"0\" cellpadding=\"0\" style=\"\" class=\"mainlist\">"
            ."<tr height=\"35px\"><td colspan=\"".($max_disp_cols+2)."\">";
    $out .= "<button name=\"selectallbtn\" type=\"button\" onclick=\"checkAll();\" value=\"\">全选</button> &nbsp;";
    $out .= "<button name=\"reversebtn\" type=\"button\" onclick=\"uncheckAll();\" value=\"\">反选</button>";
    $out .= "&nbsp; ".$navi->getNavi()." &nbsp;&nbsp;&nbsp;<button name='deepsearch' onclick=\"javascript:doActionEx('"
    	.$jdo."&act=deepsearch', 'contentarea');\" title=\"深度复合查询\">深  搜</button>"
    	."&nbsp;&nbsp;<button name='deepsearch' onclick=\"javascript:doActionEx('"
    	.$jdo."&act=pivot&pntc=".$navi->get('totalcount')."', 'contentarea');\" title=\"数据透视分析\">透  视</button>";
	if(true){
        $iswatch = Wht::get($_REQUEST, 'pnwatch');
        $watchInterval = $_CONFIG['watch_interval']; # seconds
        $watchAct = "doActionEx(\'".$jdo."&act=list&pnwatch=1\', \'actarea\')";
        $out .= "&nbsp;&nbsp;&nbsp;<button name='watchbtn' title='觀察模式' onclick=\"javascript:parent.doActionEx('"
                .$jdo."&act=list&pnwatch=".($iswatch==1?'0':'1')."', 'actarea');\">"
                .($iswatch==1?'觀察中...':'觀  察')."</button>";
        if($iswatch == 1){
            # parent.doActionEx('".$jdo."&act=list&pnwatch=1', 'actarea');
            $out .= "<script type=\"text/javascript\">parent.registerAct({'status':'onload', "
                    ."'action':escape('$watchAct'),"
                    ." 'delaytime':$watchInterval});</script>";
        }
        else if(isset($_REQUEST['pnwatch'])){
            $out .= "<script type=\"text/javascript\">"
                    ."var timerId=parent.window.setTimeout(function(){}, 1);timerId--;"
                    ."while(timerId--){console.log('timerId:'+timerId);parent.window.clearTimeout(timerId);}</script>";
        }
    }
    $out .= "&nbsp;<div style=\"float:right;\"><button name=\"searchor\" onclick=\"javascript:searchBy('"
    	.$jdo."&act=list&pnsm=or');\" title=\"满足其中一个条件即可\">或搜</button>&nbsp;&nbsp;&nbsp;"
    	."<button name=\"searchand\" onclick=\"javascript:searchBy('"
    	.$jdo."&act=list&pnsm=and');\" title=\"同时满足所有检索条件\">并搜</button>&nbsp;&nbsp;</div>"
    	."</td></tr>";
    ## list-sort start
    $out .= "<tr style=\"font-weight:bold;\" height=\"28px\">";
    if($hasid){
        $out .= "<td valign=\"middle\" nowrap>&nbsp;<a href=\"javascript:void(0);\" title=\"Sort by ID\" onclick=\"javascript:doAction('"
                .str_replace("&pnob","&xxpnob",$jdo)."&act=list&pnobid=".($navi->getAsc($gtbl->getMyId())==0?1:0)."'); \">序/编号</a></td>";
    }
	else{
        $out .= "<td valign=\"middle\">Nbr.</td>";
    }
    for($hmi=$dispi=$min_idx; $hmi<=$max_idx; $hmi++){
        $field = $gtbl->getField($hmi);
        if($gtbl->filterHiddenField($field,$opfield,$timefield) 
                || $gtbl->getListView($field) == 0 || $dispi > $max_disp_cols){
            continue;
        }
        $dispi++;
        $out .= "<td valign=\"middle\"><a href=\"javascript:void(0);\" title=\"Sort by ".$gtbl->getCHN($field)
            ."\" onclick=\"doAction('".str_replace("&pnob","&xxpnob",$jdo)."&act=list&pnob".$field."="
            .($navi->getAsc($field)==0?1:0)."')\">".$gtbl->getCHN($field)."&#8639;&#8642;</a></td>";
    }
    $out .= "</tr>";
    ## list-sort end
    ## list-search start
    $untouched = '~~~';
    $out .= "<tr style=\"font-weight:bold;\">";
    $out .= "<td valign=\"middle\"><input type=\"hidden\" name=\"fieldlist\" id=\"fieldlist\" value=\""
            .implode(",",array_keys($hmfield))."\" /> <input type=\"hidden\" name=\"fieldlisttype\" id=\"fieldlisttype\" value=\""
            .$gtbl->getFieldType()."\"/>";
    $out .= "<div style=\"display:none\" id=\"pnsk_id_op_div\"><select style=\"width:60px\" name=\"oppnsk_id\" id=\"oppnsk_id\">"
            .$gtbl->getLogicOp($gtbl->getMyId())."</select></div>";
    $out .= "<input value=\"".($id=='' ? $untouched : $id)."\" style=\"width:50px;"
            .($id==''?"color:white;":"")."\" id=\"pnsk_id\" name=\"pnsk_id\" ";
    $out .= "style=\"COLOR:#777;\" title=\"Search By ...\" onclick=\"this.select();this.style.color='black';\" "
            ."onfocus=\"document.getElementById('pnsk_id_op_div').style.display='block';\" "
            ."onkeydown=\"javascript:if(event.keyCode == 13){ searchBy('".$jdo."&act=list&pnsm=and');}\" /></td>";
    for($hmi=$dispi=$min_idx; $hmi<=$max_idx; $hmi++){
        $field = $gtbl->getField($hmi);
        if($gtbl->filterHiddenField($field,$opfield,$timefield) 
                || $gtbl->getListView($field) == 0 || $dispi > $max_disp_cols ){
            continue;
        }
        $dispi++;
        $out .= "<td>"; 
        if($gtbl->getInputType($field) == 'select'){
			if($gtbl->getInput2Select($field)==1){
				if(!isset($tmpfieldv)){ $tmpfieldv = $untouched; }
				$out .= "<div style=\"display:none\" id=\"pnsk_{$field}_op_div\"><select name=\"oppnsk_{$field}\" id=\"oppnsk_{$field}\" "
				        ."style=\"width:60px\">".$gtbl->getLogicOp($field)."</select></div>";
				$out .= "<input value=\"".$_REQUEST["input2sele_$field"]."\" id=\"input2sele_".$field."\" name=\"input2sele_".$field
				    ."\" style=\"COLOR:#777;width:50px;".($tmpfieldv==$untouched?"color:white;":"")."\" title=\"Search By ...\" "
				    ."onclick=\"this.select();this.style.color='black';\" onfocus=\"document.getElementById('pnsk_".$field
				    ."_op_div').style.display='block';document.getElementById('pnsk_".$field
				    ."_sele_div').style.display='block';\" onkeydown=\"javascript:if(event.keyCode == 13){ searchBy('"
				    .$jdo."&act=list&pnsm=and');}\"";
				$out .= " onkeyup=\"javascript: input2Search(this,'$field');\" />";
				$out .= "<div style=\"display:none;position:absolute;background:#fff;border:#777 solid 1px;margin:-1px 0 0;padding: 5px;"
				        ."font-size:12px; overflow:auto;z-index:38;\" id=\"pnsk_{$field}_sele_div\"></div>";
				# load select options
				$out .= $gtbl->getSelectOption($field, (isset($_REQUEST['pnsk'.$field])?$_REQUEST['pnsk'.$field]:null),"pnsk_",0,0);
				$out .= "<script type=\"text/javascript\">var hidesele_$field=document.getElementById('pnsk_"
				        .$field."'); hidesele_$field.style.display='none';</script>";
			}
			else{
				$out .= $gtbl->getSelectOption($field, (isset($_REQUEST['pnsk'.$field])?$_REQUEST['pnsk'.$field]:null),"pnsk_",0,0);
			}
        }else{
            $tmpfieldv = $_REQUEST['pnsk'.$field];
            if(!isset($tmpfieldv)){ $tmpfieldv = $untouched; }
            $out .= "<div style=\"display:none\" id=\"pnsk_{$field}_op_div\"><select name=\"oppnsk_{$field}\" id=\"oppnsk_{$field}\" "
                    ."style=\"width:60px\">".$gtbl->getLogicOp($field)."</select></div>";
            $out .= "<input value=\"".$tmpfieldv."\" id=\"pnsk_".$field."\" name=\"pnsk_".$field."\" style=\"COLOR:#777;width:50px;"
                    .($tmpfieldv==$untouched?"color:white;":"")."\" title=\"Search By ...\" "
                    ."onclick=\"this.select();this.style.color='black';\" onfocus=\"document.getElementById('pnsk_"
                    .$field."_op_div').style.display='block';\" onkeydown=\"javascript:if(event.keyCode == 13){ searchBy('"
                    .$jdo."&act=list&pnsm=and');}\" ".$gtbl->getJsAction($field)."/>";
        }
        $out .= "</td>";
    }
    $out .= "<!-- <td><a href=\"javascript:searchBy('".$jdo."&act=list&pnsm=or');\" title=\"满足其中一个条件即可\">或搜</a><br/>"
            ."<a href=\"javascript:searchBy('".$jdo."&act=list&pnsm=and');\" title=\"同时满足所有检索条件\">与搜</a></td> --> </tr>";
    ## list-search end
    ## main data loop
    $hm = $gtbl->getBy("*", $navi->getCondition($gtbl, $user));
    if($hm[0]){
        $hm = $hm[1]; $i = 0; $fstfields = ''; $hmsum = array();
        # record start
        foreach($hm as $k=>$rec){
			$gtbl->set($gtbl->resultset, $rec);
           $bgcolor = "#DCDEDE";
           if($i%2 == 0){
                $bgcolor = "";
           }
           $out .= "<tr height=\"35px\" valign=\"middle\" bgcolor=\""
                   .$bgcolor."\" id=\"list_tr_".(++$i)."\">"; # rec[$gtbl->getMyId()]
           if($hasid){
               $id = $rec[$gtbl->getMyId()]; $listid[0] = $id; # id/myid as the very first
               $out .= "<td nowrap> <input name=\"checkboxid\" type=\"checkbox\" value=\"".$id
                ."\"> &nbsp; <a onmouseover=\"javascript:showActList('".$i."', 1, '"
                .str_replace("&".$gtbl->getMyId()."=","&oid=", $jdo)."&".$gtbl->getMyId()."=".$id
                ."');\" onmouseout=\"javascript:showActList('".$i."', 0, '".str_replace("&".$gtbl->getMyId()."=","&oid=", $jdo)
                ."&".$gtbl->getMyId()."=".$id."');\" href='javascript:void(0);' onclick=\"javascript:doActionEx('".$jdo."&act=view&"
                .$gtbl->getMyId()."=".$id."','contentarea');;\" title=\"详细信息\">"
                .($i + (intval($navi->get('pnpn'))-1) * (intval($navi->get('pnps'))))." / ".$id
                ." &#x25BE;</a> <div id=\"divActList_$i\" style=\"display:none; position: absolute; margin-left:50px; "
                ."margin-top:-11px; z-index:99; background-color:silver;\">actlist-$i</div> </td>";
           }
           else{
               $url_uni_extra = $gtbl->getUniquePara($rec);
               $out .= "<td nowrap> <input name=\"checkboxid\" type=\"checkbox\" value=\"".$id
                ."\"> &nbsp; <a onmouseover=\"javascript:showActList('".$i."', 1, '".$jdo."&".$url_uni_extra
                ."');\" onmouseout=\"javascript:showActList('".$i."', 0, '".$jdo."&".$url_uni_extra
                ."');\" href='javascript:void(0);' onclick=\"javascript:doActionEx('".$jdo."&act=view&".$url_uni_extra
                ."','contentarea');;\" title=\"详细信息\">".($i + (intval($navi->get('pnpn'))-1) * (intval($navi->get('pnps'))))
                ." / ".$id." &#x25BE;</a> <div id=\"divActList_$i\" style=\"display:none; position: absolute; margin-left:50px; "
                ."margin-top:-11px; z-index:99; background-color:silver;\">actlist-$i</div> </td>";
           }
           for($hmi=$dispi=$min_idx; $hmi<=$max_idx; $hmi++){
               $field = $gtbl->getField($hmi);
               if($gtbl->filterHiddenField($field,$opfield,$timefield) 
                       || $gtbl->getListView($field) == 0 || $dispi > $max_disp_cols){
                   continue;
               }
               $dispi++;
               $inputtype = $gtbl->getInputType($field);

               if(!$user->canRead($field,'','', $_REQUEST[$gtbl->getMyId()],$id)){
                    $out .= "<td> * </td>";
               }
               else if($inputtype == 'select'){
                   $fhref = $gtbl->getHref($field, $rec);
                   if(count($fhref) == 0){
                       $fhref = "";
                   }
                   else{
                        if(strpos($fhref[0],"javascript") !== false){
                    	   $fhref_tmp = "<a href=\"javascript:void(0);\" onclick=\"".$fhref[0]."\" title=\""
                    	           .$fhref[1]."\" target=\"".$fhref[2]."\">";
                    	}
                    	else{
                    	   $fhref_tmp = "<a href=\"".$fhref[0]."\" title=\"".$fhref[1]."\" target=\"".$fhref[2]."\">";
                    	}			
                    	$fhref = $fhref_tmp;
                    }
                    $fhref_end = ''; if($fhref != ''){ $href_end = "</a>"; }
				   $myinputtype = $inputtype;
                   $readonly = $gtbl->getReadOnly($field);
                   if($gtbl->getJsAction($field) != '' || $gtbl->getSelectMultiple($field)==1){ $readonly = 'readonly'; }
				   if($gtbl->getInput2Select($field)==1){ $myinputtype = "input2select";  }
                   $tmpv_orig = $tmpv=$gtbl->getSelectOption($field, $rec[$field],'',1, $gtbl->getSelectMultiple($field));
                   $tmpv = shortenStr($tmpv, $list_disp_limit);
                   $out .= "<td ondblclick=\"javascript:switchEditable('othercont_div_".$id."_".$field."','".$field."','"
                           .$myinputtype."','".$rec[$field]."','".$jdo."&act=updatefield&field=".$field."&".$gtbl->getMyId()."=".$id."','"
                           .$readonly."');\" ".$gtbl->getCss($field, $rec[$field])." title=\"".$tmpv_orig."\"><div id=\"othercont_div_"
                           .$id."_".$field."\">".$fhref.$tmpv.$fhref_end."</div>";
                   $out .= "</td>";

                   $listid[$dispi] = $tmpv_orig;
               }
               else if($inputtype == 'file'){
					   $fhref = $gtbl->getHref($field, $rec);
					   if(strpos($rec[$field], "$shortDirName/") !== false){ 
					       $rec[$field] = str_replace("$shortDirName/", "", $rec[$field]); 
					   }
					   $origValueF = $rec[$field];
					   $srcprefix = $gtbl->getSrcPrefix();
					   if($origValueF != '' && $srcprefix != '' && !startsWith($origValueF, 'http')){
						   $rec[$field] = $srcprefix.'/'.$rec[$field];
						}
					   if(count($fhref) == 0){
						   $fieldT = '';
						   if($rec[$field] != ''){ 
								$fieldT = Base62x::decode(substr($rec[$field], strpos($rec[$field],'_')+1)).' -- '; 
							}
							$fhref = "<a href=\"javascript:void(0);\" onclick=\"window.open('".$rec[$field]."');\" title=\""
							        .$fieldT."点击大图或者下载 ".$rec[$field]."\">"; 	   
						}
						else{
							if(strpos($fhref[0],"javascript") !== false){
							   $fhref_tmp = "<a href=\"javascript:void(0);\" onclick=\"".$fhref[0]."\" title=\""
							           .$fhref[1]."\" target=\"".$fhref[2]."\">";
							}
							else{
							   $fhref_tmp = "<a href=\"".$fhref[0]."\" title=\"".$fhref[1]."\" target=\"".$fhref[2]."\">";
							}			
							$fhref = $fhref_tmp;
						}
                   $out .= "<td> ".$fhref;
				   $isimg = isImg($rec[$field]);
                   if($isimg){
                       $out .= "<img src=\"".$rec[$field]."\" style=\"max-width:99%; max-height:99%\" onload=\"javascript: "
                               ."var baseSize=118; if(this.width > baseSize){ this.style.width = baseSize+'px';}"
                               ."else if(this.height > baseSize){ this.style.height=baseSize+'px'; } \" id=\"img_"
                               .$rec[$field]."\" />";

                   }else{
                        $out .= "".shortenStr($rec[$field], $list_disp_limit)."";
                   }
                   $out .= "</a>"; # <br/>".$rec[$field]."</td>";
               }
               else if($gtbl->getExtraInput($field) != ''){

                   $out .= "<td ondblclick=\"javascript:show('span_disp_".$field."','".$gtbl->getExtraInput($field)."&act=".$act
                    ."&field=".$field."&otbl=".$tbl."&oldv=".$rec[$field]."&oid=".$id."',true,true);\" title=\"".addslashes($rec[$field])
                    ."\">".shortenStr($rec[$field], $list_disp_limit)." <span id=\"span_disp_".$field."\"> </span> <div id=\"extrainput_"
                    .$act."_".$field."\" class=\"extrainput\">  </div> </td>";

                   $listid[$dispi] = $rec[$field];
               }
               else{
                   $fv = str_replace('<', '&lt;', $rec[$field]);
                   $fv_short = $fv_orig = $fv;
                   $fv_short = shortenStr($fv, $list_disp_limit);
                   $fhref = $gtbl->getHref($field, $rec);
                   if(count($fhref)>0){
                       if(strpos($fhref[0],"javascript") !== false){
                           $fv_short = "<a href=\"javascript:void(0);\" onclick=\"".$fhref[0]."\" title=\""
                                   .$fhref[1]."\" target=\"".$fhref[2]."\">".$fv_short."</a>";
                       }else{
                           $fv_short = "<a href=\"".$fhref[0]."\" title=\"".$fhref[1]."\" target=\""
                                   .$fhref[2]."\">".$fv_short."</a>";
                       }
                   }
				   $readonly = $gtbl->getReadOnly($field);
                   if($inputtype == 'textarea'){ $readonly = true; }
                   $out .= "<td ondblclick=\"javascript:switchEditable('othercont_div_".$id."_"
                           .$field."','".$field."','".$inputtype."','','".$jdo."&act=updatefield&field="
                           .$field."&".$gtbl->getMyId()."=".$id."','".$readonly."');\" title=\""
                           .str_replace("\"","", $fv_orig)."\" ".$gtbl->getCss($field)." "
                            .$gtbl->getJsAction($field, $rec)."><div id=\"othercont_div_".$id."_".$field."\">"
                            .$fv_short."</div></td>";
                   $out .= $gtbl->getDelayJsAction($field);
				   $listid[$dispi] = $rec[$field];
               }
               # hmsum, sum or count each item
               if($gtbl->getInputType($field) != 'select' 
                       && $gtbl->isNumeric($hmfield[$field])
                       && strpos($hmfield[$field], "date") === false){

                   $hmsum[$field] += $rec[$field]; 

               }else{
                   if($rec[$field] != ''){
                        if(!isset($hmsumuniq[$field][$rec[$field]])){
                            $hmsum[$field]++;
                            $hmsumuniq[$field][$rec[$field]] = 1;
                        }
                   }else{
                        if(!isset($hmsum[$field])){
                            $hmsum[$field] = 1;
                        }
                   }
               }
           }
           $out .= "</tr>\n"; 
           if(!isset($_REQUEST['linkfieldcopy'])){
               $listi = $hasid ? 0 : 1;
               $fstfields .= $listid[1].",";
           }
           else{ $fstfields .= $listid[$_REQUEST['linkfieldcopy']].","; }
        } 
        # record end
        # sum bgn
        $out .= "<tr height=\"35px\" valign=\"middle\" bgcolor=\"".$bgcolor."\"><td>&nbsp;</td>";
        foreach($hmsum as $k=>$v){
            if($gtbl->filterHiddenField($k,$opfield,$timefield)){ # || $gtbl->getInputType($k) == 'select'
                $out .= "<td> - </td>";
            }else{ 
                $tmpsum = $hmsum[$k];
                if($gtbl->getStat($k) == 'average'){
                    $tmpsum = sprintf("%.2f",$tmpsum/$i);
                }
                $out .= "<td>".(is_int($tmpsum) ? number_format($tmpsum) : $tmpsum)."</td>";
            }
        }
        $out .= "<td></td></tr>\n";
        # sum end
        $out .= "<tr height=\"35px\"><td style=\"border-bottom:0px\" colspan=\"".($max_disp_cols+2)."\">";
        $out .= "<button name=\"selectallbtn\" type=\"button\" onclick=\"checkAll();\" value=\"\">全选</button> &nbsp;";
        $out .= "<button name=\"reversebtn\" type=\"button\" onclick=\"uncheckAll();\" value=\"\">反选</button>";
        $out .= $navi->getNavi();
        //$out .= " <script> parent.sendLinkInfo('".implode(",",$listid)."','w',''); </script> ";
        $out .= " <script type=\"text/javascript\"> parent.sendLinkInfo('".urlencode(substr($fstfields, 0, strlen($fstfields)-1))."','w','".$_REQUEST['field']."'); </script> ";
        $out .= "</td></tr>";
    }
	else{
        $queryFields = ''; $skiptag = $_CONFIG['skiptag'];
        foreach($_REQUEST as $k=>$v){
            $opv = Wht::get($_REQUEST, 'op'.$k);
            $opv = $opv=='' ? '=' : $opv;
            if(startsWith($k, 'pnsk') && $opv != $skiptag){
                $queryFields .= $gtbl->getCHN(str_replace('pnsk', '', $k))." &nbsp;  &nbsp; "
                        .$opv." &nbsp;  &nbsp; ";
                $field = str_replace('pnsk', '', $k);
                $inputtype = $gtbl->getInputType($field);
                if($inputtype == 'select'){
                    $tmpv = $gtbl->getSelectOption($field, $v,'',1, $gtbl->getSelectMultiple($field));
                    $queryFields .= $tmpv."(".$v.")";
                }
                else{   
                    $queryFields .= $v; 
                }
                $queryFields .= " &nbsp;  &nbsp; <a href=\"javascript:pnAction('"
                        .str_replace($k, 'old'.$k, $jdo)
                        ."');\" title='Remove this filter/去掉此条件'>[X]</a><br/>";
            }
        }
        $out .= "<tr><td colspan='".($max_disp_cols+2)."' style='text-align:center'><br/>No Data for These Criterions. "
                ."/ 没有符合条件的数据. 請嘗試去掉一些條件再試試<br/><br/>".$queryFields."<br/>1609240951.<br/><br/></td></tr>";
    }
    $out .= "</table>";
    # list end
    ## list-toexcel
    if(startsWith($act, "list-toexcel")){
       include("./act/toexcel.php"); 
    }
	} # resultobj end

}else if(startsWith($act,"view")){ 

    include("./act/view.php");

}else if($act == 'print'){

    $smttpl = getSmtTpl(__FILE__, $act);
    include("./act/print.php");

}else if($act == 'updatefield'){

    include("./act/updatefield.php");

}
else if($act == 'deepsearch'){

    include("./act/deepsearch.php");

}
else if(startsWith($act, 'pivot')){

	include("./act/pivot.php");

}
else{
    $out .= "Ooops! No such action:[$act].<br/>&nbsp;\n";
}

require("./comm/footer.inc.php");

?>
