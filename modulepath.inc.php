<?php

# get detail module path
# Xenxin@Ufqi, Thu, 20 Jul 2017 17:11:36 +0800

$levelcode = Wht::get($_REQUEST, 'levelcode');
if($levelcode == ''){
    $hm = $gtbl->execBy("select levelcode, linkname, modulename from ".$_CONFIG['tblpre']
            ."info_menulist where modulename in ('".str_replace($_CONFIG['tblpre'],"",$tbl)."', '".$tbl."')", null,
            $withCache=array('key'=>'info_menulist-select-'.$tbl));
    #debug($hm);
    if($hm[0]){
        $levelcode = $hm[1][0]['levelcode'];
    }
}
if($levelcode != ''){
    $codelist = substr($levelcode,0,2)."','".substr($levelcode,0,4)."','".substr($levelcode,0,6)."','"
            .substr($levelcode,0,8); # max 4 levels allowed
    $hm = $gtbl->execBy("select levelcode, linkname, modulename, thedb from ".$_CONFIG['tblpre']."info_menulist where levelcode in ('"
            .$codelist."') order by levelcode", null, $withCache=array('key'=>'info_menulist-select-level-'.$codelist));
    if($hm[0]){
        $hm = $hm[1]; $lastLinkName = ''; #print_r($hm);
        foreach($hm as $k=>$v){
            if($v['modulename'] != ''){
                $module_path .= "<a href='".$ido."&tbl=".$v['modulename']."&db=".$v['thedb']."'>".$v['linkname']."</a> &rarr;";
            }
            else{
                $module_path .= "<a href='".$url."&navidir=".$v['levelcode']."'>".$v['linkname']."</a> &rarr;";
            }
            $lastLinkName = $v['linkname'];
        }
        $module_path = substr($module_path, 0, strlen($module_path)-6);
    }
}
$module_path = $module_path == '' ? '<a href="'.$url.'&navidir=99">桌面 & 系统配置</a> &rarr; '.$tit : $module_path;
if($lastLinkName != $tit){ $module_path .= "&nbsp;|&nbsp;".$tit; }
$module_path = "<b> &Pi; <a href=\"".$url."\">首页</a> "
        ."<span class=\"f17px\">&rarr;</span> ".$module_path." </b> ";

?>