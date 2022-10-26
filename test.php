<?php
function GetVariableFrom ($from,$name,$default="") 
{
	if (!is_array($from)) $from=array();
	if (!isset($from[$name]))   $from[$name]=$default;
	elseif (($from[$name]=="") and ($default!="")) $from[$name]=$default;
    return $from[$name];
}
$action=GetVariableFrom($_POST,"action","new");
$username=GetVariableFrom($_POST,"username","");
$password=GetVariableFrom($_POST,"password","");
$idmail=GetVariableFrom($_POST,"idmail","");

$smtpserver=GetVariableFrom($_POST,"smtpserver","");
$smtpport=GetVariableFrom($_POST,"smtpport","465");
$smtpsecure=GetVariableFrom($_POST,"smtpsecure","ssl");
$smtpauth=GetVariableFrom($_POST,"smtpauth","Y");
$smtplogin=GetVariableFrom($_POST,"smtplogin","");
$smtppass=GetVariableFrom($_POST,"smtppass","");
$replyto=GetVariableFrom($_POST,"replyto","");
$from=GetVariableFrom($_POST,"from","");
$withar=GetVariableFrom($_POST,"withar","N");
$subject=GetVariableFrom($_POST,"subject","sujet");
$body=GetVariableFrom($_POST,"body","body");
$body=stripslashes(strip_tags($body));
$body=substr(str_replace(chr(160)," ",$body),0,8191);

$cclist=GetVariableFrom($_POST,"cclist","");
$bcclist=GetVariableFrom($_POST,"bcclist","");

$html="<!DOCTYPE html><html><head><meta charset='ISO8859-1'><meta http-equiv='X-UA-Compatible' content='IE=edge,chrome=1'><title>Test API Mail</title></head>\r\n";
$html.="<body><form method='post' action='index.php'>\r\n";
$html.="<table style='border:none;'>\r\n";
$html.="<tr><td  style='width:200px;'>Username</td><td><input type='text' name='username' value=\"".$username."\"></td>";
$html.="<td style='padding-left:30px; vertical-align:top;' rowspan='17'><fieldset style='border-radius:10px;'><legend>Aide</legend>";
$html.="Les méthodes utilisables sont :";
$html.="<ul><li>New : création d'un email</li>";
$html.="<li>putcc : mise en place - remplacement de la liste des destinataires</li>";
$html.="<li>putbcc : mise en place - remplacement de la liste des destinataires cachés</li>";
$html.="<li>addfile : Ajout d'un fichier partagé</li>";
$html.="<li>send : Envoi du mail</li>";
$html.="</ul><br>";
$html.="Remarques :";
$html.="<ul><li>New permet de tout faire d'un coup (sauf l'ajout de pièces jointes)</li>";
$html.="<li>putcc et putbcc remplacent les destinataires par une nouvelle liste (écrase la liste précédente)</li>";
$html.="<li>addfile :A utiliser pour chaque ajout d'un fichier partagé</li>";
$html.="<li>putcc, putbcc, addfile et send EXIGENT que le numéro de mail (idmail) soit renseigné</li>";
$html.="</ul><br>";
$html.="</fieldset></td></tr>\r\n";
$html.="<tr><td>Password</td><td><input type='text' name='password' value=\"".$password."\"></td></tr>\r\n";
$html.="<tr><td>Action</td><td><select name='action'>";
		$html.="<option value='new' ".($action=="new"?" selected":"").">new</option>";
		$html.="<option value='putcc' ".($action=="putcc"?" selected":"").">putcc</option>";
		$html.="<option value='putbcc' ".($action=="putbcc"?" selected":"").">putbcc</option>";
		$html.="<option value='send' ".($action=="send"?" selected":"").">send</option>";
$html.="</select></td></tr>\r\n";
$html.="<tr><td>IdMail</td><td><input type=number name=idmail value=\"".$idmail."\"></td></tr>\r\n";

$html.="<tr><td>smtpserver</td><td><input type='text' name='smtpserver' value=\"".$smtpserver."\"></td></tr>\r\n";
$html.="<tr><td>smtpport</td><td><input type='number' name='smtpport' value=\"".$smtpport."\"></td></tr>\r\n";
$html.="<tr><td>smtplogin</td><td><input type='text' name='smtplogin' value=\"".$smtplogin."\"></td></tr>\r\n";
$html.="<tr><td>smtppass</td><td><input type='text' name='smtppass' value=\"".$smtppass."\"></td></tr>\r\n";

$html.="<tr><td>smtpsecure</td><td><select name='smtpsecure' style='width:100px;'>";
		$html.="<option value='ssl' ".($smtpsecure=="ssl"?" selected":"").">ssl</option>";
		$html.="<option value='tls' ".($smtpsecure=="tls"?" selected":"").">tls</option>";
		$html.="<option value='' ".($smtpsecure==""?" selected":"")."></option>";
$html.="</select></td></tr>\r\n";
$html.="<tr><td>smtpauth</td><td><select name='smtpauth' style='width:100px;'>";
		$html.="<option value='Y' ".($smtpauth=="Y"?" selected":"").">Oui</option>";
		$html.="<option value='N' ".($smtpauth=="N"?" selected":"").">Non</option>";
$html.="</select></td></tr>\r\n";
$html.="<tr><td>Accusé réception</td><td><select name='withar' style='width:100px;'>";
		$html.="<option value='Y' ".($withar=="Y"?" selected":"").">Oui</option>";
		$html.="<option value='N' ".($withar=="N"?" selected":"").">Non</option>";
$html.="</select></td></tr>\r\n";

$html.="<tr><td>from</td><td><input type='mail' name='from' value=\"".$from."\"></td></tr>\r\n";
$html.="<tr><td>replyto</td><td><input type='mail' name='replyto' value=\"".$replyto."\"></td></tr>\r\n";
$html.="<tr><td>sujet</td><td><input type='text' name='subject' value=\"".$subject."\"></td></tr>\r\n";
$html.="<tr><td>body</td><td><textarea name='body' rows='3' cols='45'>".$body."</textarea></td></tr>\r\n";
$html.="<tr><td>cclist: liste séparée par des ;</td><td><input style='width:350px;' type=text name=cclist value=\"".$cclist."\"></td></tr>\r\n";
$html.="<tr><td>bcclist liste séparée par des ;</td><td><input style='width:350px;'  type=text name=bcclist value=\"".$bcclist."\"></td></tr>\r\n";
$html.="<tr><td></td><td><input type='submit' value='tester'></td></tr>\r\n";
$html.="</table></form>\r\n";
$html.="<hr>\r\n";
$html.="<table style='border:none;'>\r\n";
$html.="<form method='post' enctype='multipart/form-data' action='index.php'>\r\n";
$html.="<tr><td style='width:200px;'>Username</td><td><input type=text name=username value=\"".$username."\"></td></tr>\r\n";
$html.="<tr><td style='width:200px;'>Password</td><td><input type=text name=password value=\"".$password."\"></td></tr>\r\n";
$html.="<tr><td style='width:200px;'>IdMail</td><td><input type=number name=idmail value=\"".$idmail."\"></td></tr>\r\n";
$html.="<input type=hidden name='action' value='addfile'>";
$html.="<tr><td>fileattach</td><td><input type='file' name='userfile'></td></tr>\r\n";
$html.="<tr><td></td><td><input type='submit' value='tester'></td></tr>\r\n";
$html.="</table></form>\r\n";
$html.="</body></html>";

echo($html);


?>
